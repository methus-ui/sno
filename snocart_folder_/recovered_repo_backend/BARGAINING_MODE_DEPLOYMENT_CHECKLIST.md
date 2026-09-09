# Bargaining Mode - Deployment Checklist

**Version:** 1.0
**Date:** 2026-03-17
**Environment:** Production/Staging

---

## ✅ Pre-Deployment Checklist

### 1. Database Preparation

- [ ] Backup current database
  ```bash
  mysqldump -u root -p your_database > backup_$(date +%Y%m%d_%H%M%S).sql
  ```

- [ ] Verify migrations exist
  ```bash
  ls database/migrations/2026_03_17_*
  # Should show 6 migration files
  ```

- [ ] Check database connection
  ```bash
  php artisan db:show
  ```

### 2. Configuration Review

- [ ] Environment variables set
  ```bash
  grep -E "BARGAINING_" .env
  ```

  Required:
  - `BARGAINING_ENABLED=true`
  - `BARGAINING_INSTANT_MODE=true`
  - `BARGAINING_WAIT_MODE=true`
  - `BARGAINING_WAIT_DURATION=60`
  - `BARGAINING_MIN_CART_VALUE=0`

- [ ] Config file published
  ```bash
  ls config/bargaining.php
  ```

### 3. Code Review

- [ ] All files present (15 files)
  - 6 migrations ✓
  - 6 models ✓
  - 1 service ✓
  - 2 controllers ✓
  - 1 middleware ✓
  - 1 command ✓
  - Routes integrated ✓
  - Translation keys added ✓

- [ ] No syntax errors
  ```bash
  php -l app/Services/BargainingService.php
  php -l app/Http/Controllers/Api/V2/BargainingController.php
  php -l app/Http/Controllers/Api/V2/Vendor/BargainingController.php
  ```

### 4. Dependencies

- [ ] Laravel version compatible (10.x)
- [ ] PHP version compatible (8.1+)
- [ ] MySQL version compatible (8.0+)

---

## 🚀 Deployment Steps

### Step 1: Run Migrations (5 minutes)

```bash
# Run migrations in order
php artisan migrate --path=database/migrations/2026_03_17_000001_create_bargaining_requests_table.php
php artisan migrate --path=database/migrations/2026_03_17_000002_create_bargaining_cart_items_table.php
php artisan migrate --path=database/migrations/2026_03_17_000003_create_bargaining_item_matches_table.php
php artisan migrate --path=database/migrations/2026_03_17_000004_create_bargaining_store_offers_table.php
php artisan migrate --path=database/migrations/2026_03_17_000005_create_bargaining_offer_items_table.php
php artisan migrate --path=database/migrations/2026_03_17_000006_create_store_bargaining_settings_table.php
```

**Verify:**
```bash
php artisan tinker --execute="
    echo 'bargaining_requests: ' . Schema::hasTable('bargaining_requests') . PHP_EOL;
    echo 'bargaining_cart_items: ' . Schema::hasTable('bargaining_cart_items') . PHP_EOL;
    echo 'bargaining_item_matches: ' . Schema::hasTable('bargaining_item_matches') . PHP_EOL;
    echo 'bargaining_store_offers: ' . Schema::hasTable('bargaining_store_offers') . PHP_EOL;
    echo 'bargaining_offer_items: ' . Schema::hasTable('bargaining_offer_items') . PHP_EOL;
    echo 'store_bargaining_settings: ' . Schema::hasTable('store_bargaining_settings') . PHP_EOL;
"
# All should output: 1
```

### Step 2: Clear All Caches (2 minutes)

```bash
# Application cache
php artisan cache:clear

# Config cache
php artisan config:clear
php artisan config:cache

# Route cache
php artisan route:clear
php artisan route:cache

# View cache
php artisan view:clear

# OPcache (if available)
php artisan optimize:clear
```

### Step 3: Verify Routes (1 minute)

```bash
php artisan route:list | grep bargaining
```

**Expected Output:**
```
POST    api/v2/bargaining/initiate
GET     api/v2/bargaining/status/{requestCode}
POST    api/v2/bargaining/accept-offer
POST    api/v2/bargaining/cancel/{requestCode}
GET     api/v2/bargaining/history
GET     api/v2/bargaining/offer/{offerId}
GET     api/v2/vendor/bargaining/available
POST    api/v2/vendor/bargaining/counter-offer
GET     api/v2/vendor/bargaining/settings
PUT     api/v2/vendor/bargaining/settings
GET     api/v2/vendor/bargaining/my-offer/{requestCode}
POST    api/v2/vendor/bargaining/withdraw-offer/{offerId}
GET     api/v2/vendor/bargaining/analytics
```

### Step 4: Test Cron Job (2 minutes)

```bash
# Register command
php artisan schedule:list | grep bargaining

# Should show:
# 0 * * * * php artisan bargaining:expire

# Test execution
php artisan bargaining:expire

# Should output:
# No expired requests found (or actual count)
```

### Step 5: Run Test Script (3 minutes)

```bash
php scripts/test-bargaining-api.php
```

**Expected:**
- ✅ Feature Flag: ENABLED
- ✅ All routes: EXISTS
- ✅ Cron Command: EXISTS
- ✅ Cron Schedule: REGISTERED
- ✅ Cron Execution: SUCCESS

**Target:** 100% pass rate

### Step 6: Restart Services (2 minutes)

```bash
# Queue workers (if using)
php artisan queue:restart

# Web server (choose one)
sudo systemctl restart nginx    # or apache2
sudo systemctl restart php8.1-fpm

# Supervisor (if using)
sudo supervisorctl reread
sudo supervisorctl update
```

---

## 🧪 Post-Deployment Testing

### Test 1: Feature Flag (30 seconds)

```bash
curl https://your-domain.com/api/v2/bargaining/status/test \
  -H "Authorization: Bearer fake_token"
```

**Expected (if BARGAINING_ENABLED=false):**
```json
{
  "errors": [{
    "code": "bargaining_disabled",
    "message": "Bargaining mode is currently unavailable"
  }]
}
```

### Test 2: Customer API (2 minutes)

**Get auth token:**
```bash
# Get a test customer token
php artisan tinker --execute="
    \$user = App\Models\User::first();
    if (!\$user->api_token) {
        \$user->api_token = Str::random(80);
        \$user->save();
    }
    echo 'Token: ' . \$user->api_token;
"
```

**Test initiate:**
```bash
curl -X POST https://your-domain.com/api/v2/bargaining/initiate \
  -H "Authorization: Bearer {TOKEN}" \
  -H "zoneId: [1]" \
  -H "moduleId: 1" \
  -H "Content-Type: application/json" \
  -d '{
    "mode": "instant",
    "latitude": 12.9716,
    "longitude": 77.5946
  }'
```

**Expected:**
- HTTP 201 (if cart not empty and zone has stores)
- HTTP 400 with error message (if cart empty or invalid)

### Test 3: Vendor API (2 minutes)

```bash
curl https://your-domain.com/api/v2/vendor/bargaining/settings \
  -H "Authorization: Bearer {VENDOR_TOKEN}"
```

**Expected:**
- HTTP 200 with settings object
- Default: bargaining_enabled=true, auto_participate=true

### Test 4: Cron Job (1 minute)

```bash
# Create an expired request manually
php artisan tinker --execute="
    \$req = new App\Models\BargainingRequest([
        'request_code' => 'TEST-123',
        'user_id' => 1,
        'zone_id' => 1,
        'module_id' => 1,
        'original_cart_snapshot' => [],
        'status' => 'initiated',
        'expires_at' => now()->subMinutes(10)
    ]);
    \$req->save();
    echo 'Created expired request: ' . \$req->id;
"

# Run cron
php artisan bargaining:expire

# Verify expiration
php artisan tinker --execute="
    \$req = App\Models\BargainingRequest::where('request_code', 'TEST-123')->first();
    echo 'Status: ' . \$req->status;  // Should be 'expired'
"
```

---

## 📊 Monitoring Setup

### 1. Log Monitoring

```bash
# Watch bargaining logs
tail -f storage/logs/laravel.log | grep -i bargaining
```

**Key events to monitor:**
- Bargaining initiated
- Item matching completed
- Offers generated
- Offers accepted
- Requests expired

### 2. Performance Monitoring

**Query count:**
```bash
# Enable query logging temporarily
php artisan tinker --execute="
    DB::enableQueryLog();
    // Make API call
    echo count(DB::getQueryLog()) . ' queries';
"
```

**Target:** <20 queries per request

### 3. Error Monitoring

```bash
# Check for errors
grep "Bargaining.*failed" storage/logs/laravel.log | tail -20
```

### 4. Database Monitoring

```sql
-- Active sessions
SELECT COUNT(*) FROM bargaining_requests
WHERE status IN ('initiated', 'matching', 'offers_received');

-- Today's activity
SELECT
  status,
  COUNT(*) as count
FROM bargaining_requests
WHERE DATE(created_at) = CURDATE()
GROUP BY status;

-- Store participation
SELECT COUNT(DISTINCT store_id) FROM store_bargaining_settings
WHERE bargaining_enabled = 1;
```

---

## 🚨 Rollback Plan

### If Issues Detected Within 1 Hour:

**Quick Disable (30 seconds):**
```bash
# Set in .env
BARGAINING_ENABLED=false

# Clear config cache
php artisan config:cache

# Verify
curl https://your-domain.com/api/v2/bargaining/status/test
# Should return 503 error
```

### If Major Issues Detected:

**Full Rollback (5 minutes):**
```bash
# 1. Disable feature
BARGAINING_ENABLED=false
php artisan config:cache

# 2. Restore database backup
mysql -u root -p your_database < backup_YYYYMMDD_HHMMSS.sql

# 3. Rollback migrations
php artisan migrate:rollback --step=6

# 4. Clear caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

---

## ✅ Success Criteria

### Deployment Successful If:

- [ ] All 6 migrations completed without errors
- [ ] All 13 API routes accessible (route:list)
- [ ] Cron job scheduled and executable
- [ ] Test script passes 100%
- [ ] Customer can initiate bargaining (if cart not empty)
- [ ] Vendor can view settings
- [ ] No errors in logs for 1 hour
- [ ] Feature flag works (disable/enable)

### Performance Metrics:

- [ ] Initiate request: <3 seconds
- [ ] Status check: <200ms
- [ ] Accept offer: <1 second
- [ ] Database queries: <20 per request
- [ ] No 500 errors

---

## 📋 Post-Deployment Tasks

### Within 24 Hours:

- [ ] Monitor error logs every 2 hours
- [ ] Check database growth (bargaining_requests table)
- [ ] Verify cron job running every minute
- [ ] Test with 5-10 real customers
- [ ] Collect initial metrics (initiation rate, acceptance rate)

### Within 1 Week:

- [ ] Review performance metrics
- [ ] Analyze customer usage patterns
- [ ] Identify optimization opportunities
- [ ] Plan Phase 3 (Real-time updates)
- [ ] Gather user feedback

---

## 🔧 Troubleshooting Guide

### Issue: Routes not found

**Symptoms:** 404 errors on all bargaining endpoints

**Fix:**
```bash
php artisan route:clear
php artisan route:cache
php artisan config:cache
```

### Issue: Cron not expiring requests

**Symptoms:** Requests stuck in "offers_received" status past expiration

**Fix:**
```bash
# Check crontab
crontab -l | grep schedule:run

# If missing, add:
* * * * * cd /var/www/html/new_public/new && php artisan schedule:run >> /dev/null 2>&1

# Test manually
php artisan bargaining:expire
```

### Issue: "Unauthorized" errors

**Symptoms:** 403 errors when accessing endpoints

**Fix:**
- Verify auth token is valid
- Check user/vendor owns the resource
- Verify middleware is registered

### Issue: No stores matched

**Symptoms:** `total_stores_matched = 0`

**Causes:**
- No stores in customer's zone with bargaining enabled
- All stores have `auto_participate = false`

**Fix:**
```bash
# Enable bargaining for stores
php artisan tinker --execute="
    App\Models\StoreBargainingSetting::query()->update([
        'bargaining_enabled' => true,
        'auto_participate' => true
    ]);
"
```

### Issue: No item matches

**Symptoms:** `total_matches_found = 0` for all cart items

**Causes:**
- Items have no barcodes
- `BARGAINING_REQUIRE_BARCODE=true`
- Store items have different barcodes/names

**Fix:**
```bash
# Allow fuzzy matching
BARGAINING_REQUIRE_BARCODE=false
php artisan config:cache
```

---

## 📞 Support Contacts

**Development Team:** [Contact Info]

**Emergency Hotline:** [Phone Number]

**Slack Channel:** #bargaining-mode

**Email:** support@your-domain.com

---

## 📝 Deployment Sign-Off

**Deployed By:** ___________________

**Deployment Date:** ___________________

**Deployment Time:** ___________________

**Environment:** ☐ Staging  ☐ Production

**Test Results:** ☐ All Pass  ☐ Some Fail

**Rollback Required:** ☐ Yes  ☐ No

**Notes:**
_____________________________________
_____________________________________
_____________________________________

**Approved By:** ___________________

---

**Last Updated:** 2026-03-17
**Version:** 1.0 (Phase 1 & 2)
