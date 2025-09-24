CREATE TABLE Plane(
    plane_id INT,

    PRIMARY KEY (plane_id)
    
);
CREATE TABLE Commercial(
    plane_id INT,
    seats INT,
    
    PRIMARY KEY (plane_id),
    FOREIGN KEY (plane_id) REFERENCES Plane(plane_id)

);

CREATE TABLE Tickets(
    seat_id INT,
    booked INT,
    
);

CREATE TABLE economy(
    price INT,
    seat_id INT,
    booked INT,
    FOREIGN KEY (seat_id) REFERENCES Tickets(seat_id),
    FOREIGN KEY (booked) REFERENCES Tickets(booked)
);

CREATE TABLE business(
    price INT,
    seat_id INT,
    booked INT,
    FOREIGN KEY (seat_id) REFERENCES Tickets(seat_id),
    FOREIGN KEY (booked) REFERENCES Tickets(booked)
);

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

CREATE TABLE registred_user(
    USER_ID INT NOT NULL,
    pwd INT,
    privilege INT,
    
);

--Parent entity: flights
CREATE TABLE flights (
    flight_id INT PRIMARY KEY,
    Aairport_id INT NOT NULL,
    Dairport_id INT NOT NULL,
    plane_id INT NOT NULL,
    plane_status VARCHAR(20) NOT NULL,
    FOREIGN KEY (plane_id) REFERENCES plane(plane_id),
    FOREIGN KEY (Aairport_id) REFERENCES Airport(airport_id),
    FOREIGN KEY (Dairport_id) REFERENCES Airport(airport_id)
);


-- Child entity: dom_flight (Domestic Flight)
CREATE TABLE dom_flight (
    flight_id INT PRIMARY KEY,
    dom_fee INT,
    FOREIGN KEY (flight_id) REFERENCES flights(flight_id)
);

-- Child entity: int_flight (International Flight)
CREATE TABLE int_flight (
    flight_id INT PRIMARY KEY,
    int_fee INT,
    FOREIGN KEY (flight_id) REFERENCES flights(flight_id)
);

CREATE TABLE fee(
    dom_fee INT,
    int_fee INT,
    country VARCHAR(20)
)
