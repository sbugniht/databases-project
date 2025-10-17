#!/usr/bin/env python3

import cgi
import cgitb
import mysql.connector

cgitb.enable()  # Show errors in browser (useful during dev)

print("Content-Type: text/html\n")  # Required HTTP header

form = cgi.FieldStorage()
flight_number = form.getfirst("flight_number", "").strip()

# Connect to MariaDB
db = mysql.connector.connect(
    host="gbrugnara@clabsql.clamv.constructor.university",
    user="gbrugnara",
    password="KeRjnLwqj+rTTG3E",
    database="db_gbrugnara"
)
cursor = db.cursor()

if flight_number:
    cursor.execute("INSERT INTO flights (flight_number) VALUES (%s)", (flight_number,))
    db.commit()
    message = f"Flight {flight_number} added."
else:
    message = "No flight number provided."

# Simple HTML response
print(f"""
<html>
<head><title>Flight added</title></head>
<body>
<h1>{message}</h1>
<a href="index.html">Back to home</a>
</body>
</html>
""")
