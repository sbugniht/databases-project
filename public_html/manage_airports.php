<?php
session_start();

include_once 'logTracker.php';

$servername = "127.0.0.1";
$username_db = "gbrugnara";
$password_db = "KeRjnLwqj+rTTG3E";
$dbname = "db_gbrugnara";
$conn = new mysqli($servername, $username_db, $password_db, $dbname, null, "/run/mysql/mysql.sock");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
if (!isset($_SESSION['user_id']) || (int)$_SESSION['privilege'] !== 1) {
    die("Access Denied. Only Administrators can manage airports.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    
    $action = $_POST['action'];
    $success = false;
    $message = "";
    $admin_id = $_SESSION['user_id'];

    try {
        if ($action === 'add_airport') {
            // Aggiungi un nuovo aeroporto
            $airport_id = (int)$_POST['airport_id'];
            $iata = trim($_POST['iata']);
            $city = trim($_POST['city']);
            $country = trim($_POST['country']);

            if (empty($iata) || empty($city) || empty($country)) {
                throw new Exception("All fields (ID, IATA, City, Country) are required.");
            }
            
            // Verifica se il Paese esiste nella tabella Fee prima di procedere
            $check_fee = $conn->prepare("SELECT country FROM Fee WHERE country = ?");
            $check_fee->bind_param("s", $country);
            $check_fee->execute();
            if ($check_fee->get_result()->num_rows === 0) {
                 throw new Exception("Country '$country' must first be defined in the Fee table.");
            }
            $check_fee->close();
            

            $sql_add = "INSERT INTO Airport (airport_id, iata, city, country) VALUES (?, ?, ?, ?)";
            $stmt_add = $conn->prepare($sql_add);
            $stmt_add->bind_param("isss", $airport_id, $iata, $city, $country);
            
            if (!$stmt_add->execute()) {
                // Cattura l'errore SQL specifico (es. ID o IATA già esistente)
                 throw new Exception("Error executing insert: " . $stmt_add->error);
            }

            $success = true;
            $message = "Airport $iata ($city) successfully added.";
            log_event("AIRPORT_ADD_SUCCESS", "Airport ID $airport_id added.", $admin_id);
            $stmt_add->close();

        } elseif ($action === 'remove_airport') {
            // Rimuovi un aeroporto
            $airport_id = (int)$_POST['airport_id'];

            // 1. Verifica se l'aeroporto è in uso come destinazione/partenza di un volo
            $check_usage = $conn->prepare("SELECT flight_id FROM Flights WHERE Dairport_id = ? OR Aairport_id = ? LIMIT 1");
            $check_usage->bind_param("ii", $airport_id, $airport_id);
            $check_usage->execute();

            if ($check_usage->get_result()->num_rows > 0) {
                 throw new Exception("Cannot remove Airport ID $airport_id. It is currently linked to existing flights. Remove flights first.");
            }
            $check_usage->close();

            // 2. Rimuovi l'aeroporto
            $sql_remove = "DELETE FROM Airport WHERE airport_id = ?";
            $stmt_remove = $conn->prepare($sql_remove);
            $stmt_remove->bind_param("i", $airport_id);
            $stmt_remove->execute();

            if ($stmt_remove->affected_rows === 0) {
                throw new Exception("Airport ID $airport_id not found.");
            }
            
            $success = true;
            $message = "Airport ID $airport_id successfully removed.";
            log_event("AIRPORT_REMOVE_SUCCESS", "Airport ID $airport_id removed.", $admin_id);
            $stmt_remove->close();
        }
    
    } catch (Exception $e) {
        $message = "Operation failed: " . $e->getMessage();
        $log_action = strtoupper($action) . "_AIRPORT_FAILED";
        log_event($log_action, $message, $admin_id);
        $success = false;
    }
    
    $conn->close();

    $status_param = $success ? 'success' : 'error';
    header("Location: admin.php?status=$status_param&msg=" . urlencode($message));
    exit();
}


header("Location: admin.php");
exit();
?>