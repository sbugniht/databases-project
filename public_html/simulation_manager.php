<?php
// simulation_manager.php

// ==========================================
// ⚙️ SIMULATION CONFIGURATION
// ==========================================

// General Settings
const SIM_DAYS_LOOKAHEAD    = 7;    // Generate flights for the next X days
const SIM_MIN_FLIGHTS_DAY   = 5;    // If a day has fewer than this, generate more
const SIM_NEW_FLIGHTS_MIN   = 3;    // Min new flights to create per run per day
const SIM_NEW_FLIGHTS_MAX   = 8;    // Max new flights to create per run per day

// Flight Timing Settings
const SIM_DURATION_MIN      = 45;   // Minimum flight duration (minutes)
const SIM_DURATION_MAX      = 720;  // Maximum flight duration (12 hours)
const SIM_START_HOUR        = 6;    // Earliest departure hour (0-23)
const SIM_END_HOUR          = 22;   // Latest departure hour (0-23)

// Booking Simulation Settings
const SIM_BOOKING_CHANCE    = 30;   // % chance that a flight gets new bookings today
const SIM_MAX_SEATS_BOOK    = 6;    // Max seats to book in a single batch
const SIM_BOT_USER_ID       = 1000; // The User ID for automated bookings

// ==========================================

function run_simulation($conn) {
    $today = date('Y-m-d');
    $last_run_file = __DIR__ . '/last_sim_run.txt';
    
    // Check if simulation already ran today
    $last_run = file_exists($last_run_file) ? file_get_contents($last_run_file) : '';
    if ($last_run === $today) {
        return;
    }

    // --- 1. CLEANUP: Remove past flights ---
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    
    // Using a loop to ensure dependencies are deleted first
    $sql_find_old = "SELECT flight_id FROM Flights WHERE flight_date <= ?";
    $stmt = $conn->prepare($sql_find_old);
    $stmt->bind_param("s", $yesterday);
    $stmt->execute();
    $res = $stmt->get_result();
    
    while ($row = $res->fetch_assoc()) {
        $fid = $row['flight_id'];
        $conn->query("DELETE FROM Bookings WHERE flight_id = $fid");
        $conn->query("DELETE FROM SeatAssignment WHERE flight_id = $fid");
        $conn->query("DELETE FROM Tickets WHERE flight_id = $fid");
        $conn->query("DELETE FROM Dom_flight WHERE flight_id = $fid");
        $conn->query("DELETE FROM Int_flight WHERE flight_id = $fid");
        $conn->query("DELETE FROM Flights WHERE flight_id = $fid");
    }
    $stmt->close();


    // --- 2. GENERATION: Create future flights ---
    $airports = [];
    $res_air = $conn->query("SELECT airport_id FROM Airport");
    while($row = $res_air->fetch_assoc()) $airports[] = $row['airport_id'];

    $planes = [];
    $res_pln = $conn->query("SELECT plane_id FROM Commercial");
    while($row = $res_pln->fetch_assoc()) $planes[] = $row['plane_id'];

    if (count($airports) >= 2 && count($planes) > 0) {
        for ($i = 0; $i < SIM_DAYS_LOOKAHEAD; $i++) {
            $target_date = date('Y-m-d', strtotime("+$i days"));

            // Check density
            $check = $conn->query("SELECT COUNT(*) as c FROM Flights WHERE flight_date = '$target_date'");
            $row = $check->fetch_assoc();
            
            if ($row['c'] < SIM_MIN_FLIGHTS_DAY) {
                $flights_to_create = rand(SIM_NEW_FLIGHTS_MIN, SIM_NEW_FLIGHTS_MAX);
                
                for ($j = 0; $j < $flights_to_create; $j++) {
                    // Select random Route
                    $dep_idx = array_rand($airports);
                    $dep_id = $airports[$dep_idx];
                    do {
                        $arr_idx = array_rand($airports);
                        $arr_id = $airports[$arr_idx];
                    } while ($dep_id == $arr_id);

                    $plane_id = $planes[array_rand($planes)];
                    $new_flight_id = rand(10000, 99999); 
                    
                    // Generate Random Time & Duration
                    $rand_hour = rand(SIM_START_HOUR, SIM_END_HOUR);
                    $rand_min = rand(0, 59);
                    $dep_time = sprintf("%02d:%02d:00", $rand_hour, $rand_min);
                    
                    $duration = rand(SIM_DURATION_MIN, SIM_DURATION_MAX);

                    // Insert Flight
                    $stmt_ins = $conn->prepare("INSERT INTO Flights (flight_id, Dairport_id, Aairport_id, plane_id, plane_status, flight_date, dep_time, duration_minutes) VALUES (?, ?, ?, ?, 'On Time', ?, ?, ?)");
                    $stmt_ins->bind_param("iiiissi", $new_flight_id, $dep_id, $arr_id, $plane_id, $target_date, $dep_time, $duration);
                    
                    if ($stmt_ins->execute()) {
                        populate_tickets($conn, $new_flight_id, $plane_id);
                    }
                    $stmt_ins->close();
                }
            }
        }
    }

    // --- 3. BOOKING: Simulate random seats taken ---
    simulate_random_bookings($conn);

    // Log success
    file_put_contents($last_run_file, $today);
}

function populate_tickets($conn, $flight_id, $plane_id) {
    $res = $conn->query("SELECT seats FROM Commercial WHERE plane_id = $plane_id");
    if ($res->num_rows == 0) return;
    $seats = $res->fetch_assoc()['seats'];

    $sql_ticket = "INSERT INTO Tickets (seat_id, flight_id) VALUES (?, ?)";
    $stmt_t = $conn->prepare($sql_ticket);
    $sql_assign = "INSERT INTO SeatAssignment (seat_id, flight_id, class) VALUES (?, ?, ?)";
    $stmt_a = $conn->prepare($sql_assign);

    // Limits for classes
    $first_limit = max(1, round($seats * 0.05));
    $bus_limit = round($seats * 0.20);

    for ($s = 1; $s <= $seats; $s++) {
        $stmt_t->bind_param("ii", $s, $flight_id);
        $stmt_t->execute();

        $class = 'Economy';
        if ($s <= $first_limit) $class = 'FirstClass';
        else if ($s <= $bus_limit) $class = 'Business';

        $stmt_a->bind_param("iis", $s, $flight_id, $class);
        $stmt_a->execute();
    }
}

function simulate_random_bookings($conn) {
    // Get all future flights
    $res_f = $conn->query("SELECT flight_id FROM Flights WHERE flight_date >= CURDATE()");
    
    while ($f = $res_f->fetch_assoc()) {
        // Roll dice based on percentage config
        if (rand(0, 100) < SIM_BOOKING_CHANCE) {
            $fid = $f['flight_id'];
            
            // Find currently free seats
            $sql_free = "
                SELECT T.seat_id 
                FROM Tickets T 
                LEFT JOIN Bookings B ON T.flight_id = B.flight_id AND T.seat_id = B.seat_id
                WHERE T.flight_id = $fid AND B.booking_id IS NULL
            ";
            $res_free = $conn->query($sql_free);
            $free_seats = [];
            while ($r = $res_free->fetch_assoc()) $free_seats[] = $r['seat_id'];
            
            if (count($free_seats) > 0) {
                $to_book = rand(1, min(SIM_MAX_SEATS_BOOK, count($free_seats)));
                shuffle($free_seats); // Randomize position
                
                $stmt_book = $conn->prepare("INSERT INTO Bookings (user_id, flight_id, seat_id) VALUES (?, ?, ?)");
                for ($i = 0; $i < $to_book; $i++) {
                    $stmt_book->bind_param("iii", SIM_BOT_USER_ID, $fid, $free_seats[$i]);
                    $stmt_book->execute();
                }
                $stmt_book->close();
            }
        }
    }
}
?>