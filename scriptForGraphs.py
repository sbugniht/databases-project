
import pandas as pd
import matplotlib.pyplot as plt
from matplotlib.backends.backend_pdf import PdfPages
import sys
import numpy as np
import matplotlib.colors as mcolors


INPUT_ACCESS_CSV = 'public_html/access_data.csv'
INPUT_EVENTS_CSV = 'public_html/events_data.csv'
OUTPUT_PDF = 'WebLog_Analysis_Diagrams.pdf'


plt.style.use('seaborn-v0_8-whitegrid')



def load_data(csv_path):
    """Loads CSV data and converts the timestamp column to datetime objects."""
    try:
        df = pd.read_csv(csv_path)
        # Parse the timestamp (Format: DD/Mon/YYYY:HH:MM:SS +/-TZ)
        df['timestamp'] = pd.to_datetime(df['timestamp'], format='%d/%b/%Y:%H:%M:%S %z', utc=True)
        return df
        
    except FileNotFoundError:
        print(f"Error: CSV file not found at '{csv_path}'. Ensure the file is downloaded.", file=sys.stderr)
        return None
    except Exception as e:
        print(f"Error parsing data in {csv_path}: {e}", file=sys.stderr)
        return None

def clean_uri(uri):
    """Extracts the simple page name (e.g., /~user/index.php -> index)."""
    
    cleaned = uri.split('/')[-1]
    if '?' in cleaned: 
        cleaned = cleaned.split('?')[0]
    return cleaned.replace('.php', '') or 'root' 

def create_identity_map(df_access):
    """
    Creates a unique color map based on the combined identity (IP/ID + Browser).
    Returns: dict mapping identity string to a unique color and label.
    """
   
    df_access['identity'] = df_access['user_id'].astype(str) + ' @ ' + df_access['ip'] + ' | ' + df_access['browser']
    
    unique_identities = df_access['identity'].unique()
    
   
    cmap = plt.cm.get_cmap('hsv', len(unique_identities))
    
    color_map = {
        identity: {'color': mcolors.rgb2hex(cmap(i)), 'label': identity}
        for i, identity in enumerate(unique_identities)
    }
    return color_map

# --- Plotting Functions ---

def plot_access_timeline_colored(df_access, pdf, color_map):
    """
    Timeline plot with distinct colors per user identity (IP/ID + Browser).
    Uses dot for GET/other and cross for POST.
    """
    
    df_access['page_name'] = df_access['uri_path'].apply(clean_uri)
    
    fig, ax = plt.subplots(figsize=(15, 8))
    
   
    plotted_labels = set()
    
    for identity, group in df_access.groupby('identity'):
        
        color_info = color_map.get(identity, {'color': 'gray', 'label': 'Unknown'})
        base_color = color_info['color']
        base_label = color_info['label']
        
    
        group_get = group[group['method'] != 'POST']
        if not group_get.empty:
            label_get = f'{base_label} (GET/Other)'
            ax.plot(group_get['timestamp'], group_get['page_name'], 'o', 
                    markersize=6, alpha=0.7, color=base_color, 
                    label=label_get if base_label not in plotted_labels else "")
            plotted_labels.add(base_label) 

      
        group_post = group[group['method'] == 'POST']
        if not group_post.empty:
            label_post = f'{base_label} (POST)'
            ax.plot(group_post['timestamp'], group_post['page_name'], 'x', 
                    markersize=8, color=base_color, 
                    label=label_post if base_label not in plotted_labels else "")
            plotted_labels.add(base_label)

    ax.set_title('Page Access Timeline by Unique User Identity and Action Type')
    ax.set_xlabel('Time')
    ax.set_ylabel('Page Accessed')
    
 
    ax.legend(title='Identity (ID@IP | Browser) and Action', loc='upper left', bbox_to_anchor=(1.05, 1), fontsize='small')
    
    ax.grid(True, axis='x', linestyle='--')
    fig.autofmt_xdate()
    plt.tight_layout(rect=[0, 0, 0.8, 1]) 
    pdf.savefig(fig)
    plt.close(fig)
    print("Generated: Colored Access Timeline.")


def plot_event_timeline_colored(df_events, pdf, color_map):
    """
    Event timeline plot using the same color mapping as the access plot.
    """
    
    
    
    df_events['temp_identity'] = df_events['user_id'].astype(str) + ' @ ' + df_events['ip']
    
    
    identity_access_map = {
        (row['ip'], row['user_id']): color_map.get(row['identity'])['color']
        for index, row in df_access.iterrows()
    }
    
    fig, ax = plt.subplots(figsize=(15, 8))
    
    plotted_labels = set()
    
    for (ip, user_id), group in df_events.groupby(['ip', 'user_id']):
        
        
        color = identity_access_map.get((ip, user_id), 'gray') 
        base_label = f'{user_id} @ {ip}'
        
       
        ax.plot(group['timestamp'], group['event_type'], 'o', 
                markersize=8, alpha=0.7, color=color, 
                label=base_label if base_label not in plotted_labels else "")
        plotted_labels.add(base_label)

    ax.set_title('Application Event Timeline by User Identity')
    ax.set_xlabel('Time')
    ax.set_ylabel('Event Type (Success/Failure)')
    
    ax.legend(title='Identity (ID@IP)', loc='upper left', bbox_to_anchor=(1.05, 1), fontsize='small')
    ax.grid(True, axis='x', linestyle='--')
    fig.autofmt_xdate()
    plt.tight_layout(rect=[0, 0, 0.8, 1])
    pdf.savefig(fig)
    plt.close(fig)
    print("Generated: Colored Event Timeline.")


def plot_statistics(df_access, pdf):
    """Generates bar charts for key statistics: Browsers and Top Users."""
    
    
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

# --- Main Execution ---

if __name__ == '__main__':
    
   
    df_access = load_data(INPUT_ACCESS_CSV)
    df_events = load_data(INPUT_EVENTS_CSV)

    if df_access is None and df_events is None:
        print("Cannot proceed. No data files were successfully loaded.")
        sys.exit(1)

    print("\n--- Generating Plots ---")
    
   
    if df_access is not None and not df_access.empty:
        identity_color_map = create_identity_map(df_access)
    else:
        identity_color_map = {}

    with PdfPages(OUTPUT_PDF) as pdf:
        
      
        if df_access is not None and not df_access.empty:
            plot_access_timeline_colored(df_access, pdf, identity_color_map)

       
        if df_events is not None and not df_events.empty and df_access is not None and not df_access.empty:
            
            plot_event_timeline_colored(df_events, pdf, identity_color_map)
        elif df_events is not None and not df_events.empty:
             print("Warning: Cannot use consistent coloring for events; access data is missing.")


       
        if df_access is not None and not df_access.empty:
            plot_statistics(df_access, pdf)
        
        print(f"\nSuccess! All diagrams saved to {OUTPUT_PDF}")