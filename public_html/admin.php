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
## system that manages flights automatically based on time user = 1000 pwd= system



if (!isset($_SESSION['user_id']) || (int)$_SESSION['privilege'] !== 1) {
  header("Location: login.php");
  exit();
}

$admin_id = $_SESSION['user_id'];
log_event("ADMIN_ACCESS_SUCCESS", "Admin dashboard access granted.", $admin_id);


$airports = [];
$sql_airports = "SELECT airport_id, iata, city FROM Airport ORDER BY iata ASC";
$result_airports = $conn->query($sql_airports);
if ($result_airports) {
    while ($row = $result_airports->fetch_assoc()) {
        $airports[] = $row;
    }
}


$planes = [];
$sql_planes = "
    SELECT 
        P.plane_id, 
        C.seats,
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


$flights = [];
$existing_flights = []; 
$sql_flights = "
    SELECT 
        flight_id, 
        dep_iata, 
        dep_city, 
        arr_iata, 
        arr_city, 
        plane_id, 
        plane_status,
        flight_date,
        dep_time,
        duration_minutes
    FROM View_SearchFlights
    ORDER BY flight_date DESC, dep_time ASC;
";
$existing_countries = [];
$sql_countries = "SELECT country FROM Fee ORDER BY country ASC";
$result_countries = $conn->query($sql_countries);
if ($result_countries) {
    while ($row = $result_countries->fetch_assoc()) {
        $existing_countries[] = $row['country'];
    }
}
$result_flights = $conn->query($sql_flights);
if ($result_flights) {
    while ($row = $result_flights->fetch_assoc()) {
        $flights[] = $row;
        $existing_flights[] = [
            'id' => $row['flight_id'],
            'label' => $row['flight_id'] . ': ' . $row['dep_iata'] . ' (' . $row['dep_city'] . ') -> ' . $row['arr_iata'] . ' (' . $row['arr_city'] . ')'
        ];
    }
}

// visualizing seats logic
$simulated_seats = [];
$error_message = '';

if (isset($_GET['view_flight_id']) && is_numeric($_GET['view_flight_id'])) {
    $target_flight_id = (int)$_GET['view_flight_id'];
    
    $sql_seat_data = "
        SELECT 
            T.seat_id,
            SA.class,
            B.booking_id IS NOT NULL AS is_reserved
        FROM Tickets T
        JOIN SeatAssignment SA ON T.flight_id = SA.flight_id AND T.seat_id = SA.seat_id
        LEFT JOIN Bookings B ON T.flight_id = B.flight_id AND T.seat_id = B.seat_id
        WHERE T.flight_id = ?
        ORDER BY T.seat_id ASC
    ";

    $stmt_seat_data = $conn->prepare($sql_seat_data);
    $stmt_seat_data->bind_param("i", $target_flight_id);
    $stmt_seat_data->execute();
    $result_seat_data = $stmt_seat_data->get_result();

    if ($result_seat_data->num_rows > 0) {
        
        log_event("ADMIN_VIEW_SEATS_SUCCESS","Seat map visualized for Flight ID: " . $target_flight_id, $_SESSION['user_id']);
        
        $seats_per_row = 6;
        $seat_counter = 0; 
        $row_index = 0;
        
        while ($row = $result_seat_data->fetch_assoc()) {
            if ($seat_counter % $seats_per_row === 0) {
                
                $simulated_seats[$row_index] = [];
            }
            
            $simulated_seats[$row_index][] = [
                'number' => $row['seat_id'],
                'status' => $row['is_reserved'] ? 'reserved' : 'available',
                'class' => $row['class']
            ];
            
            $seat_counter++;

            if ($seat_counter % $seats_per_row === 0) {
                $row_index++;
            }
            
        }
    } else {
        $error_message = "Nessun posto trovato per il Volo ID $target_flight_id.";
        log_event("ADMIN_VIEW_SEATS_FAILURE","No seats found for Flight ID: " . $target_flight_id, $_SESSION['user_id']);
    }
    $stmt_seat_data->close();

}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - SkyBook</title>
    <link rel="stylesheet" href="admin-style.css"> 
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

<div class="results admin-management">
    <h1>Welcome Admin, ID: <?php echo htmlspecialchars($_SESSION['user_id']); ?>!</h1>
    <p>Use this dashboard to manage flights and view existing inventory.</p>

    <?php if (isset($_GET['status'])): ?>
        <div class="<?php echo htmlspecialchars($_GET['status']) === 'success' ? 'success' : 'error'; ?>">
             <?php echo htmlspecialchars($_GET['msg']); ?>
        </div>
    <?php endif; ?>
    <section class="admin-management-content">
        
        <h2>Existing Planes & Inventory</h2>
        <div class="plane-inventory-container">
            
            <div class="plane-list-card" style="flex-basis: 350px;">
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
    
    <div class="plane-list-card" style="flex-basis: 350px;">
        <h3>Existing Airport Locations</h3>
        <div class="scrollable-table-container" style="max-height: 250px;">
            <?php if (!empty($airports)): ?>
                <ul>
                    <?php foreach ($airports as $a): ?>
                        <li>
                            <strong>ID <?php echo htmlspecialchars($a['airport_id']); ?>:</strong> 
                            <?php echo htmlspecialchars($a['city']) . ' (' . htmlspecialchars($a['iata']) . ') - ' . htmlspecialchars($a['country']); ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>No airports found.</p>
            <?php endif; ?>
        </div>
    </div>
            
            
            <div class="plane-seats-card">
                <h3>Seat Visualizer (Flight: <?php echo isset($target_flight_id) ? $target_flight_id : 'Seleziona'; ?>)</h3>
                <p>Select Flight ID to visualize seats status:</p>
                
                <form method="get" action="admin.php" class="seat-selection-form">
                    <label for="select_flight">Flight ID:</label>
                    <select id="select_flight" name="view_flight_id" onchange="this.form.submit()" class="full-width-select">
                        <option value="">Select Flight</option>
                        <?php foreach ($flights as $f): ?>
                            <option value="<?php echo htmlspecialchars($f['flight_id']); ?>" 
                                    <?php echo (isset($_GET['view_flight_id']) && (int)$_GET['view_flight_id'] == (int)$f['flight_id']) ? 'selected' : ''; ?>>
                                ID <?php echo htmlspecialchars($f['flight_id']); ?> (<?php echo $f['dep_iata']; ?> &rarr; <?php echo $f['arr_iata']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>

                <div class="seat-map-placeholder">
                    <?php if (!empty($simulated_seats)): ?>
                        <div class="airplane-layout">
                            <div class="fuselage-marker"> Legend: Reserved (Red) | Available (Green)</div>
                            <div class="aisle-marker">Corridor</div>

                            <div class="seat-rows-container">

                            <?php foreach ($simulated_seats as $row_index => $row): ?>
                                <div class="seat-row">
                                    <div class="row-label"><?php echo $row_index + 1; ?></div>

                                    <?php 
                                    $seat_in_row_counter = 0;
                                    foreach ($row as $seat): 
                                        $seat_in_row_counter++;
                                        
                                        $gap_class = ($seat_in_row_counter === 4) ? 'has-aisle' : ''; 
                                        ?>
                                            <button class="seat-btn <?php echo $seat['status']; ?> <?php echo $gap_class; ?> <?php echo strtolower($seat['class']); ?>" 
                                                title="<?php echo 'Seat: ' . $seat['number'] . ' | Class: ' . $seat['class']; ?>">
                                                <?php echo htmlspecialchars($seat['number']); ?>
                                            </button>
                                        <?php endforeach; ?>
                                </div>
                            <?php endforeach; ?>
                                    
                            </div> <div class="fuselage-marker">Airplane Tail</div>                    
                        </div> <?php else: ?>
                        <p><?php echo $error_message ?: 'Select a plane to visualize the seats.'; ?></p>
                    <?php endif; ?>
                </div>
            
        </div>
        
        <hr>
        
        <h2>Existing Flight IDs</h2>
        <div class="flight-list-scroll-wrapper">
             <div class="plane-list-card full-width-card">
                <h3>Actual Flights</h3>
                <div class="scrollable-table-container">
                    <?php if (!empty($flights)): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Flight ID</th>
                                    <th>Date & Time</th> <th>Route</th>       <th>Dur.</th>        <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($flights as $flight): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($flight['flight_id']); ?></strong><br><small>Plane: <?php echo $flight['plane_id']; ?></small></td>
                                        <td>
                                            <?php echo htmlspecialchars($flight['flight_date']); ?><br>
                                            <?php echo htmlspecialchars(substr($flight['dep_time'], 0, 5)); ?>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($flight['dep_iata']); ?> &rarr; <?php echo htmlspecialchars($flight['arr_iata']); ?>
                                        </td>
                                        <td><?php echo floor($flight['duration_minutes']/60).'h '.($flight['duration_minutes']%60).'m'; ?></td>
                                        <td><?php echo htmlspecialchars($flight['plane_status']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>No flight found.</p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="plane-seats-card full-width-card">
                <p>Check ID and route in the table on the left before adding a new flight.</p>
                
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

                    <label for="d_airport">Departure Airport:</label>
                    <select id="d_airport" name="d_airport_id" required>
                        <option value="">Select Departure</option>
                        <?php foreach ($airports as $a): ?>
                            <option value="<?php echo htmlspecialchars($a['airport_id']); ?>">
                                <?php echo htmlspecialchars($a['iata']) . ' (' . htmlspecialchars($a['city']) . ')'; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label for="a_airport">Arrival Airport:</label>
                    <select id="a_airport" name="a_airport_id" required>
                        <option value="">Select Arrival</option>
                        <?php foreach ($airports as $a): ?>
                            <option value="<?php echo htmlspecialchars($a['airport_id']); ?>">
                                <?php echo htmlspecialchars($a['iata']) . ' (' . htmlspecialchars($a['city']) . ')'; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label for="plane_id">Plane ID:</label>
                    <select id="plane_id" name="plane_id" required>
                        <option value="">Select Airplane</option>
                        <?php foreach ($planes as $p): ?>
                            <?php if (strpos($p['type_status'], 'Commercial') !== false): ?>
                                <option value="<?php echo htmlspecialchars($p['plane_id']); ?>">
                                    ID <?php echo htmlspecialchars($p['plane_id']) . ' ' . $p['type_status']; ?>
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>

                    <label for="status">Status:</label>
                    <select id="status" name="status" required>
                        <option value="on time">On Time</option>
                        <option value="delayed">Delayed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>

                    <label for="flight_type">Type (Classification):</label>
                    <select id="flight_type" name="flight_type" required>
                        <option value="Dom_flight">Domestic</option>
                        <option value="Int_flight">International</option>
                    </select>
                    <label for="new_flight_date">Departure Date:</label>
                    <input type="date" id="new_flight_date" name="flight_date" required>

                    <label for="new_dep_time">Departure Time:</label>
                    <input type="time" id="new_dep_time" name="dep_time" required>

                    <p style="font-size: 0.85em; color: #666; margin-bottom: 10px;">
                        * Duration will be automatically generated based on Flight Type.
                    </p>

                    <button type="submit" class="btn-primary">Add Flight</button>
                </form>
            </div>

            <div class="form-card">
                <h3>Remove Existing Flight</h3>
                <form method="post" action="manage_flights.php" class="flight-form">
                    <input type="hidden" name="action" value="remove">
                    
                    <label for="remove_flight_id">Flight ID to Remove:</label>
                    <select id="remove_flight_id" name="flight_id" required class="full-width-select">
                        <option value="">Select Flight To Remove</option>
                        <?php foreach ($existing_flights as $flight): ?>
                            <option value="<?php echo htmlspecialchars($flight['id']); ?>">
                                <?php echo htmlspecialchars($flight['label']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <button type="submit" class="btn-secondary">Remove Flight</button>
                </form>
            </div>
        </div>

        <hr>
        <h2>Manage Airports & Locations</h2>
        <div class="flight-management-grid">
            
            <div class="form-card">
                <h3>Add New Airport Location</h3>
                <form method="post" action="manage_airports.php" class="flight-form">
                    <input type="hidden" name="action" value="add_airport">
                    
                    <label for="new_airport_id">Airport ID (New):</label>
                    <input type="number" id="new_airport_id" name="airport_id" required>

                    <label for="new_iata">IATA Code (3 letters):</label>
                    <input type="text" id="new_iata" name="iata" maxlength="3" required style="text-transform:uppercase;">

                    <label for="new_city">City:</label>
                    <input type="text" id="new_city" name="city" required>
                    
                    <label for="country_select">Country:</label>
                    <select id="country_select" name="country" required>
                        <option value="">Select Existing Country</option>
                        
                        <?php foreach ($existing_countries as $country_name): ?>
                            <option value="<?php echo htmlspecialchars($country_name); ?>">
                                <?php echo htmlspecialchars($country_name); ?>
                            </option>
                        <?php endforeach; ?>
                        
                        <option value="NEW_COUNTRY_ENTRY">--- Add New Country ---</option>
                    </select>

                    <div id="new-country-fields" style="display: none; border: 1px dashed #ccc; padding: 10px; margin-top: 10px;">
                        <h4>New Country Fee Data:</h4>
                        
                        <label for="new_country_name">New Country Name:</label>
                        <input type="text" id="new_country_name" name="new_country_name" placeholder="Enter new country name">

                        <label for="dom_fee">Domestic Fee:</label>
                        <input type="number" id="dom_fee" name="dom_fee" placeholder="e.g. 50" min="0">
                        
                        <label for="int_fee">International Fee:</label>
                        <input type="number" id="int_fee" name="int_fee" placeholder="e.g. 100" min="0">
                    </div>

                    <button type="submit" class="btn-primary">Add Airport</button>
                </form>
            </div>

            <div class="form-card">
                <h3>Remove Existing Airport</h3>
                <form method="post" action="manage_airports.php" class="flight-form">
                    <input type="hidden" name="action" value="remove_airport">
                    
                    <label for="remove_airport_id">Airport to Remove:</label>
                    <select id="remove_airport_id" name="airport_id" required class="full-width-select">
                        <option value="">Select Airport ID (City/IATA)</option>
                        <?php foreach ($airports as $a): ?>
                            <option value="<?php echo htmlspecialchars($a['airport_id']); ?>">
                                ID <?php echo htmlspecialchars($a['airport_id']) . ': ' . htmlspecialchars($a['city']) . ' (' . htmlspecialchars($a['iata']) . ')'; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <button type="submit" class="btn-secondary" style="background-color: var(--error-color);">Remove Airport</button>
                    <p style="font-size: 0.9em; color: var(--error-color); margin-top: 10px;">Warning: Cannot remove airports linked to existing flights.</p>
                </form>
            </div>
        </div>
    </section>
    
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>

<script>
$(function() {
    const $countrySelect = $('#country_select');
    const $newCountryFields = $('#new-country-fields');
    const $newCountryName = $('#new_country_name');
    const $domFee = $('#dom_fee');
    const $intFee = $('#int_fee');

    // Function to toggle visibility and required status of Fee fields
    function toggleNewCountryFields(show) {
        if (show) {
            $newCountryFields.slideDown();
            $newCountryName.prop('required', true);
            $domFee.prop('required', true);
            $intFee.prop('required', true);
        } else {
            $newCountryFields.slideUp();
            $newCountryName.prop('required', false).val('');
            $domFee.prop('required', false).val('');
            $intFee.prop('required', false).val('');
        }
    }

    // Event listener for the Country dropdown change
    $countrySelect.on('change', function() {
        if ($(this).val() === 'NEW_COUNTRY_ENTRY') {
            toggleNewCountryFields(true);
            $(this).removeAttr('name');
            $newCountryName.attr('name', 'country'); 
        } else {
            toggleNewCountryFields(false);
            $(this).attr('name', 'country');
            $newCountryName.removeAttr('name');
        }
    }).trigger('change'); 
});
</script>
</body>
</html>