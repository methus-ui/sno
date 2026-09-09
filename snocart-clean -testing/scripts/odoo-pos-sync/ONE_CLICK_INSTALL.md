# Snocart Local POS - TRUE One-Click Installation

## For Mac/Linux (3 Steps)

### Step 1: Install Docker Desktop (One-Time)
Download and install: https://www.docker.com/products/docker-desktop

### Step 2: Download and Extract
Download the ZIP from your vendor dashboard and extract to `~/SnocartPOS/`

### Step 3: Run Setup (ONE COMMAND)
```bash
cd ~/SnocartPOS/odoo-pos-sync
chmod +x setup-fixed.sh
./setup-fixed.sh
```

**That's it!** The script automatically:
- ✅ Checks Docker installation
- ✅ Cleans up old containers
- ✅ Creates all config files
- ✅ Downloads Docker images
- ✅ Initializes database
- ✅ Installs all 53 Odoo modules
- ✅ Starts POS system

Access at: **http://localhost:8069**

---

## For Windows (3 Steps)

### Step 1: Install Docker Desktop
Download and install: https://www.docker.com/products/docker-desktop

### Step 2: Download and Extract
Download ZIP from vendor dashboard and extract to `C:\SnocartPOS\`

### Step 3: Run Setup (ONE COMMAND)
Open PowerShell as Administrator:
```powershell
cd C:\SnocartPOS\odoo-pos-sync
.\setup.ps1
```

Access at: **http://localhost:8069**

---

## What If It Fails?

### Quick Fix:
```bash
# Stop everything
docker compose down -v

# Run setup again
./setup-fixed.sh
```

### Get Help:
- Check logs: `docker compose logs odoo`
- Email: support@snocart.com
- Include error message from terminal

---

## Login Credentials

- **URL:** http://localhost:8069
- **Database:** odoo_pos
- **Username:** admin
- **Password:** Aclass@2425

---

## Daily Usage

```bash
# Start POS
docker compose start

# Stop POS
docker compose stop

# Restart POS
docker compose restart

# View logs
docker compose logs -f odoo
```

---

## Troubleshooting

**Port 8069 already in use?**
```bash
# Find what's using it
lsof -i :8069

# Or use different port (edit docker-compose.yml)
# Change "8069:8069" to "8070:8069"
```

**Docker not running?**
- Start Docker Desktop app
- Wait for whale icon to appear (Mac) or tray icon (Windows)

**Still not working?**
```bash
# Complete reset
docker compose down -v
rm -rf config/*
./setup-fixed.sh
```

---

## Uninstall

```bash
# Stop and remove everything
docker compose down -v

# Remove folder
rm -rf ~/SnocartPOS
```

---

**Questions? Email: support@snocart.com**
