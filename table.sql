--Parent entity: Plane
CREATE TABLE Plane(
    plane_id INT,
    PRIMARY KEY (plane_id)
    
);
--child entity: Commercial
CREATE TABLE Commercial(
    plane_id INT,
    seats INT, -- total number of bookable seats of the specific airplane
    
    PRIMARY KEY (plane_id),
    FOREIGN KEY (plane_id) REFERENCES Plane(plane_id)

);
--Parent entity: Tickets
CREATE TABLE Tickets(
    seat_id INT, -- number of seat, from 0 to seats from Commercial 
    booked INT UNIQUE DEFAULT (NULL), -- if NULL not booked, otherwise contains the User_id of the customer who bought it
    CHECK ( booked = NULL OR booked = Users.USER_ID) 
);
--child entity: Economy
CREATE TABLE economy(
    price INT,
    seat_id INT,
    booked INT,
    FOREIGN KEY (seat_id) REFERENCES Tickets(seat_id),
    FOREIGN KEY (booked) REFERENCES Tickets(booked)
);

--child entity: Business
CREATE TABLE business(
    price INT,
    seat_id INT,
    booked INT,
    FOREIGN KEY (seat_id) REFERENCES Tickets(seat_id),
    FOREIGN KEY (booked) REFERENCES Tickets(booked)
);

--child entity: FirstClass
CREATE TABLE firstClass(
    price INT,
    seat_id INT,
    booked INT,
    FOREIGN KEY (seat_id) REFERENCES Tickets(seat_id),
    FOREIGN KEY (booked) REFERENCES Tickets(booked)
);

CREATE TABLE Cargo(
    plane_id INT,

    PRIMARY KEY (plane_id),
    FOREIGN KEY (plane_id) REFERENCES Plane(plane_id)
);

CREATE TABLE Airport(
    airport_id INT,
    iata CHAR UNIQUE,
    country VARCHAR(20),
    PRIMARY KEY (airport_id)
);

--Parent entity: Users
CREATE TABLE Users(
    USER_ID INT PRIMARY KEY,
    pwd INT,
    privilege INT, -- allows to differentiate between admin pwd and customer
    
);

--child entity: customer
CREATE TABLE customer(
    USER_ID INT PRIMARY KEY,
    pwd INT,
    privilege INT,
    bookedFlights ARRAY, -- one customer may book more than one flight
    FOREIGN KEY (USER_ID) REFERENCES Users(USER_ID),
    FOREIGN KEY (pwd) REFERENCES Users(pwd),
    FOREIGN KEY (privilege) REFERENCES Users(privilege)

);

--child entity: admin, can modify flights
CREATE TABLE Admin (
    USER_ID INT PRIMARY KEY,
    pwd INT,
    privilege INT,
    last_login TIMESTAMP, -- for security checks on the last access of one admin
    FOREIGN KEY (USER_ID) REFERENCES Users(USER_ID),
    FOREIGN KEY (pwd) REFERENCES Users(pwd),
    FOREIGN KEY (privilege) REFERENCES Users(privilege)


);

--Parent entity: flights
CREATE TABLE flights (
    flight_id INT PRIMARY KEY,
    Aairport_id INT NOT NULL, -- arrival airport
    Dairport_id INT NOT NULL, -- deparature airport, useful both for search and to deteermine the amount to pay to book
    plane_id INT NOT NULL,
    plane_status VARCHAR(20) NOT NULL,
    FOREIGN KEY (plane_id) REFERENCES plane(plane_id),
    FOREIGN KEY (Aairport_id) REFERENCES Airport(airport_id),
    FOREIGN KEY (Dairport_id) REFERENCES Airport(airport_id)
);


-- Child entity: dom_flight (Domestic Flight)
CREATE TABLE dom_flight (
    flight_id INT PRIMARY KEY,
    Aairport_id INT NOT NULL, -- arrival airport
    Dairport_id INT NOT NULL, -- deparature airport, useful both for search and to deteermine the amount to pay to book
    plane_id INT NOT NULL,
    plane_status VARCHAR(20) NOT NULL,
    dom_fee INT, -- airport contains the country that determines this amount
    FOREIGN KEY (plane_id) REFERENCES plane(plane_id),
    FOREIGN KEY (Aairport_id) REFERENCES Airport(airport_id),
    FOREIGN KEY (Dairport_id) REFERENCES Airport(airport_id),
    FOREIGN KEY (dom_fee) REFERENCES fee(dom_fee)
    
);

-- Child entity: int_flight (International Flight)
CREATE TABLE int_flight (
    fflight_id INT PRIMARY KEY,
    Aairport_id INT NOT NULL, -- arrival airport
    Dairport_id INT NOT NULL, -- deparature airport, useful both for search and to deteermine the amount to pay to book
    plane_id INT NOT NULL,
    plane_status VARCHAR(20) NOT NULL,
    int_fee INT,
    FOREIGN KEY (plane_id) REFERENCES plane(plane_id),
    FOREIGN KEY (Aairport_id) REFERENCES Airport(airport_id),
    FOREIGN KEY (Dairport_id) REFERENCES Airport(airport_id),
    FOREIGN KEY (int_fee_fee) REFERENCES fee(int_fee)
    
    
);
-- used to save the fees by country and calculate the final ticket cost
CREATE TABLE fee(
    country VARCHAR(20),
    dom_fee INT,
    int_fee INT,

);
