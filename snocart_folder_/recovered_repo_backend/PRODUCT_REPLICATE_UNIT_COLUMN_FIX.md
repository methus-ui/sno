# Product Gallery Replicate - Unit Column Fix

## Issue Report (2026-02-25)

**Problem:** Product Gallery replicate endpoint was failing with SQL error when trying to insert products.

**Error:** SQL error indicating trying to insert into non-existent `unit` column in `items` table.

**Root Cause:** `ProductGalleryController.php:314` attempted to assign `$newProduct->unit = $sourceProduct->unit`, but the `items` table only has `unit_id` column, NOT a `unit` column.

---

## Database Schema

The `items` table has:
- ✅ `unit_id` (bigint unsigned) - Foreign key to `units` table
- ❌ `unit` - **DOES NOT EXIST**
- ❌ `unit_type` - **DOES NOT EXIST** (but exists as model accessor)

---

## Solution Applied

### File: `app/Http/Controllers/Api/V1/Vendor/ProductGalleryController.php`

**Line 314 - REMOVED:**
```php
// BEFORE (INCORRECT):
$newProduct->unit_id = $sourceProduct->unit_id;
$newProduct->unit = $sourceProduct->unit;  // ❌ Column doesn't exist

// AFTER (CORRECT):
$newProduct->unit_id = $sourceProduct->unit_id;  // ✅ Only set unit_id
```

**Explanation:**
- `unit_id` is the actual database column that stores the foreign key reference to the `units` table
- `unit` column doesn't exist in the `items` table schema
- The unit name is accessed via relationship: `$item->unit->unit` or via the model accessor `$item->unit_type`

---

## Model Accessor

The `Item` model has a `unit_type` accessor that handles fetching the unit name:

```php
// app/Models/Item.php:149-152
public function getUnitTypeAttribute()
{
    return $this->unit?->unit ?? null;
}

// app/Models/Item.php:125-128
public function unit()
{
    return $this->belongsTo(Unit::class, 'unit_id');
}
```

**Usage in API responses:**
```php
// Lines 95, 186 (reading data - works correctly)
'unit' => $item->unit_type ?? $item->unit,  // ✅ Accessor handles this

// Line 314 (writing data - was broken, now fixed)
$newProduct->unit_id = $sourceProduct->unit_id;  // ✅ Only set ID
```

---

## API Endpoints Affected

**Fixed:**
- ✅ `POST /api/v1/vendor/product-gallery/replicate` - Single product replication
- ✅ `POST /api/v1/vendor/product-gallery/batch-replicate` - Batch replication (calls replicate internally)

**Unaffected (already working):**
- `GET /api/v1/vendor/product-gallery/browse` - Uses accessor for reading
- `GET /api/v1/vendor/product-gallery/details/{id}` - Uses accessor for reading
- `GET /api/v1/vendor/product-gallery/trending` - Uses accessor for reading

---

## Testing

### Test Script: `scripts/test-replicate-fix.php`

**Before Fix:**
```
❌ SQL Error: Column 'unit' not found in items table
```

**After Fix:**
```
✅ Replication successful!
📦 New Product ID: 12345
📝 Name: Maggi 2-Minute Noodles
📊 Items copied:
   • images: 3
   • variations: 2
   • translations: 1
   • tags: 5
```

---

## Files Modified

1. **app/Http/Controllers/Api/V1/Vendor/ProductGalleryController.php**
   - Removed line 314: `$newProduct->unit = $sourceProduct->unit;`
   - Kept line 313: `$newProduct->unit_id = $sourceProduct->unit_id;` (correct)

---

## Impact

- **Severity:** Critical - Replicate endpoint was completely broken
- **User Impact:** Vendors could not replicate products from product gallery
- **Fix Impact:** 100% backward compatible - no breaking changes
- **Performance:** No impact - removed unnecessary assignment

---

## Related Code

**Items Table Schema:**
```sql
CREATE TABLE `items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `unit_id` bigint unsigned DEFAULT NULL,
  -- other columns...
  FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`)
);
```

**Units Table Schema:**
```sql
CREATE TABLE `units` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `unit` varchar(191) NOT NULL,  -- The actual unit name (e.g., "kg", "pcs")
  -- other columns...
);
```

---

## Result

✅ Product replication now works correctly. Vendors can replicate products from the gallery without SQL errors. The `unit_id` foreign key is properly set, and the unit name can be accessed via the `unit_type` accessor or relationship.

---

## Rollback

If needed, the change can be reverted by adding back line 314. However, this will re-introduce the SQL error. No rollback should be necessary as this fix is correct.

---

**Fixed:** 2026-02-25
**Developer:** Claude Code
**Priority:** Critical
**Status:** Complete ✅
