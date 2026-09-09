# 🚀 Performance Optimization Deployment Checklist

## Pre-Deployment

### 1. Backup (CRITICAL - DO NOT SKIP)
- [ ] Create full database backup
  ```bash
  mysqldump snocart > /backups/snocart_pre_optimization_$(date +%Y%m%d_%H%M%S).sql
  ```
- [ ] Verify backup file size is reasonable (>100MB expected)
- [ ] Test backup restoration on dev/staging (optional but recommended)

### 2. Code Review
- [ ] Review `app/Jobs/RecordDeliveryLocationJob.php` changes
- [ ] Review `database/performance_indexes.sql` script
- [ ] Check migration file: `database/migrations/2026_02_07_000459_add_performance_indexes_for_items_and_delivery.php`
- [ ] Read `storage/logs/PERFORMANCE_FIX_SUMMARY.md` for details

### 3. Testing on Staging/Dev (Recommended)
- [ ] Deploy code changes to staging
- [ ] Run index creation script on staging
- [ ] Test item listing pages
- [ ] Test delivery man location updates
- [ ] Monitor staging logs for 30 minutes

---

## Deployment Steps

### Step 1: Deploy Code Changes
```bash
# Pull latest code
cd /var/www/html/new_public/new
git pull origin main  # Or your deployment branch

# Or copy modified files if not using git
# - app/Jobs/RecordDeliveryLocationJob.php
```

### Step 2: Create Database Indexes
**Choose ONE of the following options:**

#### Option A: Direct SQL (RECOMMENDED for production)
```bash
# Run the SQL script
mysql snocart < database/performance_indexes.sql

# Expected output: "Performance indexes created successfully!"
# Duration: 2-5 minutes
```

#### Option B: Laravel Migration
```bash
php artisan migrate --path=database/migrations/2026_02_07_000459_add_performance_indexes_for_items_and_delivery.php --force
```

### Step 3: Clear Caches & Restart Services
```bash
# Clear application caches
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# CRITICAL: Restart queue workers to load new job code
php artisan queue:restart

# If using supervisor, restart the worker
sudo supervisorctl restart laravel-worker:*

# Restart PHP-FPM (if needed)
sudo systemctl restart php8.1-fpm  # Adjust PHP version as needed
```

### Step 4: Verify Deployment
```bash
# Check if indexes were created
mysql snocart -e "SHOW INDEX FROM items WHERE Key_name LIKE 'idx_%';" | wc -l
# Expected: Should show 5+ new indexes

# Check if queue workers are running
php artisan queue:monitor RecordDeliveryLocationJob --max=30

# Check application is responding
curl -I https://your-domain.com/api/v1/config
```

---

## Post-Deployment Monitoring

### Monitor for 30 Minutes (Stay Alert!)

#### 1. Watch Slow Query Log
```bash
# Terminal 1: Watch slow queries in real-time
sudo tail -f /var/log/mysql/slow.log

# Should see SIGNIFICANTLY fewer entries
# If still seeing many slow queries, investigate immediately
```

#### 2. Monitor Laravel Logs
```bash
# Terminal 2: Watch Laravel logs
tail -f storage/logs/laravel-$(date +%Y-%m-%d).log | grep -E "Slow query|ERROR|CRITICAL"

# Should see fewer "Slow query detected" warnings
```

#### 3. Monitor Queue Jobs
```bash
# Terminal 3: Monitor job processing
php artisan queue:monitor RecordDeliveryLocationJob

# Check for failures
php artisan queue:failed
```

#### 4. Monitor Database Locks
```bash
# Check for lock waits every 5 minutes
watch -n 300 'mysql snocart -e "SELECT COUNT(*) as waiting FROM information_schema.processlist WHERE state LIKE \"%waiting%\";"'
```

#### 5. Monitor Application Performance
- [ ] Open item listing pages → Should load in <1 second
- [ ] Open store detail pages → Should load in <1 second
- [ ] Test delivery man app location updates → Should be instant
- [ ] Check admin dashboard → Should be responsive

---

## Performance Verification Tests

### Test 1: Verify Index Usage
```sql
-- Run EXPLAIN to verify indexes are being used
EXPLAIN
SELECT * FROM items
WHERE status = 1
AND is_approved = 1
AND stock > 0
LIMIT 50;

-- ✅ Expected: key = 'idx_items_active_stock'
-- ✅ Expected: rows < 1000 (was 180K+)
-- ❌ If key = NULL, indexes not being used!
```

### Test 2: Check Query Performance
```sql
-- This should be fast now (<100ms)
SELECT COUNT(*) FROM items
WHERE status = 1
AND is_approved = 1
AND stock > 0;

-- Check slow query log count
SELECT COUNT(*) as slow_queries_last_hour
FROM mysql.slow_log
WHERE start_time > DATE_SUB(NOW(), INTERVAL 1 HOUR);
```

### Test 3: Check Lock Contention
```sql
-- Should return 0 or very low numbers
SELECT
    COUNT(*) as waiting_queries,
    COALESCE(AVG(time), 0) as avg_wait_seconds
FROM information_schema.processlist
WHERE state LIKE '%waiting%'
AND command != 'Sleep';
```

---

## Success Criteria

### ✅ Deployment Successful If:
- [ ] All indexes created successfully (verify with SQL query)
- [ ] No increase in error rate in Laravel logs
- [ ] Queue workers processing jobs normally
- [ ] Slow query log shows 70-80% reduction in entries
- [ ] Item pages loading in <1 second
- [ ] Delivery location updates working without errors
- [ ] No increase in failed jobs
- [ ] Database CPU usage stable or decreased

### ⚠️ Warning Signs (Investigate):
- Slow query log still showing many entries for items table
- Lock wait timeout errors appearing
- Queue jobs backing up
- Application response time not improved

### 🔴 Critical Issues (Consider Rollback):
- Application errors increasing
- Database CPU usage spiked and staying high
- Queue workers crashing
- Users reporting application is down/slow

---

## Rollback Procedure

### If Critical Issues Occur

#### 1. Rollback Code (Fast)
```bash
# Revert job code
git checkout HEAD~1 app/Jobs/RecordDeliveryLocationJob.php

# Restart queue workers
php artisan queue:restart
sudo supervisorctl restart laravel-worker:*
```

#### 2. Remove Indexes (If Needed - Usually NOT necessary)
```bash
# Only run this if indexes are confirmed to cause issues
mysql snocart < database/rollback_indexes.sql

# Manual rollback:
mysql snocart -e "
ALTER TABLE items DROP INDEX IF EXISTS idx_items_active_stock;
ALTER TABLE items DROP INDEX IF EXISTS idx_items_store_active;
ALTER TABLE items DROP INDEX IF EXISTS idx_items_module_status;
ALTER TABLE items DROP INDEX IF EXISTS idx_items_discount;
ALTER TABLE items DROP INDEX IF EXISTS idx_items_recommended;
ALTER TABLE stores DROP INDEX IF EXISTS idx_stores_active_model;
ALTER TABLE store_subscriptions DROP INDEX IF EXISTS idx_sub_store_status;
"
```

#### 3. Restore Database (LAST RESORT)
```bash
# Only if severe data corruption (highly unlikely from index changes)
mysql snocart < /backups/snocart_pre_optimization_YYYYMMDD_HHMMSS.sql
```

---

## 24-Hour Monitoring Plan

### Hour 0-1 (Critical Window)
- [ ] Active monitoring of all terminals
- [ ] Test all major features manually
- [ ] Check for user complaints
- [ ] Monitor server resources (CPU, RAM, Disk I/O)

### Hour 1-4
- [ ] Check logs every 30 minutes
- [ ] Verify slow query count is reduced
- [ ] Monitor queue job success rate

### Hour 4-24
- [ ] Check logs every 2 hours
- [ ] Collect performance metrics
- [ ] Document improvements

---

## Performance Metrics Collection

### Before Deployment (Baseline)
Record these metrics BEFORE deployment:
```bash
# 1. Slow query count (last hour)
mysql -e "SELECT COUNT(*) FROM mysql.slow_log WHERE start_time > DATE_SUB(NOW(), INTERVAL 1 HOUR);" > /tmp/before_slow_queries.txt

# 2. Average query time for items
# Run this and note the time
time mysql snocart -e "SELECT COUNT(*) FROM items WHERE status=1 AND is_approved=1 AND stock>0;"

# 3. Queue job metrics
php artisan queue:monitor RecordDeliveryLocationJob > /tmp/before_queue_stats.txt
```

### After Deployment (Comparison)
Run the same commands 1 hour after deployment and compare.

---

## Communication Plan

### Before Deployment
- [ ] Notify team of deployment window
- [ ] Set status page to "Scheduled Maintenance" (if applicable)
- [ ] Prepare rollback plan

### During Deployment
- [ ] Post status updates every 15 minutes
- [ ] Keep team informed of progress

### After Deployment
- [ ] Announce completion
- [ ] Share initial performance metrics
- [ ] Request user feedback

---

## Expected Results

### Performance Improvements
| Metric | Before | After | Improvement |
|--------|--------|-------|-------------|
| Delivery location update | 2-7s | <500ms | 85% faster |
| Lock wait time | 6.7s | <200ms | 97% faster |
| Item query time | 2-4s | <500ms | 80% faster |
| Slow queries/hour | 100+ | <20 | 80% reduction |

### User Experience
- Pages load faster
- Delivery location tracking is smooth
- Admin dashboard is more responsive
- Fewer timeout errors

---

## Completion

### Final Checklist
- [ ] All deployment steps completed
- [ ] Performance verified
- [ ] Metrics collected
- [ ] Team notified
- [ ] Documentation updated
- [ ] Backup retained for 30 days

### Sign-Off
- **Deployed By**: _______________
- **Date/Time**: _______________
- **Status**: ☐ Success  ☐ Success with Issues  ☐ Rolled Back
- **Notes**: _____________________________________

---

## Support Contact

If issues arise:
1. Check `storage/logs/PERFORMANCE_FIX_SUMMARY.md` for troubleshooting
2. Review Laravel logs: `storage/logs/laravel-$(date +%Y-%m-%d).log`
3. Check MySQL slow log: `/var/log/mysql/slow.log`
4. Monitor server resources: `htop`, `iotop`

**End of Checklist**
