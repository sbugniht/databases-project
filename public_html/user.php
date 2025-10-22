<?php
session_start();

// Inclusione delle credenziali e setup della connessione
$servername = "127.0.0.1";
$username_db = "gbrugnara";
$password_db = "KeRjnLwqj+rTTG3E";
$dbname = "db_gbrugnara";
$conn = new mysqli($servername, $username_db, $password_db, $dbname, null, "/run/mysql/mysql.sock");

// Controllo di sicurezza e connessione
if (!isset($_SESSION['user_id']) || (int)$_SESSION['privilege'] !== 0) {
  header("Location: login.php");
  exit();
}
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

$message = "";
$search_results = [];
$user_id = $_SESSION['user_id'];
$departure = '';
$arrival = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'search') {
    
    // 1. Dati di Ricerca
    $departure = trim($_POST['departure'] ?? '');
    $arrival = trim($_POST['arrival'] ?? '');
    
    if (!empty($departure) && !empty($arrival)) {

        // 2. Query per voli DISPONIBILI
        // Unisce View_SearchFlights con Tickets e SeatAssignment per trovare posti non ancora prenotati.
        // I posti prenotati (Bookings) non saranno restituiti.
        $sql = "
            SELECT 
                VSF.flight_id, VSF.dep_iata, VSF.dep_city, VSF.arr_iata, VSF.arr_city, 
                T.seat_id, SA.class, CP.PRICE
            FROM View_SearchFlights VSF
            JOIN Tickets T ON VSF.flight_id = T.flight_id
            JOIN SeatAssignment SA ON T.flight_id = SA.flight_id AND T.seat_id = SA.seat_id
            JOIN classPrice CP ON SA.class = CP.class
            LEFT JOIN Bookings B ON T.flight_id = B.flight_id AND T.seat_id = B.seat_id
            WHERE 
                (UPPER(VSF.dep_city) = UPPER(?) OR UPPER(VSF.dep_iata) = UPPER(?))
                AND (UPPER(VSF.arr_city) = UPPER(?) OR UPPER(VSF.arr_iata) = UPPER(?))
                AND B.booking_id IS NULL -- Esclude i posti già prenotati
            ORDER BY VSF.flight_id, SA.class, T.seat_id
        ";

        $stmt = $conn->prepare($sql);
        
        if ($stmt === false) {
            $message = "<p class='error'>SQL Prepare failed: " . $conn->error . "</p>"; 
        } else {
            // Binding dei parametri
            $stmt->bind_param("ssss", $departure, $departure, $arrival, $arrival);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $search_results[] = $row;
                }
                $message = "<p class='success'>Flights and available seats found. Ready to book.</p>";
            } else {
                $message = "<p class='error'>No available seats found for the selected route.</p>";
            }
            $stmt->close();
        }
    } else {
        $message = "<p class='error'>Please enter both a Departure and Arrival location.</p>";
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>User Dashboard - SkyBook</title>
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
        <li><a href="user.php" class="active">Search & Book</a></li>
        <li><a href="logout.php">Logout</a></li>
      </ul>
    </nav>
  </header>

  <div class="user-container">
    <h1>Welcome Customer, ID: <?php echo htmlspecialchars($user_id); ?>!</h1>
    <p>Search for available flights and book your seat below.</p>

    <div class="search-card user-search">
      <form method="post" action="user.php">
        <input type="hidden" name="action" value="search">
        <div class="form-group">
          <label for="departure">Departure (City/IATA)</label>
          <input type="text" id="departure" name="departure" placeholder="e.g. New York or JFK" required value="<?php echo htmlspecialchars($departure); ?>">
        </div>

        <div class="form-group">
          <label for="arrival">Arrival (City/IATA)</label>
          <input type="text" id="arrival" name="arrival" placeholder="e.g. Los Angeles or LAX" required value="<?php echo htmlspecialchars($arrival); ?>">
        </div>

        <button type="submit" class="btn-primary">Search Available Seats</button>
      </form>
      <?php 
        // Messaggio di stato dopo la ricerca o la prenotazione
        if (isset($_GET['status'])) {
            $msg_class = $_GET['status'] === 'success' ? 'success' : 'error';
            $msg_text = htmlspecialchars($_GET['msg']);
            echo "<p class='{$msg_class}'>{$msg_text}</p>";
        }
        echo $message; 
      ?>
    </div>

    <?php if (!empty($search_results)): ?>
    <div class="results">
      <h2>Available Seats</h2>
      <table>
        <tr>
          <th>Flight ID</th>
          <th>Route</th>
          <th>Seat ID</th>
          <th>Class</th>
          <th>Base Price</th>
          <th>Action</th>
        </tr>
        <?php foreach ($search_results as $flight): ?>
        <tr>
          <td><?php echo htmlspecialchars($flight['flight_id']); ?></td>
          <td><?php echo htmlspecialchars($flight['dep_city']) . ' &rarr; ' . htmlspecialchars($flight['arr_city']); ?></td>
          <td><?php echo htmlspecialchars($flight['seat_id']); ?></td>
          <td><?php echo htmlspecialchars($flight['class']); ?></td>
          <td><?php echo htmlspecialchars($flight['PRICE']) . ' €'; ?></td>
          <td>
            <form method="post" action="book_flight.php" style="margin: 0;">
              <input type="hidden" name="flight_id" value="<?php echo htmlspecialchars($flight['flight_id']); ?>">
              <input type="hidden" name="seat_id" value="<?php echo htmlspecialchars($flight['seat_id']); ?>">
              <button type="submit" class="btn-primary">Book Now</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </table>
    </div>
    <?php endif; ?>
  </div>
</body>
</html>