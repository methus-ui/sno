# Store Order View - Availability Markers (2026-03-12)

## Feature
Added clear **Available** and **Unavailable** status markers to the vendor/store order view panel, making it easy for vendors to see which items are in stock and which are out of stock.

## Changes Applied

### 1. Visual Enhancements to Existing Badges (Item Names)
**File:** `resources/views/vendor-views/order/order-view.blade.php` (Lines 320-340)

**Before:**
- Yellow "Unavailable" badge shown only when item is unavailable
- No clear indicator for available items
- "Picked up" badge was green (confusing with availability)

**After:**
- ✅ **Green "Available" badge** for items that are in stock
- ❌ **Red "Unavailable" badge** for out-of-stock items (upgraded from yellow to red for urgency)
- 🛒 **Blue "Picked up" badge** for picked items (changed from green to avoid confusion)
- All badges include icons for better visual recognition
- Unavailable items show additional note if provided

**Implementation:**
```blade
{{-- Availability Status Badge --}}
@if($detail->is_unavailable)
    <span class="badge badge-danger ml-1" style="font-size: 11px;">
        <i class="tio-clear-circle-outlined"></i> {{ translate('messages.unavailable') }}
    </span>
    @if($detail->unavailable_note)
        <small class="d-block text-muted mt-1">
            <i class="tio-info-outlined"></i> {{ $detail->unavailable_note }}
        </small>
    @endif
@else
    <span class="badge badge-success ml-1" style="font-size: 11px;">
        <i class="tio-checkmark-circle-outlined"></i> {{ translate('messages.available') }}
    </span>
@endif
```

### 2. New Status Column in Order Items Table
**File:** `resources/views/vendor-views/order/order-view.blade.php`

**Added:**
- New **"Status"** column in the order items table header (Line 289)
- Status badges for each item row showing availability (Lines 408-421 for regular items, Lines 500-513 for campaign items)
- Larger badges (13px font, 8px padding) for better visibility
- Soft badge variants for cleaner look

**Table Structure:**
```
| # | Item Details | Status | Addons (food only) | Price |
```

**Status Column Implementation:**
```blade
{{-- Availability Status Column --}}
<td class="text-center align-middle">
    @if($detail->is_unavailable)
        <span class="badge badge-soft-danger" style="font-size: 13px; padding: 8px 12px;">
            <i class="tio-clear-circle-outlined"></i>
            {{ translate('messages.unavailable') }}
        </span>
    @else
        <span class="badge badge-soft-success" style="font-size: 13px; padding: 8px 12px;">
            <i class="tio-checkmark-circle-outlined"></i>
            {{ translate('messages.available') }}
        </span>
    @endif
</td>
```

### 3. Translation Key Added
**File:** `resources/lang/en/messages.php` (Line 7260)

**Added:**
```php
'available' => 'Available',
```

**Existing:**
```php
'unavailable' => 'Unavailable',
```

## Database Schema Used

The feature uses the existing `order_details` table field:
- `is_unavailable` (tinyint) - 1 = unavailable, 0 = available
- `unavailable_note` (text) - Optional note explaining why item is unavailable

No database changes required - the field already exists from the Order Edit V2 implementation.

## Visual Design

### Badge Colors:
- **Available:** Green (`badge-success`, `badge-soft-success`)
- **Unavailable:** Red (`badge-danger`, `badge-soft-danger`)
- **Picked Up:** Blue (`badge-info`)

### Icons Used:
- Available: `tio-checkmark-circle-outlined` ✓
- Unavailable: `tio-clear-circle-outlined` ✕
- Picked Up: `tio-shopping-basket-outlined` 🛒
- Info Note: `tio-info-outlined` ℹ

### Badge Sizes:
- Inline badges (next to item names): 11px font
- Table column badges: 13px font, 8px padding

## Where It Appears

1. **Order Items Table** - Dedicated status column showing availability for each item
2. **Item Name Row** - Inline badges next to each item name with additional details
3. **Both Regular Items and Campaign Items** - Consistent display across all item types

## Files Modified

1. `resources/views/vendor-views/order/order-view.blade.php`
   - Added status column to table header (+1 line)
   - Enhanced inline availability badges (+20 lines)
   - Added status column for regular items (+14 lines)
   - Added status column for campaign items (+14 lines)

2. `resources/lang/en/messages.php`
   - Added 'available' translation key (+1 line)

## Testing

To test the feature:
1. Go to vendor panel: `/store-panel/order/list/all`
2. Click on any order to view details
3. Check the "Status" column in the order items table
4. Verify green "Available" badges for in-stock items
5. Verify red "Unavailable" badges for out-of-stock items
6. Check inline badges next to item names

## Backward Compatibility

✅ **100% Backward Compatible**
- Uses existing database fields
- Only adds visual enhancements
- No breaking changes to existing functionality
- Works with existing order edit features
- Compatible with both regular items and campaign items

## Related Features

This enhancement works seamlessly with:
- **Order Edit V2** - Mark items unavailable feature
- **Outside Purchase** - Unavailable items can be sourced externally
- **MRP Updates** - Price updates for available items
- **Picked Up Status** - Shows when items have been collected

## Future Enhancements (Optional)

- Add filter to show only available/unavailable items
- Add bulk mark available/unavailable buttons
- Add availability status to order list (not just details)
- Add availability statistics in order summary
- Add color coding to order cards based on availability

## Result

✅ Vendors can now instantly see which items are available or unavailable
✅ Clear visual distinction with color-coded badges and icons
✅ Dedicated status column for at-a-glance availability checking
✅ Improved order fulfillment workflow
✅ Zero breaking changes - pure enhancement
