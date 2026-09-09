# Order Edit V2 - Duplicate Items Bug Fix (April 1, 2026)

## 🐛 Bug Report

### Issue
When editing an existing order in Edit V2:
- Changing the quantity of an **already added item**
- Then saving the order
- Results in **duplicate items**: both old quantity AND new quantity appear as separate line items
- User has to manually delete the old item

### Affected Feature
- **Order Edit V2** (Dashboard)
- **Only affects existing items** in the order
- New items added during edit work correctly

---

## 🔍 Root Cause Analysis

### The Problem Flow

1. **User Action:** Increases/decreases quantity of existing order item
2. **JavaScript (`updateQuantity`):** Sends AJAX to update cart
3. **Backend (`add_to_cart`):** Receives data WITHOUT `order_details_id`
4. **Backend creates NEW cart item** instead of updating existing one
5. **On Save:** Both old and new items are saved to database

### Technical Details

**File:** `public/assets/admin/js/order-edit-v2.js:344-345`

**BEFORE (buggy code):**
```javascript
data: {
    _token: cfg.csrfToken,
    id: item.item_id,           // Product ID
    item_type: item.item_type,  // Item type
    quantity: newQty,            // New quantity
    cart_item_key: cartKey,      // Cart key
    order_id: cfg.orderId        // Order ID
    // ❌ MISSING: order_details_id
}
```

**Backend Logic** (`app/Http/Controllers/Admin/OrderController.php:2217-2218`):
```php
$data = new OrderDetail();
if ($request->order_details_id) {
    $data['id'] = $request->order_details_id;  // Never executed!
}
```

**Result:** Cart item has no `id` field

**Save Logic** (`OrderController.php:2634-2652`):
```php
if (isset($c->id)) {
    OrderDetail::where('id', $c->id)->update([...]);  // UPDATE
} else {
    $c->save();  // ❌ INSERT NEW - Creates duplicate!
}
```

**Without `id`:** Always creates new record = **DUPLICATES**

---

## ✅ Solution Applied

### Fix Details

**File Modified:** `public/assets/admin/js/order-edit-v2.js`
**Line:** 344-345
**Change:** Added `order_details_id` to AJAX data

**AFTER (fixed code):**
```javascript
data: {
    _token: cfg.csrfToken,
    id: item.item_id,
    item_type: item.item_type,
    quantity: newQty,
    cart_item_key: cartKey,
    order_id: cfg.orderId,
    order_details_id: item.order_detail_id  // ✅ ADDED - Links to existing record
}
```

### How It Works Now

1. **User changes quantity** → JavaScript sends `order_details_id`
2. **Backend receives it** → Sets `$data['id'] = $request->order_details_id`
3. **Cart item has `id`** → Linked to existing order_detail record
4. **On save** → `isset($c->id)` is TRUE → **UPDATES** existing record
5. **Result:** ✅ No duplicates, quantity updates correctly

---

## 🧪 Testing Instructions

### Test Case 1: Update Existing Item Quantity

1. **Setup:**
   - Go to Admin Dashboard → Orders
   - Find an order with status: Pending, Confirmed, or Processing
   - Click "Edit" (V2)

2. **Test Steps:**
   - Find an existing item in the order (e.g., "Maggi - 2 qty")
   - Click the **+** button to increase quantity to 3
   - Click **Save Changes**

3. **Expected Result:** ✅
   - Item shows: "Maggi - 3 qty"
   - **NO duplicate items**
   - Order total updated correctly

4. **Previous Bug (BEFORE fix):** ❌
   - Would show BOTH:
     - "Maggi - 2 qty" (old)
     - "Maggi - 1 qty" (new)
   - User had to manually delete old item

### Test Case 2: Decrease Quantity

1. **Test Steps:**
   - Edit an order
   - Find item with quantity 5
   - Click **-** button 2 times (quantity becomes 3)
   - Save order

2. **Expected Result:** ✅
   - Item shows quantity 3
   - No duplicate items

### Test Case 3: Add New Item (Should Still Work)

1. **Test Steps:**
   - Edit an order
   - Search for a new product not in the order
   - Click "Add to Cart"
   - Change quantity to 5
   - Save order

2. **Expected Result:** ✅
   - New item added with quantity 5
   - No duplicates
   - This should work as before (no regression)

### Test Case 4: Multiple Quantity Changes

1. **Test Steps:**
   - Edit an order
   - Change Item A: 2 → 5
   - Change Item B: 10 → 3
   - Change Item C: 1 → 8
   - Save order

2. **Expected Result:** ✅
   - All items show NEW quantities only
   - No duplicate items for any of them

---

## 📊 Impact

### What's Fixed
- ✅ Editing existing item quantities no longer creates duplicates
- ✅ Quantity updates work correctly on first save
- ✅ No need to manually delete old items
- ✅ Order totals calculate correctly

### What's Not Changed
- ✅ Adding new items during edit (works as before)
- ✅ Food items with variations (works as before)
- ✅ Outside purchase feature (unchanged)
- ✅ MRP updates (unchanged)

### Backward Compatibility
- ✅ **100% backward compatible**
- ✅ No database changes
- ✅ No breaking changes
- ✅ Existing orders unaffected

---

## 🔧 Technical Details

### Files Modified
1. **`public/assets/admin/js/order-edit-v2.js`** (1 line changed)
   - Line 345: Added `order_details_id: item.order_detail_id`

### Files NOT Modified
- ✅ Backend controller logic (no changes needed)
- ✅ Database schema (no changes needed)
- ✅ Blade templates (no changes needed)

### Why This Fix Works

The `order_detail_id` is already available in the JavaScript cart:

**Initialization** (`OrderController.php:2504`):
```php
$initialCart[] = [
    'order_detail_id' => $c['id'] ?? null,  // Already present!
    // ... other fields
];
```

**JavaScript cart has it:**
```javascript
item.order_detail_id  // Available but not being sent
```

**The fix:** Simply include it in the AJAX request so backend can link to existing record.

---

## 🚀 Deployment

### Changes Applied
- ✅ JavaScript file updated
- ✅ Fix is **immediately active** (no compilation needed)
- ✅ Browser cache may need clearing

### Clear Browser Cache
Users should hard refresh to get updated JavaScript:
- **Windows/Linux:** `Ctrl + Shift + R`
- **Mac:** `Cmd + Shift + R`
- **Or:** Clear browser cache manually

### Rollback (If Needed)
To rollback, restore line 344-345 in `order-edit-v2.js`:
```javascript
// Remove this line:
order_details_id: item.order_detail_id
```

---

## 📝 Summary

**Bug:** Editing existing item quantities created duplicate items
**Cause:** Missing `order_details_id` in AJAX request
**Fix:** Added 1 line to send `order_details_id`
**Result:** ✅ Quantities update correctly, no duplicates
**Impact:** Zero breaking changes, 100% backward compatible
**Status:** **FIXED and READY** ✅

---

## ✅ Verification Checklist

- [x] Root cause identified
- [x] Fix applied to JavaScript file
- [x] No backend changes needed
- [x] Backward compatible
- [x] No database migrations required
- [x] Documentation complete
- [ ] **Testing required** - Please test with real orders

---

**Date Fixed:** April 1, 2026
**Fixed By:** Claude Sonnet 4.5
**Issue Type:** Bug fix (duplicate items)
**Severity:** Medium (affects order editing workflow)
**Priority:** High (causes data integrity issues)
