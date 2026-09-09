#!/bin/bash

################################################################################
# Odoo 17 Multi-Tenant POS Server Installation Script
#
# This script automates the installation of Odoo 17 Community Edition
# for Snocart's One-Click POS Integration.
#
# Requirements:
# - Ubuntu 22.04 LTS
# - 8GB RAM minimum (16GB recommended)
# - 4 CPU cores
# - 100GB SSD storage
# - Root or sudo access
#
# Usage:
#   chmod +x install-odoo-server.sh
#   sudo ./install-odoo-server.sh
#
################################################################################

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
ODOO_USER="odoo"
ODOO_HOME="/opt/odoo"
ODOO_VERSION="17.0"
ODOO_PORT="8069"
POSTGRES_USER="odoo"
DOMAIN="odoo.snocart.com"  # Change this to your domain

# Logging
LOG_FILE="/var/log/odoo-installation.log"
exec 1> >(tee -a "$LOG_FILE")
exec 2>&1

################################################################################
# Helper Functions
################################################################################

print_header() {
    echo -e "\n${BLUE}═══════════════════════════════════════════════════════════${NC}"
    echo -e "${BLUE}  $1${NC}"
    echo -e "${BLUE}═══════════════════════════════════════════════════════════${NC}\n"
}

print_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

print_error() {
    echo -e "${RED}✗ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠ $1${NC}"
}

print_info() {
    echo -e "${BLUE}ℹ $1${NC}"
}

check_root() {
    if [[ $EUID -ne 0 ]]; then
        print_error "This script must be run as root or with sudo"
        exit 1
    fi
}

check_ubuntu() {
    if ! grep -q "Ubuntu 22.04" /etc/os-release; then
        print_warning "This script is designed for Ubuntu 22.04. Continue anyway? (y/n)"
        read -r response
        if [[ ! "$response" =~ ^[Yy]$ ]]; then
            exit 1
        fi
    fi
}

check_resources() {
    print_header "Checking System Resources"

    # Check RAM
    total_ram=$(free -g | awk '/^Mem:/{print $2}')
    if [ "$total_ram" -lt 8 ]; then
        print_warning "System has ${total_ram}GB RAM. 8GB minimum recommended."
        print_warning "Continue anyway? (y/n)"
        read -r response
        if [[ ! "$response" =~ ^[Yy]$ ]]; then
            exit 1
        fi
    else
        print_success "RAM: ${total_ram}GB (OK)"
    fi

    # Check CPU cores
    cpu_cores=$(nproc)
    if [ "$cpu_cores" -lt 4 ]; then
        print_warning "System has ${cpu_cores} CPU cores. 4 cores recommended."
    else
        print_success "CPU Cores: ${cpu_cores} (OK)"
    fi

    # Check disk space
    disk_space=$(df -BG / | awk 'NR==2 {print $4}' | sed 's/G//')
    if [ "$disk_space" -lt 50 ]; then
        print_warning "Available disk space: ${disk_space}GB. 100GB recommended."
    else
        print_success "Disk Space: ${disk_space}GB available (OK)"
    fi
}

################################################################################
# Installation Steps
################################################################################

step1_update_system() {
    print_header "Step 1: Updating System Packages"

    apt-get update
    apt-get upgrade -y

    print_success "System updated successfully"
}

step2_install_dependencies() {
    print_header "Step 2: Installing Dependencies"

    print_info "Installing PostgreSQL..."
    apt-get install -y postgresql postgresql-client

    print_info "Installing Python and development tools..."
    apt-get install -y python3 python3-pip python3-dev python3-venv \
        build-essential wget git

    print_info "Installing system libraries..."
    apt-get install -y libxml2-dev libxslt1-dev libldap2-dev libsasl2-dev \
        libtiff5-dev libjpeg8-dev libopenjp2-7-dev zlib1g-dev \
        libfreetype6-dev liblcms2-dev libwebp-dev libharfbuzz-dev \
        libfribidi-dev libxcb1-dev libpq-dev libssl-dev \
        node-less npm

    print_success "Dependencies installed successfully"
}

step3_install_wkhtmltopdf() {
    print_header "Step 3: Installing wkhtmltopdf (for PDF receipts)"

    cd /tmp
    wget -q https://github.com/wkhtmltopdf/packaging/releases/download/0.12.6-1/wkhtmltox_0.12.6-1.focal_amd64.deb
    apt-get install -y ./wkhtmltox_0.12.6-1.focal_amd64.deb
    rm wkhtmltox_0.12.6-1.focal_amd64.deb

    print_success "wkhtmltopdf installed successfully"
}

step4_create_odoo_user() {
    print_header "Step 4: Creating Odoo System User"

    if id "$ODOO_USER" &>/dev/null; then
        print_warning "User $ODOO_USER already exists. Skipping..."
    else
        useradd -m -d "$ODOO_HOME" -U -r -s /bin/bash "$ODOO_USER"
        print_success "User $ODOO_USER created successfully"
    fi
}

step5_install_odoo() {
    print_header "Step 5: Installing Odoo $ODOO_VERSION"

    print_info "Cloning Odoo repository..."
    if [ -d "$ODOO_HOME/odoo$ODOO_VERSION" ]; then
        print_warning "Odoo directory already exists. Skipping clone..."
    else
        sudo -u "$ODOO_USER" git clone https://www.github.com/odoo/odoo --depth 1 \
            --branch "$ODOO_VERSION" "$ODOO_HOME/odoo$ODOO_VERSION"
        print_success "Odoo repository cloned"
    fi

    print_info "Installing Python dependencies (this may take 5-10 minutes)..."
    pip3 install wheel
    pip3 install -r "$ODOO_HOME/odoo$ODOO_VERSION/requirements.txt"

    print_success "Odoo installed successfully"
}

step6_configure_postgresql() {
    print_header "Step 6: Configuring PostgreSQL"

    # Start PostgreSQL if not running
    systemctl start postgresql
    systemctl enable postgresql

    # Create PostgreSQL user for Odoo
    if sudo -u postgres psql -tAc "SELECT 1 FROM pg_roles WHERE rolname='$POSTGRES_USER'" | grep -q 1; then
        print_warning "PostgreSQL user $POSTGRES_USER already exists. Skipping..."
    else
        sudo -u postgres createuser -s "$POSTGRES_USER"
        print_success "PostgreSQL user $POSTGRES_USER created"
    fi

    # Generate random password for PostgreSQL user
    POSTGRES_PASSWORD=$(openssl rand -base64 32)
    sudo -u postgres psql -c "ALTER USER $POSTGRES_USER WITH PASSWORD '$POSTGRES_PASSWORD';"

    print_success "PostgreSQL configured successfully"
    print_info "PostgreSQL Password: $POSTGRES_PASSWORD"
    echo "$POSTGRES_PASSWORD" > /root/.odoo_postgres_password
    chmod 600 /root/.odoo_postgres_password
}

step7_create_odoo_config() {
    print_header "Step 7: Creating Odoo Configuration"

    # Generate master password
    ODOO_MASTER_PASSWORD=$(openssl rand -base64 32)

    # Create log directory
    mkdir -p /var/log/odoo
    chown "$ODOO_USER:$ODOO_USER" /var/log/odoo

    # Get PostgreSQL password
    POSTGRES_PASSWORD=$(cat /root/.odoo_postgres_password)

    # Create configuration file
    cat > /etc/odoo.conf <<EOF
[options]
# Security
admin_passwd = $ODOO_MASTER_PASSWORD

# Database
db_host = localhost
db_port = 5432
db_user = $POSTGRES_USER
db_password = $POSTGRES_PASSWORD

# Server
http_port = $ODOO_PORT
logfile = /var/log/odoo/odoo.log
addons_path = $ODOO_HOME/odoo$ODOO_VERSION/addons

# Performance
workers = 4
max_cron_threads = 2
limit_memory_hard = 2684354560
limit_memory_soft = 2147483648
limit_request = 8192
limit_time_cpu = 600
limit_time_real = 1200

# Multi-Tenant Configuration (CRITICAL)
dbfilter = ^odoo_vendor_.*$
list_db = False
EOF

    chmod 640 /etc/odoo.conf
    chown "$ODOO_USER:$ODOO_USER" /etc/odoo.conf

    print_success "Odoo configuration created"
    print_info "Master Password: $ODOO_MASTER_PASSWORD"
    echo "$ODOO_MASTER_PASSWORD" > /root/.odoo_master_password
    chmod 600 /root/.odoo_master_password
}

step8_create_systemd_service() {
    print_header "Step 8: Creating systemd Service"

    cat > /etc/systemd/system/odoo.service <<EOF
[Unit]
Description=Odoo Multi-Tenant POS Server
Requires=postgresql.service
After=network.target postgresql.service

[Service]
Type=simple
User=$ODOO_USER
Group=$ODOO_USER
ExecStart=/usr/bin/python3 $ODOO_HOME/odoo$ODOO_VERSION/odoo-bin -c /etc/odoo.conf
Restart=always
RestartSec=10
StandardOutput=journal
StandardError=journal

[Install]
WantedBy=multi-user.target
EOF

    systemctl daemon-reload
    systemctl enable odoo

    print_success "systemd service created"
}

step9_install_nginx() {
    print_header "Step 9: Installing and Configuring Nginx"

    print_info "Installing Nginx..."
    apt-get install -y nginx certbot python3-certbot-nginx

    print_info "Creating Nginx configuration..."
    cat > /etc/nginx/sites-available/odoo <<EOF
upstream odoo {
    server 127.0.0.1:$ODOO_PORT;
}

server {
    listen 80;
    server_name $DOMAIN;

    # Allow certbot verification
    location /.well-known/acme-challenge/ {
        root /var/www/html;
    }

    # Redirect all other traffic to HTTPS
    location / {
        return 301 https://\$server_name\$request_uri;
    }
}

server {
    listen 443 ssl http2;
    server_name $DOMAIN;

    # SSL will be configured by certbot
    # ssl_certificate /etc/letsencrypt/live/$DOMAIN/fullchain.pem;
    # ssl_certificate_key /etc/letsencrypt/live/$DOMAIN/privkey.pem;

    access_log /var/log/nginx/odoo_access.log;
    error_log /var/log/nginx/odoo_error.log;

    proxy_read_timeout 720s;
    proxy_connect_timeout 720s;
    proxy_send_timeout 720s;

    # Proxy headers
    proxy_set_header X-Forwarded-Host \$host;
    proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto \$scheme;
    proxy_set_header X-Real-IP \$remote_addr;

    # Increase buffer size
    proxy_buffers 16 64k;
    proxy_buffer_size 128k;

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
EOF

    # Enable site
    ln -sf /etc/nginx/sites-available/odoo /etc/nginx/sites-enabled/

    # Test Nginx configuration
    nginx -t

    print_success "Nginx configured successfully"
}

step10_start_odoo() {
    print_header "Step 10: Starting Odoo Service"

    print_info "Starting Odoo..."
    systemctl start odoo

    # Wait for Odoo to start
    sleep 10

    # Check if Odoo is running
    if systemctl is-active --quiet odoo; then
        print_success "Odoo service started successfully"
    else
        print_error "Odoo service failed to start. Check logs: journalctl -u odoo -n 50"
        exit 1
    fi

    systemctl restart nginx
    print_success "Nginx restarted"
}

step11_configure_ssl() {
    print_header "Step 11: Configuring SSL Certificate"

    print_warning "Before proceeding with SSL configuration:"
    print_info "1. Ensure domain '$DOMAIN' points to this server's IP address"
    print_info "2. Port 80 and 443 must be open in firewall"
    print_info ""
    print_warning "Do you want to configure SSL now? (y/n)"
    read -r response

    if [[ "$response" =~ ^[Yy]$ ]]; then
        print_info "Obtaining SSL certificate from Let's Encrypt..."
        certbot --nginx -d "$DOMAIN" --non-interactive --agree-tos --email admin@snocart.com

        # Test auto-renewal
        certbot renew --dry-run

        print_success "SSL certificate configured successfully"
    else
        print_warning "SSL configuration skipped. You can run this later:"
        print_info "sudo certbot --nginx -d $DOMAIN"
    fi
}

step12_configure_firewall() {
    print_header "Step 12: Configuring Firewall"

    if command -v ufw &> /dev/null; then
        print_info "Configuring UFW firewall..."
        ufw allow 22/tcp
        ufw allow 80/tcp
        ufw allow 443/tcp
        ufw --force enable
        print_success "Firewall configured"
    else
        print_warning "UFW not installed. Make sure ports 22, 80, 443 are open."
    fi
}

################################################################################
# Post-Installation
################################################################################

print_installation_summary() {
    print_header "Installation Complete!"

    echo ""
    print_success "Odoo $ODOO_VERSION installed successfully"
    echo ""
    echo -e "${GREEN}═══════════════════════════════════════════════════════════${NC}"
    echo -e "${GREEN}  IMPORTANT: Save These Credentials${NC}"
    echo -e "${GREEN}═══════════════════════════════════════════════════════════${NC}"
    echo ""
    echo "Odoo Master Password: $(cat /root/.odoo_master_password)"
    echo "PostgreSQL User: $POSTGRES_USER"
    echo "PostgreSQL Password: $(cat /root/.odoo_postgres_password)"
    echo ""
    echo "Credentials saved to:"
    echo "  - /root/.odoo_master_password"
    echo "  - /root/.odoo_postgres_password"
    echo ""
    echo -e "${GREEN}═══════════════════════════════════════════════════════════${NC}"
    echo ""

    print_info "Access Odoo at:"
    echo "  - Local: http://localhost:$ODOO_PORT"
    echo "  - Domain: https://$DOMAIN (after SSL setup)"
    echo ""

    print_info "Useful Commands:"
    echo "  - Check status: sudo systemctl status odoo"
    echo "  - View logs: sudo journalctl -u odoo -f"
    echo "  - Restart: sudo systemctl restart odoo"
    echo "  - Stop: sudo systemctl stop odoo"
    echo ""

    print_info "Next Steps:"
    echo "  1. Update Laravel .env with Odoo credentials"
    echo "  2. Test database creation via API"
    echo "  3. Enable POS for a test vendor"
    echo ""

    print_info "Configuration stored in /etc/odoo.conf"
    print_info "Logs stored in /var/log/odoo/odoo.log"
    echo ""
}

################################################################################
# Main Installation Flow
################################################################################

main() {
    clear

    cat << "EOF"
╔═══════════════════════════════════════════════════════════════════╗
║                                                                   ║
║       Odoo 17 Multi-Tenant POS Server Installation Script        ║
║                      For Snocart POS Integration                  ║
║                                                                   ║
╚═══════════════════════════════════════════════════════════════════╝
EOF

    echo ""
    print_info "This script will install and configure Odoo 17 Community Edition"
    print_info "Installation log: $LOG_FILE"
    echo ""

    # Pre-flight checks
    check_root
    check_ubuntu
    check_resources

    print_warning "Ready to begin installation. Continue? (y/n)"
    read -r response
    if [[ ! "$response" =~ ^[Yy]$ ]]; then
        print_info "Installation cancelled"
        exit 0
    fi

    # Installation steps
    step1_update_system
    step2_install_dependencies
    step3_install_wkhtmltopdf
    step4_create_odoo_user
    step5_install_odoo
    step6_configure_postgresql
    step7_create_odoo_config
    step8_create_systemd_service
    step9_install_nginx
    step10_start_odoo
    step11_configure_ssl
    step12_configure_firewall

    # Summary
    print_installation_summary
}

# Run installation
main
