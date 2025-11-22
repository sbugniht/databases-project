<?php
include_once 'logTracker.php'; 
$initial_filter = trim($_GET['search'] ?? '');
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>SkyBook - All Available Flights</title>
  
  <link rel="stylesheet" href="style.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="//code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
  
  <style>
    .filter-controls {
        display: flex;
        gap: 15px;
        align-items: flex-end; 
        flex-wrap: wrap;
        width: 100%;
    }
    
    .special-msg {
        text-align: center;
        padding: 40px;
        font-size: 1.5em;
        color: #666;
        font-style: italic;
        background-color: #f9f9f9;
        border: 1px dashed #ccc;
        border-radius: 8px;
        margin-top: 20px;
    }

    .status-badge {
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 0.85em;
        font-weight: bold;
        color: #fff;
        background-color: #6c757d; /* Default grey */
        text-transform: capitalize;
        white-space: nowrap;
    }
    .status-badge.on-time {
        background-color: #28a745; /* Green */
    }
    .status-badge.delayed {
        background-color: #dc3545; /* Red */
    }
    .status-badge.cancelled {
        background-color: #343a40; /* Dark/Black */
    }
  </style>
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
    <h1>✈️ All Available Flights</h1>
    <p>View all flights or search by location and date.</p>

    <div class="search-card user-search">
        <div class="filter-controls">
            
            <div class="form-group" style="flex: 2; min-width: 250px;">
                <label for="filter-search">Airport/City Filter</label>
                <input type="text" id="filter-search" name="search" 
                       placeholder="Start typing: London, BER, Rome..." 
                       value="<?php echo htmlspecialchars($initial_filter); ?>">
            </div>
            
            <div class="form-group" style="flex: 1; min-width: 150px;">
                <label for="filter-date">Date</label>
                <input type="date" id="filter-date" name="date">
            </div>

            <div class="form-group" style="flex: 0 0 auto;">
                <button id="clear-all-btn" class="btn-primary" style="background-color: var(--secondary-color); height: 42px; margin-top: 0;">
                    Clear Filters
                </button>
            </div>
        </div>
        
        <div id="status-message" class="success" style="margin-top: 15px;"></div>
    </div>
    
    <div class="results" style="margin: 30px auto 0; padding: 0; box-shadow: none;">
      <h2 id="results-header">Flight List</h2>
      
      <div id="special-message-container"></div> 
      
      <table id="flights-table">
        <thead>
          <tr>
            <th>Flight Info</th>
            <th>Route</th>
            <th>Departure Time</th>
            <th>Arrival Time</th>
            <th>Duration</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody id="flights-table-body">
          <tr><td colspan="6" style="text-align: center;">Loading flights...</td></tr>
        </tbody>
      </table>
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
    // Endpoints
    const FLIGHTS_ENDPOINT = 'get_all_flights.php';
    const AUTOCOMPLETE_ENDPOINT = 'get_locations.php';

    const $tableBody = $('#flights-table-body');
    const $table = $('#flights-table');
    const $statusMessage = $('#status-message');
    const $specialContainer = $('#special-message-container');
    const $textInput = $('#filter-search');
    const $dateInput = $('#filter-date');
    const $resultsHeader = $('#results-header');

   
    function fetchFlights() {
        const textVal = $textInput.val();
        const dateVal = $dateInput.val();

        $tableBody.html('<tr><td colspan="6" style="text-align: center;">Searching...</td></tr>');
        $statusMessage.text('').removeClass('success error');
        $specialContainer.html(''); 
        $table.show();


        $.ajax({
            url: FLIGHTS_ENDPOINT,
            dataType: 'json',
            method: 'GET',
            data: {
                filter: textVal,
                date: dateVal
            },
            success: function(data) {
                if (data.error) {
                    $statusMessage.addClass('error').text('Error fetching data: ' + data.error);
                    $tableBody.html('<tr><td colspan="6" style="text-align: center;">Error loading flights.</td></tr>');
                    return;
                }

                if (data.special_message) {
                    $table.hide(); 
                    $specialContainer.html(`<div class="special-msg">${data.special_message}</div>`);
                    $statusMessage.text(''); 
                    return;
                }

                if (data && data.length > 0) {
                    let html = '';
                    data.forEach(flight => {
                        let statusClass = flight.plane_status ? flight.plane_status.toLowerCase().replace(/\s+/g, '-') : 'unknown';

                        html += `
                            <tr>
                                <td>
                                    <span style="font-weight: bold; font-size: 1.1em;">#${flight.flight_id}</span>
                                    <br>
                                    <small style="color: #666;">Plane: ${flight.plane_id}</small>
                                </td>

                                <td>
                                    <strong>${flight.dep_city} (${flight.dep_iata})</strong>
                                    <div style="font-size: 0.8em; color: #999;">&darr; to</div>
                                    <strong>${flight.arr_city} (${flight.arr_iata})</strong>
                                </td>

                                <td>${flight.formatted_dep}</td>

                                <td>${flight.formatted_arr}</td>

                                <td>${flight.formatted_duration}</td>

                                <td>
                                    <span class="status-badge ${statusClass}">
                                        ${flight.plane_status}
                                    </span>
                                </td>
                            </tr>
                        `;
                    });
                    $tableBody.html(html);
                    
                    let msg = `${data.length} flights found`;
                    if (dateVal) msg += ` for ${dateVal}`;
                    $statusMessage.addClass('success').text(msg);
                    
                } else {
                    $tableBody.html('<tr><td colspan="6" style="text-align: center;">No flights found matching your criteria.</td></tr>');
                    $statusMessage.addClass('error').text(`No flights found.`);
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error:", status, error);
                $statusMessage.addClass('error').text('Failed to connect to the flight data server.');
                $tableBody.html('<tr><td colspan="6" style="text-align: center;">Failed to load data.</td></tr>');
            }
        });
    }

    function setupDynamicAutocomplete() {
        $textInput.autocomplete({
            source: function(request, response) {
                $.ajax({
                    url: AUTOCOMPLETE_ENDPOINT,
                    dataType: "json",
                    data: {
                        term: request.term
                    },
                    success: function(data) {
                        response(data); 
                    },
                    error: function() {
                        response([]); 
                    }
                });
            },
            minLength: 2, 
            delay: 300,
            select: function(event, ui) {
                const selectedValue = ui.item.value;
                const match = selectedValue.match(/\(([^)]+)\)$/);
                let finalValue = selectedValue;
                
                if (match && match[1]) {
                    const iata = match[1];
                    const city = selectedValue.substring(0, selectedValue.indexOf(' (')).trim();
                    finalValue = iata.length <= city.length ? iata : city;
                } 
                
                $textInput.val(finalValue);
                fetchFlights(); 
                event.preventDefault(); 
            }
        });
    }

    $( function() {
        setupDynamicAutocomplete();
        
        fetchFlights();

        $textInput.on('keypress', function(e) {
            if (e.which === 13) { 
                e.preventDefault(); 
                fetchFlights();
            }
        });
        
        $dateInput.on('change', function() {
            fetchFlights();
        });
        
        $('#clear-all-btn').on('click', function() {
            $textInput.val('');
            $dateInput.val('');
            fetchFlights();
        });
    } );
  </script>

</body>
</html>