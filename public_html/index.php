<?php
// Start session to check if a user is logged in for logging purposes
session_start(); 

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include_once 'logTracker.php';
include_once 'simulation_manager.php';

$servername = "127.0.0.1";
$username = "gbrugnara";
$password = "KeRjnLwqj+rTTG3E";
$dbname = "db_gbrugnara";
$conn = new mysqli($servername, $username, $password, $dbname, null, "/run/mysql/mysql.sock");


if ($conn->connect_error) {
  $error_message = "Connection failed: " . $conn->connect_error;
  error_log("Fatal error:" . $error_message, 0);
  die($error_message);
}

// Run the simulation logic
run_simulation($conn);

$message = "";
$search_results = [];
$departure = '';
$arrival = '';

// Determine user for logging
$current_user = $_SESSION['user_id'] ?? 'GUEST';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $departure = trim($_POST['departure'] ?? '');
    $arrival = trim($_POST['arrival'] ?? '');
    $date = $_POST['date'] ?? '';
    
    if (!empty($departure) && !empty($arrival) && !empty($date)) {

        $sql = "SELECT flight_id, dep_iata, dep_city, arr_iata, arr_city, plane_id, plane_status, flight_date, dep_time, duration_minutes 
                FROM View_SearchFlights
                WHERE 
            (UPPER(dep_city) = UPPER(?) OR UPPER(dep_iata) = UPPER(?))
            AND (UPPER(arr_city) = UPPER(?) OR UPPER(arr_iata) = UPPER(?))
            AND flight_date = ?";

        $stmt = $conn->prepare($sql);
        
        if ($stmt === false) {
            die("SQL Prepare failed: " . $conn->error); 
        }

        $stmt->bind_param("sssss", $departure, $departure, $arrival, $arrival, $date);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $dep_datetime = new DateTime($row['flight_date'] . ' ' . $row['dep_time']);
                $arr_datetime = clone $dep_datetime;
                $arr_datetime->modify('+' . $row['duration_minutes'] . ' minutes');
                
                $row['formatted_dep'] = $dep_datetime->format('H:i');
                $row['formatted_arr'] = $arr_datetime->format('H:i');
                $row['formatted_duration'] = floor($row['duration_minutes']/60).'h '.($row['duration_minutes']%60).'m';
                
                $search_results[] = $row;
            }
            $message = "<p class='success'>". $result->num_rows . " flights found.</p>";
            
            log_event("SEARCH_SUCCESS", "Flight Found: " . $result->num_rows . " flights for search: Departure='$departure', Arrival='$arrival'", $current_user);
        } else {
            $message = "<p class='error'>No flights found matching your search.</p>";
            log_event("SEARCH_FAILURE", "No flights found for search: Departure='$departure', Arrival='$arrival'", $current_user);
        }

        $stmt->close();
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
  <title>SkyBook - Airline Reservation System</title>
  <link rel="stylesheet" href="style.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  
  <link rel="stylesheet" href="//code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
  
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
     integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
     crossorigin=""/>
     
  <style>
      /* Custom style for the map container */
      #user-map {
          height: 400px;
          width: 100%;
          border-radius: 8px;
          margin-top: 15px;
          z-index: 1; /* Ensure it doesn't overlap dropdowns incorrectly */
      }
  </style>
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
        <li><a href="flight.php">Flights</a></li>
        <li><a href="login.php">Login</a></li>
        <li><a href="imprint.hmtl">Imprint</a></li>
      </ul>
    </nav>
  </header>

  <section class="hero">
    <h1>Your Journey Starts Here</h1>
    <p>Book flights with confidence and ease.</p>

    <div class="search-card">
      <form method="post" action="index.php">

        <div class="form-group">
          <label for="departure">Departure</label>
          <input type="text" id="departure" name="departure" placeholder="e.g. Berlin" required value="<?php echo htmlspecialchars($departure); ?>">
        </div>

        <div class="form-group">
          <label for="arrival">Arrival</label>
          <input type="text" id="arrival" name="arrival" placeholder="e.g. Paris" required value="<?php echo htmlspecialchars($arrival); ?>">
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
        <th>Route</th>
        <th>Departure</th>
        <th>Arrival</th>
        <th>Duration</th>
        <th>Status</th>
      </tr>
      <?php foreach ($search_results as $flight): ?>
      <tr>
        <td>
            <strong>#<?php echo htmlspecialchars($flight['flight_id']); ?></strong>
            <br><small>Plane: <?php echo htmlspecialchars($flight['plane_id']); ?></small>
        </td>
        <td>
            <?php echo htmlspecialchars($flight['dep_city']); ?> (<?php echo htmlspecialchars($flight['dep_iata']); ?>) 
            &rarr; 
            <?php echo htmlspecialchars($flight['arr_city']); ?> (<?php echo htmlspecialchars($flight['arr_iata']); ?>)
        </td>
        <td><?php echo htmlspecialchars($flight['formatted_dep']); ?></td>
        <td><?php echo htmlspecialchars($flight['formatted_arr']); ?></td>
        <td><?php echo htmlspecialchars($flight['formatted_duration']); ?></td>
        <td><?php echo htmlspecialchars($flight['plane_status']); ?></td>
      </tr>
      <?php endforeach; ?>
    </table>
  </div>
<?php endif; ?>


  <section class="features">
    <div class="feature-card">
      <h3>Seat Classes</h3>
      <p>Choose between economy, business, or first class — designed for comfort.</p>
    </div>
    <div class="feature-card">
      <h3>Global Destinations</h3>
      <p>Book domestic and international flights easily from one platform.</p>
      <p>admin credentials: 1 - admin01, user credentials: 3 - john123</p>
    </div>
    <div class="feature-card">
      <h3>Live Flight Status</h3>
      <p>Stay up to date with real-time flight information and updates.</p>
    </div>
  </section>
  
  <div class="results" style="margin-top: 40px;">
      <h2>User Location (Linked Service)</h2>
      <p>Your current region based on IP address:</p>
      <div id="user-map"></div>
  </div>

  <section class="disclaimer">
    <p>
      This website is student lab work and does not necessarily reflect Constructor University opinions...
    </p>
  </section>

  <footer>
    <p>&copy; 2025 SkyBook — <a href="imprint.hmtl">Imprint</a></p>
  </footer>
  
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
  
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
     integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
     crossorigin=""></script>
  
  <script>
    $( function() {
      
      // --- 1. AUTOCOMPLETE LOGIC (EXISTING) ---
      const DYNAMIC_AUTOCOMPLETE_ENDPOINT = 'get_locations.php';
      
      function setupDynamicAutocomplete(selector) {
          $( selector ).autocomplete({
              source: function(request, response) {
                  $.ajax({
                      url: DYNAMIC_AUTOCOMPLETE_ENDPOINT,
                      dataType: "json",
                      data: { term: request.term },
                      success: function(data) {
                          if (data.length === 0 && request.term.length >= 2) {
                             console.log("Autocomplete: 0 results.");
                          }
                          response(data); 
                      },
                      error: function(xhr, status, error) { response([]); }
                  });
              },
              minLength: 2, 
              delay: 300,
              select: function(event, ui) {
                  const selectedValue = ui.item.value;
                  const match = selectedValue.match(/\(([^)]+)\)$/);
                  let finalValue = selectedValue;
                  if (match && match[1]) {
                      const iata = match[1];
                      const city = selectedValue.substring(0, selectedValue.indexOf(' (')).trim();
                      if (iata.length < city.length) { finalValue = iata; } else { finalValue = city; }
                  } 
                  $(selector).val(finalValue);
                  event.preventDefault(); 
              }
          });
      }

      setupDynamicAutocomplete("#departure");
      setupDynamicAutocomplete("#arrival");
      
      
      // --- 2. NEW: LINKED SERVICE (MAP IMPLEMENTATION) ---
      // We fetch the client's location directly from the browser using ipinfo.io
      // This ensures it works even on localhost (it sees your public IP)
      
      fetch('https://ipinfo.io/json?token=') // Add token if you have one, otherwise it works limitedly
        .then(response => response.json())
        .then(data => {
            // data.loc contains "lat,long" string
            if (data.loc) {
                const [lat, lon] = data.loc.split(',');
                const ip = data.ip;
                const region = data.region;
                const city = data.city;
                
                // Initialize Leaflet Map
                var map = L.map('user-map').setView([lat, lon], 13);

                // Add OpenStreetMap Tile Layer
                L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
                }).addTo(map);

                // Add Marker
                var marker = L.marker([lat, lon]).addTo(map);
                
                // Add Popup (Callout)
                marker.bindPopup(`<b>IP Address:</b> ${ip}<br><b>Location:</b> ${city}, ${region}`).openPopup();
            } else {
                console.error("Could not detect location from IP.");
                document.getElementById('user-map').innerHTML = "<p style='padding:20px'>Location could not be detected.</p>";
            }
        })
        .catch(error => {
            console.error('Error fetching IP info:', error);
            document.getElementById('user-map').innerHTML = "<p style='padding:20px'>Error loading map service.</p>";
        });
        
    });
  </script>
</body>
</html>