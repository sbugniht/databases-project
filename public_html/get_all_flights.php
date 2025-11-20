<?php
$servername = "127.0.0.1";
$username = "gbrugnara";
$password = "KeRjnLwqj+rTTG3E";
$dbname = "db_gbrugnara";
$conn = new mysqli($servername, $username, $password, $dbname, null, "/run/mysql/mysql.sock");

if ($conn->connect_error) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database connection failed.']);
    exit();
}

$filter_query = trim($_GET['filter'] ?? '');
$search_term = '%' . $filter_query . '%';

// Selezioniamo le nuove colonne dalla View aggiornata
$sql = "SELECT flight_id, dep_iata, dep_city, arr_iata, arr_city, plane_id, plane_status, flight_date, dep_time, duration_minutes 
        FROM View_SearchFlights";

if (!empty($filter_query)) {
    $sql .= " WHERE (
                UPPER(dep_city) LIKE UPPER(?) OR UPPER(dep_iata) LIKE UPPER(?) OR 
                UPPER(arr_city) LIKE UPPER(?) OR UPPER(arr_iata) LIKE UPPER(?)
            )";
}

$sql .= " ORDER BY flight_date ASC, dep_time ASC"; // Ordina per data e orario

$flights = [];
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'SQL Prepare failed: ' . $conn->error]);
    exit();
}

if (!empty($filter_query)) {
    $stmt->bind_param("ssss", $search_term, $search_term, $search_term, $search_term);
}

$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // --- Calcolo dell'Arrivo ---
        $departure_string = $row['flight_date'] . ' ' . $row['dep_time'];
        $dep_datetime = new DateTime($departure_string);
        
        $arr_datetime = clone $dep_datetime;
        $arr_datetime->modify('+' . $row['duration_minutes'] . ' minutes');
        
        // Formattazione per il Frontend
        $row['formatted_dep'] = $dep_datetime->format('d M Y, H:i'); // es: 20 Nov 2025, 14:30
        $row['formatted_arr'] = $arr_datetime->format('d M Y, H:i');
        
        // Aggiungiamo la durata formattata (es: 2h 15m)
        $hours = floor($row['duration_minutes'] / 60);
        $minutes = $row['duration_minutes'] % 60;
        $row['formatted_duration'] = "{$hours}h {$minutes}m";
        
        $flights[] = $row;
    }
}

$stmt->close();
$conn->close();

header('Content-Type: application/json');
echo json_encode($flights);
?>