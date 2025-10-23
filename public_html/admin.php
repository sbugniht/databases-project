<?php
session_start();
// === CONFIGURAZIONE E CONNESSIONE DATABASE ===
$servername = "127.0.0.1";
$username_db = "gbrugnara"; 
$password_db = "KeRjnLwqj+rTTG3E";
$dbname = "db_gbrugnara";
$conn = new mysqli($servername, $username_db, $password_db, $dbname, null, "/run/mysql/mysql.sock");

if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}
// ===========================================

// Controllo di sicurezza: DEVE essere un admin (privilege 1)
if (!isset($_SESSION['user_id']) || (int)$_SESSION['privilege'] !== 1) {
  header("Location: login.php");
  exit();
}

// 1. FETCH DATI AEROPORTI per le tendine
$airports = [];
$sql_airports = "SELECT airport_id, iata, city FROM Airport ORDER BY iata ASC";
$result_airports = $conn->query($sql_airports);
if ($result_airports) {
    while ($row = $result_airports->fetch_assoc()) {
        $airports[] = $row;
    }
}

// 2. FETCH DATI AEREI
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

// 3. FETCH DATI VOLI
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
        plane_status
    FROM View_SearchFlights
    ORDER BY flight_id DESC;
";
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

// 4. LOGICA DI VISUALIZZAZIONE POSTI
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
        $seats_per_row = 6;
        $seat_counter = 0; // Inizia da zero per il calcolo del modulo
        $row_index = 0;
        
        while ($row = $result_seat_data->fetch_assoc()) {
            if ($seat_counter % $seats_per_row === 0) {
                // Inizia una nuova riga nell'array ogni 6 posti (0, 6, 12, ...)
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
    <link rel="stylesheet" href="style.css"> 
    <link rel="admin_style" href="admiin-style.css">
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
                <h3>Seat Visualizer (Volo: <?php echo isset($target_flight_id) ? $target_flight_id : 'Seleziona'; ?>)</h3>
                <p>Seleziona l'ID di un **volo** per visualizzare lo stato dei posti:</p>
                
                <form method="get" action="admin.php" class="seat-selection-form">
                    <label for="select_flight">Volo ID:</label>
                    <select id="select_flight" name="view_flight_id" onchange="this.form.submit()" class="full-width-select">
                        <option value="">Seleziona Volo</option>
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
                            <div class="fuselage-marker">Fronte Aereo / Legenda: Reserved (Rosso) | Available (Verde)</div>
                            <div class="aisle-marker">Corridoio</div>

                            <div class="seat-rows-container">

                            <?php foreach ($simulated_seats as $row_index => $row): ?>
                                <div class="seat-row">
                                    <div class="row-label"><?php echo $row_index + 1; ?></div>

                                    <?php 
                                    $seat_in_row_counter = 0;
                                    foreach ($row as $seat): 
                                        $seat_in_row_counter++;
                                        $gap_class = ($seat_in_row_counter === 3) ? 'has-aisle' : '';
                                    ?>
                                        <button class="seat-btn <?php echo $seat['status']; ?> <?php echo $gap_class; ?> <?php echo strtolower($seat['class']); ?>" 
                                                title="<?php echo 'Seat: ' . $seat['number'] . ' | Class: ' . $seat['class']; ?>">
                                            <?php echo htmlspecialchars($seat['number']); ?>
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                            <?php endforeach; ?>
                                    
                            </div> <div class="fuselage-marker">Coda Aereo</div>                    
                        </div> <?php else: ?>
                        <p><?php echo $error_message ?: 'Seleziona un Volo per visualizzare i posti.'; ?></p>
                    <?php endif; ?>
                </div>
            
        </div>
        
        <hr>
        
        <h2>Existing Flight IDs</h2>
        <div class="flight-list-scroll-wrapper">
             <div class="plane-list-card full-width-card">
                <h3>Voli Attuali</h3>
                <div class="scrollable-table-container">
                    <?php if (!empty($flights)): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>ID Volo</th>
                                    <th>Partenza (IATA/City)</th>
                                    <th>Arrivo (IATA/City)</th>
                                    <th>Aereo ID</th>
                                    <th>Stato</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($flights as $flight): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($flight['flight_id']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($flight['dep_iata']) . ' (' . htmlspecialchars($flight['dep_city']) . ')'; ?></td>
                                        <td><?php echo htmlspecialchars($flight['arr_iata']) . ' (' . htmlspecialchars($flight['arr_city']) . ')'; ?></td>
                                        <td><?php echo htmlspecialchars($flight['plane_id']); ?></td>
                                        <td><?php echo htmlspecialchars($flight['plane_status']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>Nessun volo trovato.</p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="plane-seats-card full-width-card">
                <p>Controlla l'ID e la rotta nella tabella a sinistra prima di aggiungere un nuovo volo.</p>
                <p>Ricorda di reindirizzare sempre a `admin.php?status=success&msg=...` o `admin.php?status=error&msg=...` da `manage_flights.php` per vedere i messaggi di conferma/errore qui.</p>
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
                        <option value="">Seleziona Partenza</option>
                        <?php foreach ($airports as $a): ?>
                            <option value="<?php echo htmlspecialchars($a['airport_id']); ?>">
                                <?php echo htmlspecialchars($a['iata']) . ' (' . htmlspecialchars($a['city']) . ')'; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label for="a_airport">Arrival Airport:</label>
                    <select id="a_airport" name="a_airport_id" required>
                        <option value="">Seleziona Arrivo</option>
                        <?php foreach ($airports as $a): ?>
                            <option value="<?php echo htmlspecialchars($a['airport_id']); ?>">
                                <?php echo htmlspecialchars($a['iata']) . ' (' . htmlspecialchars($a['city']) . ')'; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label for="plane_id">Plane ID:</label>
                    <select id="plane_id" name="plane_id" required>
                        <option value="">Seleziona Aereo</option>
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

                    <button type="submit" class="btn-primary">Add Flight</button>
                </form>
            </div>

            <div class="form-card">
                <h3>Remove Existing Flight</h3>
                <form method="post" action="manage_flights.php" class="flight-form">
                    <input type="hidden" name="action" value="remove">
                    
                    <label for="remove_flight_id">Flight ID to Remove:</label>
                    <select id="remove_flight_id" name="flight_id" required class="full-width-select">
                        <option value="">Seleziona Volo da Rimuovere</option>
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
        
    </section>
</div>

</body>
</html>