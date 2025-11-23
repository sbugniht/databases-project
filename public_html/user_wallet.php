<?php
session_start();
include_once 'logTracker.php';

$servername = "127.0.0.1";
$username_db = "gbrugnara";
$password_db = "KeRjnLwqj+rTTG3E";
$dbname = "db_gbrugnara";
$conn = new mysqli($servername, $username_db, $password_db, $dbname, null, "/run/mysql/mysql.sock");

if (!isset($_SESSION['user_id']) || (int)$_SESSION['privilege'] !== 0) {
    die(json_encode(['success' => false, 'message' => 'Access Denied']));
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'add_funds') {
    $user_id = $_SESSION['user_id'];
    $amount = 100.00; // Fixed amount for simulation

    $stmt = $conn->prepare("UPDATE Customer SET balance = balance + ? WHERE USER_ID = ?");
    $stmt->bind_param("di", $amount, $user_id);
    
    if ($stmt->execute()) {
        // Get new balance
        $res = $conn->query("SELECT balance FROM Customer WHERE USER_ID = $user_id");
        $new_balance = $res->fetch_assoc()['balance'];
        
        echo json_encode(['success' => true, 'new_balance' => number_format($new_balance, 2)]);
        log_event("WALLET_UPDATE", "User added 100 EUR. New Balance: $new_balance", $user_id);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
    $stmt->close();
}
$conn->close();
?>