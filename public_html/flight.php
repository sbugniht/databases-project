<?php
// Include necessary PHP files if needed, but for the experimental page, we keep it minimal.
// We only include the logTracker for consistency.
include_once 'logTracker.php'; 
// Set the message variable for potential status messages later
$message = ""; 


?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>SkyBook - Autocomplete Search Demo</title>
  
  <link rel="stylesheet" href="style.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  
  <link rel="stylesheet" href="//code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
</head>
<body>

  <header>
    <div class="logo-wrapper">
      <img src="images/logo.JPG" alt="SkyBook Logo" class="logo-image">
      <div class="logo">SkyBook</div>
    </div>

    <nav>
      <ul>
        <li><a href="index.php">Home</a></li>
        <li><a href="flight.php" class="active">Flights</a></li>
        <li><a href="login.php">Login</a></li>
        <li><a href="imprint.hmtl">Imprint</a></li>
      </ul>
    </nav>
  </header>

  <div class="results admin-management">
    <h1>✈️ Autocomplete Search Experiment</h1>
    <p>This page demonstrates the jQuery Autocomplete feature using a constant list of airport codes/cities.</p>

    <div class="search-card user-search">
      <form id="autocomplete-form" method="get" action="#">
        
        <div class="form-group">
          <label for="autocomplete-departure">Departure Airport/City</label>
          <input type="text" id="autocomplete-departure" name="departure" placeholder="Start typing: London, Paris, Berlin..." required>
        </div>

        <div class="form-group">
          <label for="autocomplete-arrival">Arrival Airport/City</label>
          <input type="text" id="autocomplete-arrival" name="arrival" placeholder="Start typing: New York, Rome, Tokyo..." required>
        </div>

        <button type="submit" class="btn-primary">Test Autocomplete</button>
      </form>
      <?php echo $message; ?>
    </div>
  </div>


  <section class="disclaimer">
    <p>
      This website is student lab work and does not necessarily reflect Constructor University opinions...
      </p>
  </section>

  <footer>
    <p>&copy; 2025 SkyBook — <a href="imprint.hmtl">Imprint</a></p>
  </footer>

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
  
  <script>
    // Document ready function to ensure the DOM is loaded
    $( function() {
      
      // Step 3: Fetch data from the server and initialize Autocomplete
      $.ajax({
          url: 'get_locations.php', // The URL of the new PHP script
          dataType: 'json',
          method: 'GET',
          success: function(data) {
              // 'data' is the JSON array returned by get_locations.php
              
              if (data && data.length > 0) {
                  // Initialize Autocomplete on the Departure field
                  $( "#autocomplete-departure" ).autocomplete({
                      source: data // Use the dynamic data from the server
                  });
                  
                  // Initialize Autocomplete on the Arrival field
                  $( "#autocomplete-arrival" ).autocomplete({
                      source: data // Use the dynamic data from the server
                  });
              } else {
                  console.error("Server returned empty or invalid data for autocomplete.");
                  // Optionally, fall back to a message or default tags
              }
          },
          error: function(xhr, status, error) {
              console.error("Error fetching autocomplete data:", status, error);
              // Handle error: e.g., display a message to the user or load default tags
          }
      });
      
      // Basic logging/feedback on form submission for testing
      $("#autocomplete-form").on("submit", function(e) {
          e.preventDefault();
          alert("Search submitted! Departure: " + $("#autocomplete-departure").val() + ", Arrival: " + $("#autocomplete-arrival").val());
      });
      
    } );
  </script>


</body>
</html>