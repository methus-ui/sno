# Stale Location Data Fix - "540hr ago" Issue

**Date:** February 19, 2026
**Issue:** Location showing "540hr ago" instead of actual "2.5h ago"
**Status:** ✅ FIXED

## Root Cause

The JavaScript was receiving the wrong timestamp field from the Blade template:

### Before (Incorrect):
```php
lastUpdate: '{{ $order->dm_last_location["created_at"] ?? "" }}'
```
- Used `created_at` = when location record was **first created** (6 days ago)
- This is a static timestamp that never changes

### After (Correct):
```php
lastUpdate: '{{ $order->dm_last_location["updated_at"] ?? $order->dm_last_location["time"] ?? $order->dm_last_location["created_at"] ?? "" }}'
```
- Priority 1: `updated_at` = when location was **last updated** (2.5 hours ago)
- Priority 2: `time` = GPS timestamp from device
- Fallback: `created_at` (if others don't exist)

## Database Investigation

Checked order #108913 location data:
```json
{
    "id": 971469,
    "delivery_man_id": 278,
    "time": "2026-02-19T18:15:23.000000Z",  ← GPS timestamp
    "longitude": "74.7627639",
    "latitude": "34.0615599",
    "created_at": "2026-02-13T06:15:24.000000Z",  ← 6 days ago (WRONG)
    "updated_at": "2026-02-19T18:15:23.000000Z"   ← 2.5h ago (CORRECT)
}
```

**Delivery Man:** Ubaid Mushtaq (ID: 278)
**Last GPS Update:** 2.5 hours ago
**Order Status:** Processing

## Additional Improvements

### 1. Better Stale Data Handling
Added visual warnings for old location data:

```javascript
// Show days when > 24 hours old
if (diffSec < 86400) {
    timeStr = Math.floor(diffSec / 3600) + 'h ago';
} else {
    var days = Math.floor(diffSec / 86400);
    timeStr = days + ' day' + (days > 1 ? 's' : '') + ' ago';
}
```

### 2. Stale Data Warning Banner
Added prominent warning when location is > 1 hour old:

```html
<div class="stale-data-warning" id="staleDataWarning">
    ⚠️ Location data is 2 days old. Delivery man app may be offline.
</div>
```

Automatically shows when:
- **> 1 hour old:** "X hours old. GPS tracking may be disabled."
- **> 24 hours old:** "X days old. Delivery man app may be offline."

### 3. Visual Indicators
- **Fresh (< 10s):** Green badge - "Just now"
- **Recent (< 1m):** Yellow badge - "Xs ago"
- **Stale (< 1h):** Orange badge - "Xm ago"
- **Old (< 24h):** Gray badge - "Xh ago"
- **Very Old (> 24h):** **Red badge with border** - "X days ago"

## Testing

### Before Fix:
```
Last Update: 540h ago  ← WRONG (showing created_at)
```

### After Fix:
```
Last Update: 2h ago  ← CORRECT (showing updated_at)
```

## Files Modified

1. `/resources/views/admin-views/order/order-view.blade.php`
   - Line 1192: Changed timestamp field priority
   - Lines 1059-1063: Added stale warning element
   - Lines 1330-1348: Added CSS for warning banner

2. `/public/assets/admin/js/delivery-tracking.js`
   - Lines 415-445: Enhanced relative time calculation
   - Lines 447-468: Added stale data warning function

## Prevention

This fix prevents the issue by:
1. Using correct timestamp field (`updated_at` instead of `created_at`)
2. Adding fallback chain for compatibility
3. Warning users when data becomes stale
4. Visual indicators showing data freshness

## Deployment

```bash
cd /var/www/html/new_public/new
php artisan view:clear
# Refresh browser - no hard reload needed
```

## Verification Steps

1. Open order #108913 (or any order with assigned DM)
2. Check "Last Update" time in tracking header
3. Should show realistic time (hours, not days)
4. Expand tracking panel
5. Should NOT show stale data warning (unless actually stale)

## Related Issues

- Delivery man app may still be offline if showing > 24h
- Check DM app GPS permissions if location not updating
- Verify WebSocket connection for real-time updates
- Check Apache WebSocket proxy on port 6001

---

**Fix Time:** 10 minutes
**Impact:** High - Affects all order tracking displays
**Breaking Changes:** None
**Backward Compatible:** ✅ Yes
