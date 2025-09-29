drop table if exists Admin;
drop table if exists Customer;
drop table if exists Bookings;
drop table if exists Int_flight;
drop table if exists Dom_flight;
drop table if exists FirstClass;
drop table if exists Business;
drop table if exists Economy;
drop table if exists SeatAssignment;
drop table if exists Tickets;
drop table if exists Flights;
drop table if exists Users;
drop table if exists Airport;
drop table if exists Commercial;
drop table if exists Cargo;
drop table if exists Plane;
drop table if exists Fee;

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

CREATE TABLE Airport(
    airport_id INT PRIMARY KEY,
    iata CHAR(3) UNIQUE,
    country VARCHAR(20),
    FOREIGN KEY (country) REFERENCES Fee(country)
);

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

--Parent entity: Tickets
CREATE TABLE Tickets(
    seat_id INT PRIMARY KEY -- number of seat, from 0 to seats from Commercial 
    
);

CREATE TABLE SeatAssignment(
    seat_id INT PRIMARY KEY,
    flight_id INT NOT NULL,
    class ENUM('Economy','Business','FirstClass') NOT NULL,
    FOREIGN KEY (flight_id) REFERENCES Flights(flight_id)
);

--child entity: Economy
CREATE TABLE Economy(
    
    seat_id INT PRIMARY KEY,
    price INT,
    FOREIGN KEY (seat_id) REFERENCES SeatAssignment(seat_id)
    
);

--child entity: Business
CREATE TABLE Business(
   
    seat_id INT PRIMARY KEY,
    price INT,
    FOREIGN KEY (seat_id) REFERENCES SeatAssignment(seat_id)
   
    );

--child entity: FirstClass
CREATE TABLE FirstClass(
   
    seat_id INT PRIMARY KEY,
    price INT,
    FOREIGN KEY (seat_id) REFERENCES SeatAssignment(seat_id)
    );

-- Child entity: dom_flight (Domestic Flight)
CREATE TABLE Dom_flight (
    flight_id INT PRIMARY KEY,
    Aairport_id INT NOT NULL, -- arrival airport
    Dairport_id INT NOT NULL, -- deparature airport, useful both for search and to deteermine the amount to pay to book
    plane_id INT NOT NULL,
    plane_status VARCHAR(20) NOT NULL,
    FOREIGN KEY (plane_id) REFERENCES Plane(plane_id),
    FOREIGN KEY (Aairport_id) REFERENCES Airport(airport_id),
    FOREIGN KEY (Dairport_id) REFERENCES Airport(airport_id)
    
    
);

-- Child entity: int_flight (International Flight)
CREATE TABLE Int_flight (
    flight_id INT PRIMARY KEY,
    Aairport_id INT NOT NULL, -- arrival airport, from the airport we know the country and therefore find the fee amount as well
    Dairport_id INT NOT NULL, -- deparature airport, useful both for search and to deteermine the amount to pay to book
    plane_id INT NOT NULL,
    plane_status VARCHAR(20) NOT NULL,
    FOREIGN KEY (plane_id) REFERENCES Plane(plane_id),
    FOREIGN KEY (Aairport_id) REFERENCES Airport(airport_id),
    FOREIGN KEY (Dairport_id) REFERENCES Airport(airport_id)
    
    );

CREATE TABLE Bookings (
    booking_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    flight_id INT NOT NULL,
    seat_id INT NOT NULL,
    booking_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES Users(user_id),
    FOREIGN KEY (flight_id) REFERENCES Flights(flight_id),
    FOREIGN KEY (seat_id) REFERENCES Tickets(seat_id)
);

--child entity from User: customer
CREATE TABLE Customer(
    USER_ID INT PRIMARY KEY,
    FOREIGN KEY (USER_ID) REFERENCES Users(USER_ID)

);

--child entity from User: admin, can modify flights
CREATE TABLE Admin (
    USER_ID INT PRIMARY KEY ,
    last_login TIMESTAMP, -- for security checks on the last access of one admin
    FOREIGN KEY (USER_ID) REFERENCES Users(USER_ID)

);





