CREATE TABLE Plane(
    plane_id INT,
    PRIMARY KEY (plane_id)
    
);

CREATE TABLE Airport(
    airport_id INT,
    iata CHAR UNIQUE ,
    PRIMARY KEY (airport_id)
);

CREATE TABLE Visitor(
USER_ID INT,
PRIMARY KEY (USER_ID),

);

CREATE TABLE Logged_user(
USER_ID INT,
PRIMARY KEY (USER_ID),
);

CREATE TABLE Admin(
USER_ID INT,
PRIMARY KEY (USER_ID),
);

CREATE TABLE Flight(
flight_id INT,
airport_id INT,
plane_id INT,

PRIMARY KEY (flight_id),
FOREIGN KEY (plane_id) REFERENCES Plane(plane_id),
FOREIGN KEY (airport_id) REFERENCES Airport(airport_id)
);