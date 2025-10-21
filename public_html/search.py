#!/usr/bin/env python3

import cgi
import cgitb
import MySQLdb

cgitb.enable()  # Show errors in browser (useful during dev)

print("Content-Type: text/html\n")  # Required HTTP header

form = cgi.FieldStorage()
flight_number = form.getfirst("flight_number", "").strip()

message = ""

if flight_number:
    try:
        # Connessione a MariaDB
        db = MySQLdb.connect(
            host="gbrugnara@clabsql.clamv.constructor.university",
            user="gbrugnara",
            passwd="KeRjnLwqj+rTTG3E",
            db="db_gbrugnara"
        )
        cursor = db.cursor()

        # ✅ Query SQL – inserisce il volo
        query = """INSERT INTO Flights (flight_id, Aairport_id, Dairport_id, plane_id, plane_status)
                   VALUES (%s, 'FCO' , 'JFK' , '11', 'on time')"""
        cursor.execute(query, (flight_number))
        db.commit()

        message = "Flight %s saved successfully." % flight_number

    except MySQLdb.Error as e:
        message = "Database error: %s" % str(e)

    finally:
        try:
            cursor.close()
            db.close()
        except:
            pass
else:
    message = "Missing information. Please fill all fields."


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


