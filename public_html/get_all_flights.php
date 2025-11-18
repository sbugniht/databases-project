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

$sql = "SELECT flight_id, dep_iata, dep_city, arr_iata, arr_city, plane_id, plane_status 
        FROM View_SearchFlights";

if (!empty($filter_query)) {
    $sql .= " WHERE (
                UPPER(dep_city) LIKE UPPER(?) OR UPPER(dep_iata) LIKE UPPER(?) OR 
                UPPER(arr_city) LIKE UPPER(?) OR UPPER(arr_iata) LIKE UPPER(?)
            )";
}

$sql .= " ORDER BY flight_id DESC"; 

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
        $flights[] = $row;
    }
}

$stmt->close();
$conn->close();

header('Content-Type: application/json');
echo json_encode($flights);
?>