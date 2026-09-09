# Odoo Server Installation - Manual Guide

This guide provides step-by-step manual installation instructions for Odoo 17 Community Edition server.

## Prerequisites Checklist

Before starting, ensure you have:

- [ ] **Server Requirements Met**
  - Ubuntu 22.04 LTS
  - 8GB RAM minimum (16GB recommended for 50+ vendors)
  - 4 CPU cores
  - 100GB SSD storage
  - Root or sudo access

- [ ] **Domain Name Ready**
  - Domain configured (e.g., `odoo.snocart.com`)
  - DNS A record pointing to server IP
  - Ports 80, 443 open in firewall

- [ ] **SSH Access**
  - SSH key or password access
  - Firewall allows port 22

## Installation Methods

### Method 1: Automated Script (Recommended)

**Fastest way - Completes in ~15 minutes:**

```bash
# 1. Download the installation script
cd /var/www/html/new_public/new/scripts
chmod +x install-odoo-server.sh

# 2. Edit the domain name in the script
nano install-odoo-server.sh
# Change: DOMAIN="odoo.snocart.com" to your actual domain

# 3. Run the installation
sudo ./install-odoo-server.sh

# 4. Save the credentials shown at the end!
```

The script will:
- Install all dependencies
- Download and install Odoo 17
- Configure PostgreSQL
- Setup systemd service
- Install and configure Nginx
- Setup SSL certificate (optional)
- Configure firewall

**Credentials will be saved to:**
- Master Password: `/root/.odoo_master_password`
- PostgreSQL Password: `/root/.odoo_postgres_password`

---

### Method 2: Manual Installation

If you prefer to understand each step or customize the installation:

## Step 1: Update System (5 minutes)

```bash
# Update package list
sudo apt update

# Upgrade existing packages
sudo apt upgrade -y

# Verify Ubuntu version
lsb_release -a
# Should show: Ubuntu 22.04
```

## Step 2: Install Dependencies (10 minutes)

### PostgreSQL Database

```bash
# Install PostgreSQL
sudo apt install -y postgresql postgresql-client

# Start and enable PostgreSQL
sudo systemctl start postgresql
sudo systemctl enable postgresql

# Verify installation
sudo systemctl status postgresql
```

### Python and Development Tools

```bash
# Install Python 3 and pip
sudo apt install -y python3 python3-pip python3-dev python3-venv \
    build-essential wget git

# Verify Python version
python3 --version
# Should show: Python 3.10.x or higher
```

### System Libraries

```bash
# Install required libraries for Odoo
sudo apt install -y \
    libxml2-dev libxslt1-dev \
    libldap2-dev libsasl2-dev \
    libtiff5-dev libjpeg8-dev libopenjp2-7-dev \
    zlib1g-dev libfreetype6-dev liblcms2-dev \
    libwebp-dev libharfbuzz-dev libfribidi-dev \
    libxcb1-dev libpq-dev libssl-dev \
    node-less npm

# This may take 5-10 minutes
```

### wkhtmltopdf (for PDF receipts)

```bash
# Download wkhtmltopdf
cd /tmp
wget https://github.com/wkhtmltopdf/packaging/releases/download/0.12.6-1/wkhtmltox_0.12.6-1.focal_amd64.deb

# Install
sudo apt install -y ./wkhtmltox_0.12.6-1.focal_amd64.deb

# Verify installation
wkhtmltopdf --version

# Clean up
rm wkhtmltox_0.12.6-1.focal_amd64.deb
```

## Step 3: Create Odoo System User (1 minute)

```bash
# Create odoo user with home directory
sudo useradd -m -d /opt/odoo -U -r -s /bin/bash odoo

# Verify user created
id odoo
# Should show: uid=... gid=... groups=odoo
```

## Step 4: Install Odoo 17 (15 minutes)

### Clone Odoo Repository

```bash
# Switch to odoo user
sudo su - odoo

# Clone Odoo 17 (this may take 5-10 minutes)
git clone https://www.github.com/odoo/odoo --depth 1 --branch 17.0 /opt/odoo/odoo17

# Verify clone
ls -la /opt/odoo/odoo17
# Should show: odoo-bin, addons/, requirements.txt, etc.
```

### Install Python Dependencies

```bash
# Still as odoo user
cd /opt/odoo/odoo17

# Upgrade pip
python3 -m pip install --upgrade pip

# Install wheel (speeds up installation)
pip3 install wheel

# Install Odoo requirements (this takes 10-15 minutes)
pip3 install -r requirements.txt

# Exit back to root user
exit
```

**Note:** If you see errors about specific packages failing, you may need to install additional system libraries. Common issues:
- `psycopg2` error: `sudo apt install libpq-dev`
- `lxml` error: `sudo apt install libxml2-dev libxslt1-dev`
- `Pillow` error: `sudo apt install libjpeg-dev zlib1g-dev`

## Step 5: Configure PostgreSQL (3 minutes)

### Create PostgreSQL User

```bash
# Create PostgreSQL user for Odoo
sudo -u postgres createuser -s odoo

# Set password for PostgreSQL user
sudo -u postgres psql

# In PostgreSQL shell:
ALTER USER odoo WITH PASSWORD 'your_secure_password_here';
\q

# Test connection
psql -U odoo -h localhost -d postgres
# Enter password when prompted
\q
```

**IMPORTANT:** Save the PostgreSQL password securely!

## Step 6: Create Odoo Configuration (5 minutes)

### Generate Master Password

```bash
# Generate a secure master password
MASTER_PASSWORD=$(openssl rand -base64 32)
echo "Master Password: $MASTER_PASSWORD"

# Save it securely
echo "$MASTER_PASSWORD" > /root/.odoo_master_password
chmod 600 /root/.odoo_master_password
```

### Create Log Directory

```bash
sudo mkdir -p /var/log/odoo
sudo chown odoo:odoo /var/log/odoo
```

### Create Configuration File

```bash
sudo nano /etc/odoo.conf
```

**Configuration file contents:**

```ini
[options]
# Security - Master password for database management
admin_passwd = YOUR_MASTER_PASSWORD_HERE

# Database Configuration
db_host = localhost
db_port = 5432
db_user = odoo
db_password = YOUR_POSTGRES_PASSWORD_HERE

# Server Configuration
http_port = 8069
logfile = /var/log/odoo/odoo.log
addons_path = /opt/odoo/odoo17/addons

# Performance Settings
workers = 4
max_cron_threads = 2
limit_memory_hard = 2684354560
limit_memory_soft = 2147483648
limit_request = 8192
limit_time_cpu = 600
limit_time_real = 1200

# Multi-Tenant Configuration (CRITICAL FOR SNOCART)
# This regex ensures only databases starting with "odoo_vendor_" are accessible
dbfilter = ^odoo_vendor_.*$

# Disable database listing for security
list_db = False
```

**Replace:**
- `YOUR_MASTER_PASSWORD_HERE` with the generated master password
- `YOUR_POSTGRES_PASSWORD_HERE` with the PostgreSQL password

**Save and set permissions:**

```bash
sudo chmod 640 /etc/odoo.conf
sudo chown odoo:odoo /etc/odoo.conf
```

## Step 7: Create systemd Service (3 minutes)

### Create Service File

```bash
sudo nano /etc/systemd/system/odoo.service
```

**Service file contents:**

```ini
[Unit]
Description=Odoo Multi-Tenant POS Server
Requires=postgresql.service
After=network.target postgresql.service

[Service]
Type=simple
User=odoo
Group=odoo
ExecStart=/usr/bin/python3 /opt/odoo/odoo17/odoo-bin -c /etc/odoo.conf
Restart=always
RestartSec=10
StandardOutput=journal
StandardError=journal

[Install]
WantedBy=multi-user.target
```

**Save and enable service:**

```bash
# Reload systemd to recognize new service
sudo systemctl daemon-reload

# Enable service to start on boot
sudo systemctl enable odoo

# Verify service file is valid
sudo systemctl status odoo
```

## Step 8: Start Odoo (2 minutes)

```bash
# Start Odoo service
sudo systemctl start odoo

# Wait a few seconds for startup
sleep 10

# Check if Odoo is running
sudo systemctl status odoo

# Should show: "Active: active (running)"

# View live logs
sudo journalctl -u odoo -f
# Press Ctrl+C to exit log view
```

**Verify Odoo is listening:**

```bash
# Check if Odoo is listening on port 8069
sudo netstat -tulpn | grep 8069

# Should show: tcp ... 0.0.0.0:8069 ... LISTEN ... python3

# Test local access
curl http://localhost:8069/web/database/selector

# Should return HTML content (Odoo database selector page)
```

## Step 9: Install and Configure Nginx (10 minutes)

### Install Nginx and Certbot

```bash
# Install Nginx
sudo apt install -y nginx

# Install Certbot for SSL certificates
sudo apt install -y certbot python3-certbot-nginx

# Verify Nginx is running
sudo systemctl status nginx
```

### Create Nginx Configuration

```bash
sudo nano /etc/nginx/sites-available/odoo
```

**Nginx configuration:**

```nginx
upstream odoo {
    server 127.0.0.1:8069;
}

# HTTP server - redirects to HTTPS
server {
    listen 80;
    server_name odoo.snocart.com;  # Change to your domain

    # Allow certbot verification
    location /.well-known/acme-challenge/ {
        root /var/www/html;
    }

    # Redirect all other traffic to HTTPS
    location / {
        return 301 https://$server_name$request_uri;
    }
}

# HTTPS server
server {
    listen 443 ssl http2;
    server_name odoo.snocart.com;  # Change to your domain

    # SSL certificates (will be configured by certbot)
    ssl_certificate /etc/letsencrypt/live/odoo.snocart.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/odoo.snocart.com/privkey.pem;

    # Logs
    access_log /var/log/nginx/odoo_access.log;
    error_log /var/log/nginx/odoo_error.log;

    # Proxy timeouts (important for long operations)
    proxy_read_timeout 720s;
    proxy_connect_timeout 720s;
    proxy_send_timeout 720s;

    # Proxy headers
    proxy_set_header X-Forwarded-Host $host;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto $scheme;
    proxy_set_header X-Real-IP $remote_addr;

    # Proxy buffers
    proxy_buffers 16 64k;
    proxy_buffer_size 128k;

    # Main location
    location / {
        proxy_pass http://odoo;
        proxy_redirect off;
    }

    # Long polling for live updates
    location /longpolling {
        proxy_pass http://odoo;
    }

    # Gzip compression
    gzip on;
    gzip_types text/css text/scss text/plain text/xml application/xml application/json application/javascript;
}
```

**Enable site and test configuration:**

```bash
# Create symbolic link to enable site
sudo ln -s /etc/nginx/sites-available/odoo /etc/nginx/sites-enabled/

# Test Nginx configuration
sudo nginx -t

# Should show: "test is successful"
```

**Note:** Don't restart Nginx yet - we need to get SSL certificate first.

## Step 10: Configure SSL Certificate (5 minutes)

**IMPORTANT:** Before running certbot, ensure:
1. Domain DNS points to server IP address
2. Ports 80 and 443 are open in firewall

```bash
# Obtain SSL certificate from Let's Encrypt
sudo certbot --nginx -d odoo.snocart.com

# Follow the prompts:
# - Enter email address
# - Agree to Terms of Service (yes)
# - Certbot will automatically configure Nginx

# Test auto-renewal
sudo certbot renew --dry-run

# Should show: "Congratulations, all simulated renewals succeeded"
```

**Restart Nginx:**

```bash
sudo systemctl restart nginx

# Verify Nginx is running
sudo systemctl status nginx
```

## Step 11: Configure Firewall (2 minutes)

```bash
# Install UFW if not already installed
sudo apt install -y ufw

# Allow SSH (IMPORTANT: do this first!)
sudo ufw allow 22/tcp

# Allow HTTP and HTTPS
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp

# Enable firewall
sudo ufw enable

# Verify firewall status
sudo ufw status

# Should show:
# 22/tcp    ALLOW    Anywhere
# 80/tcp    ALLOW    Anywhere
# 443/tcp   ALLOW    Anywhere
```

## Step 12: Verify Installation (5 minutes)

### Test Local Access

```bash
# Test Odoo directly (port 8069)
curl -I http://localhost:8069

# Should return: HTTP/1.0 303 SEE OTHER
```

### Test Domain Access

Open browser and visit: `https://odoo.snocart.com`

You should see:
- SSL certificate is valid (green lock)
- Odoo database management page
- "Create Database" form

**DO NOT create any database manually!** Databases will be created automatically by Laravel.

### Test Database Creation API

```bash
# Test XML-RPC endpoint
curl -I https://odoo.snocart.com/xmlrpc/2/db

# Should return: HTTP/2 200
```

### Check Logs

```bash
# View Odoo logs
sudo tail -f /var/log/odoo/odoo.log

# View Nginx access logs
sudo tail -f /var/log/nginx/odoo_access.log

# View Nginx error logs
sudo tail -f /var/log/nginx/odoo_error.log
```

## Post-Installation Tasks

### 1. Save Credentials

Create a secure document with:

```
Odoo Server Credentials
========================

Server IP: YOUR_SERVER_IP
Domain: odoo.snocart.com

Odoo Master Password: [from /root/.odoo_master_password]
PostgreSQL User: odoo
PostgreSQL Password: [your postgres password]

SSH Access:
  User: root
  Key: [your SSH key path]

Firewall Ports Open: 22, 80, 443
```

### 2. Update Laravel .env

On your Laravel server, update `/var/www/html/new_public/new/.env`:

```env
# Odoo POS Integration
ODOO_INTEGRATION_ENABLED=true
ODOO_URL=https://odoo.snocart.com
ODOO_MASTER_PASSWORD=your_master_password_here
```

```bash
# Clear Laravel config cache
cd /var/www/html/new_public/new
php artisan config:clear
```

### 3. Test Laravel → Odoo Connection

```bash
cd /var/www/html/new_public/new

php artisan tinker
```

In tinker:

```php
// Test Odoo connection
$service = new \App\Services\OdooIntegrationService();
$pingResult = $service->ping();
echo $pingResult ? "✓ Connection successful" : "✗ Connection failed";

// Test credentials
config('odoo.url');        // Should show: https://odoo.snocart.com
config('odoo.enabled');    // Should show: true
```

### 4. Enable POS for Test Vendor

Via Laravel tinker:

```php
$vendor = \App\Models\Store::first();
$deploymentService = new \App\Services\OdooDeploymentService();
$result = $deploymentService->enablePOSForVendor($vendor->id);
print_r($result);

// Should show: ['success' => true, 'message' => '...', 'credentials' => [...]]
```

If successful, you'll see a new database created:

```bash
sudo -u postgres psql -l | grep odoo_vendor_

# Should show: odoo_vendor_1 | odoo | ...
```

## Troubleshooting

### Issue: Odoo won't start

```bash
# Check logs
sudo journalctl -u odoo -n 100

# Common issues:
# 1. PostgreSQL not running
sudo systemctl status postgresql

# 2. Permission errors
sudo chown -R odoo:odoo /opt/odoo
sudo chown odoo:odoo /etc/odoo.conf

# 3. Port 8069 already in use
sudo netstat -tulpn | grep 8069
```

### Issue: Can't access domain

```bash
# Check DNS
nslookup odoo.snocart.com

# Should show your server IP

# Check Nginx
sudo nginx -t
sudo systemctl status nginx

# Check firewall
sudo ufw status
```

### Issue: SSL certificate failed

```bash
# Ensure DNS is correct
nslookup odoo.snocart.com

# Ensure ports are open
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp

# Try manual certbot
sudo certbot certonly --webroot -w /var/www/html -d odoo.snocart.com
```

### Issue: Database creation fails

```bash
# Check PostgreSQL
sudo -u postgres psql -c "SELECT version();"

# Check Odoo logs
sudo tail -f /var/log/odoo/odoo.log

# Test PostgreSQL connection
psql -U odoo -h localhost -d postgres
```

## Maintenance Commands

### Start/Stop/Restart Odoo

```bash
sudo systemctl start odoo
sudo systemctl stop odoo
sudo systemctl restart odoo
sudo systemctl status odoo
```

### View Logs

```bash
# Live logs
sudo journalctl -u odoo -f

# Last 100 lines
sudo journalctl -u odoo -n 100

# Odoo log file
sudo tail -f /var/log/odoo/odoo.log
```

### Update Odoo

```bash
# Stop Odoo
sudo systemctl stop odoo

# Update code
sudo su - odoo
cd /opt/odoo/odoo17
git pull origin 17.0
exit

# Update Python dependencies
pip3 install --upgrade -r /opt/odoo/odoo17/requirements.txt

# Restart Odoo
sudo systemctl start odoo
```

### Backup Databases

```bash
# Backup all Odoo databases
sudo -u postgres pg_dumpall > /root/odoo_backup_$(date +%Y%m%d).sql

# Backup specific vendor database
sudo -u postgres pg_dump odoo_vendor_1 > /root/odoo_vendor_1_$(date +%Y%m%d).sql
```

### Restore Database

```bash
# Restore specific database
sudo -u postgres psql < /root/odoo_vendor_1_20260224.sql
```

## Security Checklist

- [ ] Master password is strong and saved securely
- [ ] PostgreSQL password is strong and saved securely
- [ ] Firewall is enabled (ports 22, 80, 443 only)
- [ ] SSL certificate is valid
- [ ] Database listing is disabled (`list_db = False`)
- [ ] Database filter is configured (`dbfilter = ^odoo_vendor_.*$`)
- [ ] Nginx is configured with proper headers
- [ ] Auto-renewal for SSL is working
- [ ] Regular backups are scheduled

## Performance Tuning (Optional)

For better performance with 50+ vendors:

```bash
# Edit Odoo config
sudo nano /etc/odoo.conf

# Increase workers (number of CPU cores)
workers = 8

# Increase memory limits
limit_memory_hard = 4294967296  # 4GB
limit_memory_soft = 3221225472  # 3GB

# Restart Odoo
sudo systemctl restart odoo
```

## Next Steps

1. ✅ Odoo server installed and running
2. ✅ SSL certificate configured
3. ✅ Laravel connected to Odoo
4. ⏭️ Build admin UI (Enable POS button)
5. ⏭️ Build vendor UI (Open POS button)
6. ⏭️ Test with pilot vendors

---

**Installation Time:** ~45 minutes (manual) or ~15 minutes (automated script)

**Status:** Production-ready for Snocart POS integration
