-- Insert rows into table 'Plane'
INSERT ignore INTO Plane (plane_id)  -- columns to insert data into

VALUES
    (1),  -- first row: values for the columns in the list above
    (2), -- second row: values for the columns in the list above
    (3),
    (4),
    (5),
    (6),
    (7),
    (8),
    (9),
    (10);

-- Insert rows into table 'Fee'
insert ignore into Fee(country, dom_fee, int_fee) VALUES
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

insert ignore into Airport(airport_id, iata, country) VALUES
(1, 'JFK', 'USA'),
(2, 'LAX', 'USA'),
(3, 'YYZ', 'CANADA'),
(4, 'YVR', 'CANADA'),
(5, 'MEX', 'MEXICO'),
(6, 'GRU', 'BRAZIL'),
(7, 'EZE', 'ARGENTINA'),
(8, 'LHR', 'UK'),
(9, 'CDG', 'FRANCE'),
(10, 'FRA', 'GERMANY'),
(11, 'FCO', 'ITALY'),
(12, 'MAD', 'SPAIN');

insert ignore into Users(user_id, pwd, privilege) VALUES
(1, "pippo", 1), -- admin
(2, "coca", 0), -- customer
(3, "ina", 0); -- customer

insert ignore into Commercial(plane_id, seats) VALUES
(1, 150),
(2, 200),
(3, 180),
(6, 220),
(7, 160),
(8, 210),
(9, 190),
(10, 230);

insert ignore into Cargo(plane_id) VALUES
(4),
(5);

insert ignore into Flights(flight_id, Aairport_id, Dairport_id, plane_id, plane_status) VALUES
(1, 1, 2, 1, 'on time'),
(2, 3, 4, 2, 'delayed'),
(3, 5, 6, 3, 'on time'),
(4, 7, 8, 4, 'cancelled'),
(5, 9, 10, 5, 'on time'),
(6, 11, 12, 6, 'delayed'),
(7, 2, 1, 7, 'on time'),
(8, 4, 3, 8, 'on time'),
(9, 6, 5, 9, 'delayed'),
(10, 8, 7, 10, 'on time');
insert ignore into Tickets(seat_id,flight_id) VALUES
(1,1),
(2,1),
(3,2),
(4,2),
(5,3),
(6,3),
(7,6),
(8,6),
(9,7),
(10,7);
insert ignore into Bookings(user_id, flight_id, seat_id) VALUES
(2, 1, 1),
(3, 1, 2),
(2, 3, 3);
insert ignore into SeatAssignment(seat_id,flight_id, class) VALUES
(1,1, "Economy"),
(3,1, "Business"),
(4,3, "FirstClass"),
(5,6, "FirstClass"),
(6,7, "Economy"),
(7,8, "Business"),
(8,9, "FirstClass"),
(9,10, "Economy");

 
insert ignore into Dom_flight(flight_id) VALUES
(1),
(2),
(7);
insert ignore into Int_flight(flight_id) VALUES
(4),
(5),
(6);

