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

$filter_text = trim($_GET['filter'] ?? '');
$filter_date = trim($_GET['date'] ?? '');

if (!empty($filter_date)) {
    $res_max = $conn->query("SELECT MAX(flight_date) as max_date FROM Flights");
    $row_max = $res_max->fetch_assoc();
    $max_simulated_date = $row_max['max_date'];

    if ($filter_date > $max_simulated_date) {
        header('Content-Type: application/json');
        echo json_encode(['special_message' => 'Flights yet to be determined']);
        exit();
    }
}

$sql = "SELECT flight_id, dep_iata, dep_city, arr_iata, arr_city, plane_id, plane_status, flight_date, dep_time, original_dep_time, duration_minutes 
        FROM View_SearchFlights WHERE 1=1"; 

$params = [];
$types = "";

if (!empty($filter_text)) {
    $sql .= " AND (
                UPPER(dep_city) LIKE UPPER(?) OR UPPER(dep_iata) LIKE UPPER(?) OR 
                UPPER(arr_city) LIKE UPPER(?) OR UPPER(arr_iata) LIKE UPPER(?)
            )";
    $search_term = '%' . $filter_text . '%';
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "ssss";
}

if (!empty($filter_date)) {
    $sql .= " AND flight_date = ?";
    $params[] = $filter_date;
    $types .= "s";
}

$sql .= " ORDER BY flight_date ASC, dep_time ASC";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$flights = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $dep_string = $row['flight_date'] . ' ' . $row['dep_time'];
        $dep_dt = new DateTime($dep_string);
        
        $arr_dt = clone $dep_dt;
        $arr_dt->modify('+' . $row['duration_minutes'] . ' minutes');
        
        $row['formatted_dep'] = $dep_dt->format('d M Y, H:i');
        $row['formatted_arr'] = $arr_dt->format('d M Y, H:i');
        
        if (strtolower($row['plane_status']) === 'delayed' && !empty($row['original_dep_time'])) {
            $orig_dt = new DateTime($row['flight_date'] . ' ' . $row['original_dep_time']);
            
            $row['formatted_dep'] = '<span style="text-decoration: line-through; color: #999;">' . $orig_dt->format('d M Y, H:i') . '</span><br>' . 
                                    '<span style="color: #dc3545; font-weight: bold;">' . $dep_dt->format('d M Y, H:i') . '</span>';
        }

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