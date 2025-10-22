<?php
session_start();

// Controllo di sicurezza:
// 1. Verifica che l'utente sia loggato (controllando la presenza di 'user_id').
// 2. Verifica che il privilegio sia 0 (Customer).
if (!isset($_SESSION['user_id']) || (int)$_SESSION['privilege'] !== 0) {
  header("Location: login.php");
  exit();
}
?>
<h1>Welcome Customer, ID: <?php echo htmlspecialchars($_SESSION['user_id']); ?>!</h1>
<p>This is the user page where you can manage your bookings.</p>
<a href="logout.php">Logout</a>
