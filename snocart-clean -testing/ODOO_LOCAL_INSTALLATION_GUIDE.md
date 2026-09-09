# Snocart Odoo POS - Local Installation Guide

## Overview

This guide explains how to install and use Odoo POS **locally on vendor PCs** with automatic synchronization to the Snocart cloud platform.

## Architecture

```
┌─────────────────────────────────────────────────────────────┐
│   VENDOR'S PC (Windows/Mac/Linux)                           │
│                                                              │
│   ┌──────────────────────────────────────────────┐         │
│   │  Odoo 17 POS (Local Installation)            │         │
│   │  - PostgreSQL database (local)                │         │
│   │  - Runs on http://localhost:8069              │         │
│   │  - Works 100% offline                         │         │
│   │  - Product search, barcode scanning           │         │
│   │  - Receipt printing                            │         │
│   └──────────────────────────────────────────────┘         │
│                           ↕                                  │
│   ┌──────────────────────────────────────────────┐         │
│   │  Snocart Sync Service (Python)                │         │
│   │  - Runs in background                         │         │
│   │  - Syncs every 5 minutes                      │         │
│   │  - Pushes orders to cloud                     │         │
│   │  - Pulls products from cloud                  │         │
│   │  - Updates inventory                           │         │
│   └──────────────────────────────────────────────┘         │
│                           ↕                                  │
└──────────────────────────┼──────────────────────────────────┘
                           │
                           │ HTTPS API
                           │ (with API key authentication)
                           │
                           ▼
┌─────────────────────────────────────────────────────────────┐
│   SNOCART CLOUD PLATFORM (Laravel)                          │
│                                                              │
│   API Endpoints:                                             │
│   - POST /api/v1/vendor/sync-orders                         │
│   - GET  /api/v1/vendor/sync-products                       │
│   - GET  /api/v1/vendor/sync-inventory                      │
│   - POST /api/v1/vendor/update-inventory                    │
│                                                              │
│   Admin Dashboard:                                           │
│   - View all vendor POS activity                            │
│   - Monitor sync status                                     │
│   - Generate API keys for vendors                           │
└─────────────────────────────────────────────────────────────┘
```

## Key Features

✅ **100% Offline Operation** - Vendors can take orders without internet
✅ **Automatic Sync** - Orders sync to cloud when online (every 5 min)
✅ **No Installation for Customers** - All on vendor's PC
✅ **Existing Hardware** - Works with current PC, barcode scanners, printers
✅ **Low Cost** - No cloud server fees, no monthly subscriptions
✅ **Fast** - Local database means instant response time
✅ **Secure** - Data encrypted during sync, API key authentication

## Installation

### For Vendors (Windows)

1. **Download Installer**
   - Visit: https://new.snocart.com/vendor/download-pos-installer
   - Or get installer from USB drive provided by admin

2. **Run Installer**
   - Right-click `install-windows.ps1`
   - Select "Run with PowerShell"
   - Click "Yes" when prompted for administrator access

3. **Follow Installation Wizard**
   - Installer will automatically:
     - Install PostgreSQL database
     - Install Python 3.11
     - Install Odoo 17 Community Edition
     - Configure Odoo for POS
     - Install sync service
     - Create desktop shortcuts

4. **Enter API Key**
   - When prompted, enter API key from vendor dashboard
   - Get this from: https://new.snocart.com/vendor/settings/api-key

5. **Wait for Installation** (~20 minutes)
   - PostgreSQL: ~5 minutes
   - Python: ~2 minutes
   - Odoo: ~10 minutes
   - Configuration: ~3 minutes

6. **Installation Complete**
   - Desktop shortcut "Snocart POS" will be created
   - Odoo will open automatically in browser

### For Vendors (Mac)

```bash
# 1. Download installer
curl -O https://new.snocart.com/downloads/install-mac.sh

# 2. Make executable
chmod +x install-mac.sh

# 3. Run installer
sudo ./install-mac.sh
```

### For Vendors (Linux)

```bash
# 1. Download installer
wget https://new.snocart.com/downloads/install-linux.sh

# 2. Make executable
chmod +x install-linux.sh

# 3. Run installer
sudo ./install-linux.sh
```

## Initial Setup

### 1. First-Time Login

1. Open browser to: `http://localhost:8069`
2. Login with:
   - Email: `admin`
   - Password: `admin123`

3. Install POS Module:
   - Go to: Apps → Search "Point of Sale"
   - Click "Install"
   - Wait for installation (~2 minutes)

### 2. Configure POS

1. **Go to POS Settings**:
   - Point of Sale → Configuration → Settings

2. **Enable Features**:
   - ✅ Barcode Scanner
   - ✅ Receipts
   - ✅ Offline Mode
   - ✅ Product Categories
   - ✅ Discounts

3. **Configure Receipt Header**:
   - Add store name
   - Add phone number
   - Add address

### 3. Wait for Initial Sync

1. **Products will sync automatically** within 5 minutes
2. **Check sync status**:
   - Point of Sale → Products
   - Should see all products from Snocart

3. **If products don't appear**:
   - Check sync service is running: Open Task Manager → Look for "python snocart_sync_service.py"
   - Check sync logs: `C:\Snocart\OdooPOS\snocart_sync.log`

## Daily Operations

### Taking Orders

1. **Open POS**:
   - Double-click "Snocart POS" desktop shortcut
   - Or visit: `http://localhost:8069/pos/web`

2. **Search for Products**:
   - Type product name in search box
   - OR scan barcode with scanner

3. **Add to Cart**:
   - Click product to add to cart
   - Adjust quantity with +/- buttons
   - Apply discount if needed

4. **Process Payment**:
   - Click "Payment" button
   - Select payment method (Cash/Card)
   - Enter amount received
   - Click "Validate"

5. **Print Receipt**:
   - Receipt prints automatically
   - Or click "Print" button

### Orders Sync to Cloud

- **Automatic**: Every 5 minutes, new orders sync to Snocart
- **Manual**: Restart sync service to sync immediately
- **Verify**: Check vendor dashboard to see orders appear

### Product Updates

- **From Cloud**: Products update automatically every 15 minutes
- **Price Changes**: Updated prices sync to local POS
- **New Products**: Appear in POS within 15 minutes
- **Deleted Products**: Removed from POS automatically

### Inventory Management

- **Stock Levels**: Sync bidirectionally every 30 minutes
- **When Order Placed**: Stock decrements in POS
- **When Synced**: Stock updates in Snocart
- **Low Stock Alerts**: Shown in POS if enabled

## Troubleshooting

### Issue: POS won't open

**Solution:**
1. Check Odoo service is running:
   - Open Services (Win+R → `services.msc`)
   - Look for "Odoo Server 17.0"
   - If stopped, right-click → Start

2. Check if port 8069 is available:
   ```powershell
   netstat -ano | findstr :8069
   ```

3. Restart Odoo service:
   ```powershell
   Restart-Service "Odoo Server 17.0"
   ```

### Issue: Barcode scanner not working

**Solution:**
1. Check scanner is in "keyboard wedge" mode
2. Test scanner in Notepad (should type barcode)
3. In POS settings, enable "Barcode Scanner"
4. Scan barcode in product search field (not quantity field)

### Issue: Orders not syncing

**Solution:**
1. Check internet connection
2. Check sync service is running:
   - Task Manager → Details → Look for `python.exe`

3. View sync logs:
   ```powershell
   notepad C:\Snocart\OdooPOS\snocart_sync.log
   ```

4. Restart sync service:
   - Task Scheduler → Find "SnocartPOSSync"
   - Right-click → Run

5. Verify API key is correct:
   - Open `C:\Snocart\OdooPOS\config.json`
   - Check `api_key` matches vendor dashboard

### Issue: Products not appearing

**Solution:**
1. Wait 15 minutes for initial sync
2. Check sync service logs
3. Manually trigger sync:
   ```powershell
   cd C:\Snocart\OdooPOS
   python snocart_sync_service.py --config config.json --once
   ```

4. Verify products exist in Snocart vendor dashboard

### Issue: Receipt won't print

**Solution:**
1. Check printer is connected and powered on
2. In POS → Configuration → PoS:
   - Enable "Receipt Printing"
   - Select printer

3. Test print from Windows to verify printer works

## Admin Panel Features

### For Super Admin

1. **Generate API Keys**:
   - Admin Panel → Stores → View Store
   - Click "Generate API Key"
   - Copy and send to vendor

2. **Monitor Sync Status**:
   - Admin Panel → Reports → POS Sync Status
   - View all vendors' last sync time
   - See failed syncs with error messages

3. **View POS Orders**:
   - Admin Panel → Orders
   - Filter by "Sync Source: Odoo"
   - See all orders from local POS installations

### For Vendors

1. **Get API Key**:
   - Vendor Dashboard → Settings → API Integration
   - Click "Generate New API Key"
   - Copy key for installation

2. **View Sync Status**:
   - Vendor Dashboard → POS → Sync Status
   - Last sync time
   - Orders synced count
   - Products synced count

3. **Download Installer**:
   - Vendor Dashboard → POS → Download Installer
   - Select platform (Windows/Mac/Linux)
   - Download and run

## Hardware Requirements

### Minimum Requirements

- **Operating System**: Windows 10/11, macOS 10.15+, or Linux (Ubuntu 20.04+)
- **Processor**: Intel Core i3 or equivalent
- **RAM**: 4GB (8GB recommended)
- **Storage**: 10GB free space
- **Internet**: Broadband connection (for initial setup and sync)
- **Display**: 1366x768 or higher

### Recommended Hardware

- **Barcode Scanner**: USB keyboard wedge scanner (e.g., Zebra DS2208)
- **Receipt Printer**: Thermal printer with USB (e.g., Epson TM-T20III)
- **Cash Drawer**: Connected to receipt printer (optional)
- **Customer Display**: Secondary monitor (optional)

## Security

### Data Protection

- **Encryption**: All sync traffic encrypted with HTTPS/TLS
- **API Key**: Unique per vendor, can be regenerated anytime
- **Local Database**: Password-protected PostgreSQL
- **No Remote Access**: POS only accessible from local network

### Best Practices

- **Change Default Passwords**: Update Odoo admin password after installation
- **Backup Database**: Schedule daily backups of local PostgreSQL
- **Update Software**: Keep Windows/Mac OS updated
- **Antivirus**: Install and maintain antivirus software
- **Physical Security**: Lock PC when not in use

## Backup & Restore

### Backup Local Database

**Windows:**
```powershell
# Run daily via Task Scheduler
cd "C:\Program Files\PostgreSQL\15\bin"
.\pg_dump.exe -U odoo -F c -b -v -f "C:\Backups\snocart_pos_%date:~-4,4%%date:~-10,2%%date:~-7,2%.backup" snocart_pos
```

**Mac/Linux:**
```bash
# Add to cron: 0 2 * * * (daily at 2 AM)
pg_dump -U odoo -F c -b -v -f ~/backups/snocart_pos_$(date +%Y%m%d).backup snocart_pos
```

### Restore from Backup

```powershell
cd "C:\Program Files\PostgreSQL\15\bin"
.\pg_restore.exe -U odoo -d snocart_pos -v "C:\Backups\snocart_pos_20260224.backup"
```

## Costs

### One-Time Costs (Per Vendor)

- **Software**: FREE (Odoo Community Edition)
- **Installation**: FREE (self-install) or $100 (professional installation)
- **Barcode Scanner**: $50-150 (if not already owned)
- **Receipt Printer**: $100-300 (if not already owned)

### Ongoing Costs

- **Cloud Sync**: FREE (included in Snocart subscription)
- **Maintenance**: FREE (self-managed) or $20/month (support plan)
- **Updates**: FREE (automatic)

**Total Cost**: $0-600 one-time, $0-20/month ongoing

Compare to cloud POS solutions: $50-100/month + transaction fees!

## Support

### Self-Help Resources

- **User Guide**: https://new.snocart.com/docs/local-pos
- **Video Tutorials**: https://new.snocart.com/videos/local-pos
- **FAQ**: https://new.snocart.com/faq/local-pos

### Contact Support

- **Email**: support@snocart.com
- **Phone**: +1-XXX-XXX-XXXX
- **Chat**: Available in vendor dashboard
- **Hours**: Mon-Fri 9AM-6PM

---

**Last Updated**: February 24, 2026
**Version**: 1.0.0
**Compatible with**: Snocart v3.0+, Odoo 17.0+
