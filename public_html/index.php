
<?php

$servername = "127.0.0.1";
$username = "gbrugnara";
$password = "KeRjnLwqj+rTTG3E";
$dbname = "db_gbrugnara";
## 4JjJ0zWOOo76
$conn = new mysqli($servername, $username, $password, $dbname, null, "/run/mysql/mysql.sock");


if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}


$message = "";
$search_results = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $departure = $_POST['departure'];
    $arrival = $_POST['arrival'];
}

   
    $sql = "SELECT * FROM Flights
            WHERE Aairport_id = $arrival AND Dairport_id = $departure";

    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $search_results[] = $row;
        }
    } else {
        $message = "<p class='error'>No flights found matching your search.</p>";
    }

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>SkyBook - Airline Reservation System</title>
  <link rel="stylesheet" href="style.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body>


  <header>
    <div class="logo-wrapper">
      <img src="images/logo.JPG" alt="SkyBook Logo" class="logo-image">
      <div class="logo">SkyBook</div>
    </div>

    <nav>
      <ul>
        <li><a href="index.php" class="active">Home</a></li>
        <li><a href="#">Flights</a></li>
        <li><a href="#">About</a></li>
        <li><a href="imprint.hmtl">Imprint</a></li>
      </ul>
    </nav>
  </header>

  <!-- Hero Section -->
  <section class="hero">
    <h1>Your Journey Starts Here</h1>
    <p>Book flights with confidence and ease.</p>

    <!-- Search Card -->
    <div class="search-card">
      <form method="post" action="index.php">

        <div class="form-group">
          <label for="departure">Departure</label>
          <input type="text" id="departure" name="departure" placeholder="e.g. Berlin" required>
        </div>

        <div class="form-group">
          <label for="arrival">Arrival</label>
          <input type="text" id="arrival" name="arrival" placeholder="e.g. Paris" required>
        </div>

        <div class="form-group">
          <label for="date">Date</label>
          <input type="date" id="date" name="date" required>
        </div>

        <button type="submit" class="btn-primary">Search Flight</button>
      </form>
      <?php echo $message; ?>
    </div>
  </section>
  
  <?php if (!empty($search_results)): ?>
  <div class="results">
    <h2>Flights Found</h2>
    <table>
      <tr>
        <th>Flight ID</th>
        <th>Departure</th>
        <th>Arrival</th>
        <th>Plane ID</th>
        <th>Status</th>
      </tr>
      <?php foreach ($search_results as $flight): ?>
      <tr>
        <td><?php echo htmlspecialchars($flight['flight_id']); ?></td>
        <td><?php echo htmlspecialchars($flight['Dairport_id']); ?></td>
        <td><?php echo htmlspecialchars($flight['Aairport_id']); ?></td>
        <td><?php echo htmlspecialchars($flight['plane_id']); ?></td>
        <td><?php echo htmlspecialchars($flight['plane_status']); ?></td>
      </tr>
      <?php endforeach; ?>
    </table>
  </div>
<?php endif; ?>


  <!-- Features Section -->
  <section class="features">
    <div class="feature-card">
      <h3>Seat Classes</h3>
      <p>Choose between economy, business, or first class — designed for comfort.</p>
    </div>
    <div class="feature-card">
      <h3>Global Destinations</h3>
      <p>Book domestic and international flights easily from one platform.</p>
    </div>
    <div class="feature-card">
      <h3>Live Flight Status</h3>
      <p>Stay up to date with real-time flight information and updates.</p>
    </div>
  </section>

  <!-- Disclaimer -->
  <section class="disclaimer">
    <p>
      This website is student lab work and does not necessarily reflect Constructor University opinions.
      Constructor University does not endorse this site, nor is it checked by Constructor University regularly,
      nor is it part of the official Constructor University web presence. For each external link existing on this website,
      we initially have checked that the target page does not contain contents which is illegal wrt. German jurisdiction.
      However, as we have no influence on such contents, this may change without our notice. Therefore we deny any
      responsibility for the websites referenced through our external links from here.
      No information conflicting with GDPR is stored in the server.
      For any questions or problems, please contact Leonel at lvaldez at constructor dot university.
      You can also reach out to Giacomo, Felipe or Santiago via their university emails. (gbrugnara, fsalazarbu, sbatistadi at constructor dot university).
      Alternatively, you can reach us at our university address: Am Fallturm 1, 28359 Bremen, Germany.
    </p>
  </section>

  <!-- Footer -->
  <footer>
    <p>&copy; 2025 SkyBook — <a href="imprint.hmtl">Imprint</a></p>
  </footer>

</body>
</html>
