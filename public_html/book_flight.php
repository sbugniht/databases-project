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
if (!isset($_SESSION['user_id']) || (int)$_SESSION['privilege'] !== 0) {
    $attempt_user = $_SESSION['user_id'] ?? 'GUEST';
    log_event("ACCESS_DENIED", "Attempted unauthorized booking access.", $attempt_user);
    die("Access Denied. Only logged-in customers can book flights.");
}

$user_id = $_SESSION['user_id'];
$message = "";
$success = false;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['flight_id']) && isset($_POST['seat_id'])) {
    
    $flight_id = (int)$_POST['flight_id'];
    $seat_id = (int)$_POST['seat_id'];
    
    
    $check_sql = "SELECT booking_id FROM Bookings WHERE flight_id = ? AND seat_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $flight_id, $seat_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        $message = "Booking failed: The seat has just been taken.";
        log_event("BOOKING_FAILURE", "Seat $seat_id on Flight $flight_id already booked.", $user_id);
    } else {
        try {
            
            $sql = "INSERT INTO Bookings (user_id, flight_id, seat_id) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iii", $user_id, $flight_id, $seat_id);
            $stmt->execute();

            if ($stmt->affected_rows === 1) {
                $success = true;
                $message = "Successfully booked seat $seat_id on Flight $flight_id!";
                log_event("BOOKING_SUCCESS", $message, $user_id);
            } else {
                $message = "Booking failed: Could not insert record.";
                log_event("BOOKING_FAILURE", $message, $user_id);
            }
            $stmt->close();

        } catch (mysqli_sql_exception $e) {
            
            $message = "Booking failed due to an error: " . $e->getMessage();
            log_event("BOOKING_FAILURE", $message, $user_id);
        }
    }
    
    $conn->close();

    
    $status_param = $success ? 'success' : 'error';
    header("Location: user.php?status=$status_param&msg=" . urlencode($message));
    exit();
}


header("Location: user.php");
exit();
?>