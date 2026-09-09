# Delivery Boy Location Tracking via WebSocket

## Overview

This document covers how delivery boy (rider) location updates are received, processed, and stored in the system.

---

## Architecture

```
Mobile App (Delivery Boy)
        │
        ▼ (WebSocket connection)
┌───────────────────────────────────┐
│  Laravel WebSocket Server         │
│  Port: 6001                       │
│  Route: delivery-man/live-location│
└───────────────────────────────────┘
        │
        ▼ (Instant write)
┌───────────────────────────────────┐
│  MySQL Database                   │
│  Table: delivery_histories        │
└───────────────────────────────────┘
        │
        ▼ (Broadcast)
┌───────────────────────────────────┐
│  Pusher Channel                   │
│  delivery-man.{id}                │
└───────────────────────────────────┘
```

---

## Key Files

| Component | File Path |
|-----------|-----------|
| WebSocket Config | `config/websockets.php` |
| Broadcasting Config | `config/broadcasting.php` |
| **WebSocket Handler** | `app/WebSockets/Handler/DMLocationSocketHandler.php` |
| Broadcasting Event | `app/Events/DeliveryManLocationUpdated.php` |
| DeliveryHistory Model | `app/Models/DeliveryHistory.php` |
| REST API Controller | `app/Http/Controllers/Api/V1/DeliverymanController.php` |
| API Routes | `routes/api/v1/api.php` (line 107) |

---

## WebSocket Configuration

**File:** `config/websockets.php`

```php
'handlers' => [
    'delivery-man/live-location' => \App\WebSockets\Handler\DMLocationSocketHandler::class,
],
```

- **Port:** 6001 (configurable via `LARAVEL_WEBSOCKETS_PORT`)
- **Package:** BeyondCode Laravel WebSockets

---

## Data Flow

### 1. Mobile App Sends JSON Payload

```json
{
    "token": "delivery_man_auth_token",
    "latitude": "34.0778684",
    "longitude": "74.7747345",
    "location": "Address string (optional)",
    "speed": "45 (optional)",
    "accuracy": "10 (optional)",
    "heading": "180 (optional)"
}
```

### 2. WebSocket Handler Processes

**File:** `app/WebSockets/Handler/DMLocationSocketHandler.php`

```php
public function onMessage(ConnectionInterface $from, MessageInterface $msg)
{
    $data = json_decode($msg->getPayload(), true);

    if (isset($data['token'], $data['longitude'], $data['latitude'])) {
        $dm = DeliveryMan::where(['auth_token' => $data['token']])->first();

        if ($dm) {
            // 1. Log the update
            $logMessage = "DM#{$dm->id} location update at " . now()->format('H:i:s');
            Log::info($logMessage);

            // 2. INSTANT database write
            DeliveryHistory::updateOrCreate(
                ['delivery_man_id' => $dm->id],
                [
                    'longitude' => $data['longitude'],
                    'latitude'  => $data['latitude'],
                    'time'      => now(),
                    'location'  => $data['location'] ?? null,
                    'updated_at'=> now(),
                ]
            );

            // 3. Check if near customer (for notifications)
            $this->checkNearbyCustomer($dm, $data['latitude'], $data['longitude']);

            // 4. Broadcast to Pusher
            broadcast(new DeliveryManLocationUpdated($dm->id, [...]));

            // 5. Acknowledge to app
            $from->send(json_encode(['status' => 'ok', 'msg' => 'location recorded']));
        }
    }
}
```

---

## Database Write Strategy

### Method: INSTANT (Not Queued/Batched)

- Uses Eloquent `updateOrCreate()` for atomic upsert
- Executes **synchronously** within WebSocket message handler
- **No job queuing** or batching
- Single record per delivery man (continuously overwritten)

### Table: `delivery_histories`

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| delivery_man_id | bigint | Foreign key to delivery_men |
| latitude | varchar | Current latitude |
| longitude | varchar | Current longitude |
| location | varchar | Address string (optional) |
| time | timestamp | Location timestamp |
| created_at | timestamp | Record creation time |
| updated_at | timestamp | Last update time |

---

## Update Frequency

| Aspect | Value |
|--------|-------|
| **Server-side throttling** | None |
| **Typical app interval** | ~10 seconds |
| **Rate limiting** | None implemented |

The frequency depends entirely on how often the mobile app sends updates. No server-side debouncing.

---

## Monitoring & Debugging

### Check WebSocket Server Status

```bash
# Check if running
ps aux | grep websocket

# Check supervisor status
supervisorctl status websockets

# Check port
netstat -tlnp | grep 6001
```

### View Live Logs

```bash
# WebSocket server logs
tail -f /var/log/supervisor/websockets.log

# Filter for specific delivery man
tail -f /var/log/supervisor/websockets.log | grep "DM#33"
```

### Check Database for Specific Driver

```bash
php artisan tinker --execute="
\$h = \App\Models\DeliveryHistory::where('delivery_man_id', 33)->first();
echo 'Lat: ' . \$h->latitude . '\n';
echo 'Lng: ' . \$h->longitude . '\n';
echo 'Updated: ' . \$h->updated_at;
"
```

### Monitor DB Updates in Real-time

```bash
for i in 1 2 3 4 5; do
  php artisan tinker --execute="
  \$h = \App\Models\DeliveryHistory::where('delivery_man_id', 33)->first();
  echo now()->format('H:i:s') . ' | Updated: ' . \$h->updated_at;
  " 2>/dev/null
  sleep 2
done
```

### Check Delivery Man Status

```bash
php artisan tinker --execute="
\$dm = \App\Models\DeliveryMan::find(33);
echo 'Name: ' . \$dm->f_name . '\n';
echo 'Active: ' . (\$dm->active ? 'Yes' : 'No') . '\n';
echo 'Auth Token: ' . (\$dm->auth_token ? 'Present' : 'NULL');
"
```

---

## Supervisor Configuration

**File:** `/etc/supervisor/conf.d/websockets.conf`

```ini
[program:websockets]
command=/usr/bin/php /var/www/html/new_public/new/artisan websockets:serve --host=0.0.0.0 --port=6001
directory=/var/www/html/new_public/new
autostart=true
autorestart=true
user=root
redirect_stderr=true
stdout_logfile=/var/log/supervisor/websockets.log
stdout_logfile_maxbytes=10MB
stdout_logfile_backups=5
```

### Restart WebSocket Server

```bash
supervisorctl restart websockets
```

---

## Alternate Route: REST API

If WebSocket fails, there's a REST API fallback:

- **Endpoint:** `POST /api/v1/delivery-man/record-location-data`
- **Controller:** `DeliverymanController@record_location_data`
- **Parameters:** Same as WebSocket (token, latitude, longitude, location)

---

## Broadcasting (Real-time to Frontend)

**Event:** `App\Events\DeliveryManLocationUpdated`

```php
public function broadcastOn()
{
    return [new Channel('delivery-man.' . $this->deliveryManId)];
}

public function broadcastAs()
{
    return 'location.updated';
}
```

**Channel:** `delivery-man.{delivery_man_id}`
**Event Name:** `location.updated`

---

## Nearby Customer Notification

When delivery boy comes within configurable radius of customer:

- **Config Key:** `business_settings.nearby_notification_radius`
- **Default:** 100 meters
- **Distance Calculation:** Haversine formula
- **Triggers for:** Orders with status `picked_up` or `handover`

---

## Common Issues & Troubleshooting

### 1. Location Not Updating in Database

**Check:**
- Is WebSocket server running? (`supervisorctl status websockets`)
- Is delivery man sending updates? (Check logs: `grep "DM#ID" /var/log/supervisor/websockets.log`)
- Is auth token valid? (Check `delivery_men.auth_token`)

### 2. WebSocket Server Crashes

**Check logs:**
```bash
tail -100 /var/log/supervisor/websockets.log
```

**Common causes:**
- MySQL connection issues (`SQLSTATE[HY000] [2002]`)
- Redis not ready (`LOADING Redis is loading`)
- Memory exhaustion

### 3. Driver App Not Connecting

**Verify:**
- Driver has valid `auth_token`
- Driver `active` status is 1
- Driver `status` is 1
- Network connectivity on device

---

## Performance Considerations

### Current Implementation
- Every location update = 1 database write
- No batching or throttling
- High-frequency updates from many drivers can create DB load

### Potential Optimizations (if needed)
1. **Debounce:** Only write if moved > X meters
2. **Batch writes:** Queue updates, flush every N seconds
3. **Redis caching:** Store latest location in Redis, periodic DB sync
4. **Rate limiting:** Max 1 update per N seconds per driver

---

## Summary Table

| Aspect | Implementation |
|--------|----------------|
| Protocol | WebSocket (Laravel WebSockets) |
| Port | 6001 |
| Route | `delivery-man/live-location` |
| DB Write | INSTANT via `updateOrCreate()` |
| Write Pattern | Upsert (1 record per driver) |
| Throttling | None |
| Broadcasting | Pusher channel per driver |
| Fallback | REST API endpoint |

---

*Last Updated: January 22, 2026*
