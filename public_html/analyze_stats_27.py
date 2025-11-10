# -*- coding: utf-8 -*-
# Progettato per Python 2.7.18

import re
import csv
import sys # Importa sys per la corretta gestione dell'output in Py2

# Configurazione dei file
ACCESS_LOG_FILE = 'application.log'
EVENTS_LOG_FILE = 'events.log'
OUTPUT_ACCESS_CSV = 'access_data.csv'
OUTPUT_EVENTS_CSV = 'events_data.csv'

# Espressioni Regolari (aggiornate per i nuovi formati)

# 1. Log Accessi (application.log): [IP] [USER_ID] - - [Data] "Metodo URI Protocollo" ...
ACCESS_LOG_PATTERN = re.compile(
    r'^\[(.*?)\] '          # 1. IP Address
    r'\[(.*?)\] '          # 2. User ID (ID o GUEST)
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

# 2. Log Eventi (events.log): [Data] [Tipo Evento] [IP] [USER_ID] Messaggio
EVENT_LOG_PATTERN = re.compile(
    r'^\[(.*?)\] '         # 1. Timestamp
    r'\[(.*?)\] '         # 2. Event Type (es. LOGIN_SUCCESS, BOOKING_FAILURE)
    r'\[(.*?)\] '         # 3. IP Address
    r'\[(.*?)\] '         # 4. User ID (l'account coinvolto)
    r'(.*)$'              # 5. Message
)


# --- Funzioni di Utility ---

def extract_browser_from_ua(user_agent):
    """Estrae il nome del browser (semplificato) dallo User-Agent."""
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
    """Scrive i record analizzati in un file CSV (compatibile con Python 2)."""
    if not records:
        print >> sys.stderr, "Attenzione: Nessun dato da scrivere in %s." % output_path
        return

    try:
        # Usa 'wb' per evitare problemi di newline su Python 2
        with open(output_path, 'wb') as csvfile: 
            # csv.DictWriter in Python 2.7 non supporta Unicode benissimo di default
            writer = csv.DictWriter(csvfile, fieldnames=fieldnames)
            writer.writeheader()
            
            # Scrivi i dati
            for data in records:
                # Conversione implicita a stringhe semplici (compatibile con Py2 CSV)
                clean_data = {k: v.encode('utf-8') if isinstance(v, unicode) else str(v) for k, v in data.items()}
                writer.writerow(clean_data)
        
        print "File %s salvato con %d righe." % (output_path, len(records))
        
    except Exception as e:
        print >> sys.stderr, "Errore durante la scrittura del file CSV %s: %s" % (output_path, e)


# --- Funzioni di Parsing ---

def parse_access_log(log_path):
    """Parsa application.log e restituisce i record."""
    records = []
    
    try:
        with open(log_path, 'r') as f:
            for line in f:
                match = ACCESS_LOG_PATTERN.match(line)
                if match:
                    # I gruppi sono: 1:IP, 2:USER_ID, 3:TIME, 4:METHOD, 5:URI, 6:PROTOCOL, 7:STATUS, 8:REFERER, 9:USER_AGENT
                    ip, user_id, time_str, method, uri, _, status, _, user_agent = match.groups()
                    
                    path = uri.split('?')[0] # Rimuove i parametri
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
        print >> sys.stderr, "Errore: File di log accessi (%s) non trovato." % log_path
        return []

def parse_event_log(log_path):
    """Parsa events.log e restituisce i record."""
    events = []
    
    try:
        with open(log_path, 'r') as f:
            for line in f:
                match = EVENT_LOG_PATTERN.match(line)
                if match:
                    # I gruppi sono: 1:TIME, 2:EVENT_TYPE, 3:IP, 4:USER_ID, 5:MESSAGE
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
        print >> sys.stderr, "Errore: File di log eventi (%s) non trovato." % log_path
        return []


# --- Esecuzione Principale ---

if __name__ == '__main__':
    print "Inizio analisi dei log (Python 2.7)..."
    
    # 1. Parsing e salvataggio dei log di accesso
    access_records = parse_access_log(ACCESS_LOG_FILE)
    access_fields = ['timestamp', 'ip', 'user_id', 'method', 'uri_path', 'browser', 'status']
    write_csv(access_records, access_fields, OUTPUT_ACCESS_CSV)
    
    # 2. Parsing e salvataggio dei log di eventi
    event_records = parse_event_log(EVENTS_LOG_FILE)
    event_fields = ['timestamp', 'event_type', 'ip', 'user_id', 'message']
    write_csv(event_records, event_fields, OUTPUT_EVENTS_CSV)
    
    print "\nAnalisi completata."
    print "Scarica %s e %s per la visualizzazione Python 3." % (OUTPUT_ACCESS_CSV, OUTPUT_EVENTS_CSV)