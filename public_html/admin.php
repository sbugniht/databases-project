<?php
session_start();
// === CONFIGURAZIONE E CONNESSIONE DATABASE ===
// ASSUMENDO CHE QUESTO BLOCCO SIA STATO INSERITO COME DA INDICAZIONI PRECEDENTI
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

// 1. FETCH DATI AEREI (esistente)
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


// 2. FETCH DATI VOLI (NUOVO e CORRETTO)
$flights = [];
$existing_flights = []; // Variabile per popolare le tendine
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
        // Prepara la lista per le tendine (ID Volo: JFK -> LAX)
        $existing_flights[] = [
            'id' => $row['flight_id'],
            'label' => $row['flight_id'] . ': ' . $row['dep_iata'] . ' (' . $row['dep_city'] . ') -> ' . $row['arr_iata'] . ' (' . $row['arr_city'] . ')'
        ];
    }
}

// 3. LOGICA DI VISUALIZZAZIONE POSTI (NUOVA)
$simulated_seats = [];
$error_message = '';

if (isset($_GET['view_plane_id']) && is_numeric($_GET['view_plane_id'])) {
    $target_plane_id = (int)$_GET['view_plane_id'];
    
    // Trova i posti totali per l'aereo commerciale selezionato
    $seats_count = null;
    foreach ($planes as $p) {
        if ($p['plane_id'] == $target_plane_id && isset($p['seats'])) {
            $seats_count = (int)$p['seats'];
            break;
        }
    }
    
    if ($seats_count > 0) {
        $total_seats = $seats_count;
        $seats_per_row = 5; // Simula 5 posti (A, B, C | D, E)
        $num_rows = ceil($total_seats / $seats_per_row);
        $seat_labels = ['A', 'B', 'C', 'D', 'E']; 
        
        $seat_counter = 1;
        for ($r = 1; $r <= $num_rows; $r++) {
            $row_seats = [];
            for ($s = 0; $s < $seats_per_row; $s++) {
                if ($seat_counter <= $total_seats) {
                    $seat_number = $r . $seat_labels[$s];
                    // Simula lo stato: 1/3 posti riservati, 2/3 disponibili
                    $status = ($seat_counter % 3 === 0) ? 'reserved' : 'available'; 
                    $row_seats[] = ['number' => $seat_number, 'status' => $status];
                    $seat_counter++;
                }
            }
            $simulated_seats[] = $row_seats;
        }
    } elseif ($seats_count === 0) {
        $error_message = "L'aereo selezionato non ha una configurazione di posti (Cargo o Commerciale con 0 posti).";
    } else {
        $error_message = "Dati posti non disponibili per l'ID aereo selezionato.";
    }
}

// Chiude la connessione SOLO dopo aver completato tutti i fetch
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

<div class="results admin-management">
    <h1>Welcome Admin, ID: <?php echo htmlspecialchars($_SESSION['user_id']); ?>!</h1>
    <p>Use this dashboard to manage flights and view existing inventory.</p>

    <?php if (isset($_GET['success'])): ?>
        <div class="success">✅ Operazione completata con successo!</div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <div class="error">❌ Errore durante l'operazione: <?php echo htmlspecialchars($_GET['error']); ?></div>
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
                <h3>Seat Visualizer (Detailed View)</h3>
                <p>Seleziona l'ID di un aereo per visualizzare una mappa *simulata* dei posti:</p>
                
                <form method="get" action="admin.php" class="seat-selection-form">
                    <label for="select_plane">Aereo ID:</label>
                    <select id="select_plane" name="view_plane_id" onchange="this.form.submit()" class="full-width-select">
                        <option value="">Seleziona Aereo Commerciale</option>
                        <?php foreach ($planes as $p): ?>
                            <?php if (isset($p['seats'])): // Mostra solo aerei commerciali ?>
                                <option value="<?php echo htmlspecialchars($p['plane_id']); ?>" 
                                        <?php echo (isset($_GET['view_plane_id']) && $_GET['view_plane_id'] == $p['plane_id']) ? 'selected' : ''; ?>>
                                    ID <?php echo htmlspecialchars($p['plane_id']); ?> (<?php echo $p['seats']; ?> posti)
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </form>

                <div class="seat-map-placeholder">
                    <?php if (!empty($simulated_seats)): ?>
                        <div class="airplane-layout">
                            <div class="fuselage-marker">Fronte Aereo / Cabina</div>
                            <div class="aisle-marker">Corridoio</div>
                            
                            <?php foreach ($simulated_seats as $row_index => $row): ?>
                                <div class="seat-row">
                                    <div class="row-label"><?php echo $row_index + 1; ?></div>
                                    <?php foreach ($row as $seat): ?>
                                        <?php 
                                            // Aggiunge la classe per il corridoio dopo il terzo posto (C)
                                            $gap_class = (substr($seat['number'], -1) == 'C') ? 'has-aisle' : '';
                                        ?>
                                        <button class="seat-btn <?php echo $seat['status']; ?> <?php echo $gap_class; ?>" 
                                                title="Posto <?php echo $seat['number']; ?>">
                                            <?php echo substr($seat['number'], -1); ?>
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                            <?php endforeach; ?>
                            <div class="fuselage-marker">Coda Aereo</div>
                        </div>
                    <?php else: ?>
                        <p><?php echo $error_message ?: 'Seleziona un Aereo Commerciale per visualizzare i posti.'; ?></p>
                    <?php endif; ?>
                </div>
                
            </div>
        </div>
        
        <hr>
        
        <h2>Existing Flight IDs</h2>
<div class="plane-inventory-container">
    <div class="plane-list-card">
        <h3>Voli Attuali</h3>
        <?php if (!empty($flights)): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID Volo</th>
                        <th>Partenza</th>
                        <th>Arrivo</th>
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
    <div class="plane-seats-card">
        <p>Controlla l'ID e la rotta nella tabella a sinistra prima di aggiungere un nuovo volo.</p>
        <p>Ricorda di reindirizzare sempre a `admin.php?success=1` o `admin.php?error=...` da `manage_flights.php` per vedere i messaggi di conferma/errore qui.</p>
    </div>
</div>
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