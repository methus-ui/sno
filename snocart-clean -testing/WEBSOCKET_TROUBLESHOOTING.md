# WebSocket Not Updating - Troubleshooting Guide

## Date: 2026-03-27

---

## 🔍 Issue: Distance Not Refreshing in Real-Time

**Symptom:** Delivery man is moving but admin panel distance doesn't update automatically.

---

## ✅ FIXES APPLIED:

### 1. **Queue Workers Restarted** ✅
```bash
php artisan queue:restart
```

**Why:** Queue workers were running old code (started Mar 26). They didn't have the new broadcast code. Restarting loads the new code.

---

## 🧪 Step-by-Step Debugging:

### **Step 1: Test WebSocket Connection**

Open this page:
👉 **http://new.snocart.com/test-websocket-connection.html**

**What to check:**
1. Enter Delivery Man ID (e.g., 1)
2. Click "Connect WebSocket"
3. Should see:
   ```
   ✅ CONNECTED to Pusher!
   ✅ Successfully subscribed to channel!
   👂 Listening for "location.updated" events...
   ```

4. Have DM move with mobile app
5. Should see location updates appear:
   ```
   🎉 === LOCATION UPDATE RECEIVED === #1
   📍 Latitude: 28.6139
   📍 Longitude: 77.2090
   🚀 Speed: 15.5 km/h
   ```

**If NO updates appear:** Backend is not broadcasting (go to Step 2)
**If updates appear:** Backend is working, frontend issue (go to Step 3)

---

### **Step 2: Check Backend Broadcasting**

#### A. Check if location updates are being received:
```bash
tail -f storage/logs/laravel.log | grep "Speed calculation"
```

**Should see (when DM moves):**
```
Speed calculation for DM 1: 15.5 km/h
Speed calculation for DM 1: 18.2 km/h
```

**If YES:** Backend is calculating speed ✅
**If NO:** Mobile app not sending location updates ❌

#### B. Check if WebSocket broadcasts are sent:
```bash
tail -f storage/logs/laravel.log | grep "WebSocket broadcast"
```

**Should see (when DM moves):**
```
📡 WebSocket broadcast sent for DM 1 - Speed: 15.5 km/h
📡 WebSocket broadcast sent for DM 1 - Speed: 18.2 km/h
```

**If YES:** Backend is broadcasting ✅ (frontend issue)
**If NO:** Broadcasting is not working ❌ (continue below)

---

### **Step 3: Fix Broadcasting Issues**

#### Issue A: Queue Workers Not Processing

**Check queue workers:**
```bash
ps aux | grep "queue:work"
```

**Should see:** 2-3 processes running

**If NO processes:** Start queue workers
```bash
php artisan queue:work redis --daemon &
```

**If processes exist but OLD:** Restart them
```bash
php artisan queue:restart
# Wait 30 seconds for workers to restart
ps aux | grep "queue:work"  # Verify new PIDs
```

#### Issue B: Pusher Credentials Wrong

**Check .env file:**
```bash
grep PUSHER /var/www/html/new_public/new/.env
```

**Should show:**
```
PUSHER_APP_ID=your_app_id
PUSHER_APP_KEY=80236ec36aada60a8520
PUSHER_APP_SECRET=your_secret
PUSHER_APP_CLUSTER=mt1
BROADCAST_DRIVER=pusher
```

**Verify on Pusher Dashboard:**
1. Go to https://dashboard.pusher.com
2. Select your app
3. Go to "App Keys"
4. Verify KEY and CLUSTER match .env

#### Issue C: Redis Not Running

**Check Redis:**
```bash
redis-cli ping
```

**Should return:** `PONG`

**If error:** Start Redis
```bash
sudo systemctl start redis
```

---

### **Step 4: Frontend Issues**

#### A. Clear Browser Cache (AGAIN!)

Even if you cleared before, clear again:
```
1. Ctrl + Shift + Delete
2. Select "All time"
3. Clear "Cached images and files"
4. Close and reopen browser
```

#### B. Check Console Errors

Open order page, press F12, check console:

**Should see:**
```
✅ 🚴 Real-time tracking initialized
✅ Pusher : State changed : connecting -> connected
✅ Subscribed to real-time location updates
```

**Should NOT see:**
```
❌ Uncaught SyntaxError
❌ WebSocket connection failed
❌ Pusher error
```

#### C. Verify WebSocket Code is Loaded

In console, run:
```javascript
console.log(typeof trackingPusher);  // Should be "object"
console.log(trackingPusher.connection.state);  // Should be "connected"
```

---

## 🔧 Manual Test Broadcasting

Test if broadcasting works at all:

```bash
php artisan tinker
```

Then run:
```php
event(new \App\Events\DeliveryManLocationUpdated(
    1,  // DM ID
    [
        'latitude' => 28.6139,
        'longitude' => 77.2090,
        'speed' => 25.5,
        'location' => 'Test Location',
        'timestamp' => now()->toIso8601String(),
    ]
));
```

**Then check:**
- Test page (http://new.snocart.com/test-websocket-connection.html)
- Should receive the event immediately
- If YES: Broadcasting works! ✅
- If NO: Pusher credentials or Redis issue ❌

---

## 🎯 Common Solutions:

### Solution 1: Queue Workers Had Old Code ✅ APPLIED
```bash
php artisan queue:restart
```

### Solution 2: Broadcasting Queue Not Processing
```bash
# Check queue
php artisan queue:failed

# If failed jobs exist, retry them
php artisan queue:retry all

# Monitor queue in real-time
php artisan queue:work redis --verbose
```

### Solution 3: Pusher Credentials Wrong
```bash
# Test Pusher connection
curl -X POST \
  "https://api-${PUSHER_CLUSTER}.pusher.com/apps/${PUSHER_APP_ID}/events?auth_key=${PUSHER_APP_KEY}&auth_signature=..." \
  -H "Content-Type: application/json" \
  -d '{}'
```

### Solution 4: Browser Cache
```bash
# Force reload with timestamp
http://new.snocart.com/admin/order/list?v=<?php echo time(); ?>
```

---

## 📊 Expected Data Flow:

```
1. DM Mobile App
   ↓ POST /api/v1/deliveryman/record-location-data

2. DeliverymanController
   ↓ Dispatch RecordDeliveryLocationJob

3. Redis Queue
   ↓ Queue Worker picks up job

4. RecordDeliveryLocationJob::handle()
   ├─ Calculate speed
   ├─ Save to database
   └─ Broadcast event ← YOU ARE HERE

5. Pusher API
   ↓ Sends to connected clients

6. Admin Panel WebSocket
   ↓ Receives event

7. updateTrackingUI()
   └─ Updates distance/speed/map
```

---

## 🔍 Debugging Checklist:

- [x] Queue workers restarted ✅
- [ ] Test page shows "Connected"
- [ ] Test page receives location updates
- [ ] Backend logs show "WebSocket broadcast"
- [ ] Redis is running
- [ ] Pusher credentials correct
- [ ] Browser cache cleared
- [ ] Console shows no errors
- [ ] Admin panel distance updates

---

## 🆘 Still Not Working?

### Enable Verbose Logging:

**config/logging.php:**
```php
'pusher' => [
    'driver' => 'monolog',
    'handler' => StreamHandler::class,
    'with' => [
        'stream' => storage_path('logs/pusher.log'),
    ],
    'level' => 'debug',
],
```

**Then tail logs:**
```bash
tail -f storage/logs/pusher.log
```

---

## 📱 Test with Postman (Simulate DM Movement)

```
POST http://new.snocart.com/api/v1/deliveryman/record-location-data

Headers:
Content-Type: application/json

Body:
{
    "token": "YOUR_DM_TOKEN",
    "latitude": 28.6139,
    "longitude": 77.2090,
    "location": "Test Location",
    "speed": 25.5
}
```

**After sending:**
1. Check test-websocket-connection.html page
2. Should see update immediately
3. Check admin order page
4. Should see distance change

---

## ✅ Success Indicators:

**Backend:**
```bash
tail -f storage/logs/laravel.log
# Should show:
Speed calculation for DM 1: 15.5 km/h
📡 WebSocket broadcast sent for DM 1 - Speed: 15.5 km/h
```

**Test Page:**
```
✅ CONNECTED to Pusher!
✅ Successfully subscribed to channel!
🎉 === LOCATION UPDATE RECEIVED === #1
```

**Admin Panel:**
- Distance changes automatically
- Speed updates
- Map marker moves
- Toast notification appears
- Section color changes if needed

---

## 🎯 Next Steps:

1. ✅ Queue workers restarted (DONE)
2. Open test page: http://new.snocart.com/test-websocket-connection.html
3. Enter DM ID
4. Click "Connect WebSocket"
5. Have DM move with mobile app
6. Watch for updates on test page
7. If updates appear, check admin panel
8. If no updates, check backend logs

---

**Status:** Queue workers restarted with new code ✅

**Test URL:** http://new.snocart.com/test-websocket-connection.html

**Next:** Test with real DM movement and report results!
