<?php
session_start();
include_once 'logTracker.php';

$servername = "127.0.0.1";
$username_db = "gbrugnara";
$password_db = "KeRjnLwqj+rTTG3E";
$dbname = "db_gbrugnara";
$conn = new mysqli($servername, $username_db, $password_db, $dbname, null, "/run/mysql/mysql.sock");

if (!isset($_SESSION['user_id']) || (int)$_SESSION['privilege'] !== 1) {
    die("Access Denied.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $country = $_POST['country'];
    $dom_fee = (int)$_POST['dom_fee'];
    $int_fee = (int)$_POST['int_fee'];

    $stmt = $conn->prepare("UPDATE Fee SET dom_fee = ?, int_fee = ? WHERE country = ?");
    $stmt->bind_param("iis", $dom_fee, $int_fee, $country);
    
    if ($stmt->execute()) {
        log_event("FEE_UPDATE", "Fees updated for $country. Dom: $dom_fee, Int: $int_fee", $_SESSION['user_id']);
        header("Location: admin.php?status=success&msg=Fees updated for $country");
    } else {
        header("Location: admin.php?status=error&msg=Update failed");
    }
    $stmt->close();
}
$conn->close();
?>