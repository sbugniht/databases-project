
    -- Find the airport with the highest prices of domestic flights

    SELECT plane_id
    FROM Flights AS F
    JOIN Fee AS Fe ON F.Dairport_id = Fe.country
    WHERE Fe.dom_fee = (
        SELECT MAX(dom_fee)
        FROM Fee
    )


-- Find the airports with most amount of flights between them, the number of flights, and average price
SELECT A1.iata AS departure_iata, A2.iata AS arrival_iata, COUNT(*) AS num_flights,
       AVG(
           CASE 
               WHEN A1.country = A2.country THEN Fe.dom_fee
               ELSE Fe.int_fee
           END
       ) AS avg_price
FROM Flights F
JOIN Airport A1 ON F.Dairport_id = A1.airport_id
JOIN Airport A2 ON F.Aairport_id = A2.airport_id
JOIN Fee Fe ON A1.country = Fe.country
GROUP BY A1.iata, A2.iata
ORDER BY num_flights DESC
LIMIT 1;


-- View that shows the total cost for each user including base price and fees
-- The view is named 'view_costo'
CREATE OR REPLACE VIEW view_costo AS
SELECT U.user_id, 
       CASE 
           WHEN SA.class = 'Economy' THEN E.price
           WHEN SA.class = 'Business' THEN Bz.price
           WHEN SA.class = 'FirstClass' THEN Fc.price
           ELSE 0
       END AS base_price,
       A1.country AS departure_country,
       A2.country AS arrival_country,
       CASE 
           WHEN A1.country = A2.country THEN Fe.dom_fee
           ELSE Fe.int_fee
       END AS feeamount,
       (CASE 
           WHEN SA.class = 'Economy' THEN E.price
           WHEN SA.class = 'Business' THEN Bz.price
           WHEN SA.class = 'FirstClass' THEN Fc.price
           ELSE 0
         END +
        CASE 
           WHEN A1.country = A2.country THEN Fe.dom_fee
           ELSE Fe.int_fee
        END) AS total_price
FROM Users U
LEFT JOIN SeatAssignment SA ON U.user_id = U.user_id
LEFT JOIN Economy E ON SA.seat_id = E.seat_id
LEFT JOIN Business Bz ON SA.seat_id = Bz.seat_id
LEFT JOIN FirstClass Fc ON SA.seat_id = Fc.seat_id
LEFT JOIN Flights F ON SA.flight_id = F.flight_id
LEFT JOIN Airport A1 ON F.Dairport_id = A1.airport_id
LEFT JOIN Airport A2 ON F.Aairport_id = A2.airport_id
LEFT JOIN Fee Fe ON A1.country = Fe.country;

SELECT b.booking_id, b.booking_date, t.seat_id, f.flight_id, s.class
FROM Bookings b
JOIN Tickets t ON b.seat_id = t.seat_id
JOIN Flights f ON b.flight_id = f.flight_id
JOIN SeatAssignment s ON t.seat_id = s.seat_id
WHERE b.user_id = 1;


SELECT e.seat_id, e.price
FROM Economy e
JOIN SeatAssignment s ON e.seat_id = s.seat_id
WHERE s.class = 'Economy';