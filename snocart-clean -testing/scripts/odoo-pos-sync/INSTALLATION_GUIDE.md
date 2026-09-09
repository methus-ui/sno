# Snocart Local POS - Installation Guide

Complete guide to install and run Snocart POS on your local computer using Docker.

---

## 📋 Table of Contents

1. [Prerequisites](#prerequisites)
2. [Installation Steps](#installation-steps)
3. [Configuration](#configuration)
4. [Usage](#usage)
5. [Troubleshooting](#troubleshooting)
6. [Maintenance](#maintenance)

---

## ✅ Prerequisites

Before you begin, ensure you have:

### 1. **Computer Requirements**
- **Windows 10/11** (64-bit) OR **macOS 10.15+** OR **Linux** (Ubuntu 20.04+)
- **Minimum:** 4GB RAM, 2 CPU cores, 20GB free disk space
- **Recommended:** 8GB RAM, 4 CPU cores, 50GB free disk space

### 2. **Internet Connection**
- Required for initial setup and sync
- Can work offline after installation (orders sync when reconnected)

### 3. **Snocart API Key**
- Get from: https://new.snocart.com/store-panel
- Click **"Setup Local POS"** button
- Copy the generated API key

---

## 🚀 Installation Steps

### **For Windows Users:**

#### **Step 1: Install Docker Desktop**

1. Download **Docker Desktop** from: https://www.docker.com/products/docker-desktop
2. Run the installer (`Docker Desktop Installer.exe`)
3. Follow the installation wizard
4. **Restart your computer** when prompted
5. Start Docker Desktop (should start automatically)
6. Wait for the whale icon in system tray to say "Docker Desktop is running"

#### **Step 2: Download Snocart POS Files**

1. Go to your Snocart vendor dashboard: https://new.snocart.com/store-panel
2. Scroll to **"Local POS System"** section
3. Click **"Download Installer"** → **"Windows"**
4. Extract the downloaded ZIP file to a folder (e.g., `C:\SnocartPOS`)

#### **Step 3: Run Setup Script**

1. Open **PowerShell as Administrator**:
   - Press `Windows Key`
   - Type "PowerShell"
   - Right-click → "Run as administrator"

2. Navigate to the extracted folder:
   ```powershell
   cd C:\SnocartPOS\odoo-pos-sync
   ```

3. Run the setup script:
   ```powershell
   .\setup.ps1
   ```

4. Follow the prompts:
   - Enter your **Snocart API Key**
   - Set an **Odoo admin password** (or let it generate one)

5. Wait 2-3 minutes for installation to complete

✅ **Done!** Your POS is now running at http://localhost:8069

---

### **For Mac/Linux Users:**

#### **Step 1: Install Docker**

**macOS:**
1. Download **Docker Desktop** from: https://www.docker.com/products/docker-desktop
2. Drag Docker.app to Applications folder
3. Open Docker Desktop
4. Wait for Docker to be ready (whale icon in menu bar)

**Linux (Ubuntu/Debian):**
```bash
# Install Docker
curl -fsSL https://get.docker.com | sh

# Install Docker Compose
sudo apt-get install docker-compose-plugin

# Add your user to docker group
sudo usermod -aG docker $USER

# Log out and log back in for group change to take effect
```

#### **Step 2: Download Snocart POS Files**

1. Go to: https://new.snocart.com/store-panel
2. Click **"Download Installer"** → **"macOS"** or **"Linux"**
3. Extract the downloaded ZIP file:
   ```bash
   cd ~/Downloads
   unzip snocart-pos-installer-*.zip
   cd odoo-pos-sync
   ```

#### **Step 3: Run Setup Script**

```bash
# Make script executable
chmod +x setup.sh

# Run setup
./setup.sh
```

Follow the prompts:
- Enter your **Snocart API Key**
- Set an **Odoo admin password** (or let it generate one)

✅ **Done!** Your POS is now running at http://localhost:8069

---

## ⚙️ Configuration

### **Accessing Your POS**

1. Open browser: http://localhost:8069
2. Login:
   - **Username:** `admin`
   - **Password:** (shown during setup)
3. Go to **Point of Sale** menu
4. Click **"Open POS Session"**

### **Configuration Files**

All configuration is in the `.env` file:

```bash
SNOCART_API_KEY=your_api_key_here          # Your API key from Snocart
SNOCART_API_URL=https://new.snocart.com    # Snocart server URL
ODOO_ADMIN_PASSWORD=your_password          # Odoo admin password
SYNC_INTERVAL=300                          # Sync every 5 minutes
```

To change settings:
1. Stop POS: `docker-compose stop`
2. Edit `.env` file
3. Start POS: `docker-compose start`

---

## 💡 Usage

### **Daily Operations**

#### **Start POS (after reboot):**
```bash
# Navigate to POS folder
cd C:\SnocartPOS\odoo-pos-sync  # Windows
cd ~/SnocartPOS/odoo-pos-sync   # Mac/Linux

# Start services
docker-compose start
```

#### **Stop POS (end of day):**
```bash
docker-compose stop
```

#### **View Logs (troubleshooting):**
```bash
# All services
docker-compose logs -f

# Specific service
docker-compose logs -f odoo
docker-compose logs -f sync_service
```

### **Using the POS**

1. **Open POS Session:**
   - Login to http://localhost:8069
   - Go to **Point of Sale** → **Open Session**

2. **Take Orders:**
   - Search products (type name or scan barcode)
   - Add to cart
   - Click **Payment** → Select method → **Validate**

3. **Offline Mode:**
   - POS works without internet
   - Orders queue automatically
   - Sync when internet returns

4. **End of Day:**
   - Click **Close Session**
   - Print reports if needed
   - Stop Docker containers

---

## 🔧 Troubleshooting

### **Problem: Docker not starting**

**Solution:**
- **Windows:** Check if Hyper-V is enabled (Windows Features)
- **Mac:** Check if Docker Desktop has permissions in System Preferences
- **Linux:** Run `sudo systemctl start docker`

### **Problem: "Port 8069 already in use"**

**Solution:**
```bash
# Find what's using the port
netstat -ano | findstr :8069    # Windows
lsof -i :8069                   # Mac/Linux

# Stop the other service or change POS port in docker-compose.yml
```

### **Problem: Odoo won't start / crashes**

**Solution:**
```bash
# Check logs
docker-compose logs odoo

# Restart all services
docker-compose restart

# If still failing, reset everything:
docker-compose down -v
docker-compose up -d
```

### **Problem: Orders not syncing to cloud**

**Solution:**
1. Check internet connection
2. Verify API key is correct in `.env` file
3. Check sync service logs:
   ```bash
   docker-compose logs sync_service
   ```
4. Restart sync service:
   ```bash
   docker-compose restart sync_service
   ```

### **Problem: Forgot admin password**

**Solution:**
```bash
# Stop services
docker-compose stop

# Edit .env file and change ODOO_ADMIN_PASSWORD

# Restart services
docker-compose start
```

### **Problem: "Out of memory" error**

**Solution:**
- Close other applications
- Increase Docker memory limit:
  - Docker Desktop → Settings → Resources → Memory (set to 4GB+)
- Reduce Odoo workers in `config/odoo.conf` (set `workers = 1`)

---

## 🛠️ Maintenance

### **Backup Your Data**

**Important:** Backup regularly to prevent data loss!

```bash
# Backup database and files
docker-compose exec postgres pg_dump -U odoo odoo_pos > backup_$(date +%Y%m%d).sql
docker cp snocart_pos_odoo:/var/lib/odoo odoo_backup_$(date +%Y%m%d)
```

### **Update Odoo**

```bash
# Pull latest images
docker-compose pull

# Restart with new images
docker-compose down
docker-compose up -d
```

### **Check System Health**

```bash
# Check all containers
docker-compose ps

# Check resource usage
docker stats
```

### **Clean Up (free disk space)**

```bash
# Remove unused images
docker image prune -a

# Remove old containers
docker container prune
```

### **Complete Uninstall**

```bash
# Stop and remove everything
docker-compose down -v

# Remove Docker images
docker rmi odoo:17.0 postgres:15-alpine

# Delete POS folder
rm -rf ~/SnocartPOS  # Mac/Linux
# Or manually delete C:\SnocartPOS on Windows
```

---

## 📞 Support

### **Need Help?**

- **Email:** support@snocart.com
- **Website:** https://new.snocart.com/support
- **Phone:** [Your support number]

### **Common Resources**

- **Odoo Documentation:** https://www.odoo.com/documentation/17.0/
- **Docker Documentation:** https://docs.docker.com/
- **Snocart API Docs:** https://new.snocart.com/docs/api

---

## 📝 Quick Reference

### **Useful Commands**

| Action | Command |
|--------|---------|
| Start POS | `docker-compose start` |
| Stop POS | `docker-compose stop` |
| Restart POS | `docker-compose restart` |
| View logs | `docker-compose logs -f` |
| Check status | `docker-compose ps` |
| Full reset | `docker-compose down -v && docker-compose up -d` |

### **Default Credentials**

- **URL:** http://localhost:8069
- **Username:** `admin`
- **Password:** (check `.env` file)

### **Sync Settings**

- **Interval:** 5 minutes (configurable)
- **Retry:** 3 attempts on failure
- **Offline:** Orders queue automatically

---

## ✨ Features

✅ **Offline Capable** - Works without internet, syncs when back online
✅ **Barcode Scanner** - USB scanner support (keyboard wedge mode)
✅ **Receipt Printing** - Browser print or direct thermal printer
✅ **Multi-Terminal** - Run on multiple PCs simultaneously
✅ **Auto Backup** - PostgreSQL automatic backups
✅ **Low Maintenance** - Docker handles updates and dependencies

---

**Version:** 1.0
**Last Updated:** 2026-02-24
**Compatible With:** Snocart v2.0+

---

© 2026 Snocart. All rights reserved.
