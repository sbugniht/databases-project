# -*- coding: utf-8 -*-


import re
import csv
import sys
import pandas as pd


ACCESS_LOG_FILE = 'application.log'
EVENTS_LOG_FILE = 'events.log'
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
    r'\[(.*?)\] '         # 2. Event Type
    r'\[(.*?)\] '         # 3. IP Address
    r'\[(.*?)\] '         # 4. User ID
    r'(.*)$'              # 5. Message
)




def extract_browser_from_ua(user_agent):
    """Extracts the browser name (simplified)."""
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

def parse_log_file(log_path, pattern, log_type):
    """Generic function to parse log files."""
    records = []
    
    try:
        with open(log_path, 'r', encoding='utf-8') as f:
            for line in f:
                match = pattern.match(line)
                if match:
                    if log_type == 'access':
                        # Groups: 1:IP, 2:USER_ID, 3:TIME, 4:METHOD, 5:URI, ... 9:UA
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
                    
                    elif log_type == 'event':
                        # Groups: 1:TIME, 2:EVENT_TYPE, 3:IP, 4:USER_ID, 5:MESSAGE
                        time_str, event_type, ip, user_id, message = match.groups()
                        
                        records.append({
                            'timestamp': time_str,
                            'event_type': event_type,
                            'ip': ip,
                            'user_id': user_id,
                            'message': message.strip()
                        })
        return records

    except FileNotFoundError:
        print(f"Error: Log file {log_path} not found.")
        return []

def write_csv_pandas(records, output_path, fieldnames):
    """Uses Pandas to save data to CSV (more robust in Py3)."""
    if not records:
        print(f"Warning: No data to write to {output_path}.")
        return

    try:
        df = pd.DataFrame(records)
        df.to_csv(output_path, index=False, columns=fieldnames, encoding='utf-8')
        print(f"File {output_path} saved with {len(records)} rows.")
        
    except Exception as e:
        print(f"Error writing CSV file with Pandas: {e}", file=sys.stderr)




if __name__ == '__main__':
    print("Starting log analysis (Python 3.6 with Pandas)...")
    
    
    access_records = parse_log_file(ACCESS_LOG_FILE, ACCESS_LOG_PATTERN, 'access')
    access_fields = ['timestamp', 'ip', 'user_id', 'method', 'uri_path', 'browser', 'status']
    write_csv_pandas(access_records, OUTPUT_ACCESS_CSV, access_fields)
    
    
    event_records = parse_log_file(EVENTS_LOG_FILE, EVENT_LOG_PATTERN, 'event')
    event_fields = ['timestamp', 'event_type', 'ip', 'user_id', 'message']
    write_csv_pandas(event_records, OUTPUT_EVENTS_CSV, event_fields)
    
    print("\nAnalysis complete. Download CSV files for graphical visualization.")