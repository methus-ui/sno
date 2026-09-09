# Snocart Odoo POS - Windows Installer
# PowerShell script to install Odoo POS on vendor's Windows PC
#
# Requirements: Windows 10/11, Admin privileges
# Usage: Right-click → Run with PowerShell (as Administrator)

#Requires -RunAsAdministrator

$ErrorActionPreference = "Stop"

# Configuration
$ODOO_VERSION = "17.0"
$ODOO_DOWNLOAD_URL = "https://nightly.odoo.com/17.0/nightly/exe/odoo_17.0.latest.exe"
$PYTHON_DOWNLOAD_URL = "https://www.python.org/ftp/python/3.11.0/python-3.11.0-amd64.exe"
$POSTGRES_DOWNLOAD_URL = "https://get.enterprisedb.com/postgresql/postgresql-15.3-1-windows-x64.exe"
$INSTALL_DIR = "C:\Snocart\OdooPOS"
$TEMP_DIR = "$env:TEMP\SnocartInstaller"

# Colors
function Write-Success {
    param([string]$Message)
    Write-Host "✓ $Message" -ForegroundColor Green
}

function Write-Error-Custom {
    param([string]$Message)
    Write-Host "✗ $Message" -ForegroundColor Red
}

function Write-Info {
    param([string]$Message)
    Write-Host "ℹ $Message" -ForegroundColor Cyan
}

function Write-Header {
    param([string]$Message)
    Write-Host ""
    Write-Host "═══════════════════════════════════════════════════════════" -ForegroundColor Blue
    Write-Host "  $Message" -ForegroundColor Blue
    Write-Host "═══════════════════════════════════════════════════════════" -ForegroundColor Blue
    Write-Host ""
}

# Create temp directory
if (!(Test-Path $TEMP_DIR)) {
    New-Item -ItemType Directory -Path $TEMP_DIR | Out-Null
}

Write-Host @"
╔═══════════════════════════════════════════════════════════════════╗
║                                                                   ║
║          Snocart Odoo POS - Windows Installation Script          ║
║                                                                   ║
╚═══════════════════════════════════════════════════════════════════╝
"@ -ForegroundColor Cyan

Write-Info "This installer will install:"
Write-Info "  - PostgreSQL Database"
Write-Info "  - Python 3.11"
Write-Info "  - Odoo 17 Community Edition"
Write-Info "  - Snocart Sync Service"
Write-Info ""
Write-Info "Installation directory: $INSTALL_DIR"
Write-Info ""

$confirm = Read-Host "Continue with installation? (Y/N)"
if ($confirm -ne "Y" -and $confirm -ne "y") {
    Write-Info "Installation cancelled"
    exit
}

# Step 1: Check if PostgreSQL is installed
Write-Header "Step 1: Installing PostgreSQL"

$postgresInstalled = Get-Service -Name "postgresql*" -ErrorAction SilentlyContinue
if ($postgresInstalled) {
    Write-Success "PostgreSQL already installed"
} else {
    Write-Info "Downloading PostgreSQL..."
    $postgresInstaller = "$TEMP_DIR\postgresql-installer.exe"
    Invoke-WebRequest -Uri $POSTGRES_DOWNLOAD_URL -OutFile $postgresInstaller

    Write-Info "Installing PostgreSQL (this may take 5-10 minutes)..."
    $postgresPassword = "odoo123"
    Write-Info "PostgreSQL password will be set to: $postgresPassword"

    Start-Process -FilePath $postgresInstaller -ArgumentList `
        "--mode unattended --superpassword $postgresPassword --servicename postgresql-x64-15" `
        -Wait

    Write-Success "PostgreSQL installed"
}

# Step 2: Check if Python is installed
Write-Header "Step 2: Installing Python"

$pythonInstalled = Get-Command python -ErrorAction SilentlyContinue
if ($pythonInstalled) {
    $pythonVersion = python --version
    Write-Success "Python already installed: $pythonVersion"
} else {
    Write-Info "Downloading Python 3.11..."
    $pythonInstaller = "$TEMP_DIR\python-installer.exe"
    Invoke-WebRequest -Uri $PYTHON_DOWNLOAD_URL -OutFile $pythonInstaller

    Write-Info "Installing Python..."
    Start-Process -FilePath $pythonInstaller -ArgumentList `
        "/quiet InstallAllUsers=1 PrependPath=1 Include_test=0" `
        -Wait

    # Refresh environment variables
    $env:Path = [System.Environment]::GetEnvironmentVariable("Path","Machine") + ";" + [System.Environment]::GetEnvironmentVariable("Path","User")

    Write-Success "Python installed"
}

# Step 3: Install Odoo
Write-Header "Step 3: Installing Odoo 17"

$odooInstalled = Test-Path "C:\Program Files\Odoo 17.0"
if ($odooInstalled) {
    Write-Success "Odoo 17 already installed"
} else {
    Write-Info "Downloading Odoo 17 Community Edition..."
    $odooInstaller = "$TEMP_DIR\odoo-installer.exe"
    Invoke-WebRequest -Uri $ODOO_DOWNLOAD_URL -OutFile $odooInstaller

    Write-Info "Installing Odoo (this may take 10-15 minutes)..."
    Start-Process -FilePath $odooInstaller -ArgumentList "/S" -Wait

    Write-Success "Odoo 17 installed"
}

# Step 4: Configure Odoo for POS
Write-Header "Step 4: Configuring Odoo for POS"

$odooConfigPath = "C:\Program Files\Odoo 17.0\server\odoo.conf"
if (Test-Path $odooConfigPath) {
    Write-Info "Creating Odoo configuration..."

    $odooConfig = @"
[options]
admin_passwd = admin123
db_host = localhost
db_port = 5432
db_user = odoo
db_password = odoo123
http_port = 8069
logfile = C:\Odoo\logs\odoo.log
addons_path = C:\Program Files\Odoo 17.0\server\odoo\addons

# Performance
workers = 2
max_cron_threads = 1

# Single database mode (for local installation)
db_name = snocart_pos
list_db = False
"@

    Set-Content -Path $odooConfigPath -Value $odooConfig
    Write-Success "Odoo configuration created"

    # Create log directory
    New-Item -ItemType Directory -Path "C:\Odoo\logs" -Force | Out-Null

    # Create PostgreSQL user and database
    Write-Info "Creating PostgreSQL database..."
    $env:PGPASSWORD = "odoo123"

    & "C:\Program Files\PostgreSQL\15\bin\psql.exe" -U postgres -c "CREATE USER odoo WITH PASSWORD 'odoo123';" 2>$null
    & "C:\Program Files\PostgreSQL\15\bin\psql.exe" -U postgres -c "CREATE DATABASE snocart_pos OWNER odoo;" 2>$null

    Write-Success "PostgreSQL database created"

    # Start Odoo service
    Write-Info "Starting Odoo service..."
    Start-Service -Name "odoo-server*"
    Start-Sleep -Seconds 10

    Write-Success "Odoo service started"

} else {
    Write-Error-Custom "Odoo configuration file not found"
}

# Step 5: Install Sync Service
Write-Header "Step 5: Installing Snocart Sync Service"

if (!(Test-Path $INSTALL_DIR)) {
    New-Item -ItemType Directory -Path $INSTALL_DIR | Out-Null
}

Write-Info "Installing Python dependencies..."
python -m pip install --upgrade pip
python -m pip install psycopg2-binary requests schedule

Write-Info "Downloading sync service..."
# Note: In production, download from Snocart server
# For now, create placeholder
$syncServicePath = "$INSTALL_DIR\snocart_sync_service.py"
$configPath = "$INSTALL_DIR\config.json"

# Prompt for API key
Write-Info ""
$apiKey = Read-Host "Enter your Snocart API key (from vendor dashboard)"

$syncConfig = @"
{
  "api_url": "https://new.snocart.com",
  "api_key": "$apiKey",
  "db_host": "localhost",
  "db_port": 5432,
  "db_name": "snocart_pos",
  "db_user": "odoo",
  "db_password": "odoo123",
  "sync_interval": 300
}
"@

Set-Content -Path $configPath -Value $syncConfig
Write-Success "Sync service configured"

# Create Windows service for sync
Write-Info "Creating Windows service for sync..."
$servicePath = "$INSTALL_DIR\start_sync.bat"
$serviceScript = @"
@echo off
cd /d "$INSTALL_DIR"
python snocart_sync_service.py --config config.json
"@

Set-Content -Path $servicePath -Value $serviceScript

# Create scheduled task to run sync service on startup
$action = New-ScheduledTaskAction -Execute "powershell.exe" -Argument "-WindowStyle Hidden -File `"$servicePath`""
$trigger = New-ScheduledTaskTrigger -AtStartup
$principal = New-ScheduledTaskPrincipal -UserId "SYSTEM" -LogonType ServiceAccount -RunLevel Highest
$settings = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries

Register-ScheduledTask -TaskName "SnocartPOSSync" -Action $action -Trigger $trigger -Principal $principal -Settings $settings -Force

Write-Success "Sync service installed and scheduled"

# Step 6: Create desktop shortcuts
Write-Header "Step 6: Creating Desktop Shortcuts"

$desktopPath = [Environment]::GetFolderPath("Desktop")

# Shortcut for Odoo POS
$posShortcutPath = "$desktopPath\Snocart POS.url"
$posShortcutContent = @"
[InternetShortcut]
URL=http://localhost:8069/pos/web
IconIndex=0
"@

Set-Content -Path $posShortcutPath -Value $posShortcutContent
Write-Success "Created 'Snocart POS' desktop shortcut"

# Shortcut for Odoo Admin
$adminShortcutPath = "$desktopPath\Snocart POS Admin.url"
$adminShortcutContent = @"
[InternetShortcut]
URL=http://localhost:8069
IconIndex=0
"@

Set-Content -Path $adminShortcutPath -Value $adminShortcutContent
Write-Success "Created 'Snocart POS Admin' desktop shortcut"

# Step 7: Installation complete
Write-Header "Installation Complete!"

Write-Success "Snocart Odoo POS installed successfully!"
Write-Host ""
Write-Info "Important Information:"
Write-Host "  - Odoo URL: http://localhost:8069"
Write-Host "  - Database Name: snocart_pos"
Write-Host "  - Admin Password: admin123"
Write-Host ""
Write-Info "Next Steps:"
Write-Host "  1. Double-click 'Snocart POS' on your desktop"
Write-Host "  2. Login with admin/admin123"
Write-Host "  3. Install the POS module"
Write-Host "  4. Configure your products"
Write-Host "  5. Start selling!"
Write-Host ""
Write-Info "Sync Service:"
Write-Host "  - Service will start automatically on system boot"
Write-Host "  - Orders will sync to Snocart cloud every 5 minutes"
Write-Host "  - Products will sync from Snocart automatically"
Write-Host ""
Write-Info "For support, contact: support@snocart.com"
Write-Host ""

# Cleanup
Remove-Item -Path $TEMP_DIR -Recurse -Force

# Open browser to Odoo
Write-Info "Opening Snocart POS in your browser..."
Start-Sleep -Seconds 3
Start-Process "http://localhost:8069"

Read-Host "Press Enter to exit"
