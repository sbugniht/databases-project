-- ===============================================
-- POPULATION SCRIPT - SKYBOOK AIRLINE DATABASE
-- Fixes issue with missing Tickets/Commercial planes for all flights.
-- ===============================================

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

-- Insert rows into table 'Users' (USER_ID 1 and 2 are Admins, 3, 4, 5 are Customers)
INSERT IGNORE INTO Users(user_id, pwd, privilege)
VALUES
(1, 'admin01', 1),
(2, 'admin02', 1),
(3, 'john123', 0),
(4, 'maria22', 0),
(5, 'liam77', 0);

-- === 2. USER HIERARCHY ===

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

-- Insert rows into table 'Commercial' (Plane IDs: 1, 2, 3, 5, 6, 7, 8, 9, 10)
-- NOTE: Plane ID 5 è ora Commerciale per risolvere il problema FRA-CDG
INSERT IGNORE INTO Commercial(plane_id, seats)
VALUES
(1, 150),
(2, 200),
(3, 180),
(5, 170), -- RISOLTO: Ora commerciale per il volo 105
(6, 220),
(7, 160),
(8, 210),
(9, 190),
(10, 230);

-- Insert rows into table 'Cargo' (Plane ID: 4)
INSERT IGNORE INTO Cargo(plane_id)
VALUES
(4);

-- === 4. FLIGHTS AND CLASSIFICATION ===

-- Insert rows into table 'Flights'
INSERT IGNORE INTO Flights(flight_id, Aairport_id, Dairport_id, plane_id, plane_status)
VALUES
(101, 2, 1, 1, 'on time'),      -- LAX -> JFK (Domestic)
(102, 4, 3, 2, 'delayed'),      -- YVR -> YYZ (Domestic)
(103, 6, 5, 3, 'on time'),      -- GRU -> MEX (International, Commercial)
(104, 8, 7, 4, 'cancelled'),    -- LHR -> EZE (International, Cargo)
(105, 9, 10, 5, 'on time'),     -- CDG -> FRA (International, Commercial) -- RISOLTO
(106, 11, 12, 6, 'delayed'),    -- FCO -> MAD (International, Commercial)
(107, 1, 2, 7, 'on time'),      -- JFK -> LAX (Domestic)
(108, 3, 4, 8, 'on time'),      -- YYZ -> YVR (Domestic)
(109, 5, 6, 9, 'delayed'),      -- MEX -> GRU (International)
(110, 7, 8, 10, 'on time');     -- EZE -> LHR (International)


-- Insert rows into table 'Dom_flight'
INSERT IGNORE INTO Dom_flight(flight_id)
VALUES
(101), (102), (107), (108);

-- Insert rows into table 'Int_flight'
INSERT IGNORE INTO Int_flight(flight_id)
VALUES
(103), (104), (105), (106), (109), (110);


-- === 5. TICKET INVENTORY (SeatAssignment & Tickets) ===

-- Crea l'inventario dei posti per voli commerciali e assegna loro una classe/prezzo
-- Volo 101 (LAX -> JFK)
INSERT IGNORE INTO Tickets(seat_id, flight_id) VALUES (1, 101), (2, 101), (3, 101);
INSERT IGNORE INTO SeatAssignment(seat_id, flight_id, class) VALUES (1, 101, 'Economy'), (2, 101, 'Business'), (3, 101, 'FirstClass');

-- Volo 102 (YVR -> YYZ)
INSERT IGNORE INTO Tickets(seat_id, flight_id) VALUES (10, 102), (11, 102);
INSERT IGNORE INTO SeatAssignment(seat_id, flight_id, class) VALUES (10, 102, 'Economy'), (11, 102, 'FirstClass');

-- Volo 103 (GRU -> MEX)
INSERT IGNORE INTO Tickets(seat_id, flight_id) VALUES (20, 103), (21, 103);
INSERT IGNORE INTO SeatAssignment(seat_id, flight_id, class) VALUES (20, 103, 'Economy'), (21, 103, 'Business');

-- Volo 105 (CDG -> FRA) - RISOLTO
INSERT IGNORE INTO Tickets(seat_id, flight_id) VALUES (61, 105), (62, 105);
INSERT IGNORE INTO SeatAssignment(seat_id, flight_id, class) VALUES (61, 105, 'Economy'), (62, 105, 'Business');

-- Volo 106 (FCO -> MAD) - RISOLTO
INSERT IGNORE INTO Tickets(seat_id, flight_id) VALUES (51, 106), (52, 106), (53, 106);
INSERT IGNORE INTO SeatAssignment(seat_id, flight_id, class) VALUES (51, 106, 'Economy'), (52, 106, 'Business'), (53, 106, 'FirstClass');


-- Volo 107 (JFK -> LAX)
INSERT IGNORE INTO Tickets(seat_id, flight_id) VALUES (40, 107), (41, 107);
INSERT IGNORE INTO SeatAssignment(seat_id, flight_id, class) VALUES (40, 107, 'Economy'), (41, 107, 'Economy');


-- === 6. BOOKINGS (Transazioni) ===

-- Prenotazioni esistenti (user_id deve esistere in Customer; (flight_id, seat_id) deve esistere in Tickets)
INSERT IGNORE INTO Bookings(user_id, flight_id, seat_id)
VALUES
(3, 101, 1),    -- John (3) booked Economy on 101
(4, 101, 2),    -- Maria (4) booked Business on 101
(5, 102, 11),   -- Liam (5) booked FirstClass on 102
(3, 107, 40);   -- John (3) booked Economy on 107