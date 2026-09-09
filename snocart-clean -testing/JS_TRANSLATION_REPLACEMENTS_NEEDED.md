# JavaScript Translation Replacements Needed

**File:** `public/assets/admin/js/order-edit-v2.js`

## Translation Keys Added ✅

All 32 translation keys have been added to:
- ✅ `/resources/lang/en/messages.php`
- ✅ `/resources/lang/ar/messages.php`
- ⏸️ Bengali (skipped per user request)

## Translation Helper Added ✅

**Location:** Line 146-149

```javascript
// ─── Translation Helper ───────────────────────────────────────────────────
function t(key) {
    return (cfg.trans && cfg.trans[key]) || key;
}
```

## Replacements Needed in order-edit-v2.js

### Critical Messages (Required for User-Facing Errors)

| Line | Current Code | Replace With |
|------|--------------|--------------|
| 12 | `'Saving changes...'` | `t('savingChanges')` |
| 92 | `'Network error. Check your connection.'` | `t('networkError')` |
| 94 | `'Session expired. Please log in again.'` | `t('sessionExpired')` |
| 97 | `'Permission denied.'` | `t('permissionDenied')` |
| 99 | `'Resource not found.'` | `t('resourceNotFound')` |
| 103 | `'Validation failed.'` | `t('validationFailed')` |
| 105 | `'Server error. Contact support.'` | `t('serverError')` |
| 107 | `'Unexpected error.'` | `t('unexpectedError')` |
| 209 | `'You have unsaved changes. Are you sure you want to leave?'` | `t('unsavedChanges')` |
| 230 | `'Search failed.'` | `t('searchFailed')` |
| 232 | `'Error loading catalog.'` | `t('errorLoadingCatalog')` |
| 256 | `'Please select variation options.'` | `t('pleaseSelectVariation')` |
| 267 | `'Error adding item.'` | `t('errorAddingItem')` |
| 333 | `'Failed to update quantity. Please try again.'` | `t('failedToUpdateQty')` |
| 350 | `'Could not remove item.'` | `t('couldNotRemoveItem')` |
| 557 | `'No change'` | `t('noChange')` |
| 600 | `'Item marked as unavailable.'` | `t('itemMarkedUnavailable')` |
| 600 | `'Item marked as available.'` | `t('itemMarkedAvailable')` |
| 602 | `'Failed to update item.'` | `t('failedToUpdateItem')` |
| 636 | `'Please enter a valid purchase cost.'` | `t('pleaseEnterValidCost')` |
| 649 | `'Outside purchase recorded.'` | `t('outsidePurchaseRecorded')` |
| 653 | `'Failed.'` | `t('failed')` |
| 673 | `'Approved.'` | `t('approved')` |
| 706 | `'Rejected.'` | `t('rejected')` |
| 737 | `'Please enter a valid price.'` | `t('pleaseEnterValidPrice')` |
| 755 | `'Price updated.'` | `t('priceUpdated')` |
| 781 | `'MRP approved.'` | `t('mrpApproved')` |
| 808 | `'MRP rejected.'` | `t('mrpRejected')` |
| 892 | `'No product found for barcode: '` | `t('noProductFoundForBarcode') + ': '` |

### Less Critical (Internal/Fallback Messages)

| Line | Message | Priority |
|------|---------|----------|
| 465 | `'Outside Purchase'` | Medium - Button text |
| 465 | `'Edit O.P.'` | Medium - Button text |
| 425 | `'Item'` | Low - Fallback for missing item name |
| 572 | `'No change'` | Low - Duplicate, CSS display |

## Quick Replace Script

To quickly replace all messages in one go:

```bash
cd /var/www/html/new_public/new/public/assets/admin/js

# Backup first
cp order-edit-v2.js order-edit-v2.js.backup

# Replace messages (use sed for bulk replacement)
sed -i "s/'Saving changes...'/t('savingChanges')/g" order-edit-v2.js
sed -i "s/'Network error. Check your connection.'/t('networkError')/g" order-edit-v2.js
sed -i "s/'Session expired. Please log in again.'/t('sessionExpired')/g" order-edit-v2.js
sed -i "s/'Permission denied.'/t('permissionDenied')/g" order-edit-v2.js
sed -i "s/'Resource not found.'/t('resourceNotFound')/g" order-edit-v2.js
sed -i "s/'Validation failed.'/t('validationFailed')/g" order-edit-v2.js
sed -i "s/'Server error. Contact support.'/t('serverError')/g" order-edit-v2.js
sed -i "s/'Unexpected error.'/t('unexpectedError')/g" order-edit-v2.js
sed -i "s/'You have unsaved changes. Are you sure you want to leave?'/t('unsavedChanges')/g" order-edit-v2.js
sed -i "s/'Search failed.'/t('searchFailed')/g" order-edit-v2.js
sed -i "s/'Error loading catalog.'/t('errorLoadingCatalog')/g" order-edit-v2.js
sed -i "s/'Please select variation options.'/t('pleaseSelectVariation')/g" order-edit-v2.js
sed -i "s/'Error adding item.'/t('errorAddingItem')/g" order-edit-v2.js
sed -i "s/'Failed to update quantity. Please try again.'/t('failedToUpdateQty')/g" order-edit-v2.js
sed -i "s/'Could not remove item.'/t('couldNotRemoveItem')/g" order-edit-v2.js
sed -i "s/'Item marked as unavailable.'/t('itemMarkedUnavailable')/g" order-edit-v2.js
sed -i "s/'Item marked as available.'/t('itemMarkedAvailable')/g" order-edit-v2.js
sed -i "s/'Failed to update item.'/t('failedToUpdateItem')/g" order-edit-v2.js
sed -i "s/'Please enter a valid purchase cost.'/t('pleaseEnterValidCost')/g" order-edit-v2.js
sed -i "s/'Outside purchase recorded.'/t('outsidePurchaseRecorded')/g" order-edit-v2.js
sed -i "s/'Approved.'/t('approved')/g" order-edit-v2.js
sed -i "s/'Rejected.'/t('rejected')/g" order-edit-v2.js
sed -i "s/'Please enter a valid price.'/t('pleaseEnterValidPrice')/g" order-edit-v2.js
sed -i "s/'Price updated.'/t('priceUpdated')/g" order-edit-v2.js
sed -i "s/'MRP approved.'/t('mrpApproved')/g" order-edit-v2.js
sed -i "s/'MRP rejected.'/t('mrpRejected')/g" order-edit-v2.js
sed -i "s/'No product found for barcode: '/t('noProductFoundForBarcode') + ': '/g" order-edit-v2.js
sed -i "s/'Something went wrong.'/t('somethingWentWrong')/g" order-edit-v2.js
sed -i "s/'Failed.'/t('failed')/g" order-edit-v2.js

# Also fix "No change" instances
sed -i "s/'No change'/t('noChange')/g" order-edit-v2.js
```

## Status

- ✅ Translation helper `t()` added
- ✅ Translations passed via config
- ✅ 32 keys added to messages.php (EN + AR)
- ⏸️ JavaScript replacements pending (~30 instances)

## Recommendation

**Option 1: Bulk Replace (Fastest)**
Run the sed script above to replace all at once

**Option 2: Manual Replace (Safest)**
Replace each instance manually to ensure no regex issues

**Option 3: Hybrid**
Replace critical user-facing messages first, leave internal/debug messages as-is

## Testing After Replacement

1. Clear browser cache
2. Test each feature:
   - Search (error messages)
   - Add to cart (variation errors)
   - Update quantity
   - Mark unavailable
   - Outside purchase
   - MRP update/approval
   - Barcode scanning
3. Verify toastr notifications appear in English
4. Check console for translation errors

## Rollback

```bash
cp order-edit-v2.js.backup order-edit-v2.js
```

---

**Total Replacements Needed:** ~30 strings
**Estimated Time:** 5-10 minutes (bulk) or 30 minutes (manual)
