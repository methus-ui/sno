# MRP Update Fix - Summary

**Date:** 2026-02-20
**Issue:** MRP updates in Order Edit V2 were not persisting correctly

---

## The Problem

When updating MRP (Maximum Retail Price) in Order Edit V2:
1. ✅ Database was being updated correctly
2. ✅ Master item price was being updated
3. ❌ **BUT** the change was being reverted within seconds

### Root Cause

**JavaScript Auto-Save Bug:**
- After MRP update, JavaScript called `refreshCartFromServer()`
- Server returned cart from **SESSION** (which had old price)
- JavaScript's `markChanged()` triggered auto-save
- Auto-save wrote the OLD price from session back to database

**Flow:**
```
1. User updates MRP to 555 → Database: 555 ✅
2. JS calls refreshCartFromServer() → Loads from SESSION: 123.45
3. JS local cart now has: 123.45
4. markChanged() triggers auto-save
5. Auto-save writes to database: 123.45 ❌
```

---

## The Solution

**Update SESSION immediately after database update:**

In `updateItemMrp()` and `updateCampaignMrp()` methods:
```php
// After database commit, update session cart
$sessionCart = session('order_cart', collect([]));
if ($sessionCart instanceof \Illuminate\Support\Collection) {
    foreach ($sessionCart as $key => $cartItem) {
        if (isset($cartItem['id']) && in_array($cartItem['id'], $detailIds)) {
            $sessionCart[$key]['price'] = $newPrice;
        }
    }
    session()->put('order_cart', $sessionCart);
}
```

**New Flow:**
```
1. User updates MRP to 555 → Database: 555 ✅
2. Update session cart → Session: 555 ✅
3. JS calls refreshCartFromServer() → Loads from SESSION: 555 ✅
4. markChanged() triggers auto-save
5. Auto-save writes to database: 555 ✅
```

---

## Files Modified

### 1. `/app/Http/Controllers/Admin/OrderController.php`
- ✅ Cleaned up `updateItemMrp()` - removed debug logging
- ✅ Added session cart update after database commit
- ✅ Cleaned up `updateCampaignMrp()` - removed debug logging
- ✅ Added session cart update for campaigns
- ✅ Used raw SQL UPDATE for better performance

### 2. `/routes/admin.php`
- ✅ Removed route-level debug logging
- ✅ Changed route from closure to direct controller method

### 3. `/public/assets/admin/js/order-edit-v2.js`
- ✅ Removed console.log debug statements
- ✅ Cleaned up submitMrpChange() function

---

## Testing

**Test Steps:**
1. Go to Order Edit V2
2. Click "Change MRP" on any item
3. Enter new price
4. Click Submit
5. ✅ Price updates in database
6. ✅ Price updates in session
7. ✅ Order view shows correct price
8. ✅ Auto-save doesn't revert the change

**Status:** ✅ WORKING - Verified 2026-02-20

---

## Impact

- ✅ MRP updates now persist correctly
- ✅ Both item and campaign MRP updates work
- ✅ Session and database stay in sync
- ✅ Auto-save no longer reverts changes
- ✅ Code cleaned up (removed all debug logging)
- ✅ Performance improved (using raw SQL UPDATE)

---

## Technical Details

**Key Changes:**
1. **Session Sync:** Added session update after every MRP change
2. **Raw SQL:** Used `DB::table()->update()` for better performance
3. **Transaction Safety:** All changes wrapped in DB transactions
4. **Code Cleanup:** Removed 100+ lines of debug logging
5. **Consistency:** Same fix applied to both item and campaign MRP

**Database Tables Updated:**
- `items` - Master item price
- `item_campaigns` - Master campaign price
- `order_details` - Order-specific price
- Session: `order_cart` - Session cart data

---

## Rollback

If issues occur, restore from backup:
```bash
# Backup location
/var/backups/order-edit-v2-fixes-20260219_171353/

# Restore files
git checkout HEAD~1 app/Http/Controllers/Admin/OrderController.php
git checkout HEAD~1 routes/admin.php
git checkout HEAD~1 public/assets/admin/js/order-edit-v2.js

# Restart PHP
systemctl restart php8.3-fpm
```

---

## Notes

- Master item/campaign prices ARE updated (affects future orders)
- Order details get individual price updates (affects current order only)
- Session cart stays in sync with database
- Auto-save works correctly now
- All debug logging removed for production use

---

**Status:** ✅ COMPLETE & TESTED
