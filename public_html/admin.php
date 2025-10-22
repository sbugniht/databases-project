<?php
session_start();

// Controlla se l'utente è loggato (user_id è impostato) E se il privilegio è 1 (Admin)
if (!isset($_SESSION['user_id']) || (int)$_SESSION['privilege'] !== 1) {
  header("Location: login.php");
  exit();
}
?>
<h1>Welcome Admin, <?php echo htmlspecialchars($_SESSION['user_id']); ?>!</h1>
<p>This is the admin dashboard.</p>
<a href="logout.php">Logout</a>