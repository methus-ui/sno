# Snocart Local POS - Quick Start

Install and run Snocart POS on your computer in **3 simple steps**.

---

## 🎯 Quick Setup (5 Minutes)

### **Windows:**

1. **Install Docker Desktop**
   - Download: https://www.docker.com/products/docker-desktop
   - Install and restart computer

2. **Open PowerShell as Administrator** and run:
   ```powershell
   cd path\to\extracted\folder
   .\setup.ps1
   ```

3. **Enter your Snocart API Key** when prompted

✅ Done! Open http://localhost:8069

---

### **Mac/Linux:**

1. **Install Docker**
   ```bash
   # Mac: Download Docker Desktop from https://www.docker.com
   # Linux:
   curl -fsSL https://get.docker.com | sh
   ```

2. **Run setup script:**
   ```bash
   cd path/to/extracted/folder
   chmod +x setup.sh
   ./setup.sh
   ```

3. **Enter your Snocart API Key** when prompted

✅ Done! Open http://localhost:8069

---

## 📖 Need More Help?

See **[INSTALLATION_GUIDE.md](INSTALLATION_GUIDE.md)** for:
- Detailed installation steps
- Troubleshooting
- Configuration options
- Maintenance guide

---

## 🔑 Get Your API Key

1. Go to: https://new.snocart.com/store-panel
2. Find **"Local POS System"** section
3. Click **"Setup Local POS"** button
4. Copy the generated API key

---

## 📞 Support

- **Email:** support@snocart.com
- **Docs:** See INSTALLATION_GUIDE.md
- **Status:** http://localhost:8069/web/health

---

## 🎮 Daily Usage

**Start POS:**
```bash
docker-compose start
```

**Stop POS:**
```bash
docker-compose stop
```

**View Logs:**
```bash
docker-compose logs -f
```

---

**Quick? You can be up and running in 5 minutes! 🚀**
