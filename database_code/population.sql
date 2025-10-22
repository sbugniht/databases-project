-- === 1. BASE ENTITIES (PLANE, FEE, AIRPORT, USERS) ===

-- Insert rows into table 'Plane'
INSERT IGNORE INTO Plane (plane_id)
VALUES (1), (2), (3), (4), (5), (6), (7), (8), (9), (10);

-- Insert rows into table 'Fee'
INSERT IGNORE INTO Fee(country, dom_fee, int_fee)
VALUES
('USA', 100, 300),
('CANADA', 80, 250),
('MEXICO', 70, 200),
('BRAZIL', 90, 220),
('ARGENTINA', 85, 210),
('UK', 120, 350),
('FRANCE', 110, 330),
('GERMANY', 115, 340),
('ITALY', 105, 320),
('SPAIN', 95, 310);

-- Insert rows into table 'Airport'
INSERT IGNORE INTO Airport(airport_id, iata, city, country)
VALUES
(1,  'JFK', 'New York',        'USA'),
(2,  'LAX', 'Los Angeles',     'USA'),
(3,  'YYZ', 'Toronto',         'CANADA'),
(4,  'YVR', 'Vancouver',       'CANADA'),
(5,  'MEX', 'Mexico City',     'MEXICO'),
(6,  'GRU', 'São Paulo',       'BRAZIL'),
(7,  'EZE', 'Buenos Aires',    'ARGENTINA'),
(8,  'LHR', 'London',          'UK'),
(9,  'CDG', 'Paris',           'FRANCE'),
(10, 'FRA', 'Frankfurt',       'GERMANY'),
(11, 'FCO', 'Rome',            'ITALY'),
(12, 'MAD', 'Madrid',          'SPAIN');

-- === 2. USER HIERARCHY ===

-- Insert rows into table 'Users'
INSERT IGNORE INTO Users(user_id, pwd, privilege)
VALUES
(1, 'admin01', 1),   -- Admin
(2, 'admin02', 1),   -- Admin
(3, 'john123', 0),   -- Customer
(4, 'maria22', 0),   -- Customer
(5, 'liam77', 0);    -- Customer

-- Insert rows into table 'Admin'
INSERT IGNORE INTO Admin (user_id, last_login)
VALUES
(1, NOW()),
(2, NOW());

-- Insert rows into table 'Customer'
INSERT IGNORE INTO Customer (user_id)
VALUES
(3),
(4),
(5);

-- === 3. PLANE HIERARCHY ===

-- Insert rows into table 'Commercial'
INSERT IGNORE INTO Commercial(plane_id, seats)
VALUES
(1, 150), -- JFK-LAX
(2, 200), -- YYZ-YVR
(3, 180), -- MEX-GRU
(6, 220), -- FCO-MAD
(7, 160), -- LAX-JFK
(8, 210), -- YVR-YYZ
(9, 190), -- GRU-MEX
(10, 230); -- LHR-EZE (International)

-- Insert rows into table 'Cargo'
INSERT IGNORE INTO Cargo(plane_id)
VALUES
(4), -- EZE-LHR
(5); -- CDG-FRA

-- === 4. FLIGHTS, CLASSIFICATION, AND TICKET INVENTORY ===

-- Insert rows into table 'Flights'
INSERT IGNORE INTO Flights(flight_id, Aairport_id, Dairport_id, plane_id, plane_status)
VALUES
(101, 2, 1, 1, 'on time'),      -- LAX -> JFK (Domestic)
(102, 4, 3, 2, 'delayed'),      -- YVR -> YYZ (Domestic)
(103, 6, 5, 3, 'on time'),      -- GRU -> MEX (International)
(104, 8, 7, 4, 'cancelled'),    -- LHR -> EZE (International, Cargo)
(105, 10, 9, 5, 'on time'),     -- FRA -> CDG (International, Cargo)
(106, 12, 11, 6, 'delayed'),    -- MAD -> FCO (International)
(107, 1, 2, 7, 'on time'),      -- JFK -> LAX (Domestic)
(108, 3, 4, 8, 'on time'),      -- YYZ -> YVR (Domestic)
(109, 5, 6, 9, 'delayed'),      -- MEX -> GRU (International)
(110, 7, 8, 10, 'on time');     -- EZE -> LHR (International)


-- Insert rows into table 'Dom_flight'
INSERT IGNORE INTO Dom_flight(flight_id)
VALUES
(101), -- LAX -> JFK (USA)
(102), -- YVR -> YYZ (CANADA)
(107), -- JFK -> LAX (USA)
(108); -- YYZ -> YVR (CANADA)

-- Insert rows into table 'Int_flight'
INSERT IGNORE INTO Int_flight(flight_id)
VALUES
(103), -- GRU -> MEX
(104), -- LHR -> EZE
(105), -- FRA -> CDG
(106), -- MAD -> FCO
(109), -- MEX -> GRU
(110); -- EZE -> LHR


-- Insert rows into table 'Tickets' (Inventory of available seats on commercial flights)
-- Flight 101 (Plane 1, 150 seats)
INSERT IGNORE INTO Tickets(seat_id, flight_id) VALUES (1, 101), (2, 101), (3, 101), (4, 101), (5, 101);
-- Flight 107 (Plane 7, 160 seats)
INSERT IGNORE INTO Tickets(seat_id, flight_id) VALUES (1, 107), (2, 107), (3, 107);
-- Flight 102 (Plane 2, 200 seats)
INSERT IGNORE INTO Tickets(seat_id, flight_id) VALUES (10, 102), (11, 102), (12, 102), (13, 102);
--Flight 108 (Plane 8, 210 seats)
INSERT IGNORE INTO Tickets(seat_id, flight_id) VALUES (20, 108), (21, 108);
-- Flight 103 (Plane 3, 180 seats)
INSERT IGNORE INTO Tickets(seat_id, flight_id) VALUES (30, 103), (31, 103), (32, 103);
-- Flight 109 (Plane 9, 190 seats)
INSERT IGNORE INTO Tickets(seat_id, flight_id) VALUES (40, 109), (41, 109);
-- Flight 106 (Plane 6, 220 seats)
INSERT IGNORE INTO Tickets(seat_id, flight_id) VALUES (50, 106), (51, 106);
-- Flight 110 (Plane 10, 230 seats)
INSERT IGNORE INTO Tickets(seat_id, flight_id) VALUES (60, 110), (61, 110);

-- === 5. BOOKINGS AND SEAT ASSIGNMENTS (TRANSACTIONS) ===

-- Insert rows into table 'SeatAssignment' (Defines class for sold/booked seats)
-- NOTE: (seat_id, flight_id) must exist in Tickets.
INSERT IGNORE INTO SeatAssignment(seat_id, flight_id, class)
VALUES
(1, 101, 'Economy'),     -- John (user 3) is assigned this seat
(3, 101, 'Business'),    -- Maria (user 4) is assigned this seat
(10, 102, 'FirstClass'), -- Liam (user 5) is assigned this seat
(2, 107, 'Economy');     -- Maria (user 4) is assigned this seat
(50,106,'FirstClass'),  -- unbooked seat
(51,106,'Business'),    -- unbooked seat
(60,110,'Economy'),     -- unbooked seat
(61,110,'FirstClass');  -- unbooked seat


-- Insert rows into table 'Bookings' 
-- NOTE: (user_id) must exist in Customer, and (flight_id, seat_id) must exist in Tickets.
INSERT IGNORE INTO Bookings(user_id, flight_id, seat_id)
VALUES
(3, 101, 1),    -- John booked Economy on 101
(4, 101, 3),    -- Maria booked Business on 101
(5, 102, 10),   -- Liam booked FirstClass on 102
(4, 107, 2);    -- Maria booked Economy on 107
(3,106,50);   -- John booked FirstClass on 106
(5,110,61);    -- Liam booked FirstClass on 110

