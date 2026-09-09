# Nearby Delivery Notification Feature

## Overview
Automatically sends push notifications to customers when their delivery person is within 100 meters of their delivery location.

## How It Works

1. **Location Tracking**: Delivery person's location is updated every few seconds via:
   - WebSocket: `wss://new.snocart.com:6001/delivery-man/live-location`
   - HTTP API: `POST /api/v1/delivery-man/record-location-data`

2. **Proximity Check**: When location is updated, system:
   - Checks if delivery person has active orders (status: `picked_up` or `handover`)
   - Calculates distance between delivery person and customer's delivery address
   - Uses Haversine formula for accurate GPS distance calculation

3. **Notification Trigger**: When distance ≤ 100 meters:
   - Sends push notification: "Your delivery person is at your doorstep"
   - Saves notification to `user_notifications` table
   - Sets `nearby_notification_sent = 1` to prevent duplicate notifications

4. **Reset**: Flag is reset automatically when:
   - Order status changes to `delivered` or `canceled`
   - New order is assigned to delivery person

## Database Changes

### New Column: `orders.nearby_notification_sent`
- Type: `TINYINT(1)`
- Default: `0`
- Purpose: Tracks if proximity notification was sent for this order

```sql
ALTER TABLE orders ADD COLUMN nearby_notification_sent TINYINT(1) DEFAULT 0 AFTER delivery_man_id;
```

## Files Modified

1. **`app/Http/Controllers/Api/V1/DeliverymanController.php`**
   - Modified `record_location_data()` to check proximity
   - Added `calculateDistance()` helper method
   - Added `sendNearbyNotification()` method

2. **`app/WebSockets/Handler/DMLocationSocketHandler.php`**
   - Modified `onMessage()` to check proximity
   - Added same helper methods for WebSocket updates

3. **Migration**: `database/migrations/*_add_nearby_notification_to_orders.php`

## Testing

### Test Command
```bash
php artisan test:nearby {order_id}
```

Example output:
```
Order #107108
Status: picked_up
Delivery Man ID: 11
Nearby Notification Sent: No
Customer Location: 34.041895426476735, 74.87483588117922
DM Location: 34.0036878, 74.8278783
Distance: 6064.48 meters
✗ Delivery man is 6064.48m away - notification will not trigger
```

### Manual Test
1. Find active order: `SELECT * FROM orders WHERE order_status='picked_up' LIMIT 1;`
2. Check delivery man location: `SELECT * FROM delivery_histories WHERE delivery_man_id = X;`
3. Calculate distance between customer and DM
4. If < 100m, notification should be sent automatically

## Configuration

**Proximity Distance**: Currently hardcoded to 100 meters (0.1 km)

To change, modify:
- `DeliverymanController.php:366`: `if ($distance <= 0.1)`
- `DMLocationSocketHandler.php:81`: `if ($distance <= 0.1)`

## Notification Message

**Title**: "Delivery Man Nearby!"
**Description**: "Your delivery person is at your doorstep. Please be ready to receive your order."

To customize, modify translation keys:
```php
'title' => translate('Delivery Man Nearby!'),
'description' => translate('Your delivery person is at your doorstep...'),
```

## Logs

Check logs for notification activity:
```bash
tail -f storage/logs/laravel-$(date +%Y-%m-%d).log | grep -i "nearby"
```

Successful notification:
```
[INFO] Nearby notification sent to customer 15197 for order 107108
```

## Troubleshooting

### Notification not sending?

1. **Check order status**: Must be `picked_up` or `handover`
   ```sql
   SELECT id, order_status, nearby_notification_sent FROM orders WHERE id = X;
   ```

2. **Check distance**: Must be ≤ 100 meters
   ```bash
   php artisan test:nearby {order_id}
   ```

3. **Check customer token**: Must have valid Firebase token
   ```sql
   SELECT cm_firebase_token FROM users WHERE id = X;
   ```

4. **Check flag**: Should be 0 before notification
   ```sql
   UPDATE orders SET nearby_notification_sent = 0 WHERE id = X;
   ```

5. **Check WebSocket**: Server must be running
   ```bash
   supervisorctl status websockets
   ```

## Performance Impact

- **Minimal**: Distance calculation only runs when location updates
- **Optimized**: Query only checks active orders with flag = 0
- **One-time**: Notification sent only once per order

## Future Enhancements

1. Add admin setting to configure proximity distance
2. Add SMS notification option
3. Add estimated arrival time in notification
4. Add notification history tracking
5. Support multiple notification thresholds (500m, 200m, 100m)
