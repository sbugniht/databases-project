<?php
session_start();


$servername = "127.0.0.1";
$username_db = "gbrugnara";
$password_db = "KeRjnLwqj+rTTG3E";
$dbname = "db_gbrugnara";
$conn = new mysqli($servername, $username_db, $password_db, $dbname, null, "/run/mysql/mysql.sock");


if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}
if (!isset($_SESSION['user_id']) || (int)$_SESSION['privilege'] !== 1) {
  die("Access Denied. Only Administrators can manage flights.");
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    
    $action = $_POST['action'];
    $flight_id = (int)($_POST['flight_id'] ?? 0);
    $success = false;
    $message = "";

    try {
        if ($action === 'add') {
           
            $d_airport_id = (int)$_POST['d_airport_id'];
            $a_airport_id = (int)$_POST['a_airport_id'];
            $plane_id = (int)$_POST['plane_id'];
            $status = $_POST['status'];
            $flight_type = $_POST['flight_type'];
            
            
            $conn->begin_transaction(); 

            
            $sql_flight = "INSERT INTO Flights (flight_id, Dairport_id, Aairport_id, plane_id, plane_status) VALUES (?, ?, ?, ?, ?)";
            $stmt_flight = $conn->prepare($sql_flight);
            $stmt_flight->bind_param("iiiis", $flight_id, $d_airport_id, $a_airport_id, $plane_id, $status);
            $stmt_flight->execute();

            
            $sql_type = "INSERT INTO $flight_type (flight_id) VALUES (?)";
            $stmt_type = $conn->prepare($sql_type);
            $stmt_type->bind_param("i", $flight_id);
            $stmt_type->execute();

            
            $conn->commit();
            $success = true;
            $message = "Flight $flight_id successfully added as $flight_type.";

        } elseif ($action === 'remove') {
            
            
            $conn->begin_transaction(); 
            
        
            $conn->query("DELETE FROM Bookings WHERE flight_id = $flight_id");
            $conn->query("DELETE FROM SeatAssignment WHERE flight_id = $flight_id");
            
            
            $conn->query("DELETE FROM Tickets WHERE flight_id = $flight_id");
            
            
            $conn->query("DELETE FROM Dom_flight WHERE flight_id = $flight_id");
            $conn->query("DELETE FROM Int_flight WHERE flight_id = $flight_id");

            
            $sql_remove = "DELETE FROM Flights WHERE flight_id = ?";
            $stmt_remove = $conn->prepare($sql_remove);
            $stmt_remove->bind_param("i", $flight_id);
            $stmt_remove->execute();

            $conn->commit();
            $success = true;
            $message = "Flight $flight_id and all related data successfully removed.";
        }
    
    } catch (mysqli_sql_exception $e) {
        
        $conn->rollback();
        $message = "Operation failed: " . $e->getMessage();
    }
    
    $conn->close();

    
    $status_param = $success ? 'success' : 'error';
    header("Location: admin.php?status=$status_param&msg=" . urlencode($message));
    exit();
}


header("Location: admin.php");
exit();
?>