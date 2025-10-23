

CREATE OR REPLACE VIEW View_SearchFlights AS
SELECT 
    F.flight_id,
    DA.iata AS dep_iata,
    DA.city AS dep_city,
    AA.iata AS arr_iata,
    AA.city AS arr_city,
    F.plane_status,
    F.plane_id
FROM Flights F
JOIN Airport DA ON F.Dairport_id = DA.airport_id
JOIN Airport AA ON F.Aairport_id = AA.airport_id;

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

-- Insert rows into table 'Users'
INSERT IGNORE INTO Users(user_id, pwd, privilege)
VALUES
(1, 'admin01', 1),
(2, 'admin02', 1),
(3, 'john123', 0),
(4, 'maria22', 0),
(5, 'liam77', 0);

-- === 2. HIERARCHY & ROLES ===

INSERT IGNORE INTO Admin (user_id, last_login) VALUES (1, NOW()), (2, NOW());
INSERT IGNORE INTO Customer (user_id) VALUES (3), (4), (5);


INSERT IGNORE INTO Commercial(plane_id, seats) VALUES
(1, 150), (2, 200), (3, 180), (5, 170), 
(6, 220), (7, 160), (8, 210), (9, 190), (10, 230);

INSERT IGNORE INTO Cargo(plane_id) VALUES (4);

-- === 3. FLIGHTS AND CLASSIFICATION ===

INSERT IGNORE INTO Flights(flight_id, Dairport_id, Aairport_id, plane_id, plane_status)
VALUES
(101, 1, 2, 1, 'on time'),      -- JFK -> LAX
(102, 3, 4, 2, 'delayed'),      -- YYZ -> YVR
(103, 5, 6, 3, 'on time'),      -- MEX -> GRU
(104, 7, 8, 4, 'cancelled'),    -- EZE -> LHR (Cargo)
(105, 9, 10, 5, 'on time'),     -- CDG -> FRA 
(106, 11, 12, 6, 'delayed'),    -- FCO -> MAD 
(107, 2, 1, 7, 'on time'),      -- LAX -> JFK
(108, 4, 3, 8, 'on time'),      -- YVR -> YYZ
(109, 6, 5, 9, 'delayed'),      -- GRU -> MEX
(110, 8, 7, 10, 'on time');     -- LHR -> EZE


INSERT IGNORE INTO Dom_flight(flight_id) VALUES (101), (102), (107), (108);
INSERT IGNORE INTO Int_flight(flight_id) VALUES (103), (104), (105), (106), (109), (110);



INSERT IGNORE INTO classPrice(class, price)
VALUES
('Economy', 50),
('Business', 150),
('FirstClass', 300);








INSERT IGNORE INTO Bookings(user_id, flight_id, seat_id)
VALUES
(3, 101, 1),    
(4, 101, 2);   

