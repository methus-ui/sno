# Snocart Local POS Setup Script (Windows PowerShell)
# This script sets up Snocart POS on your Windows machine using Docker

$ErrorActionPreference = "Stop"

# ASCII Art Banner
Write-Host @"

   _____ _   _  ____   _____          _____ _______
  / ____| \ | |/ __ \ / ____|   /\   |  __ \__   __|
 | (___ |  \| | |  | | |       /  \  | |__) | | |
  \___ \| . ` | |  | | |      / /\ \ |  _  /  | |
  ____) | |\  | |__| | |____ / ____ \| | \ \  | |
 |_____/|_| \_|\____/ \_____/_/    \_\_|  \_\ |_|

         Local POS System - Docker Installation

"@ -ForegroundColor Cyan

# Function to print colored messages
function Write-Info {
    param($Message)
    Write-Host "[INFO] $Message" -ForegroundColor Blue
}

function Write-Success {
    param($Message)
    Write-Host "[SUCCESS] $Message" -ForegroundColor Green
}

function Write-Warning {
    param($Message)
    Write-Host "[WARNING] $Message" -ForegroundColor Yellow
}

function Write-Error {
    param($Message)
    Write-Host "[ERROR] $Message" -ForegroundColor Red
}

# Step 1: Check prerequisites
Write-Info "Checking prerequisites..."

# Check Docker
try {
    $dockerVersion = docker --version
    Write-Success "Docker is installed: $dockerVersion"
} catch {
    Write-Error "Docker is not installed!"
    Write-Host ""
    Write-Host "Please install Docker Desktop for Windows:" -ForegroundColor Yellow
    Write-Host "  1. Download from https://www.docker.com/products/docker-desktop" -ForegroundColor Yellow
    Write-Host "  2. Run the installer" -ForegroundColor Yellow
    Write-Host "  3. Restart your computer" -ForegroundColor Yellow
    Write-Host "  4. Run this script again" -ForegroundColor Yellow
    exit 1
}

# Check Docker Compose
try {
    $composeVersion = docker-compose --version
    Write-Success "Docker Compose is installed: $composeVersion"
} catch {
    Write-Error "Docker Compose is not installed!"
    Write-Host "Docker Desktop should include Compose. Please reinstall Docker Desktop." -ForegroundColor Yellow
    exit 1
}

# Check if Docker daemon is running
try {
    docker info | Out-Null
    Write-Success "Docker is running"
} catch {
    Write-Error "Docker is not running!"
    Write-Host ""
    Write-Host "Please start Docker Desktop and wait for it to be ready." -ForegroundColor Yellow
    Write-Host "Look for the whale icon in your system tray." -ForegroundColor Yellow
    exit 1
}

# Step 2: Configure environment
Write-Info "Configuring environment..."

if (Test-Path .env) {
    Write-Warning ".env file already exists"
    $response = Read-Host "Do you want to reconfigure? (y/n)"
    if ($response -ne "y") {
        Write-Info "Using existing .env file"
    } else {
        Remove-Item .env
    }
}

if (-not (Test-Path .env)) {
    Write-Info "Creating .env file..."

    # Get API key from user
    Write-Host ""
    Write-Host "═══════════════════════════════════════════════════════" -ForegroundColor Yellow
    Write-Host "  STEP 1: Enter your Snocart API Key" -ForegroundColor Yellow
    Write-Host "═══════════════════════════════════════════════════════" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "You can get your API key from:"
    Write-Host "  https://new.snocart.com/store-panel"
    Write-Host "  → Click 'Setup Local POS' button"
    Write-Host "  → Copy the generated API key"
    Write-Host ""
    $apiKey = Read-Host "Enter your Snocart API Key"

    if ([string]::IsNullOrWhiteSpace($apiKey)) {
        Write-Error "API Key cannot be empty!"
        exit 1
    }

    # Get Snocart URL
    Write-Host ""
    $snocartUrl = Read-Host "Enter Snocart URL [https://new.snocart.com]"
    if ([string]::IsNullOrWhiteSpace($snocartUrl)) {
        $snocartUrl = "https://new.snocart.com"
    }

    # Generate random admin password
    Write-Host ""
    Write-Host "═══════════════════════════════════════════════════════" -ForegroundColor Yellow
    Write-Host "  STEP 2: Set Odoo Admin Password" -ForegroundColor Yellow
    Write-Host "═══════════════════════════════════════════════════════" -ForegroundColor Yellow
    Write-Host ""
    $adminPassword = Read-Host "Enter Odoo admin password (or press Enter for random)" -AsSecureString
    $adminPasswordPlain = [Runtime.InteropServices.Marshal]::PtrToStringAuto(
        [Runtime.InteropServices.Marshal]::SecureStringToBSTR($adminPassword)
    )

    if ([string]::IsNullOrWhiteSpace($adminPasswordPlain)) {
        $adminPasswordPlain = -join ((48..57) + (65..90) + (97..122) | Get-Random -Count 16 | ForEach-Object {[char]$_})
        Write-Info "Generated random password: $adminPasswordPlain"
        Write-Host "IMPORTANT: Save this password! You'll need it to access Odoo." -ForegroundColor Yellow
    }

    # Create .env file
    $envContent = @"
# Snocart POS Configuration
# Generated on $(Get-Date)

SNOCART_API_KEY=$apiKey
SNOCART_API_URL=$snocartUrl
ODOO_ADMIN_PASSWORD=$adminPasswordPlain

# Optional settings
SYNC_INTERVAL=300
LOG_LEVEL=INFO
"@
    Set-Content -Path .env -Value $envContent

    Write-Success "Configuration saved to .env file"
}

# Step 3: Create directories
Write-Info "Creating required directories..."
New-Item -ItemType Directory -Path config -Force | Out-Null
New-Item -ItemType Directory -Path logs -Force | Out-Null
New-Item -ItemType Directory -Path addons -Force | Out-Null
Write-Success "Directories created"

# Step 4: Pull Docker images
Write-Info "Pulling Docker images (this may take a few minutes)..."
docker-compose pull

Write-Success "Docker images downloaded"

# Step 5: Start services
Write-Host ""
Write-Host "═══════════════════════════════════════════════════════" -ForegroundColor Yellow
Write-Host "  Starting Snocart POS..." -ForegroundColor Yellow
Write-Host "═══════════════════════════════════════════════════════" -ForegroundColor Yellow
Write-Host ""

docker-compose up -d

# Step 6: Wait for services to be healthy
Write-Info "Waiting for services to start (this may take 1-2 minutes)..."

$maxWait = 60
for ($i = 1; $i -le $maxWait; $i++) {
    $status = docker-compose ps
    if ($status -match "healthy") {
        break
    }
    Write-Host "." -NoNewline
    Start-Sleep -Seconds 2
}
Write-Host ""

# Step 7: Check if Odoo is accessible
Write-Info "Checking Odoo accessibility..."
$maxWait = 30
for ($i = 1; $i -le $maxWait; $i++) {
    try {
        $response = Invoke-WebRequest -Uri http://localhost:8069/web/health -UseBasicParsing -TimeoutSec 2
        if ($response.StatusCode -eq 200) {
            Write-Success "Odoo is running!"
            break
        }
    } catch {
        Start-Sleep -Seconds 2
    }
}

# Step 8: Display success message
$envContent = Get-Content .env | Where-Object { $_ -match "ODOO_ADMIN_PASSWORD" }
$password = ($envContent -split "=")[1]

Write-Host ""
Write-Host "═══════════════════════════════════════════════════════" -ForegroundColor Green
Write-Host "  ✓ Snocart POS is now running!" -ForegroundColor Green
Write-Host "═══════════════════════════════════════════════════════" -ForegroundColor Green
Write-Host ""
Write-Host "Access your POS system:" -ForegroundColor Blue
Write-Host "  URL:      " -NoNewline; Write-Host "http://localhost:8069" -ForegroundColor Green
Write-Host "  Username: " -NoNewline; Write-Host "admin" -ForegroundColor Green
Write-Host "  Password: " -NoNewline; Write-Host $password -ForegroundColor Green
Write-Host ""
Write-Host "Next steps:" -ForegroundColor Blue
Write-Host "  1. Open http://localhost:8069 in your browser"
Write-Host "  2. Login with the credentials above"
Write-Host "  3. Go to Point of Sale → Open POS Session"
Write-Host "  4. Start taking orders!"
Write-Host ""
Write-Host "Useful commands:" -ForegroundColor Yellow
Write-Host "  View logs:     docker-compose logs -f"
Write-Host "  Stop POS:      docker-compose stop"
Write-Host "  Start POS:     docker-compose start"
Write-Host "  Restart POS:   docker-compose restart"
Write-Host "  Remove all:    docker-compose down -v"
Write-Host ""
Write-Host "Need help? Contact Snocart support" -ForegroundColor Green
Write-Host ""

# Open browser
$openBrowser = Read-Host "Open Odoo in browser now? (y/n)"
if ($openBrowser -eq "y") {
    Start-Process "http://localhost:8069"
}
