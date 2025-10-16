-- this one checks which route has the most flights overall  
-- it shows the departure and arrival airports and counts how many flights go between them  
-- it also calculates the average fee applied on that route depending on whether it is domestic or international  
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


-- here we are just checking all the bookings that belong to a specific user  
-- it lists when the booking was made, which seat was chosen, the flight it belongs to, and what class that seat is in  
-- this helps see what a user has booked and if the information matches correctly across the tables  
SELECT b.booking_id, b.booking_date, t.seat_id, f.flight_id, s.class
FROM Bookings b
JOIN Tickets t ON b.seat_id = t.seat_id
JOIN Flights f ON b.flight_id = f.flight_id
JOIN SeatAssignment s ON t.seat_id = s.seat_id
WHERE b.user_id = 1;


-- this one shows the seat identifiers and their prices for seats that are marked as economy  
-- it helps confirm that the seats are correctly linked to their price category  
-- basically, it’s a small check to see which seats belong to that class and what they cost  
SELECT e.seat_id, e.price
FROM Economy e
JOIN SeatAssignment s ON e.seat_id = s.seat_id
WHERE s.class = 'Economy';


-- this query calculates how much money each flight makes in total  
-- it takes every booked seat, looks at its class price, and adds the right type of fee based on the route  
-- in the end, it sums everything so we can see which flight is earning the most overall  
SELECT F.flight_id,
       SUM(CP.price + CASE WHEN Adep.country = Aarr.country THEN Fe.dom_fee ELSE Fe.int_fee END) AS revenue
FROM Bookings B
JOIN SeatAssignment SA ON SA.flight_id = B.flight_id AND SA.seat_id = B.seat_id
JOIN classPrice CP     ON CP.class = SA.class
JOIN Flights F         ON F.flight_id = B.flight_id
JOIN Airport Adep      ON F.Dairport_id = Adep.airport_id
JOIN Airport Aarr      ON F.Aairport_id = Aarr.airport_id
JOIN Fee Fe            ON Adep.country = Fe.country
GROUP BY F.flight_id
ORDER BY revenue DESC;


-- this one checks which flights are already full  
-- it compares how many seats have been booked with how many seats the plane actually has  
-- if the number of booked seats is the same or higher than the seat capacity, it means the flight is full  
SELECT F.flight_id
FROM Flights F
JOIN Commercial C ON C.plane_id = F.plane_id
LEFT JOIN Bookings B ON B.flight_id = F.flight_id
GROUP BY F.flight_id, C.seats
HAVING COUNT(B.seat_id) >= C.seats;



-- this shows all the airports that are located in the country with the highest domestic flight fee  
-- it helps find where flying inside the same country is the most expensive based on the stored fees  
-- the subquery checks which domestic fee value is the maximum and then compares it with each airport’s country  
SELECT A.*
FROM Airport A
JOIN Fee Fe ON Fe.country = A.country
WHERE Fe.dom_fee = (SELECT MAX(dom_fee) FROM Fee);


-- here we calculate how many seats each flight has per class  
-- we also see how many are booked and how many are still available for that class  
-- this helps visualize which flights or classes are closer to being full  
SELECT
  F.flight_id,
  SA.class,
  COUNT(DISTINCT T.seat_id) AS seats_total_in_class,
  COUNT(DISTINCT B.seat_id) AS seats_booked_in_class,
  (COUNT(DISTINCT T.seat_id) - COUNT(DISTINCT B.seat_id)) AS seats_available_in_class
FROM Flights F
JOIN Tickets T            ON T.flight_id = F.flight_id
JOIN SeatAssignment SA    ON SA.flight_id = T.flight_id AND SA.seat_id = T.seat_id
LEFT JOIN Bookings B      ON B.flight_id = T.flight_id AND B.seat_id = T.seat_id
GROUP BY F.flight_id, SA.class
ORDER BY F.flight_id, SA.class;


-- this one counts how many bookings each user has made so far  
-- it includes users that have not booked anything yet thanks to the left join  
-- it’s a simple way to see who is the most active user and who hasn’t booked yet  
SELECT U.user_id,
       COALESCE(COUNT(B.booking_id), 0) AS bookings_count
FROM Users U
LEFT JOIN Bookings B ON B.user_id = U.user_id
GROUP BY U.user_id
ORDER BY bookings_count DESC, U.user_id;


-- this calculates how much money was made per flight including both base price and route fees  
-- it also adds a label that tells if the route was domestic or international  
-- it helps get a clear view of total earnings for every route in one place  
SELECT 
    F.flight_id,
    Adep.iata AS departure_iata,
    Aarr.iata AS arrival_iata,
    CASE 
        WHEN Adep.country = Aarr.country THEN 'domestic'
        ELSE 'international'
    END AS route_type,
    SUM(CP.price 
        + CASE 
            WHEN Adep.country = Aarr.country THEN Fe.dom_fee 
            ELSE Fe.int_fee 
          END
    ) AS total_cost
FROM Flights F
JOIN Airport Adep       ON Adep.airport_id = F.Dairport_id
JOIN Airport Aarr       ON Aarr.airport_id = F.Aairport_id
JOIN Fee Fe             ON Fe.country = Adep.country
JOIN Bookings B         ON B.flight_id = F.flight_id
JOIN SeatAssignment SA  ON SA.flight_id = B.flight_id AND SA.seat_id = B.seat_id
JOIN classPrice CP      ON CP.class = SA.class
GROUP BY 
    F.flight_id, Adep.iata, Aarr.iata, route_type
ORDER BY total_cost DESC;


-- this one just lists all the flights that have no bookings yet  
-- it is a quick check to see if there are flights that might be new or ignored by users  
-- it helps spot empty flights that you might want to test or fill with data later  
SELECT F.flight_id
FROM Flights F
LEFT JOIN Bookings B ON B.flight_id = F.flight_id
WHERE B.flight_id IS NULL
ORDER BY F.flight_id;


-- this shows how many booked seats there are in each class for every flight  
-- it separates the classes and counts how many passengers are sitting in each one  
-- this helps compare which class is used the most on different flights  
SELECT
  F.flight_id,
  SUM(CASE WHEN SA.class = 'Economy'    THEN 1 ELSE 0 END) AS booked_economy,
  SUM(CASE WHEN SA.class = 'Business'   THEN 1 ELSE 0 END) AS booked_business,
  SUM(CASE WHEN SA.class = 'FirstClass' THEN 1 ELSE 0 END) AS booked_first
FROM Flights F
LEFT JOIN Bookings B        ON B.flight_id = F.flight_id
LEFT JOIN SeatAssignment SA ON SA.flight_id = B.flight_id AND SA.seat_id = B.seat_id
GROUP BY F.flight_id
ORDER BY F.flight_id;


-- here we find the top five airports with the highest total traffic  
-- it adds together how many flights depart and arrive at each airport  
-- it is useful to identify which airports are the busiest in the dataset  
WITH dep AS (
  SELECT F.Dairport_id AS airport_id, COUNT(*) AS dep_count
  FROM Flights F GROUP BY F.Dairport_id
),
arr AS (
  SELECT F.Aairport_id AS airport_id, COUNT(*) AS arr_count
  FROM Flights F GROUP BY F.Aairport_id
)
SELECT
  A.iata,
  COALESCE(dep.dep_count,0) + COALESCE(arr.arr_count,0) AS total_traffic,
  COALESCE(dep.dep_count,0) AS departures,
  COALESCE(arr.arr_count,0) AS arrivals
FROM Airport A
LEFT JOIN dep ON dep.airport_id = A.airport_id
LEFT JOIN arr ON arr.airport_id = A.airport_id
ORDER BY total_traffic DESC, A.iata
LIMIT 5;
