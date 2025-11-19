<?php
session_start();

include_once 'logTracker.php';

$servername = "127.0.0.1";
$username_db = "gbrugnara";
$password_db = "KeRjnLwqj+rTTG3E";
$dbname = "db_gbrugnara";
$conn = new mysqli($servername, $username_db, $password_db, $dbname, null, "/run/mysql/mysql.sock");


if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

if (!isset($_SESSION['user_id']) || (int)$_SESSION['privilege'] !== 0) {
  $attempt_user = $_SESSION['user_id'] ?? 'GUEST';
  log_event("ACCESS_DENIED", "Attempted customer board access without valid privilege.", $attempt_user);
  header("Location: login.php");
  exit();
}

$customer_id = $_SESSION['user_id'];
log_event("CUSTOMER_ACCESS_SUCCESS", "Customer board access granted.", $customer_id);

$message = "";
$search_results = [];
$user_id = $_SESSION['user_id'];
$departure = '';
$arrival = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'search') {
       
    $departure = trim($_POST['departure'] ?? '');
    $arrival = trim($_POST['arrival'] ?? '');
    
    if (!empty($departure) && !empty($arrival)) {
 
        $sql = "
            SELECT 
                VSF.flight_id, VSF.dep_iata, VSF.dep_city, VSF.arr_iata, VSF.arr_city, 
                T.seat_id, SA.class, CP.PRICE,
                B.booking_id IS NOT NULL AS is_reserved,
                VSF.plane_id
            FROM View_SearchFlights VSF
            JOIN Tickets T ON VSF.flight_id = T.flight_id
            JOIN SeatAssignment SA ON T.flight_id = SA.flight_id AND T.seat_id = SA.seat_id
            JOIN classPrice CP ON SA.class = CP.class
            LEFT JOIN Bookings B ON T.flight_id = B.flight_id AND T.seat_id = B.seat_id
            WHERE 
                (UPPER(VSF.dep_city) = UPPER(?) OR UPPER(VSF.dep_iata) = UPPER(?))
                AND (UPPER(VSF.arr_city) = UPPER(?) OR UPPER(VSF.arr_iata) = UPPER(?))
            ORDER BY VSF.flight_id, T.seat_id
        ";

        $stmt = $conn->prepare($sql);
        
        $search_results = [];
        $flights_for_display = []; 
        $seats_per_row = 6; 

        if ($stmt === false) {
            $message = "<p class='error'>SQL Prepare failed: " . $conn->error . "</p>"; 
        } else {
            
            $stmt->bind_param("ssss", $departure, $departure, $arrival, $arrival);
            $stmt->execute();
            $result = $stmt->get_result();

        if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $flight_id = $row['flight_id'];
                    $seat_index = (int)$row['seat_id'] - 1; 

                    
                    if (!isset($flights_for_display[$flight_id])) {
                        $flights_for_display[$flight_id] = [
                            'info' => [
                                'flight_id' => $row['flight_id'],
                                'route' => $row['dep_city'] . '---> ' . $row['arr_city'],
                                'dep_iata' => $row['dep_iata'],
                                'arr_iata' => $row['arr_iata'],
                            ],
                            'seats' => [],
                        ];
                    }

                    
                    $row_index = floor($seat_index / $seats_per_row);
                    
                    
                    if (!isset($flights_for_display[$flight_id]['seats'][$row_index])) {
                        $flights_for_display[$flight_id]['seats'][$row_index] = [];
                    }

                    
                    $flights_for_display[$flight_id]['seats'][$row_index][] = [
                        'number' => $row['seat_id'],
                        'status' => $row['is_reserved'] ? 'reserved' : 'available',
                        'class' => $row['class'],
                        'price' => $row['PRICE'],
                    ];
                }
                $message = "<p class='success'>Flights found. Click an available seat to book!</p>";
                log_event("FLIGHT_SEARCH_SUCCESS", "Flights found for search: Departure='$departure', Arrival='$arrival'", $user_id);
            } else {
                $message = "<p class='error'>No available seats found for the selected route.</p>";
                log_event("FLIGHT_SEARCH_FAIL", "No existing flight for search: Departure='$departure', Arrival='$arrival'", $user_id);
            }
            $stmt->close();
        }
    } else {
        $message = "<p class='error'>Please enter both a Departure and Arrival location.</p>";
        log_event("FLIGHT_SEARCH_FAIL", "Missing departure or arrival location in search.", $user_id);
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="style.css">
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
        <li><a href="user.php" class="active">Search & Book</a></li>
        </ul>
    </nav>
    
    <a href="logout.php" class="header-action-btn">Logout</a>

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
       
        if (isset($_GET['status'])) {
            $msg_class = $_GET['status'] === 'success' ? 'success' : 'error';
            $msg_text = htmlspecialchars($_GET['msg']);
            echo "<p class='{$msg_class}'>{$msg_text}</p>";
        }
        echo $message; 
      ?>
    </div>

   <?php if (!empty($flights_for_display)): ?>
    <div class="results">
      <h2>Available Seats & Booking</h2>
      
      <div id="booking-summary" class="summary-card" style="display: none;">
          <h3>Booking Confirmation</h3>
          <p id="summary-flight"></p>
          <p>Seat: <strong id="summary-seat-number"></strong> (<span id="summary-seat-class"></span>)</p>
          <p>Price: <strong id="summary-price"></strong> €</p>
          
          <form method="post" action="book_flight.php" id="booking-form">
              <input type="hidden" name="flight_id" id="form-flight-id">
              <input type="hidden" name="seat_id" id="form-seat-id">
              <button type="submit" class="btn-primary">Confirm Booking</button>
          </form>
      </div>
      
      <?php foreach ($flights_for_display as $flight_data): 
          $info = $flight_data['info'];
          $simulated_seats = $flight_data['seats'];
      ?>
      <div class="flight-map-container">
          <h3>Volo ID <?php echo htmlspecialchars($info['flight_id']) . ': ' . $info['dep_iata'] . ' &rarr; ' . $info['arr_iata']; ?></h3>
          
          <div class="airplane-layout">
              <div class="fuselage-marker">Legend: Reserved (Red) | Available (Green)</div>
              <div class="aisle-marker">Corridor</div>

              <div class="seat-rows-container">
                  <?php foreach ($simulated_seats as $row_index => $row): ?>
                      <div class="seat-row">
                          <div class="row-label"><?php echo $row_index + 1; ?></div>
                          <?php 
                          $seat_in_row_counter = 0;
                          foreach ($row as $seat): 
                              $seat_in_row_counter++;
                              // 
                              $gap_class = ($seat_in_row_counter === 4) ? 'has-aisle' : '';
                              
                              // 
                              $is_bookable = $seat['status'] === 'available' ? ' bookable' : '';
                          ?>
                              <button 
                                  class="seat-btn <?php echo $seat['status'] . $is_bookable; ?> <?php echo strtolower($seat['class']); ?> bookable" 
                                  title="<?php echo 'Seat: ' . $seat['number'] . ' | Class: ' . $seat['class'] . ' | Price: ' . $seat['price'] . ' €'; ?>"
                                  data-flight-id="<?php echo htmlspecialchars($info['flight_id']); ?>"
                                  data-seat-num="<?php echo htmlspecialchars($seat['number']); ?>"
                                  data-seat-class="<?php echo htmlspecialchars($seat['class']); ?>"
                                  data-price="<?php echo htmlspecialchars($seat['price']); ?>"
                                  data-route="<?php echo htmlspecialchars($info['route']); ?>"
                                  <?php echo $seat['status'] === 'reserved' ? 'disabled' : ''; ?>>
                                  <?php echo htmlspecialchars($seat['number']); ?>
                              </button>
                          <?php endforeach; ?>
                      </div>
                  <?php endforeach; ?>
              </div> 
              <div class="fuselage-marker"> <- front of the airplane</div>                    
          </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
  
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
  
  <script>
    document.addEventListener('DOMContentLoaded', () => {
        const summaryCard = document.getElementById('booking-summary');
        const bookableSeats = document.querySelectorAll('.seat-btn.bookable');

        bookableSeats.forEach(button => {
            button.addEventListener('click', (event) => {
                // Rimuove la selezione dai posti precedentemente selezionati
                document.querySelectorAll('.seat-btn.selected').forEach(btn => {
                    btn.classList.remove('selected');
                });
                
                // Aggiunge la selezione al posto corrente
                event.currentTarget.classList.add('selected');

                // Popola il riepilogo
                const flightId = event.currentTarget.dataset.flightId;
                const seatNum = event.currentTarget.dataset.seatNum;
                const seatClass = event.currentTarget.dataset.seatClass;
                const price = event.currentTarget.dataset.price;
                const route = event.currentTarget.dataset.route;

                document.getElementById('summary-flight').textContent = `Flight ID ${flightId}: ${route}`;
                document.getElementById('summary-seat-number').textContent = seatNum;
                document.getElementById('summary-seat-class').textContent = seatClass;
                document.getElementById('summary-price').textContent = price;
                
                document.getElementById('form-flight-id').value = flightId;
                document.getElementById('form-seat-id').value = seatNum;

                // Mostra il riepilogo
                summaryCard.style.display = 'block';
                summaryCard.scrollIntoView({ behavior: 'smooth' });
            });
        });
        
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
                            response(data);
                        },
                        error: function() {
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

                        finalValue = iata.length <= city.length ? iata : city;
                    } 
                    
                    $(selector).val(finalValue);
                    
                    event.preventDefault(); 
                }
            });
        }

        setupDynamicAutocomplete("#departure");
        setupDynamicAutocomplete("#arrival");
  </script>
</body>
</html>