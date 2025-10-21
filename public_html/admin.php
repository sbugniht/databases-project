<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
  header("Location: login.php");
  exit();
}
?>
<h1>Welcome Admin, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
<p>This is the admin dashboard.</p>
<a href="logout.php">Logout</a>
