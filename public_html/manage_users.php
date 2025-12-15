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

$action = $_POST['action'] ?? '';
$redirect_url = 'login.php'; // Default redirect

try {
    if ($action === 'register_customer') {
        $new_id = (int)$_POST['new_user_id'];
        $new_pwd = $_POST['new_password'];

        $check = $conn->query("SELECT USER_ID FROM Users WHERE USER_ID = $new_id");
        if ($check->num_rows > 0) throw new Exception("User ID already taken.");

        $conn->begin_transaction();
        
        $stmt1 = $conn->prepare("INSERT INTO Users (USER_ID, pwd, privilege) VALUES (?, ?, 0)");
        $stmt1->bind_param("is", $new_id, $new_pwd);
        $stmt1->execute();

        $stmt2 = $conn->prepare("INSERT INTO Customer (USER_ID) VALUES (?)");
        $stmt2->bind_param("i", $new_id);
        $stmt2->execute();

        $conn->commit();
        log_event("REGISTER_SUCCESS", "New customer registered: $new_id", $new_id);
        header("Location: login.php?status=success&msg=" . urlencode("Registration successful! Please login."));
        exit();
    }

    if (!isset($_SESSION['user_id']) || (int)$_SESSION['privilege'] !== 1) {
        die("Access Denied.");
    }
    $admin_id = $_SESSION['user_id'];
    $redirect_url = 'admin.php';

    if ($action === 'add_admin') {
        $new_id = (int)$_POST['new_admin_id'];
        $new_pwd = $_POST['new_admin_pwd'];

        $check = $conn->query("SELECT USER_ID FROM Users WHERE USER_ID = $new_id");
        if ($check->num_rows > 0) throw new Exception("User ID already taken.");

        $conn->begin_transaction();
        $stmt1 = $conn->prepare("INSERT INTO Users (USER_ID, pwd, privilege) VALUES (?, ?, 1)");
        $stmt1->bind_param("is", $new_id, $new_pwd);
        $stmt1->execute();

        $stmt2 = $conn->prepare("INSERT INTO Admin (USER_ID) VALUES (?)");
        $stmt2->bind_param("i", $new_id);
        $stmt2->execute();

        $conn->commit();
        log_event("ADMIN_CREATED", "Admin $new_id created by $admin_id", $admin_id);
        header("Location: admin.php?status=success&msg=" . urlencode("New Admin added successfully."));

    } elseif ($action === 'delete_user') {
        $target_id = (int)$_POST['target_id'];
        
        $res = $conn->query("SELECT privilege FROM Users WHERE USER_ID = $target_id");
        if ($res->num_rows === 0) throw new Exception("User not found.");
        $target_role = (int)$res->fetch_assoc()['privilege'];

        // CONTROLLO SICUREZZA: Ultimo Admin
        if ($target_role === 1) {
            $res_count = $conn->query("SELECT COUNT(*) as c FROM Users WHERE privilege = 1");
            $admin_count = $res_count->fetch_assoc()['c'];
            if ($admin_count <= 1) {
                throw new Exception("Cannot delete the last remaining Administrator!");
            }
        }

        $conn->begin_transaction();

        if ($target_role === 1) {
            $conn->query("DELETE FROM Admin WHERE USER_ID = $target_id");
        } else if ($target_role === 0) {
            $conn->query("DELETE FROM Bookings WHERE user_id = $target_id");
            $conn->query("DELETE FROM Customer WHERE USER_ID = $target_id");
        } else {
             $conn->query("DELETE FROM Customer WHERE USER_ID = $target_id");
        }

        $conn->query("DELETE FROM Users WHERE USER_ID = $target_id");

        $conn->commit();
        log_event("USER_DELETED", "User $target_id deleted by $admin_id", $admin_id);
        header("Location: admin.php?status=success&msg=" . urlencode("User $target_id deleted."));
    }

} catch (Exception $e) {
    if (isset($conn)) $conn->rollback();
    header("Location: $redirect_url?status=error&msg=" . urlencode($e->getMessage()));
}

$conn->close();
