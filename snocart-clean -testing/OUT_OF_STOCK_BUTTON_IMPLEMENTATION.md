# Out of Stock Button - Order Edit V2

## Implementation Summary (2026-03-27)

Added a prominent "Out of Stock" button to the item list in Order Edit V2 page.

## What Was Changed

### 1. CSS Styling (`resources/views/admin-views/order/edit-v2.blade.php`)

Added new prominent red button style with gradient and pulse animation:

```css
.v2-btn-out-of-stock {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: #fff;
    border: 2px solid #dc2626;
    font-weight: 700;
    font-size: 13px;
    padding: 8px 16px;
    box-shadow: 0 2px 8px rgba(239, 68, 68, 0.3);
    animation: outofstock-pulse 2s infinite;
}
```

### 2. JavaScript Rendering (`public/assets/admin/js/order-edit-v2.js`)

Updated the `renderCart()` function to display the new button:

**Before:**
```javascript
html += '<button class="v2-action-btn v2-btn-unavail v2-unavail-btn" ...>
         <i class="tio-block-outlined"></i> Unavailable</button>';
```

**After:**
```javascript
html += '<button class="v2-action-btn v2-btn-out-of-stock v2-unavail-btn" ...>
         <i class="tio-shopping-cart-outlined"></i> <strong>Out of Stock</strong></button>';
```

### 3. Translation Keys

Added new translation key reference:
```javascript
itemMarkedOutOfStock: '{{ translate('messages.item_marked_as_out_of_stock') }}'
```

Uses existing translation: `item_marked_as_out_of_stock` (already in `messages.php`)

### 4. Toast Notification

Updated success message to show "Item marked as out of stock" instead of "Item marked as unavailable"

## How It Works

1. **Button appears** on each cart item (only for existing order items with `order_detail_id`)
2. **Click** the "⚠️ Out of Stock" button to mark item as unavailable
3. **Backend** uses existing `markItemUnavailable` route and controller method
4. **Database** updates `order_details.is_unavailable = 1`
5. **Item appearance** changes:
   - Opacity reduced to 60%
   - Background becomes gray (#f3f4f6)
   - Shows "UNAVAILABLE" badge
6. **Recalculation** excludes item from order total
7. **Button changes** to green "Mark Available" button

## Visual Features

- **Red gradient** background (#ef4444 → #dc2626)
- **Pulse animation** (shadow grows/shrinks every 2 seconds)
- **Shopping cart icon** for visibility
- **Bold text** "Out of Stock" in white
- **Hover effect** with lift animation
- **Prominent size** (larger padding: 8px × 16px)

## Backend Integration

Uses existing backend infrastructure:
- Route: `POST /admin/order/mark-item-unavailable`
- Controller: `OrderController::markItemUnavailable()`
- Database field: `order_details.is_unavailable`
- Session cart sync included (prevents overwrite on Save)

## Files Modified

1. `resources/views/admin-views/order/edit-v2.blade.php` (+24 lines CSS, +1 translation key)
2. `public/assets/admin/js/order-edit-v2.js` (+3 lines rendering, +1 line toast)

## Zero Breaking Changes

- No database migrations needed
- No new routes required
- Uses existing backend logic
- 100% backward compatible
- No changes to other features

## Testing

Test at: `/admin/order/edit-v2/109969`

1. Open any order in Edit V2 mode
2. Look for red "Out of Stock" button on each cart item
3. Click button → Item marked unavailable
4. Button changes to green "Mark Available"
5. Click again → Item becomes available again

## Rollback

If needed, revert changes to:
1. `resources/views/admin-views/order/edit-v2.blade.php` (CSS + translation)
2. `public/assets/admin/js/order-edit-v2.js` (rendering + toast)

No database changes, so rollback is instant.

---

**Status:** ✅ Production Ready
**Date:** 2026-03-27
**Version:** 1.0
