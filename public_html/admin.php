<?php
session_start();

$servername = "127.0.0.1";
$username_db = "gbrugnara"; 
$password_db = "KeRjnLwqj+rTTG3E";
$dbname = "db_gbrugnara";
$conn = new mysqli($servername, $username_db, $password_db, $dbname, null, "/run/mysql/mysql.sock");

if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}


// Controllo di sicurezza: DEVE essere un admin (privilege 1)
if (!isset($_SESSION['user_id']) || (int)$_SESSION['privilege'] !== 1) {
  header("Location: login.php");
  exit();
}

$planes = [];
$sql_planes = "
    SELECT 
        P.plane_id, 
        CASE 
            WHEN C.plane_id IS NOT NULL THEN CONCAT('Commercial (', C.seats, ' seats)')
            WHEN G.plane_id IS NOT NULL THEN 'Cargo'
            ELSE 'Unknown'
        END AS type_status
    FROM Plane P
    LEFT JOIN Commercial C ON P.plane_id = C.plane_id
    LEFT JOIN Cargo G ON P.plane_id = G.plane_id
    ORDER BY P.plane_id;
";
$result_planes = $conn->query($sql_planes);
if ($result_planes) {
    while ($row = $result_planes->fetch_assoc()) {
        $planes[] = $row;
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - SkyBook</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header>
    <div class="logo-wrapper">
        <img src="images/logo.JPG" alt="SkyBook Logo" class="logo-image">
        <div class="logo">SkyBook</div>
    </div>
    <nav></nav>
    <a href="logout.php" class="header-action-btn">Logout</a>
</header>

<h1>Welcome Admin, ID: <?php echo htmlspecialchars($_SESSION['user_id']); ?>!</h1>
<p>Use this dashboard to manage flights and view existing inventory.</p>

<section class="admin-management">
    
    <h2>Existing Planes & Inventory</h2>
    <div class="plane-inventory-container">
        <div class="plane-list-card">
            <h3>Current Plane IDs</h3>
            <?php if (!empty($planes)): ?>
                <ul>
                    <?php foreach ($planes as $plane): ?>
                        <li>
                            <strong>ID <?php echo htmlspecialchars($plane['plane_id']); ?>:</strong> 
                            <?php echo htmlspecialchars($plane['type_status']); ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>No planes found.</p>
            <?php endif; ?>
        </div>
        
        <div class="plane-seats-card">
            <h3>Seat Visualizer (Detailed View)</h3>
            <p>Select a flight ID to visualize seats (Feature Placeholder).</p>
            
            <div class="seat-map-placeholder">
                
            </div>
            <p>The full graphical seat map is a future complex feature.</p>
        </div>
    </div>
    
    <hr>
    
    <h2>Manage Flights</h2>
    <div class="flight-management-grid">
        
        <div class="form-card">
            <h3>Add New Flight</h3>
            <form method="post" action="manage_flights.php" class="flight-form">
                <input type="hidden" name="action" value="add">
                
                <label for="new_flight_id">Flight ID (New):</label>
                <input type="number" id="new_flight_id" name="flight_id" required>

                <label for="d_airport">Departure Airport ID:</label>
                <input type="number" id="d_airport" name="d_airport_id" required>

                <label for="a_airport">Arrival Airport ID:</label>
                <input type="number" id="a_airport" name="a_airport_id" required>

                <label for="plane_id">Plane ID (from list above):</label>
                <input type="number" id="plane_id" name="plane_id" required>

                <label for="status">Status:</label>
                <select id="status" name="status" required>
                    <option value="on time">On Time</option>
                    <option value="delayed">Delayed</option>
                    <option value="cancelled">Cancelled</option>
                </select>

                <label for="flight_type">Type (for classification):</label>
                <select id="flight_type" name="flight_type" required>
                    <option value="Dom_flight">Domestic</option>
                    <option value="Int_flight">International</option>
                </select>

                <button type="submit" class="btn-primary">Add Flight</button>
            </form>
        </div>

        <div class="form-card">
            <h3>Remove Existing Flight</h3>
            <form method="post" action="manage_flights.php" class="flight-form">
                <input type="hidden" name="action" value="remove">
                
                <label for="remove_flight_id">Flight ID to Remove:</label>
                <input type="number" id="remove_flight_id" name="flight_id" required>

                <button type="submit" class="btn-secondary">Remove Flight</button>
            </form>
        </div>
    </div>
    
</section>

</body>
</html>