#!/bin/bash

# Snocart Local POS Setup Script
# This script sets up Snocart POS on your local machine using Docker

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# ASCII Art Banner
echo -e "${BLUE}"
cat << "EOF"
   _____ _   _  ____   _____          _____ _______
  / ____| \ | |/ __ \ / ____|   /\   |  __ \__   __|
 | (___ |  \| | |  | | |       /  \  | |__) | | |
  \___ \| . ` | |  | | |      / /\ \ |  _  /  | |
  ____) | |\  | |__| | |____ / ____ \| | \ \  | |
 |_____/|_| \_|\____/ \_____/_/    \_\_|  \_\ |_|

         Local POS System - Docker Installation
EOF
echo -e "${NC}"

# Function to print colored messages
print_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Function to check if command exists
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# Step 1: Check prerequisites
print_info "Checking prerequisites..."

# Check Docker
if ! command_exists docker; then
    print_error "Docker is not installed!"
    echo ""
    echo "Please install Docker first:"
    echo "  - Windows/Mac: Download Docker Desktop from https://www.docker.com/products/docker-desktop"
    echo "  - Linux: curl -fsSL https://get.docker.com | sh"
    exit 1
fi

# Check Docker Compose
if ! command_exists docker-compose && ! docker compose version >/dev/null 2>&1; then
    print_error "Docker Compose is not installed!"
    echo ""
    echo "Please install Docker Compose:"
    echo "  - Docker Desktop includes Compose by default"
    echo "  - Linux: sudo apt-get install docker-compose-plugin"
    exit 1
fi

# Check if Docker daemon is running
if ! docker info >/dev/null 2>&1; then
    print_error "Docker is not running!"
    echo ""
    echo "Please start Docker Desktop or the Docker daemon."
    exit 1
fi

print_success "Docker is installed and running"

# Step 2: Configure environment
print_info "Configuring environment..."

if [ -f .env ]; then
    print_warning ".env file already exists"
    read -p "Do you want to reconfigure? (y/n) " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        print_info "Using existing .env file"
    else
        rm .env
    fi
fi

if [ ! -f .env ]; then
    print_info "Creating .env file..."

    # Get API key from user
    echo ""
    echo -e "${YELLOW}═══════════════════════════════════════════════════════${NC}"
    echo -e "${YELLOW}  STEP 1: Enter your Snocart API Key${NC}"
    echo -e "${YELLOW}═══════════════════════════════════════════════════════${NC}"
    echo ""
    echo "You can get your API key from:"
    echo "  https://new.snocart.com/store-panel"
    echo "  → Click 'Setup Local POS' button"
    echo "  → Copy the generated API key"
    echo ""
    read -p "Enter your Snocart API Key: " api_key

    if [ -z "$api_key" ]; then
        print_error "API Key cannot be empty!"
        exit 1
    fi

    # Get Snocart URL
    echo ""
    read -p "Enter Snocart URL [https://new.snocart.com]: " snocart_url
    snocart_url=${snocart_url:-https://new.snocart.com}

    # Generate random admin password
    echo ""
    echo -e "${YELLOW}═══════════════════════════════════════════════════════${NC}"
    echo -e "${YELLOW}  STEP 2: Set Odoo Admin Password${NC}"
    echo -e "${YELLOW}═══════════════════════════════════════════════════════${NC}"
    echo ""
    read -sp "Enter Odoo admin password (or press Enter for random): " admin_password
    echo ""

    if [ -z "$admin_password" ]; then
        admin_password=$(openssl rand -base64 16 | tr -d "=+/" | cut -c1-16)
        print_info "Generated random password: $admin_password"
        echo "IMPORTANT: Save this password! You'll need it to access Odoo."
    fi

    # Create .env file
    cat > .env << EOF
# Snocart POS Configuration
# Generated on $(date)

SNOCART_API_KEY=$api_key
SNOCART_API_URL=$snocart_url
ODOO_ADMIN_PASSWORD=$admin_password

# Optional settings
SYNC_INTERVAL=300
LOG_LEVEL=INFO
EOF

    print_success "Configuration saved to .env file"
fi

# Step 3: Create directories
print_info "Creating required directories..."
mkdir -p config logs addons
print_success "Directories created"

# Step 4: Pull Docker images
print_info "Pulling Docker images (this may take a few minutes)..."
docker-compose pull

print_success "Docker images downloaded"

# Step 5: Start services
echo ""
echo -e "${YELLOW}═══════════════════════════════════════════════════════${NC}"
echo -e "${YELLOW}  Starting Snocart POS...${NC}"
echo -e "${YELLOW}═══════════════════════════════════════════════════════${NC}"
echo ""

docker-compose up -d

# Step 6: Wait for services to be healthy
print_info "Waiting for services to start (this may take 1-2 minutes)..."

for i in {1..60}; do
    if docker-compose ps | grep -q "healthy"; then
        break
    fi
    echo -n "."
    sleep 2
done
echo ""

# Step 7: Check if Odoo is accessible
print_info "Checking Odoo accessibility..."
for i in {1..30}; do
    if curl -s http://localhost:8069/web/health >/dev/null 2>&1; then
        print_success "Odoo is running!"
        break
    fi
    sleep 2
done

# Step 8: Display success message
echo ""
echo -e "${GREEN}═══════════════════════════════════════════════════════${NC}"
echo -e "${GREEN}  ✓ Snocart POS is now running!${NC}"
echo -e "${GREEN}═══════════════════════════════════════════════════════${NC}"
echo ""
echo -e "${BLUE}Access your POS system:${NC}"
echo -e "  URL:      ${GREEN}http://localhost:8069${NC}"
echo -e "  Username: ${GREEN}admin${NC}"
echo -e "  Password: ${GREEN}$(grep ODOO_ADMIN_PASSWORD .env | cut -d'=' -f2)${NC}"
echo ""
echo -e "${BLUE}Next steps:${NC}"
echo "  1. Open http://localhost:8069 in your browser"
echo "  2. Login with the credentials above"
echo "  3. Go to Point of Sale → Open POS Session"
echo "  4. Start taking orders!"
echo ""
echo -e "${YELLOW}Useful commands:${NC}"
echo "  View logs:     docker-compose logs -f"
echo "  Stop POS:      docker-compose stop"
echo "  Start POS:     docker-compose start"
echo "  Restart POS:   docker-compose restart"
echo "  Remove all:    docker-compose down -v"
echo ""
echo -e "${GREEN}Need help? Contact Snocart support${NC}"
echo ""
