CREATE TABLE Plane(
    plane_id INT,
    PRIMARY KEY (plane_id)
    
);
CREATE TABLE City(
    city_name CHAR, 
    airports ARRAY
    PRIMARY KEY (city_name)
);
CREATE TABLE Airport(
    airport_id INT,
    iata CHAR UNIQUE,
    PRIMARY KEY (airport_id)
);

CREATE TABLE Visitor(
    USER_ID INT,
    PRIMARY KEY (USER_ID)
);

CREATE TABLE login(
    USER_ID INT NOT NULL,
    pwd INT,
    FOREIGN KEY (USER_ID) REFERENCES Visitor(USER_ID)
);

    CREATE TABLE Admin(
    USER_ID INT,
    PRIMARY KEY (USER_ID)
);

--Parent entity: flights
CREATE TABLE flights (
    flight_id INT PRIMARY KEY,
    airport_id INT NOT NULL,
    plane_id INT NOT NULL,
    plane_status VARCHAR(20) NOT NULL,
    FOREIGN KEY (plane_id) REFERENCES plane(plane_id),
    FOREIGN KEY (airport_id) REFERENCES Airport(airport_id)
);

CREATE TABLE International_flight(
    flight_id INT,
    airport_id INT,
    plane_id INT,
    plane_status CHAR,
    

    FOREIGN KEY (plane_id) REFERENCES Plane(plane_id),
    FOREIGN KEY (airport_id) REFERENCES Airport(airport_id),
    
);
CREATE TABLE national_flight(
    light_id INT,
    airport_id INT,
    plane_id INT,
    plane_status CHAR,

    FOREIGN KEY (plane_id) REFERENCES Plane(plane_id),
    FOREIGN KEY (airport_id) REFERENCES Airport(airport_id),
);

-- Child entity: dom_flight (Domestic Flight)
CREATE TABLE dom_flight (
    flight_id INT PRIMARY KEY,
    dom_region VARCHAR(50),
    FOREIGN KEY (flight_id) REFERENCES flights(flight_id)
);

-- Child entity: int_flight (International Flight)
CREATE TABLE int_flight (
    flight_id INT PRIMARY KEY,
    country VARCHAR(50),
    FOREIGN KEY (flight_id) REFERENCES flights(flight_id)
);
