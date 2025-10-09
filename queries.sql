SELECT plane_id
FROM Plane
where   plane_id NOT IN (SELECT plane_id FROM Cargo); 

SELECT b.booking_id, b.booking_date, t.seat_id, f.flight_id, s.class
FROM Bookings b
JOIN Tickets t ON b.seat_id = t.seat_id
JOIN Flights f ON b.flight_id = f.flight_id
JOIN SeatAssignment s ON t.seat_id = s.seat_id
WHERE b.user_id = ?;

SELECT b.booking_id, b.booking_date, f.flight_id, a1.iata AS from_airport, a2.iata AS to_airport, s.class
FROM Bookings b
JOIN Flights f ON b.flight_id = f.flight_id
JOIN Airport a1 ON f.Dairport_id = a1.airport_id
JOIN Airport a2 ON f.Aairport_id = a2.airport_id
JOIN SeatAssignment s ON b.seat_id = s.seat_id
WHERE b.user_id = ?;


SELECT s.seat_id, s.class
FROM SeatAssignment s
LEFT JOIN Bookings b ON s.seat_id = b.seat_id AND b.flight_id = ?
WHERE b.seat_id IS NULL AND s.flight_id = ?;

SELECT f.flight_id, a1.country AS departure_country, a2.country AS arrival_country, fee.dom_fee, fee.int_fee
FROM Int_flight f
JOIN Airport a1 ON f.Dairport_id = a1.airport_id
JOIN Airport a2 ON f.Aairport_id = a2.airport_id
JOIN Fee fee ON a2.country = fee.country;

SELECT c.USER_ID, COUNT(b.booking_id) AS num_bookings
FROM Customer c
JOIN Bookings b ON c.USER_ID = b.user_id
GROUP BY c.USER_ID
HAVING num_bookings > ?;

SELECT f.flight_id, f.plane_id, f.plane_status, s.class, e.price AS economy_price, b.price AS business_price, fc.price AS firstclass_price
FROM Flights f
JOIN SeatAssignment s ON f.flight_id = s.flight_id
LEFT JOIN Economy e ON s.seat_id = e.seat_id
LEFT JOIN Business b ON s.seat_id = b.seat_id
LEFT JOIN FirstClass fc ON s.seat_id = fc.seat_id
WHERE f.Dairport_id = 1 AND f.Aairport_id = 2 AND f.flight_id NOT IN (
    SELECT flight_id FROM Bookings WHERE booking_date = '2025-10-09'
)
ORDER BY f.flight_id, s.class;

    