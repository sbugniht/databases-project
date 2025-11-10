# -*- coding: utf-8 -*-
# Python 2.7.18

import re
import csv
import sys 


ACCESS_LOG_FILE = 'pagVisited.log'
EVENTS_LOG_FILE = 'eventTracker.log'
OUTPUT_ACCESS_CSV = 'access_data.csv'
OUTPUT_EVENTS_CSV = 'events_data.csv'




ACCESS_LOG_PATTERN = re.compile(
    r'^\[(.*?)\] '          # 1. IP Address
    r'\[(.*?)\] '          # 2. User ID (ID or GUEST)
    r'- - '
    r'\[(.*?)\] '          # 3. Timestamp
    r'"(GET|POST|HEAD|PUT|DELETE) ' # 4. Method
    r'(\S+) '              # 5. URI/Page
    r'(\S+)" '             # 6. Protocol
    r'(\d{3}) '             # 7. Status Code
    r'- '
    r'"(.*?)" '             # 8. Referer
    r'"(.*?)"$'             # 9. User-Agent
)


EVENT_LOG_PATTERN = re.compile(
    r'^\[(.*?)\] '         # 1. Timestamp
    r'\[(.*?)\] '         # 2. Event Type (es. LOGIN_SUCCESS, BOOKING_FAILURE)
    r'\[(.*?)\] '         # 3. IP Address
    r'\[(.*?)\] '         # 4. User ID 
    r'(.*)$'              # 5. Message
)




def extract_browser_from_ua(user_agent):
    """extracts browser name(simplified) from User-Agent."""
    ua = user_agent.lower()
    if 'chrome' in ua and 'safari' in ua and 'edge' not in ua:
        return 'Chrome'
    elif 'firefox' in ua:
        return 'Firefox'
    elif 'safari' in ua and 'chrome' not in ua:
        return 'Safari'
    elif 'opera' in ua or 'opr' in ua:
        return 'Opera'
    elif 'msie' in ua or 'trident' in ua:
        return 'IE/Edge'
    else:
        return 'Unknown'

def write_csv(records, fieldnames, output_path):
    """Writes the analized records in a CSV file."""
    if not records:
        print >> sys.stderr, "Warning: No data to be written in %s." % output_path
        return

    try:
       
        with open(output_path, 'wb') as csvfile: 
            
            writer = csv.DictWriter(csvfile, fieldnames=fieldnames)
            writer.writeheader()
            
            
            for data in records:
               
                clean_data = {k: v.encode('utf-8') if isinstance(v, unicode) else str(v) for k, v in data.items()}
                writer.writerow(clean_data)
        
        print "File %s saved with %d rows." % (output_path, len(records))
        
    except Exception as e:
        print >> sys.stderr, "Error while writing CSV file %s: %s" % (output_path, e)


# --- Funzioni di Parsing ---

def parse_access_log(log_path):
    """Parse application.log and return the records."""
    records = []
    
    try:
        with open(log_path, 'r') as f:
            for line in f:
                match = ACCESS_LOG_PATTERN.match(line)
                if match:
                    # groups are: 1:IP, 2:USER_ID, 3:TIME, 4:METHOD, 5:URI, 6:PROTOCOL, 7:STATUS, 8:REFERER, 9:USER_AGENT
                    ip, user_id, time_str, method, uri, _, status, _, user_agent = match.groups()
                    
                    path = uri.split('?')[0] 
                    browser = extract_browser_from_ua(user_agent)
                        
                    records.append({
                        'timestamp': time_str,
                        'ip': ip,
                        'user_id': user_id, 
                        'method': method,
                        'uri_path': path,
                        'browser': browser,
                        'status': status
                    })
        return records

    except IOError:
        print >> sys.stderr, "Error: Access log file (%s) not found." % log_path
        return []

def parse_event_log(log_path):
    """Parse events.log and returns the records."""
    events = []
    
    try:
        with open(log_path, 'r') as f:
            for line in f:
                match = EVENT_LOG_PATTERN.match(line)
                if match:
                    # groups are: 1:TIME, 2:EVENT_TYPE, 3:IP, 4:USER_ID, 5:MESSAGE
                    time_str, event_type, ip, user_id, message = match.groups()
                    
                    events.append({
                        'timestamp': time_str,
                        'event_type': event_type,
                        'ip': ip,
                        'user_id': user_id,
                        'message': message.strip()
                    })
        return events

    except IOError:
        print >> sys.stderr, "Error: events log file (%s) not found." % log_path
        return []




if __name__ == '__main__':
    print "Begin analisys log files (Python 2.7)..."
    
    # 1. Parsing e salvataggio dei log di accesso
    access_records = parse_access_log(ACCESS_LOG_FILE)
    access_fields = ['timestamp', 'ip', 'user_id', 'method', 'uri_path', 'browser', 'status']
    write_csv(access_records, access_fields, OUTPUT_ACCESS_CSV)
    
    # 2. Parsing e salvataggio dei log di eventi
    event_records = parse_event_log(EVENTS_LOG_FILE)
    event_fields = ['timestamp', 'event_type', 'ip', 'user_id', 'message']
    write_csv(event_records, event_fields, OUTPUT_EVENTS_CSV)
    
    print "\nAnalisys completed."
    print "Download %s and %s to visualize in Python 3 (not possible to create graphs with Python 2.7)." % (OUTPUT_ACCESS_CSV, OUTPUT_EVENTS_CSV)