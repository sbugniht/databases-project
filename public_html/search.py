from flask import Flask, render_template, request, redirect, url_for

app = Flask(__name__)

# Homepage
@app.route('/')
def index():
    return render_template('index.html')

# Imprint page
@app.route('/imprint')
def imprint():
    return render_template('imprint.html')

# Example input form page (e.g., for Flights)
@app.route('/input/flight', methods=['GET', 'POST'])
def input_flight():
    if request.method == 'POST':
        # Here you would save to the database
        flight_number = request.form.get('flight_number')
        departure = request.form.get('departure')
        arrival = request.form.get('arrival')

        # Insert into DB (omitted for brevity)
        print(f"Saving flight: {flight_number}, {departure} -> {arrival}")

        return redirect(url_for('feedback', message='Flight added successfully!'))

    return render_template('input_flight.html')

# Feedback page
@app.route('/feedback')
def feedback():
    message = request.args.get('message', 'Operation completed.')
    return render_template('feedback.html', message=message)

if __name__ == '__main__':
    app.run(debug=True)
