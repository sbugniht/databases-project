<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'user') {
  header("Location: login.php");
  exit();
}
?>
<h1>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
<p>This is the user page.</p>
<a href="logout.php">Logout</a>
