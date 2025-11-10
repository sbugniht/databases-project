# -*- coding: utf-8 -*-

import pandas as pd
import matplotlib.pyplot as plt
import matplotlib.dates as mdates
import sys
from datetime import datetime


INPUT_ACCESS_CSV = 'access_data.csv'
INPUT_EVENTS_CSV = 'events_data.csv'
OUTPUT_PDF = 'WebLog_Analysis_Diagrams.pdf'



def load_data(csv_path):
    """Loads CSV data and converts the timestamp column to datetime objects."""
    try:
        
        df = pd.read_csv(csv_path)
        
        # Parse the timestamp (Format: DD/Mon/YYYY:HH:MM:SS +/-TZ)
        df['timestamp'] = pd.to_datetime(df['timestamp'], format='%d/%b/%Y:%H:%M:%S %z', utc=True)
        return df
        
    except FileNotFoundError:
        print(f"Error: CSV file not found at '{csv_path}'. Please ensure the file is downloaded from the server.", file=sys.stderr)
        return None
    except Exception as e:
        print(f"Error parsing data in {csv_path}: {e}", file=sys.stderr)
        return None



def plot_access_timeline(df_access, pdf):
    """Creates a scatter plot showing access events over time (Timeline Diagram)."""
    
    fig, ax = plt.subplots(figsize=(14, 7))
    
    
    ax.plot(df_access['timestamp'], df_access['uri_path'], 'o', 
            markersize=5, alpha=0.6, label='Page Access (GET/Other)')
    
    
    df_post = df_access[df_access['method'] == 'POST']
    ax.plot(df_post['timestamp'], df_post['uri_path'], 'x', 
            markersize=8, color='red', label='Search / Form Submission (POST)')
    
    ax.set_title('Website Access Timeline and User Activity')
    ax.set_xlabel('Time')
    ax.set_ylabel('Page/Resource Accessed (URI)')
    ax.legend(loc='lower right')
    ax.grid(True, axis='x', linestyle='--')
    
    
    fig.autofmt_xdate()
    pdf.savefig(fig)
    plt.close(fig)
    print("Generated: Access Timeline.")


def plot_error_event_timeline(df_events, pdf):
    """Creates a timeline showing specific application events (Errors, Login, Booking)."""
    
    fig, ax = plt.subplots(figsize=(14, 7))
    
    
    df_failure = df_events[df_events['event_type'].str.contains('FAIL|DENIED|ERROR')]
    df_success = df_events[df_events['event_type'].str.contains('SUCCESS')]
    
    
    ax.plot(df_success['timestamp'], df_success['event_type'], 'o', 
            markersize=8, color='green', alpha=0.6, label='Success Events (Login, Booking, Search)')
    
    
    ax.plot(df_failure['timestamp'], df_failure['event_type'], 'x', 
            markersize=8, color='red', label='Error/Failure Events (Login Fail, Booking Fail, Access Denied)')
            
    ax.set_title('Application Event and Error Timeline (Login, Booking, Admin Actions)')
    ax.set_xlabel('Time')
    ax.set_ylabel('Event Type')
    ax.legend(loc='lower right')
    ax.grid(True, axis='x', linestyle='--')
    
    
    fig.autofmt_xdate()
    pdf.savefig(fig)
    plt.close(fig)
    print("Generated: Error/Event Timeline.")
    

def plot_statistics(df_access, df_events, pdf):
    """Generates bar charts for key statistics: Browsers and Top Users/IPs."""
    
    
    fig_browser, ax_browser = plt.subplots(figsize=(10, 6))
    browser_counts = df_access['browser'].value_counts()
    browser_counts.plot(kind='bar', ax=ax_browser, color='skyblue')
    ax_browser.set_title('Website Access by Browser Type')
    ax_browser.set_xlabel('Browser')
    ax_browser.set_ylabel('Total Requests')
    ax_browser.tick_params(axis='x', rotation=45)
    plt.tight_layout()
    pdf.savefig(fig_browser)
    plt.close(fig_browser)
    print("Generated: Browser Statistics.")
    
    
    fig_user, ax_user = plt.subplots(figsize=(10, 6))
    
    
    user_activity = df_access[df_access['user_id'] != 'GUEST']['user_id'].value_counts().head(10)
    
    if not user_activity.empty:
        user_activity.plot(kind='bar', ax=ax_user, color='lightcoral')
        ax_user.set_title('Top 10 Most Active Logged-in Users (by ID)')
        ax_user.set_xlabel('User ID')
        ax_user.set_ylabel('Total Page Views')
        ax_user.tick_params(axis='x', rotation=0)
        plt.tight_layout()
        pdf.savefig(fig_user)
        plt.close(fig_user)
        print("Generated: Top User Statistics.")
    else:
        print("Skipped: Top User Statistics (No logged-in activity found).")



if __name__ == '__main__':
    
    from matplotlib.backends.backend_pdf import PdfPages
    
    df_access = load_data(INPUT_ACCESS_CSV)
    df_events = load_data(INPUT_EVENTS_CSV)

    if df_access is None and df_events is None:
        print("Cannot proceed. No data files were successfully loaded.")
        sys.exit(1)

    print("\n--- Generating Plots ---")
    
    with PdfPages(OUTPUT_PDF) as pdf:
        
        if df_access is not None and not df_access.empty:
            plot_access_timeline(df_access, pdf)
            plot_statistics(df_access, df_events, pdf)

        
        if df_events is not None and not df_events.empty:
            plot_error_event_timeline(df_events, pdf)
        
        print(f"\nSuccessfully created all diagrams in {OUTPUT_PDF}")