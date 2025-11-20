<?php
include_once 'logTracker.php'; 
$message = ""; 
// Determine if a search query exists from the URL (for initial page load/refresh)
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
    /* Custom styling for the filter display box */
    .filter-display {
        display: flex;
        align-items: center;
        background-color: #fff8e1; /* var(--feature-bg) */
        border: 1px solid #ffe082;
        padding: 8px 15px;
        border-radius: 20px;
        font-weight: 600;
        font-size: 0.9em;
        margin-left: 20px;
        white-space: nowrap;
    }
    .filter-display button {
        background: none;
        border: none;
        color: #dc3545; /* var(--error-color) */
        font-weight: 700;
        cursor: pointer;
        margin-left: 8px;
        padding: 0;
        line-height: 1;
        font-size: 1.2em;
        transition: color 0.2s;
    }
    .search-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .search-card .form-group {
        flex-grow: 1;
        max-width: none;
        min-width: 250px;
    }
    .filter-container {
        display: flex;
        align-items: flex-end;
        flex-grow: 1;
    }
    
    /* Status Badges CSS */
    .status-badge {
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 0.85em;
        font-weight: bold;
        color: #fff;
        background-color: #6c757d; /* Default grey */
        text-transform: capitalize;
    }
    .status-badge.on-time {
        background-color: #28a745; /* Green */
    }
    .status-badge.delayed {
        background-color: #dc3545; /* Red */
    }
    .status-badge.cancelled {
        background-color: #343a40; /* Dark */
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
    <p>View all flights or search by a single Airport (IATA or City) to filter the list.</p>

    <div class="search-card user-search">
        <div class="search-row">
            <div class="filter-container">
                <div class="form-group" style="flex-grow: 1;">
                    <label for="filter-search">Airport/City Filter</label>
                    <input type="text" id="filter-search" name="search" placeholder="Start typing: London, BER, Rome..." 
                           value="<?php echo htmlspecialchars($initial_filter); ?>">
                </div>
                
                <div id="current-filter-box" class="filter-display" style="display: none;">
                    <span>Filtering by: <strong id="current-filter-term"></strong></span>
                    <button id="clear-filter-btn" title="Clear Filter">&times;</button>
                </div>
            </div>
        </div>
        
        <div id="status-message" class="success" style="margin-top: 15px;"></div>
    </div>
    
    <div class="results" style="margin: 30px auto 0; padding: 0; box-shadow: none;">
      <h2 id="results-header">Flight List</h2>
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
    const AUTOCOMPLETE_ENDPOINT = 'get_locations.php'; // Ensure this matches your actual file name

    const $tableBody = $('#flights-table-body');
    const $statusMessage = $('#status-message');
    const $filterInput = $('#filter-search');
    const $filterBox = $('#current-filter-box');
    const $filterTerm = $('#current-filter-term');
    const $resultsHeader = $('#results-header');

    /**
     * Fetches flight data based on a filter and updates the table.
     * @param {string} filterQuery - The search term (City or IATA) to filter flights.
     */
    function fetchAndRenderFlights(filterQuery = '') {
        $tableBody.html('<tr><td colspan="6" style="text-align: center;">Searching for flights...</td></tr>');
        $statusMessage.removeClass('success error').text('Searching...');

        const queryParam = filterQuery ? '?filter=' + encodeURIComponent(filterQuery) : '';
        
        $.ajax({
            url: FLIGHTS_ENDPOINT + queryParam,
            dataType: 'json',
            method: 'GET',
            success: function(data) {
                if (data.error) {
                    $statusMessage.addClass('error').text('Error fetching data: ' + data.error);
                    $tableBody.html('<tr><td colspan="6" style="text-align: center;">Error loading flights.</td></tr>');
                    return;
                }

                if (data && data.length > 0) {
                    let html = '';
                    data.forEach(flight => {
                        // Clean status for CSS class (e.g., "On Time" -> "on-time")
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
                    $statusMessage.addClass('success').text(`${data.length} flights found.`);
                    
                    if (filterQuery) {
                        $resultsHeader.text(`Flights matching "${filterQuery}"`);
                        $filterTerm.text(filterQuery);
                        $filterBox.show();
                    } else {
                        $resultsHeader.text(`All Available Flights`);
                        $filterBox.hide();
                    }
                    
                } else {
                    $tableBody.html('<tr><td colspan="6" style="text-align: center;">No flights found matching your criteria.</td></tr>');
                    $statusMessage.addClass('error').text(`No flights found.`);
                    $resultsHeader.text(`No Flights Found`);
                    $filterBox.hide();
                }
            },
            error: function() {
                $statusMessage.addClass('error').text('Failed to connect to the flight data server.');
                $tableBody.html('<tr><td colspan="6" style="text-align: center;">Failed to load data. Please check server connection.</td></tr>');
            }
        });
    }

    // Function to handle Autocomplete setup
    function setupDynamicAutocomplete() {
        $filterInput.autocomplete({
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
                    // Prefer IATA
                    const iata = match[1];
                    const city = selectedValue.substring(0, selectedValue.indexOf(' (')).trim();
                    finalValue = iata.length <= city.length ? iata : city;
                } 
                
                // Set input and trigger search
                $filterInput.val(finalValue);
                fetchAndRenderFlights(finalValue);
                event.preventDefault(); 
            }
        });
    }

    $( function() {
        setupDynamicAutocomplete();
        
        const initialFilter = $filterInput.val();
        fetchAndRenderFlights(initialFilter);

        $filterInput.on('keypress', function(e) {
            if (e.which === 13) { // Enter key
                e.preventDefault(); 
                fetchAndRenderFlights($(this).val());
            }
        });
        
        $('#clear-filter-btn').on('click', function() {
            $filterInput.val('');
            fetchAndRenderFlights('');
        });
    } );
  </script>

</body>
</html>