<?php
session_start();

// CONFIGURAZIONE DATABASE
$servername = "127.0.0.1";
$username_db = "gbrugnara";
$password_db = "KeRjnLwqj+rTTG3E";
$dbname = "db_gbrugnara";
$conn = new mysqli($servername, $username_db, $password_db, $dbname, null, "/run/mysql/mysql.sock");

// 1. Controllo Connessione e Permessi Admin
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}
if (!isset($_SESSION['user_id']) || (int)$_SESSION['privilege'] !== 1) {
  die("Access Denied. Only Administrators can manage flights.");
}

// 2. Gestione Logica in base all'azione
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    
    $action = $_POST['action'];
    $flight_id = (int)($_POST['flight_id'] ?? 0);
    $success = false;
    $message = "";

    try {
        if ($action === 'add') {
            // Aggiungi Volo
            $d_airport_id = (int)$_POST['d_airport_id'];
            $a_airport_id = (int)$_POST['a_airport_id'];
            $plane_id = (int)$_POST['plane_id'];
            $status = $_POST['status'];
            $flight_type = $_POST['flight_type'];
            
            // Inizia una transazione per assicurare che tutte le modifiche vadano a buon fine
            $conn->begin_transaction(); 

            // Query 1: Inserimento in Flights
            $sql_flight = "INSERT INTO Flights (flight_id, Dairport_id, Aairport_id, plane_id, plane_status) VALUES (?, ?, ?, ?, ?)";
            $stmt_flight = $conn->prepare($sql_flight);
            $stmt_flight->bind_param("iiiis", $flight_id, $d_airport_id, $a_airport_id, $plane_id, $status);
            $stmt_flight->execute();

            // Query 2: Classificazione (Dom_flight o Int_flight)
            $sql_type = "INSERT INTO $flight_type (flight_id) VALUES (?)";
            $stmt_type = $conn->prepare($sql_type);
            $stmt_type->bind_param("i", $flight_id);
            $stmt_type->execute();

            // Commit della transazione
            $conn->commit();
            $success = true;
            $message = "Flight $flight_id successfully added as $flight_type.";

        } elseif ($action === 'remove') {
            // Rimuovi Volo (necessita di cancellazione a cascata, o in ordine inverso)
            
            $conn->begin_transaction(); 
            
            // Necessario rimuovere prima i riferimenti (Bookings, SeatAssignment, Tickets)
            // e poi le specializzazioni (Dom/Int), infine Flights.
            
            // 1. Bookings e SeatAssignment (dipendono da Tickets)
            // Normalmente avresti bisogno di Foreign Keys ON DELETE CASCADE, ma le eseguiamo manualmente
            $conn->query("DELETE FROM Bookings WHERE flight_id = $flight_id");
            $conn->query("DELETE FROM SeatAssignment WHERE flight_id = $flight_id");
            
            // 2. Tickets (inventario posti)
            $conn->query("DELETE FROM Tickets WHERE flight_id = $flight_id");
            
            // 3. Classificazione
            $conn->query("DELETE FROM Dom_flight WHERE flight_id = $flight_id");
            $conn->query("DELETE FROM Int_flight WHERE flight_id = $flight_id");

            // 4. Volo principale
            $sql_remove = "DELETE FROM Flights WHERE flight_id = ?";
            $stmt_remove = $conn->prepare($sql_remove);
            $stmt_remove->bind_param("i", $flight_id);
            $stmt_remove->execute();

            $conn->commit();
            $success = true;
            $message = "Flight $flight_id and all related data successfully removed.";
        }
    
    } catch (mysqli_sql_exception $e) {
        // Rollback in caso di errore (es: ID duplicato, Airport ID non esistente, FK error)
        $conn->rollback();
        $message = "Operation failed: " . $e->getMessage();
    }
    
    $conn->close();

    // Reindirizza l'admin alla dashboard con un messaggio di stato
    $status_param = $success ? 'success' : 'error';
    header("Location: admin.php?status=$status_param&msg=" . urlencode($message));
    exit();
}

// Se si accede allo script direttamente, reindirizza
header("Location: admin.php");
exit();
?>