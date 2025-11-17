<?php

$servername = "127.0.0.1";
$username = "gbrugnara";
$password = "KeRjnLwqj+rTTG3E";
$dbname = "db_gbrugnara";
## 4JjJ0zWOOo76
$conn = new mysqli($servername, $username, $password, $dbname, null, "/run/mysql/mysql.sock");

if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database connection failed.']);
    exit();
}

$search_term = trim($_GET['term'] ?? '');

$locations = [];


if (!empty($search_term)) {
    $like_term = '%' . $search_term . '%';

    
    $sql = "
        (SELECT dep_city AS city, dep_iata AS iata FROM View_SearchFlights 
         WHERE UPPER(dep_city) LIKE UPPER(?) OR UPPER(dep_iata) LIKE UPPER(?))
        UNION 
        (SELECT arr_city AS city, arr_iata AS iata FROM View_SearchFlights 
         WHERE UPPER(arr_city) LIKE UPPER(?) OR UPPER(arr_iata) LIKE UPPER(?))
    ";
    
    $stmt = $conn->prepare($sql);
    
    if ($stmt === false) {
        error_log("SQL Prepare failed: " . $conn->error);
        header('Content-Type: application/json');
        echo json_encode([]); 
        exit();
    }

    $stmt->bind_param("ssss", $like_term, $like_term, $like_term, $like_term);
    $stmt->execute();
    $result = $stmt->get_result();

    $unique_locations = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            
            $city = trim($row['city']);
            $iata = trim($row['iata']);
            
            
            if (!empty($city) && !empty($iata)) {
                 $unique_locations["$city ($iata)"] = true;
            }
            
            
            if (!empty($city) && !is_numeric($city)) {
                $unique_locations[$city] = true;
            }
            
            
            if (!empty($iata) && strlen($iata) === 3) {
                $unique_locations[$iata] = true;
            }
        }
    }
    
    
    $locations = array_keys($unique_locations);
    $stmt->close();
}

$conn->close();

header('Content-Type: application/json');
echo json_encode($locations);
?>