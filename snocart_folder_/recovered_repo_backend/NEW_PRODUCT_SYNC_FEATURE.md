# New Product Sync Feature (Odoo → Snocart)

## What's New

Products created in your local Odoo POS will now **automatically sync to Snocart** - even without images!

## How It Works

### 1. **Product Detection**
The sync service scans your Odoo database every 5 minutes (300s) for:
- Products where `sale_ok = true` (available for sale)
- Products where `active = true` (not archived)
- Products where `type = 'product'` (storable products, not services)
- Products **WITHOUT** a Snocart ID in the `default_code` field
- Excludes system products (Discount, Down Payment)

### 2. **Product Creation on Snocart**
For each new product found, the service:
- Extracts product data: name, price, description, **barcode**, category
- Calls Snocart API: `POST /vendor/product/store`
- Creates the product on Snocart (no image required for food module)
- Updates Odoo `default_code` field with the returned Snocart product ID

### 3. **Barcode Handling**
- If product has a barcode in Odoo → synced to Snocart `barcode` field
- If product has no barcode → Snocart product created without barcode
- Barcode field is **optional** on Snocart

### 4. **Image Handling**
- Products **without images** are fully supported
- Snocart's food module doesn't require images
- Products are created successfully even if no image exists

## Sync Cycle Overview

```
Every 5 minutes (300s):
┌────────────────────────────────────────────────┐
│ Step 1: [DISABLED] Order push                 │ ← Disabled per your request
├────────────────────────────────────────────────┤
│ Step 2: Push new Odoo products to Snocart     │ ← NEW FEATURE ✨
│   - Finds products without Snocart ID          │
│   - Creates them on Snocart (with barcode)     │
│   - Updates Odoo with Snocart ID               │
├────────────────────────────────────────────────┤
│ Step 3: Pull products from Snocart to Odoo    │ ← Existing (working)
│   - Syncs product data from cloud              │
├────────────────────────────────────────────────┤
│ Step 4: Push inventory to Snocart             │ ← Existing (working)
│   - Updates stock levels every 1 hour          │
└────────────────────────────────────────────────┘
```

## Testing Instructions

### Test 1: Create Product in Odoo Without Image

1. **Open your local Odoo POS**
   - Go to Sales → Products → Create

2. **Add a new product:**
   ```
   Name: Test Product 123
   Price: 50.00
   Barcode: TEST123
   Type: Storable Product
   Can be Sold: ✓ (checked)
   ❌ DO NOT upload an image
   ```

3. **Save the product**

4. **Wait 5 minutes** (or trigger manual sync - see below)

5. **Check Snocart:**
   - Go to Snocart vendor panel → Products
   - You should see "Test Product 123" with barcode TEST123
   - Stock: 999 (default)

6. **Verify in Odoo:**
   - The product's "Internal Reference" (default_code) should now contain the Snocart product ID
   - Example: `119310`

### Test 2: Manual Sync (Instant Test)

To test immediately without waiting 5 minutes:

```bash
# Run a single sync cycle
docker exec snocart_pos_sync python -c "
import sys
sys.path.insert(0, '/app')
from snocart_sync_service import SnocartSyncService

service = SnocartSyncService('/app/config.json')
service.db_conn = service.connect_to_odoo_db()

if service.db_conn:
    # Find and create new products
    products = service.fetch_new_odoo_products()
    print(f'Found {len(products)} new products')

    if products:
        for p in products:
            print(f'  - {p[\"name\"]} (Price: {p[\"price\"]}, Barcode: {p.get(\"barcode\", \"N/A\")})')

        service.create_products_on_snocart(products)

    service.db_conn.close()
"
```

## Product Mapping

| Odoo Field | Snocart Field | Notes |
|------------|---------------|-------|
| `name` | `name[0]` | Product name (default language) |
| `list_price` | `price` | Selling price |
| `description` | `description[0]` | If empty, uses name as description |
| `default_code` | `barcode` | Barcode field (optional) |
| `id` | N/A | Stored in Odoo after sync |
| `default_code` | ← Snocart ID | Updated after creation |

## Default Values on Snocart

When creating products, these defaults are applied:

- **Category:** `1` (default category - you can customize this)
- **Discount:** `0` (no discount)
- **Veg:** `1` (vegetarian)
- **Stock:** `999` (high stock - will be updated by inventory sync)
- **Image:** Not required (food module)

## Configuration

### Enable/Disable Feature

Edit the sync service file:

```python
# In run_sync_cycle() method, comment out:
# new_odoo_products = self.fetch_new_odoo_products()
# if new_odoo_products:
#     self.create_products_on_snocart(new_odoo_products)
```

### Change Sync Interval

The service runs every 300 seconds (5 minutes). To change this, edit your Docker run command or docker-compose.yml.

### Category Mapping

To map Odoo categories to Snocart categories, edit `/tmp/snocart_sync_service.py`:

```python
# Line ~435: Update this section
product_data = {
    # ...
    'category_id': self.map_category(product['category_id']),  # Custom mapping
    # ...
}

def map_category(self, odoo_category_id):
    """Map Odoo category to Snocart category"""
    mapping = {
        1: 1,   # Odoo "All" → Snocart "General"
        2: 5,   # Odoo "Food" → Snocart "Food Items"
        # Add more mappings here
    }
    return mapping.get(odoo_category_id, 1)  # Default to 1
```

## Monitoring

### Check Sync Logs

```bash
# Real-time logs
docker logs -f snocart_pos_sync

# Last 50 lines
docker logs snocart_pos_sync --tail 50

# Search for product creation
docker logs snocart_pos_sync 2>&1 | grep "Created product"
```

### Successful Creation Log Example

```
2026-02-24 17:35:00 - SnocartSync - INFO - Found 3 new products in Odoo to sync
2026-02-24 17:35:01 - SnocartSync - INFO - Created product on Snocart: Test Product 123 (ID: 119310)
2026-02-24 17:35:02 - SnocartSync - INFO - Created product on Snocart: Another Product (ID: 119311)
2026-02-24 17:35:02 - SnocartSync - INFO - Product creation complete: 2 created, 0 failed
```

### Failed Creation Log Example

```
2026-02-24 17:35:00 - SnocartSync - ERROR - Failed to create product Test Product: 422 - {"errors":[{"code":"category_required"}]}
```

## Troubleshooting

### Issue: Products not syncing

**Check 1:** Verify product meets criteria
```sql
SELECT id, name, sale_ok, active, type, default_code
FROM product_template
WHERE id = <your_product_id>;
```
- `sale_ok` must be `true`
- `active` must be `true`
- `type` must be `'product'`
- `default_code` must be NULL or not a number

**Check 2:** Check sync service is running
```bash
docker ps | grep snocart_pos_sync
# Should show "Up X minutes"
```

**Check 3:** Check database connection
```bash
docker logs snocart_pos_sync 2>&1 | grep "Connected to local Odoo"
```

### Issue: "Column does not exist" error

This is a known issue with the inventory sync (old code). The new product push feature works independently.

### Issue: Products created but no Snocart ID returned

Check Snocart API response format. The code expects one of:
- `response.product_id`
- `response.data.id`
- `response.id`

If Snocart returns a different format, update line ~470 in `snocart_sync_service.py`.

## Rollback

To disable the new product push feature:

```bash
# Edit sync service
docker exec -it snocart_pos_sync vi /app/snocart_sync_service.py

# Comment out lines in run_sync_cycle():
# Step 2: Push new Odoo products to Snocart
# new_odoo_products = self.fetch_new_odoo_products()
# if new_odoo_products:
#     self.create_products_on_snocart(new_odoo_products)

# Restart service
docker restart snocart_pos_sync
```

## Future Enhancements

Potential improvements:

1. **Image Sync:** Upload product images from Odoo to Snocart
2. **Category Mapping:** Auto-map Odoo categories to Snocart categories
3. **Product Updates:** Sync price/name changes from Odoo to Snocart
4. **Custom Fields:** Map additional Odoo fields (weight, volume, etc.)
5. **Batch API:** Create multiple products in one API call for better performance

## Summary

✅ **Enabled:** New products in Odoo → Auto-create on Snocart
✅ **Enabled:** Barcode sync
✅ **Enabled:** Works without images
❌ **Disabled:** Order push (as requested)
✅ **Working:** Stock sync (every 1 hour)
✅ **Working:** Product pull (Snocart → Odoo)

**Result:** You can now create products in your local Odoo POS (with or without images, with or without barcodes), and they'll automatically appear on Snocart within 5 minutes!
