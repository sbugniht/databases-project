drop table if exists Admin;
drop table if exists Customer;
drop table if exists Bookings;
drop table if exists Int_flight;
drop table if exists Dom_flight;
drop table if exists SeatAssignment;
drop table if exists Tickets;
drop table if exists Flights;
drop table if exists Users;
drop table if exists Airport;
drop table if exists Commercial;
drop table if exists Cargo;
drop table if exists Plane;
drop table if exists Fee;
drop view if exists View_Price;
drop table if exists classPrice;

-- used to save the fees by country and calculate the final ticket cost
CREATE TABLE  Fee(
    country VARCHAR(20) PRIMARY KEY,
    dom_fee INT,
    int_fee INT

);

--Parent entity: Plane
CREATE TABLE Plane(
    plane_id INT PRIMARY KEY
    
    
);

-- child entity: Cargo (plane)
CREATE TABLE Cargo(
    plane_id INT PRIMARY KEY,
    FOREIGN KEY (plane_id) REFERENCES Plane(plane_id)
);

--child entity: Commercial
CREATE TABLE Commercial(
    plane_id INT PRIMARY KEY,
    seats INT, -- total number of bookable seats of the specific airplane   
    FOREIGN KEY (plane_id) REFERENCES Plane(plane_id)

);

-- REPLACE your Airport table with this definition
DROP TABLE IF EXISTS Airport;

CREATE TABLE Airport(
    airport_id INT PRIMARY KEY,
    iata CHAR(3) UNIQUE NOT NULL,
    city VARCHAR(50) NOT NULL,                  -- NEW: lets users search by city name
    country VARCHAR(20) NOT NULL,
    FOREIGN KEY (country) REFERENCES Fee(country)
);

CREATE INDEX idx_airport_city ON Airport(city);




--Parent entity: Users
CREATE TABLE Users(
    USER_ID INT PRIMARY KEY,
    pwd VARCHAR(8), -- password
    privilege INT -- allows to differentiate between admin pwd and customer
    
);

--Parent entity: flights
CREATE TABLE Flights (
    flight_id INT PRIMARY KEY,
    Aairport_id INT NOT NULL, -- arrival airport
    Dairport_id INT NOT NULL, -- deparature airport, useful both for search and to deteermine the amount to pay to book
    plane_id INT NOT NULL,
    plane_status VARCHAR(20) NOT NULL,
    FOREIGN KEY (plane_id) REFERENCES Plane(plane_id),
    FOREIGN KEY (Aairport_id) REFERENCES Airport(airport_id),
    FOREIGN KEY (Dairport_id) REFERENCES Airport(airport_id)
);


CREATE TABLE Tickets(
    seat_id INT NOT NULL, -- number of seat, from 0 to seats from Commercial 
    flight_id INT NOT NULL, 
    PRIMARY KEY (seat_id, flight_id),
    FOREIGN KEY (flight_id) REFERENCES Flights(flight_id)
);

CREATE TABLE SeatAssignment(
    seat_id INT NOT NULL,
    flight_id INT NOT NULL,
    class ENUM('Economy','Business','FirstClass') NOT NULL,
    PRIMARY KEY (seat_id, flight_id),
    FOREIGN KEY (seat_id,flight_id) REFERENCES Tickets(seat_id,flight_id)

);
create table classPrice(
    class ENUM('Economy','Business','FirstClass') PRIMARY KEY,
    PRICE int NOT NULL
);
insert into classPrice values ('Economy', 150),('Business', 300),('FirstClass', 500);


-- Child entity: dom_flight (Domestic Flight)
CREATE TABLE Dom_flight (
    flight_id INT PRIMARY KEY,
    FOREIGN KEY (flight_id) REFERENCES Flights(flight_id)
);

-- Child entity: int_flight (International Flight)
CREATE TABLE Int_flight (
    flight_id INT PRIMARY KEY,
    FOREIGN KEY (flight_id) REFERENCES Flights(flight_id)
    );

CREATE TABLE Customer(
    USER_ID INT PRIMARY KEY,
    FOREIGN KEY (USER_ID) REFERENCES Users(USER_ID)

);

CREATE TABLE Bookings (
    booking_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    flight_id INT NOT NULL,
    seat_id INT NOT NULL,
    booking_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES Customer(user_id),
    FOREIGN KEY (flight_id,seat_id) REFERENCES Flights(flight_id,seat_id),
    
);




--child entity from User: admin, can modify flights
CREATE TABLE Admin (
    USER_ID INT PRIMARY KEY ,
    last_login TIMESTAMP, -- for security checks on the last access of one admin
    FOREIGN KEY (USER_ID) REFERENCES Users(USER_ID)

);

-- Convenience view to search flights by text (city or IATA) without touching numeric IDs
DROP VIEW IF EXISTS View_SearchFlights;
CREATE VIEW View_SearchFlights AS
SELECT
  F.flight_id,
  Adep.airport_id AS dep_airport_id,
  Adep.iata       AS dep_iata,
  Adep.city       AS dep_city,
  Adep.country    AS dep_country,
  Aarr.airport_id AS arr_airport_id,
  Aarr.iata       AS arr_iata,
  Aarr.city       AS arr_city,
  Aarr.country    AS arr_country,
  F.plane_id,
  F.plane_status
FROM Flights F
JOIN Airport Adep ON F.Dairport_id = Adep.airport_id
JOIN Airport Aarr ON F.Aairport_id = Aarr.airport_id;
