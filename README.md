# databases-project
SkyBook – Airline Reservation System <br />
Team members: <br />
Alexis Leonel Valdez Castro, Giacomo Brugnara, Santiago Batista Diaz, Felipe Javier Salazar Bustamante <br />
This project consists of an airline reservation system. On the website, users can create a profile, search for flight information, book tickets, etc. The purpose is that of simulating a real-life airline booking platform. This type of data is very interesting to analyze since it is dynamic: the data changes over time since each flight has a departure and arrival time, therefore it evolves without the need for a user or admin to change the data to “see something happen”. Users are presented with a browser-like module where they can enter their desired destination along with additional travel parameters. From the user perspective, the platform provides the following features and interactions: <br />
 - **User login**: Each user can create a profile with personal data. Illegal input: attempting to register with an already-used email.
Flight search: Users are able to search for available flights based on certain parameters: price range, date, seat class, etc. Users can navigate through the list, scrolling to explore available options, and select any flight of interest.	<br />
  	Invalid input: entering non existing routes or invalid dates. <br />
 - **Booking**: Once the user chooses a flight, a link between the user and the flight in the database is created. The system displays the results containing all relevant flight options: flight number, departure/arrival, number of stopovers. The system reduces availability. <br />
- **Illegal input**: booking when flight is already full. <br />
Seat and class selection:  The system provides different options for the same flight, like Economy, Business, and First class. It also provides seat selection capabilities, showing available seats in an interactive chart. Invalid input: selecting an already booked seat.<br />
 - **Flight details**: The summary page presents the chosen flight details along with additional features, including real-time flight status. The ticket can also be cancelled. <br /> <br />
 The admin perspective allows staff to add and update flights, modify the status, manage the passengers, among others. With this platform, the goal is to provide user-friendly experience with reliable data (constantly updated).
The ER Diagram for this project is presented as follows:


<img width="1135" height="703" alt="Screenshot 2025-09-26 alle 10 39 26" src="https://github.com/user-attachments/assets/4528099a-92e2-4d65-8be3-be93fd112335" />
