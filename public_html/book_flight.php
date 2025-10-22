<?php
session_start();

// CONFIGURAZIONE DATABASE
$servername = "127.0.0.1";
$username_db = "gbrugnara";
$password_db = "KeRjnLwqj+rTTG3E";
$dbname = "db_gbrugnara";
$conn = new mysqli($servername, $username_db, $password_db, $dbname, null, "/run/mysql/mysql.sock");

// 1. Controllo Connessione e Permessi Customer (privilege 0)
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}
if (!isset($_SESSION['user_id']) || (int)$_SESSION['privilege'] !== 0) {
  die("Access Denied. Only logged-in customers can book flights.");
}

$user_id = $_SESSION['user_id'];
$message = "";
$success = false;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['flight_id']) && isset($_POST['seat_id'])) {
    
    $flight_id = (int)$_POST['flight_id'];
    $seat_id = (int)$_POST['seat_id'];
    
    // 2. Controllo Preliminare (doppio check sulla disponibilità)
    // Non strettamente necessario se la query in user.php è corretta, 
    // ma aggiunge robustezza. Se il posto è stato prenotato tra la ricerca e il click, fallirà.
    $check_sql = "SELECT booking_id FROM Bookings WHERE flight_id = ? AND seat_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $flight_id, $seat_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        $message = "Booking failed: The seat has just been taken.";
    } else {
        try {
            // 3. Esecuzione della Prenotazione
            $sql = "INSERT INTO Bookings (user_id, flight_id, seat_id) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iii", $user_id, $flight_id, $seat_id);
            $stmt->execute();

            if ($stmt->affected_rows === 1) {
                $success = true;
                $message = "Successfully booked seat $seat_id on Flight $flight_id!";
            } else {
                $message = "Booking failed: Could not insert record.";
            }
            $stmt->close();

        } catch (mysqli_sql_exception $e) {
            // Cattura errori come chiave esterna mancante (posto in Tickets non valido)
            $message = "Booking failed due to an error: " . $e->getMessage();
        }
    }
    
    $conn->close();

    // 4. Reindirizza l'utente alla dashboard con un messaggio di stato
    $status_param = $success ? 'success' : 'error';
    header("Location: user.php?status=$status_param&msg=" . urlencode($message));
    exit();
}

// Se si accede allo script direttamente, reindirizza
header("Location: user.php");
exit();
?>