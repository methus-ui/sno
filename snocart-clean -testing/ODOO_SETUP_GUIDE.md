# Odoo Setup Guide for Snocart Sync

## 1. Database Connection Configuration

The sync service connects to your **local Odoo PostgreSQL database** directly. Here's what you need:

### Current Configuration (`/app/config.json` in Docker)

```json
{
  "api_url": "https://new.snocart.com/api/v1",
  "api_key": "your_vendor_api_key_here",
  "store_id": 1,

  "db_host": "host.docker.internal",
  "db_port": 5432,
  "db_name": "odoo17",
  "db_user": "odoo",
  "db_password": "odoo",

  "sync_interval": 300,
  "stock_push_interval": 3600
}
```

### How to Update Your Configuration

**Option 1: Direct edit in Docker**
```bash
docker exec -it snocart_pos_sync vi /app/config.json
# Then restart: docker restart snocart_pos_sync
```

**Option 2: Edit and copy**
```bash
# Create local config file
cat > /tmp/odoo_config.json << 'EOF'
{
  "api_url": "https://new.snocart.com/api/v1",
  "api_key": "YOUR_SNOCART_API_KEY",
  "store_id": 1,

  "db_host": "host.docker.internal",
  "db_port": 5432,
  "db_name": "YOUR_ODOO_DATABASE_NAME",
  "db_user": "YOUR_ODOO_DB_USERNAME",
  "db_password": "YOUR_ODOO_DB_PASSWORD",

  "sync_interval": 300,
  "stock_push_interval": 3600
}
EOF

# Copy to Docker
docker cp /tmp/odoo_config.json snocart_pos_sync:/app/config.json

# Restart service
docker restart snocart_pos_sync
```

---

## 2. Find Your Odoo Database Credentials

### Method 1: Check Odoo Configuration File

**Location:** Usually at `/etc/odoo/odoo.conf` or `~/.odoorc` or `/opt/odoo/odoo.conf`

```bash
# Find odoo config
find /etc /opt ~ -name "odoo.conf" -o -name "odoo-server.conf" 2>/dev/null | head -5

# Read the config
cat /path/to/odoo.conf | grep -E "db_host|db_port|db_user|db_password|db_name"
```

Expected output:
```ini
db_host = localhost
db_port = 5432
db_user = odoo
db_password = your_password_here
db_name = odoo17
```

### Method 2: Check PostgreSQL Directly

```bash
# List all databases (if you have postgres access)
sudo -u postgres psql -c "\l" | grep odoo

# Test connection
psql -h localhost -U odoo -d odoo17 -c "SELECT count(*) FROM product_template;"
# It will prompt for password
```

### Method 3: Check Odoo's Database Manager

1. Open browser: `http://localhost:8069/web/database/manager`
2. You'll see list of databases
3. Your Odoo database name is listed there

---

## 3. Odoo Product Fields Setup

For products to sync correctly, they need these fields:

### Required Fields ✅

| Field | Technical Name | Type | Notes |
|-------|---------------|------|-------|
| **Product Name** | `name` | JSONB | Auto-handled (multi-language) |
| **Sale Price** | `list_price` | Float | Must be > 0 |
| **Can be Sold** | `sale_ok` | Boolean | Must be `true` |
| **Product Type** | `type` | Selection | Must be `'product'` (storable) |
| **Active** | `active` | Boolean | Must be `true` |

### Optional Fields (Recommended) 📋

| Field | Technical Name | Type | Notes |
|-------|---------------|------|-------|
| **Barcode** | `default_code` | String | Syncs to Snocart barcode |
| **Description** | `description` | JSONB | Used as product description |
| **Category** | `categ_id` | Many2one | Links to product category |
| **Cost Price** | `standard_price` | Float | Not synced (Odoo only) |
| **Stock** | `qty_available` | Float | Synced separately (inventory sync) |

### Field to Avoid ❌

**DO NOT manually set:**
- `default_code` to a number if product should sync
- Products with `default_code` like `"119309"` are considered "already on Snocart"

---

## 4. Product Type Configuration

### In Odoo UI:

When creating a product:

```
Inventory → Products → Create

General Information:
├─ Product Name: [Enter name]
├─ Can be Sold: ✓ (must be checked)
├─ Can be Purchased: (optional)
├─ Product Type: Storable Product ← IMPORTANT
└─ Category: [Select or create]

Sales:
└─ Sales Price: [Enter price > 0]

Inventory:
├─ Barcode: [Optional - will sync to Snocart]
└─ Internal Reference: [Leave EMPTY or use non-numeric value]
```

### Product Type Values:

- ✅ `Storable Product` (`type = 'product'`) - **WILL SYNC**
- ❌ `Consumable` (`type = 'consu'`) - **WON'T SYNC**
- ❌ `Service` (`type = 'service'`) - **WON'T SYNC**

---

## 5. Database Schema Verification

Run this in your Odoo PostgreSQL to verify:

```sql
-- Check product_template structure
\d product_template

-- Check if required columns exist
SELECT column_name, data_type
FROM information_schema.columns
WHERE table_name = 'product_template'
  AND column_name IN ('name', 'list_price', 'sale_ok', 'type', 'active', 'default_code', 'description');

-- Sample query (same as sync service uses)
SELECT
    pt.id as odoo_product_id,
    COALESCE(
        pt.name::jsonb->>'en_US',
        pt.name::jsonb->>'default',
        (pt.name::jsonb->>0)
    ) as product_name,
    pt.list_price as price,
    pt.default_code as barcode,
    pt.sale_ok,
    pt.active,
    pt.type
FROM product_template pt
WHERE pt.sale_ok = true
    AND pt.active = true
    AND pt.type = 'product'
LIMIT 5;
```

---

## 6. Network Configuration

### Docker → Local Odoo Connection

The sync service runs in Docker and needs to connect to your **local Odoo database**.

#### If PostgreSQL is on your local machine:

Use `host.docker.internal` (recommended):

```json
{
  "db_host": "host.docker.internal",
  "db_port": 5432
}
```

#### If PostgreSQL allows network connections:

1. **Edit PostgreSQL config** (`/etc/postgresql/14/main/postgresql.conf`):
   ```ini
   listen_addresses = '*'
   ```

2. **Edit pg_hba.conf** (`/etc/postgresql/14/main/pg_hba.conf`):
   ```
   # Allow Docker network
   host    all             all             172.17.0.0/16           md5
   ```

3. **Restart PostgreSQL**:
   ```bash
   sudo systemctl restart postgresql
   ```

4. **Update config.json**:
   ```json
   {
     "db_host": "172.17.0.1",  # Or your machine's IP
     "db_port": 5432
   }
   ```

---

## 7. Test Connection

### Test 1: Direct PostgreSQL Connection

```bash
docker exec -it snocart_pos_sync psql -h host.docker.internal -U odoo -d odoo17 -c "SELECT version();"
# Enter password when prompted
```

Expected output:
```
PostgreSQL 14.x on x86_64-pc-linux-gnu...
```

### Test 2: Python Connection (from sync service)

```bash
docker exec snocart_pos_sync python -c "
import psycopg2
conn = psycopg2.connect(
    host='host.docker.internal',
    port=5432,
    database='odoo17',
    user='odoo',
    password='odoo'
)
cursor = conn.cursor()
cursor.execute('SELECT count(*) FROM product_template;')
print(f'Found {cursor.fetchone()[0]} products in Odoo')
conn.close()
"
```

Expected output:
```
Found 1523 products in Odoo
```

### Test 3: Sync Service Connection

```bash
docker exec snocart_pos_sync python -c "
import sys
sys.path.insert(0, '/app')
from snocart_sync_service import SnocartSyncService

service = SnocartSyncService('/app/config.json')
conn = service.connect_to_odoo_db()

if conn:
    print('✓ Connection successful!')
    cursor = conn.cursor()
    cursor.execute('SELECT count(*) FROM product_template WHERE sale_ok=true AND active=true;')
    print(f'✓ Found {cursor.fetchone()[0]} saleable products')
    conn.close()
else:
    print('✗ Connection failed!')
"
```

---

## 8. Snocart API Configuration

### Get Your API Key

**Method 1: From Vendor Panel**

1. Login to Snocart: https://new.snocart.com/vendor/auth/login
2. Go to: Profile → API Settings → Generate API Key
3. Copy the key

**Method 2: From Database**

```bash
# If you have direct DB access to Snocart
mysql -u root -p snocart_db -e "
SELECT id, f_name, l_name, auth_token
FROM vendor_employees
WHERE store_id = 1
LIMIT 1;
"
```

### Update API Key in Config

```bash
docker exec -it snocart_pos_sync vi /app/config.json

# Update this line:
"api_key": "YOUR_ACTUAL_API_KEY_HERE"

# Save and restart
docker restart snocart_pos_sync
```

### Test API Connection

```bash
docker exec snocart_pos_sync python -c "
import requests
import json

with open('/app/config.json', 'r') as f:
    config = json.load(f)

response = requests.get(
    f\"{config['api_url']}/vendor/products\",
    headers={
        'Authorization': f\"Bearer {config['api_key']}\",
        'Accept': 'application/json'
    }
)

print(f'Status: {response.status_code}')
if response.status_code == 200:
    print('✓ API connection working!')
else:
    print(f'✗ API error: {response.text[:200]}')
"
```

---

## 9. Complete Setup Checklist

Use this checklist to verify everything:

### Odoo Side ✅
- [ ] PostgreSQL running and accessible
- [ ] Database credentials known (host, port, user, password, db_name)
- [ ] Products have `sale_ok = true`
- [ ] Products have `type = 'product'`
- [ ] Products have `active = true`
- [ ] Products have `list_price > 0`
- [ ] Products have `default_code` empty (or non-numeric for new products)

### Snocart Side ✅
- [ ] Vendor account active
- [ ] API key generated
- [ ] Store ID known
- [ ] API endpoint accessible (`https://new.snocart.com/api/v1`)

### Sync Service ✅
- [ ] Docker container running (`docker ps | grep snocart_pos_sync`)
- [ ] Config file updated with correct credentials
- [ ] Database connection successful (test #2 above)
- [ ] API connection successful (test #3 above)
- [ ] No errors in logs (`docker logs snocart_pos_sync --tail 20`)

---

## 10. Quick Setup Script

Run this to configure everything at once:

```bash
#!/bin/bash

echo "=== Odoo → Snocart Sync Setup ==="

# Step 1: Gather information
read -p "Odoo Database Host [host.docker.internal]: " DB_HOST
DB_HOST=${DB_HOST:-host.docker.internal}

read -p "Odoo Database Port [5432]: " DB_PORT
DB_PORT=${DB_PORT:-5432}

read -p "Odoo Database Name [odoo17]: " DB_NAME
DB_NAME=${DB_NAME:-odoo17}

read -p "Odoo Database User [odoo]: " DB_USER
DB_USER=${DB_USER:-odoo}

read -sp "Odoo Database Password: " DB_PASS
echo

read -p "Snocart API Key: " API_KEY

read -p "Snocart Store ID [1]: " STORE_ID
STORE_ID=${STORE_ID:-1}

# Step 2: Create config
cat > /tmp/odoo_sync_config.json << EOF
{
  "api_url": "https://new.snocart.com/api/v1",
  "api_key": "${API_KEY}",
  "store_id": ${STORE_ID},

  "db_host": "${DB_HOST}",
  "db_port": ${DB_PORT},
  "db_name": "${DB_NAME}",
  "db_user": "${DB_USER}",
  "db_password": "${DB_PASS}",

  "sync_interval": 300,
  "stock_push_interval": 3600
}
EOF

# Step 3: Copy to Docker
echo "Copying config to Docker..."
docker cp /tmp/odoo_sync_config.json snocart_pos_sync:/app/config.json

# Step 4: Restart service
echo "Restarting sync service..."
docker restart snocart_pos_sync

# Step 5: Wait and test
echo "Waiting for service to start..."
sleep 5

echo "Testing connection..."
docker exec snocart_pos_sync python -c "
import sys
sys.path.insert(0, '/app')
from snocart_sync_service import SnocartSyncService

service = SnocartSyncService('/app/config.json')
conn = service.connect_to_odoo_db()

if conn:
    print('✓ Database connection successful!')
    conn.close()
else:
    print('✗ Database connection failed!')
"

echo ""
echo "=== Setup Complete ==="
echo "Check logs: docker logs snocart_pos_sync --tail 20"
```

Save as `setup_sync.sh`, make executable, and run:

```bash
chmod +x setup_sync.sh
./setup_sync.sh
```

---

## 11. Troubleshooting

### Problem: "Connection refused"

**Solution:** PostgreSQL not allowing external connections

```bash
# Check if PostgreSQL is listening
sudo netstat -tlnp | grep 5432

# Should show: 0.0.0.0:5432 or :::5432
# If shows 127.0.0.1:5432 only → follow Network Configuration (section 6)
```

### Problem: "password authentication failed"

**Solution:** Wrong credentials in config.json

```bash
# Test manually
psql -h host.docker.internal -U odoo -d odoo17
# If this works, copy the exact same credentials to config.json
```

### Problem: "database does not exist"

**Solution:** Wrong database name

```bash
# List all databases
sudo -u postgres psql -c "\l" | grep odoo

# Update config.json with correct database name
```

### Problem: "Found 0 new products" (but products exist)

**Solution:** Products may already have Snocart IDs, or don't meet criteria

```sql
-- Check what sync service sees
SELECT
    pt.id,
    COALESCE(
        pt.name::jsonb->>'en_US',
        pt.name::jsonb->>'default',
        (pt.name::jsonb->>0)
    ) as name,
    pt.sale_ok,
    pt.active,
    pt.type,
    pt.default_code
FROM product_template pt
WHERE pt.sale_ok = true
    AND pt.active = true
    AND pt.type = 'product'
    AND (pt.default_code IS NULL OR pt.default_code NOT SIMILAR TO '[0-9]+')
LIMIT 10;
```

---

Need help? Check logs:
```bash
docker logs snocart_pos_sync --tail 50 --follow
```
