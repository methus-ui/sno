#!/bin/bash

# Snocart Local POS - ONE-CLICK SETUP (FIXED)
# This script handles EVERYTHING automatically

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}"
echo "╔════════════════════════════════════════════════════════════╗"
echo "║                                                            ║"
echo "║        SNOCART LOCAL POS - ONE-CLICK INSTALLER            ║"
echo "║                                                            ║"
echo "╚════════════════════════════════════════════════════════════╝"
echo -e "${NC}"

# Function to print colored messages
print_success() { echo -e "${GREEN}✓ $1${NC}"; }
print_error() { echo -e "${RED}✗ $1${NC}"; }
print_info() { echo -e "${BLUE}ℹ $1${NC}"; }
print_warning() { echo -e "${YELLOW}⚠ $1${NC}"; }

# Check if Docker is installed
print_info "Checking Docker installation..."
if ! command -v docker &> /dev/null; then
    print_error "Docker is not installed!"
    echo ""
    echo "Please install Docker Desktop:"
    echo "  Mac: https://www.docker.com/products/docker-desktop"
    echo "  Linux: curl -fsSL https://get.docker.com | sh"
    exit 1
fi

if ! docker info &> /dev/null; then
    print_error "Docker is not running!"
    echo ""
    echo "Please start Docker Desktop and try again."
    exit 1
fi

print_success "Docker is installed and running"

# Clean up any existing containers with same names
print_info "Cleaning up old containers (if any)..."
docker stop snocart_pos_db snocart_pos_odoo snocart_pos_sync 2>/dev/null || true
docker rm snocart_pos_db snocart_pos_odoo snocart_pos_sync 2>/dev/null || true
print_success "Cleanup complete"

# Get API key from user or .env
if [ -f .env ]; then
    source .env
fi

if [ -z "$SNOCART_API_KEY" ]; then
    echo ""
    echo -e "${YELLOW}Enter your Snocart API Key:${NC}"
    echo "(Get this from: https://new.snocart.com/store-panel)"
    read -r SNOCART_API_KEY

    if [ -z "$SNOCART_API_KEY" ]; then
        print_error "API Key is required!"
        exit 1
    fi
fi

# Set admin password
if [ -z "$ODOO_ADMIN_PASSWORD" ]; then
    ODOO_ADMIN_PASSWORD="Aclass@2425"
fi

# Create .env file
print_info "Creating configuration..."
cat > .env << EOF
SNOCART_API_KEY=${SNOCART_API_KEY}
SNOCART_API_URL=https://new.snocart.com
ODOO_ADMIN_PASSWORD=${ODOO_ADMIN_PASSWORD}
SYNC_INTERVAL=300
LOG_LEVEL=INFO
EOF
print_success ".env file created"

# Create clean odoo.conf (NO inline comments, NO variables)
print_info "Creating Odoo configuration..."
cat > config/odoo.conf << 'EOF'
[options]
db_host = postgres
db_port = 5432
db_user = odoo
db_password = odoo_secure_password_2024
db_name = odoo_pos
http_port = 8069
longpolling_port = 8072
workers = 2
max_cron_threads = 1
limit_memory_hard = 2684354560
limit_memory_soft = 2147483648
limit_request = 8192
limit_time_cpu = 600
limit_time_real = 1200
logfile = /var/lib/odoo/odoo.log
log_level = info
log_handler = :INFO
addons_path = /usr/lib/python3/dist-packages/odoo/addons,/mnt/extra-addons
data_dir = /var/lib/odoo
session_timeout = 28800
admin_passwd = Aclass@2425
list_db = False
db_maxconn = 64
db_template = template0
without_demo = all
server_wide_modules = base,web,point_of_sale
csv_internal_sep = ,
proxy_mode = False
load_language = en_US
translate_modules = point_of_sale
EOF
print_success "Odoo config created"

# Create sync_config.json
print_info "Creating sync configuration..."
cat > config/sync_config.json << EOF
{
  "api_url": "https://new.snocart.com",
  "api_key": "${SNOCART_API_KEY}",
  "db_host": "postgres",
  "db_port": "5432",
  "db_name": "odoo_pos",
  "db_user": "odoo",
  "db_password": "odoo_secure_password_2024"
}
EOF
print_success "Sync config created"

# Start containers
print_info "Starting Docker containers..."
echo "This will take 2-3 minutes on first run (downloading images)..."
docker compose up -d

# Wait for database to be healthy
print_info "Waiting for database to start..."
timeout 60 bash -c 'until docker compose exec -T postgres pg_isready -U odoo &>/dev/null; do sleep 2; done' || {
    print_error "Database failed to start!"
    docker compose logs postgres
    exit 1
}
print_success "Database is ready"

# Wait for Odoo initialization
print_info "Initializing Odoo POS (this takes 2-3 minutes)..."
print_info "Installing 53 modules including Point of Sale..."

# Function to check if Odoo is responding
check_odoo() {
    curl -s -o /dev/null -w "%{http_code}" http://localhost:8069/web 2>/dev/null
}

# Wait up to 5 minutes for Odoo to be ready
TIMEOUT=300
ELAPSED=0
while [ $ELAPSED -lt $TIMEOUT ]; do
    HTTP_CODE=$(check_odoo)
    if [ "$HTTP_CODE" = "200" ] || [ "$HTTP_CODE" = "303" ]; then
        print_success "Odoo is ready!"
        break
    fi

    if [ $((ELAPSED % 30)) -eq 0 ]; then
        print_info "Still initializing... ($ELAPSED seconds elapsed)"
    fi

    sleep 5
    ELAPSED=$((ELAPSED + 5))
done

if [ $ELAPSED -ge $TIMEOUT ]; then
    print_error "Odoo failed to start within 5 minutes"
    print_info "Check logs: docker compose logs odoo"
    exit 1
fi

# Final status check
print_info "Verifying all services..."
sleep 5

echo ""
echo -e "${GREEN}╔════════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║                                                            ║${NC}"
echo -e "${GREEN}║           ✓ INSTALLATION COMPLETE! ✓                      ║${NC}"
echo -e "${GREEN}║                                                            ║${NC}"
echo -e "${GREEN}╚════════════════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "${BLUE}🚀 Your Odoo POS is ready!${NC}"
echo ""
echo -e "${YELLOW}Access your POS:${NC}"
echo "  URL:      http://localhost:8069"
echo "  Database: odoo_pos"
echo "  Username: admin"
echo "  Password: ${ODOO_ADMIN_PASSWORD}"
echo ""
echo -e "${YELLOW}Useful commands:${NC}"
echo "  Stop:     docker compose stop"
echo "  Start:    docker compose start"
echo "  Logs:     docker compose logs -f odoo"
echo "  Status:   docker compose ps"
echo ""
echo -e "${GREEN}Happy selling! 🎉${NC}"
echo ""
