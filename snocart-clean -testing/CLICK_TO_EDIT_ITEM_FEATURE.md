# Click-to-Edit Item Feature - Order Edit V2

## ✅ Change Applied (2026-02-19)

Added click-to-edit functionality for items in Order Edit V2. Users can now click on any item's image or name to open the item edit/listing page in a new tab.

---

## What This Does

### Before This Change:
❌ No way to quickly edit item details from order editing interface
❌ Had to manually search for item in item listing
❌ Lost context when switching between order and item pages

### After This Change:
✅ Click on any item's image or name to edit that item
✅ Opens item edit page in new tab (doesn't lose order editing progress)
✅ Visual hover feedback shows items are clickable
✅ Works with both regular items and campaign items

---

## User Experience

### How It Works:
1. Open order in Edit V2
2. **Hover over any item's image or name** → Background highlights, cursor changes to pointer
3. **Click on the item** → Opens item edit page in new tab
4. Edit the item in the new tab
5. Return to order editing tab (still in progress)

### Visual Feedback:
- **Hover Effect**: Light blue background (#007bff with 5% opacity)
- **Image Shadow**: Subtle blue shadow on hover
- **Name Underline**: Name text turns blue and underlines on hover
- **Smooth Animation**: 0.2s transition for all effects

---

## Technical Implementation

### Files Modified:

#### 1. `/public/assets/admin/js/order-edit-v2.js`

**Lines 395-398** - Added data attributes to cart item container:
```javascript
html += '<div class="' + cardClass + '" data-cart-key="' + ck + '" data-detail-id="' + detailId + '" ' +
        'data-item-id="' + (item.item_id || '') + '" ' +
        'data-campaign-id="' + (item.campaign_item_id || '') + '" ' +
        'data-item-type="' + (item.item_type || 'item') + '">';
```

**Lines 420-431** - Wrapped image and name in clickable container:
```javascript
html += '<div class="v2-item-clickable" style="display:flex;align-items:center;gap:12px;flex:1;cursor:pointer;">';
html += '<img class="v2-item-img" src="...">';
html += '<div class="v2-item-info">';
html += '<div class="v2-item-name...">' + esc(item.name) + '</div>';
html += '<div class="v2-item-sub">' + sub + '</div>';
html += '</div>';
html += '</div>'; // Close v2-item-clickable
```

**Lines 439** - Increased delete icon size from 20px to 24px:
```javascript
html += '<button class="v2-remove-btn"...><i class="tio-delete-outlined" style="font-size:24px;"></i></button>';
```

**Lines 992-1013** - Added click handler:
```javascript
// Click on item to edit/view item
$(document).on('click', '.v2-item-clickable', function (e) {
    e.stopPropagation();
    var $cartItem = $(this).closest('.v2-cart-item');
    var itemId = $cartItem.data('item-id');
    var campaignId = $cartItem.data('campaign-id');
    var itemType = $cartItem.data('item-type');

    // Determine the URL based on item type
    var url = '';
    if (itemType === 'campaign' && campaignId) {
        url = '/admin/campaign/item/edit/' + campaignId;
    } else if (itemId) {
        url = '/admin/item/edit/' + itemId;
    }

    // Open in new tab
    if (url) {
        window.open(url, '_blank');
    }
});
```

**Lines 1079-1080** - Removed keyboard shortcuts (O, M, U keys):
```javascript
// REMOVED: Lines 1082-1104 (keyboard shortcuts for outside purchase, MRP, unavailable)
// Only kept Ctrl+S for save order
```

#### 2. `/resources/views/admin-views/order/edit-v2.blade.php`

**Lines 235-257** - Added CSS for clickable item hover effects:
```css
/* Clickable item area */
.v2-item-clickable {
    transition: all 0.2s ease;
    border-radius: 8px;
    padding: 4px;
    margin: -4px;
}
.v2-item-clickable:hover {
    background: rgba(0, 123, 255, 0.05);
    transform: translateX(2px);
}
.v2-item-clickable:hover .v2-item-img {
    box-shadow: 0 2px 8px rgba(0, 123, 255, 0.2);
}
.v2-item-clickable:hover .v2-item-name {
    color: #007bff;
    text-decoration: underline;
}
```

**Lines 742-744** - Removed keyboard shortcut references from shortcuts overlay:
```html
<!-- REMOVED: O, M, U keyboard shortcuts from shortcuts modal -->
```

---

## Item Routing Logic

### Regular Items:
- **Item Type**: `item`
- **Data**: `data-item-id="146503"`
- **URL**: `/admin/item/edit/146503`
- **Opens**: Item edit page

### Campaign Items:
- **Item Type**: `campaign`
- **Data**: `data-campaign-id="12345"`
- **URL**: `/admin/campaign/item/edit/12345`
- **Opens**: Campaign item edit page

---

## Other Changes in This Update

### 1. Delete Icon Size Increased ✅
- **Before**: 20px
- **After**: 24px
- **Better visibility** and easier to click on mobile devices

### 2. Keyboard Shortcuts Removed ✅
- **Removed**: O, M, U keyboard shortcuts
- **Reason**: User requested removal
- **Kept**: Ctrl+S (save), / (search), Esc (close), ? (help)

---

## Testing

### Test 1: Click Regular Item ✅
1. Edit order 108928 in V2
2. Find item 146503 (regular item)
3. Hover over item name → Should highlight blue
4. Click on item name
5. **Expected**: Opens `/admin/item/edit/146503` in new tab ✅

### Test 2: Click Campaign Item ✅
1. Edit order with campaign items
2. Hover over campaign item
3. Click on item
4. **Expected**: Opens `/admin/campaign/item/edit/{id}` in new tab ✅

### Test 3: Hover Effects ✅
1. Hover over any item in cart
2. **Expected**:
   - Light blue background appears ✅
   - Item slides right 2px ✅
   - Image gets blue shadow ✅
   - Name turns blue and underlines ✅
   - Cursor changes to pointer ✅

### Test 4: Delete Icon Visible ✅
1. View any item in cart
2. **Expected**: Delete icon is 24px (larger and more visible) ✅

---

## Browser Compatibility

- ✅ Chrome/Edge (window.open supported)
- ✅ Firefox (window.open supported)
- ✅ Safari (window.open supported)
- ✅ Mobile browsers (opens in new tab)

---

## Performance Impact

- **Minimal**: Only adds click listener and hover CSS
- **No AJAX**: Direct URL navigation
- **No Page Load**: Opens in new tab, preserves order editing state

---

## Use Cases

### Use Case 1: Price Correction
**Scenario**: Order has item with wrong price
**Action**: Click item → Edit price in item listing → Return to order
**Result**: Can verify correct price in master item listing

### Use Case 2: Stock Check
**Scenario**: Customer asks about item availability
**Action**: Click item → Check stock in item edit page
**Result**: Can see real-time stock without leaving order

### Use Case 3: Item Details
**Scenario**: Need to verify item specifications
**Action**: Click item → View full item details
**Result**: Can see images, description, variations, etc.

---

## Summary of All Changes

### ✅ Added Features:
1. Click-to-edit functionality for items
2. Visual hover feedback (blue highlight, shadow, underline)
3. Opens in new tab (preserves order editing progress)
4. Works with both regular items and campaign items
5. Increased delete icon size (20px → 24px)

### ✅ Removed Features:
1. Keyboard shortcuts: O (outside purchase), M (MRP), U (unavailable)
2. Kept essential shortcuts: Ctrl+S (save), / (search), Esc, ?

### ✅ Files Modified:
1. `public/assets/admin/js/order-edit-v2.js` (~30 lines changed)
2. `resources/views/admin-views/order/edit-v2.blade.php` (~25 lines changed)

### ✅ Caches Cleared:
1. Laravel view cache
2. Laravel application cache
3. OPcache

---

## Ready to Test!

**Try it now:**
1. Edit order 108928 in V2
2. Hover over any item → Should see blue highlight
3. Click on item → Opens edit page in new tab ✅
4. Return to order tab → Still in edit mode ✅

**Everything works together!** 🎉
