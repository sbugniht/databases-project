
<?php

include_once 'logTracker.php';

$servername = "127.0.0.1";
$username = "gbrugnara";
$password = "KeRjnLwqj+rTTG3E";
$dbname = "db_gbrugnara";
## 4JjJ0zWOOo76
$conn = new mysqli($servername, $username, $password, $dbname, null, "/run/mysql/mysql.sock");


if ($conn->connect_error) {
  $error_message = "Connection failed: " . $conn->connect_error;
  error_log("Fatal error:" . $error_message, 0);
  die($error_message);
}

$message = "";
$search_results = [];
$departure = '';
$arrival = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $departure = trim($_POST['departure'] ?? '');
    $arrival = trim($_POST['arrival'] ?? '');
    
    
    if (!empty($departure) && !empty($arrival)) {

        
        $sql = "SELECT flight_id, dep_iata, dep_city, arr_iata, arr_city, plane_id, plane_status 
                FROM View_SearchFlights
                WHERE 
            (UPPER(dep_city) = UPPER(?) OR UPPER(dep_iata) = UPPER(?))
            AND (UPPER(arr_city) = UPPER(?) OR UPPER(arr_iata) = UPPER(?))";

        
        $stmt = $conn->prepare($sql);
        
        if ($stmt === false) {
             
            die("SQL Prepare failed: " . $conn->error); 
        }

        
        $stmt->bind_param("ssss", $departure, $departure, $arrival, $arrival);

        
        $stmt->execute();
        
        
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $search_results[] = $row;
            }
            $message = "<p class='success'>". $result->num_rows . " flights found.</p>";
            log_event("SEARCH_SUCCESS", "Flight Found: " . $result->num_rows . " flights for search: Departure='$departure', Arrival='$arrival'", $ip_address);
        } else {
            $message = "<p class='error'>No flights found matching your search.</p>";
            log_event("SEARCH_FAILURE", "No flights found for search: Departure='$departure', Arrival='$arrival'", $ip_address);
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
        <th>Departure (IATA/City)</th>
        <th>Arrival (IATA/City)</th>
        <th>Plane ID</th>
        <th>Status</th>
      </tr>
      <?php foreach ($search_results as $flight): ?>
      <tr>
        <td><?php echo htmlspecialchars($flight['flight_id']); ?></td>
        <td><?php echo htmlspecialchars($flight['dep_iata']) . ' / ' . htmlspecialchars($flight['dep_city']); ?></td>
        <td><?php echo htmlspecialchars($flight['arr_iata']) . ' / ' . htmlspecialchars($flight['arr_city']); ?></td>
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
      <p>admin credentials: 1 - admin01, user credentials: 3 - john123</p>
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
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
  
  <script>
    $( function() {
      
      const DYNAMIC_AUTOCOMPLETE_ENDPOINT = 'get_locations.php';
      
      function setupDynamicAutocomplete(selector) {
          $( selector ).autocomplete({
              
              source: function(request, response) {
                  
                  $.ajax({
                      url: DYNAMIC_AUTOCOMPLETE_ENDPOINT,
                      dataType: "json",
                      data: {
                          
                          term: request.term
                      },
                      success: function(data) {
                          
                          if (data.length === 0 && request.term.length >= 2) {
                             console.log("Autocomplete: 0 results for " + request.term + ". Check DB connectivity or search view data.");
                          }
                          
                          response(data); 
                      },
                      error: function(xhr, status, error) {
                          console.error("Autocomplete AJAX Error:", status, error, xhr.responseText);
                          response([]); 
                      }
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

                      if (iata.length < city.length) {
                         finalValue = iata;
                      } else {
                         finalValue = city;
                      }
                  } 
                  
                  $(selector).val(finalValue);
                  
                  event.preventDefault(); 
              }
          
          });
      }

      
      setupDynamicAutocomplete("#departure");
      setupDynamicAutocomplete("#arrival");
      
    } );
  </script>
</body>
</html>