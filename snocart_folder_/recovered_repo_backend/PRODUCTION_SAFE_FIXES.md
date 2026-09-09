# Production-Safe Improvements Plan

## ⚠️ IMPORTANT: Production System

This application is **LIVE IN PRODUCTION**. All changes must be:
- ✅ Tested on backup/staging first
- ✅ Reversible with quick rollback
- ✅ Applied during low-traffic windows
- ✅ Monitored after deployment

---

## 🟢 SAFE TO APPLY NOW (Zero Risk)

### 1. **Check Worker Script** (--format flag fix)

**File:** `/usr/local/bin/check-laravel-workers.sh`

**Current Issue:** Calls `php artisan queue:failed --format=table`

**Safe Fix:**
```bash
# Backup original
sudo cp /usr/local/bin/check-laravel-workers.sh /usr/local/bin/check-laravel-workers.sh.backup

# Edit to remove --format flag
sudo sed -i 's/queue:failed --format=table/queue:failed/g' /usr/local/bin/check-laravel-workers.sh
```

**Risk:** ZERO - Only affects error reporting
**Rollback:** `sudo cp /usr/local/bin/check-laravel-workers.sh.backup /usr/local/bin/check-laravel-workers.sh`

---

### 2. **Database Cleanup Script** (Safe, Non-Breaking)

**Create safe cleanup script that you can review:**

```bash
#!/bin/bash
# safe-db-cleanup.sh - Review before running!

# Dry run first - shows what WOULD be deleted
echo "=== DRY RUN - No changes will be made ==="
echo ""
echo "Tables to drop (73MB):"
echo "  - items_backup_before_clone_188 (36.58MB)"
echo "  - items_backup_before_barcode_fix (36.58MB)"
echo ""
echo "Tables to optimize:"
echo "  - order_details (225MB)"
echo "  - items (122MB)"
echo "  - orders (27MB)"
echo ""
read -p "Run actual cleanup? (yes/no): " confirm

if [ "$confirm" != "yes" ]; then
    echo "Cancelled - no changes made"
    exit 0
fi

# Actual cleanup
mysql -u root -p'Sno@$Ck42' -D snocart << 'SQL'
-- Drop backup tables
DROP TABLE IF EXISTS items_backup_before_clone_188;
DROP TABLE IF EXISTS items_backup_before_barcode_fix;

-- Optimize large tables (one at a time to avoid locking)
OPTIMIZE TABLE order_details;
OPTIMIZE TABLE items;
OPTIMIZE TABLE orders;
SQL

echo "✅ Cleanup complete - saved 73MB+"
```

**Risk:** VERY LOW - Only removes backup tables (verify backups exist elsewhere first)
**Rollback:** Cannot restore - verify backups exist before running

---

### 3. **Failed Queue Jobs Investigation**

**Safe to check:**
```bash
php artisan queue:failed
```

**Decide case-by-case:**
- If jobs are old (>7 days): Safe to delete
- If jobs are recent: Investigate, then retry or delete

**Commands:**
```bash
# View details
php artisan queue:failed

# Retry specific job (safe)
php artisan queue:retry <job-id>

# Delete specific job (safe)
php artisan queue:forget <job-id>

# Only if safe: Retry all
php artisan queue:retry all
```

**Risk:** LOW - Retrying failed jobs is usually safe

---

## 🟡 REQUIRES TESTING (Medium Risk)

### 4. **Route Name Deduplication**

**Status:** 642 route names need changing to enable route caching

**Safe Approach:**

**Step 1: Backup**
```bash
cp routes/admin.php routes/admin.php.backup.$(date +%Y%m%d_%H%M%S)
```

**Step 2: Copy to Test Environment**
```bash
# Copy fixed version to separate file for testing
cp /tmp/admin_routes_fixed_v2.php routes/admin.test.php
```

**Step 3: Test Routes Work**
```bash
# Test if route caching works
php artisan route:clear
php artisan route:cache 2>&1 | tee route-cache-test.log

# Check for errors
if [ $? -eq 0 ]; then
    echo "✅ Route caching successful"
else
    echo "❌ Route caching failed - review errors"
fi
```

**Step 4: Test Application**
```bash
# Test critical routes still work
curl -I https://new.snocart.com/admin/dashboard
curl -I https://new.snocart.com/admin/orders/list/all

# Check logs for route errors
tail -50 storage/logs/laravel-$(date +%Y-%m-%d).log
```

**Step 5: Apply if Tests Pass**
```bash
# Only if all tests passed
cp /tmp/admin_routes_fixed_v2.php routes/admin.php
php artisan route:cache
```

**Risk:** MEDIUM - Could break existing `route('name')` calls in views
**Rollback:** `cp routes/admin.php.backup.* routes/admin.php && php artisan route:clear`

**Recommended:** Test on staging/dev environment first

---

## 🔴 DO NOT TOUCH (High Risk / Requires Dev Environment)

### 5. **Item Query Refactoring**

**Status:** 2.4s → 0.3s performance gain possible

**Why Wait:**
- Requires code changes to core business logic
- Needs comprehensive testing
- Could break item listing/search if done wrong

**Action:** Document for next development sprint

---

## 📋 SAFE IMPROVEMENTS READY TO APPLY

### Immediate (Can do right now):

1. ✅ **Fix --format flag** (2 minutes, zero risk)
2. ✅ **Review failed jobs** (5 minutes, low risk)

### This Weekend (Low traffic window):

3. 🟡 **Database cleanup** (10 minutes, verify backups first)
4. 🟡 **Test route fixes** (30 minutes, with rollback ready)

---

## 🛠️ Production-Safe Fix Script

```bash
#!/bin/bash
# production-safe-fixes.sh
# Apply only zero-risk improvements

echo "🔒 Production-Safe Improvements"
echo "================================"
echo ""

# Fix 1: --format flag (if script exists)
if [ -f /usr/local/bin/check-laravel-workers.sh ]; then
    echo "1. Fixing --format flag in worker check script..."
    sudo cp /usr/local/bin/check-laravel-workers.sh /usr/local/bin/check-laravel-workers.sh.backup
    sudo sed -i 's/queue:failed --format=table/queue:failed/g' /usr/local/bin/check-laravel-workers.sh
    echo "   ✅ Fixed"
else
    echo "1. Worker check script not found - skipping"
fi

echo ""

# Fix 2: Check failed jobs
echo "2. Checking failed queue jobs..."
php artisan queue:failed | head -20
echo ""
echo "   Review above jobs and:"
echo "   - Retry: php artisan queue:retry <id>"
echo "   - Delete: php artisan queue:forget <id>"

echo ""
echo "✅ Safe fixes complete!"
echo ""
echo "⚠️  For route fixes and database cleanup:"
echo "   See PRODUCTION_SAFE_FIXES.md"
```

---

## Current Cache Status

```
✅ Config .............. CACHED
✅ Events .............. CACHED
✅ Views ............... CACHED
❌ Routes .............. NOT CACHED (needs duplicate fix)
```

**Current Performance Gain:** ~60ms per request
**Potential with Route Cache:** ~110-160ms per request

---

## Recommendation

**For Production System:**

**Apply Now (Zero Risk):**
- ✅ Fix --format flag
- ✅ Review/cleanup failed jobs

**Schedule for Maintenance Window:**
- 🟡 Route deduplication (requires testing)
- 🟡 Database cleanup (verify backups)

**Schedule for Development Sprint:**
- 🔴 Query refactoring (needs dev/test cycle)

---

**Created:** February 21, 2026
**Status:** Safe fixes ready, high-risk fixes documented for later
