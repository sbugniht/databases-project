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
    die("Access Denied.");
}

$user_id = $_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['flight_id']) && isset($_POST['seat_id'])) {
    
    $flight_id = (int)$_POST['flight_id'];
    $seat_id = (int)$_POST['seat_id'];
    
    $conn->begin_transaction();

    try {
        $sql_info = "
            SELECT 
                CP.PRICE as base_price,
                F_Fee.dom_fee,
                F_Fee.int_fee,
                Adep.country as dep_country,
                Aarr.country as arr_country,
                C.balance
            FROM SeatAssignment SA
            JOIN Flights F ON SA.flight_id = F.flight_id
            JOIN classPrice CP ON SA.class = CP.class
            JOIN Airport Adep ON F.Dairport_id = Adep.airport_id
            JOIN Airport Aarr ON F.Aairport_id = Aarr.airport_id
            JOIN Fee F_Fee ON Adep.country = F_Fee.country
            JOIN Customer C ON C.USER_ID = ?
            WHERE SA.flight_id = ? AND SA.seat_id = ?
            FOR UPDATE";

        $stmt = $conn->prepare($sql_info);
        $stmt->bind_param("iii", $user_id, $flight_id, $seat_id);
        $stmt->execute();
        $res = $stmt->get_result();
        
        if ($res->num_rows === 0) throw new Exception("Flight or Seat not found.");
        $data = $res->fetch_assoc();
        $stmt->close();

        $is_domestic = ($data['dep_country'] === $data['arr_country']);
        $fee = $is_domestic ? $data['dom_fee'] : $data['int_fee'];
        $total_cost = $data['base_price'] + $fee;

        if ($data['balance'] < $total_cost) {
            throw new Exception("Insufficient funds. Balance: €" . $data['balance'] . ", Required: €" . $total_cost);
        }

        $check = $conn->query("SELECT booking_id FROM Bookings WHERE flight_id = $flight_id AND seat_id = $seat_id");
        if ($check->num_rows > 0) throw new Exception("Seat already taken.");

        $stmt_pay = $conn->prepare("UPDATE Customer SET balance = balance - ? WHERE USER_ID = ?");
        $stmt_pay->bind_param("di", $total_cost, $user_id);
        if (!$stmt_pay->execute()) throw new Exception("Payment failed.");
        $stmt_pay->close();

        $stmt_book = $conn->prepare("INSERT INTO Bookings (user_id, flight_id, seat_id) VALUES (?, ?, ?)");
        $stmt_book->bind_param("iii", $user_id, $flight_id, $seat_id);
        if (!$stmt_book->execute()) throw new Exception("Booking insert failed.");
        $stmt_book->close();

        $conn->commit();
        
        header("Location: user.php?status=success&msg=" . urlencode("Booked! Cost: €$total_cost. New Balance: €" . ($data['balance'] - $total_cost)));

    } catch (Exception $e) {
        $conn->rollback();
        header("Location: user.php?status=error&msg=" . urlencode($e->getMessage()));
    }
    exit();
}
header("Location: user.php");
exit();
?>