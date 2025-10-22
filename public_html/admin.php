<?php
session_start();
// Controllo di sicurezza: DEVE essere un admin (privilege 1)
if (!isset($_SESSION['user_id']) || (int)$_SESSION['privilege'] !== 1) {
  header("Location: login.php");
  exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - SkyBook</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<h1>Welcome Admin, ID: <?php echo htmlspecialchars($_SESSION['user_id']); ?>!</h1>
<p>Use this dashboard to manage flights.</p>

<section class="admin-management">
    
    <h2>Add New Flight</h2>
    <form method="post" action="manage_flights.php" class="flight-form">
        <input type="hidden" name="action" value="add">
        
        <label for="new_flight_id">Flight ID (New):</label>
        <input type="number" id="new_flight_id" name="flight_id" required><br>

        <label for="d_airport">Departure Airport ID:</label>
        <input type="number" id="d_airport" name="d_airport_id" required><br>

        <label for="a_airport">Arrival Airport ID:</label>
        <input type="number" id="a_airport" name="a_airport_id" required><br>

        <label for="plane_id">Plane ID (must be Commercial):</label>
        <input type="number" id="plane_id" name="plane_id" required><br>

        <label for="status">Status:</label>
        <select id="status" name="status" required>
            <option value="on time">On Time</option>
            <option value="delayed">Delayed</option>
            <option value="cancelled">Cancelled</option>
        </select><br>

        <label for="flight_type">Type (for classification):</label>
        <select id="flight_type" name="flight_type" required>
            <option value="Dom_flight">Domestic</option>
            <option value="Int_flight">International</option>
        </select><br>

        <button type="submit" class="btn-primary">Add Flight</button>
    </form>

    <hr>

    <h2>Remove Existing Flight</h2>
    <form method="post" action="manage_flights.php" class="flight-form">
        <input type="hidden" name="action" value="remove">
        
        <label for="remove_flight_id">Flight ID to Remove:</label>
        <input type="number" id="remove_flight_id" name="flight_id" required><br>

        <button type="submit" class="btn-secondary">Remove Flight</button>
    </form>
</section>

<br>
<a href="logout.php">Logout</a>

</body>
</html>