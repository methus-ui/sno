# Production Bugs Report - March 16, 2026

**Analysis Date:** 2026-03-16 16:10 UTC
**Analyzed Period:** March 13-16, 2026
**Laravel Environment:** Production
**Site:** https://new.snocart.com

---

## SUMMARY

✅ **GOOD NEWS:** No CRITICAL/ERROR/EMERGENCY crashes in Laravel logs
⚠️ **ISSUES FOUND:** 3 critical bugs, 1 warning, 47 bot-related errors

---

## 🔴 CRITICAL ISSUES (Fix Immediately)

### 1. PHP Deprecated Warning - Parameter Order Bug ⚠️

**Severity:** HIGH (Will break in PHP 9.0)
**Frequency:** 12+ occurrences today
**Impact:** All delivery man conversation pages

**Error:**
```
PHP Deprecated: Optional parameter $dataLimit declared before required parameter
$user is implicitly treated as a required parameter
```

**Files:**
- `app/Repositories/ConversationRepository.php:117`
- `app/Contracts/Repositories/ConversationRepositoryInterface.php:47`

**Current Code (WRONG):**
```php
public function getDmConversationList(
    Request $request,
    int|string $dataLimit = DEFAULT_DATA_LIMIT,  // ❌ Optional parameter BEFORE required
    int $user,                                    // ❌ Required parameter AFTER optional
    int $offset = null
)
```

**Fix Required:**
```php
public function getDmConversationList(
    Request $request,
    int $user,                                    // ✅ Required parameter FIRST
    int|string $dataLimit = DEFAULT_DATA_LIMIT,  // ✅ Optional parameter AFTER required
    int $offset = null
)
```

**Affected Pages:**
- Admin Users page (`/admin/users`)
- Delivery Man management (`/admin/users/delivery-man`)
- All conversation-related endpoints

**Risk:** This is a PHP 8.0+ deprecation. It WILL become a fatal error in PHP 9.0.

---

### 2. PHP-FPM Timeout Errors 🕐

**Severity:** CRITICAL
**Frequency:** 13 occurrences today (06:08, 09:04, 12:13-12:34, 14:47-14:48)
**Impact:** User requests failing, poor user experience

**Error:**
```
The timeout specified has expired: Error dispatching request to :
(reading input brigade)
```

**Cause:** Requests taking longer than PHP-FPM timeout (likely 60-120 seconds)

**Suspected Culprits:**
1. Slow database queries (see below)
2. Heavy processing without optimization
3. External API calls without timeouts

**Affected Endpoints:**
- Store application (`/store/apply`) - 09:04
- Various API endpoints - 12:13-12:34 (5 consecutive timeouts)
- General requests - 14:47-14:48

**Fix Required:**
1. Identify slow endpoints (enable query logging)
2. Optimize slow queries (see #3 below)
3. Add indexes to frequently queried tables
4. Implement background jobs for heavy processing
5. Consider increasing PHP-FPM timeout temporarily

---

### 3. Slow Database Queries 🐌

**Severity:** HIGH
**Frequency:** 3 occurrences today
**Impact:** Performance degradation, timeouts

**Query 1: Delivery History Insert (9.5 seconds!)**
```sql
INSERT INTO delivery_histories (delivery_man_id, latitude, longitude, location, time, ...)
ON DUPLICATE KEY UPDATE ...
Time: 9502.12ms (9.5 seconds)
Location: RecordDeliveryLocationJob.php:68
```

**Query 2: User Ref Code Update (2.7 seconds)**
```sql
UPDATE users SET ref_code = ? WHERE id = ?
Time: 2752.66ms (2.7 seconds)
Location: CustomerAuthController.php:1089
```

**Query 3: Complex Items Query (2.1 seconds)**
```sql
SELECT items.*, (select active as temp_available from stores...) FROM items
WHERE status = ? AND is_approved = ? AND exists(...) [complex nested exists]
Time: 2140.82ms (2.1 seconds)
Location: item.php:653
```

**Fix Required:**

**For Query 1 (Delivery History):**
- Add composite index: `(delivery_man_id, time)`
- Check for table locks (ON DUPLICATE KEY can lock entire table)
- Consider batching location updates

**For Query 2 (User Ref Code):**
- Add index on `users.ref_code` if not exists
- This is during registration - check for table locks
- Consider setting ref_code at creation time, not after

**For Query 3 (Items Query):**
- Already has indexes from delivery stats optimization
- Consider adding `temp_available` as a real column (not subquery)
- Break down complex EXISTS clauses into JOINs

---

## ⚠️ WARNINGS

### 4. Dev Site (dev.snocart.com) Returning 503 Errors

**Severity:** MEDIUM (Non-production site)
**Frequency:** 47 occurrences today
**Impact:** Search engine bots getting errors, SEO impact

**Error Pattern:**
```
HTTP 503 Service Unavailable
From: Googlebot, ChatGPT-User, OAI-SearchBot
```

**Timeframe:** 02:13 - 13:05 (intermittent)

**Cause:** Apache redirect loop detected
```
Request exceeded the limit of 10 internal redirects due to probable
configuration error.
```

**Fix Required:**
1. Check dev.snocart.com Apache configuration
2. Review .htaccess for infinite redirects
3. Check if site is in maintenance mode
4. Verify PHP-FPM is running for dev site

**Note:** Production site (new.snocart.com) is working fine.

---

## 📊 STATISTICS

### Error Breakdown (Today)

| Error Type | Count | Severity |
|------------|-------|----------|
| PHP Deprecation Warning | 12+ | HIGH |
| PHP-FPM Timeouts | 13 | CRITICAL |
| Slow Queries (>2s) | 3 | HIGH |
| 503 Errors (dev site) | 47 | MEDIUM |
| **Total Issues** | **75+** | **MIXED** |

### Query Performance

| Query Type | Time | Status |
|------------|------|--------|
| Delivery History Insert | 9502ms | 🔴 CRITICAL |
| User Update | 2752ms | 🔴 CRITICAL |
| Items Select | 2140ms | 🟡 WARNING |

### Site Status

| Site | Status | Notes |
|------|--------|-------|
| new.snocart.com | ✅ OPERATIONAL | Some timeouts |
| dev.snocart.com | 🔴 DOWN | 503 errors |
| snocart.com | ✅ OPERATIONAL | No issues |

---

## 🎯 ACTION ITEMS (Priority Order)

### Priority 1: Fix Today
1. ✅ Fix ConversationRepository parameter order bug
2. 🔍 Investigate PHP-FPM timeouts (add logging)
3. 🐌 Optimize delivery_histories insert query

### Priority 2: Fix This Week
4. 📊 Add indexes for slow queries
5. 🔧 Fix dev.snocart.com redirect loop
6. 🔍 Enable slow query logging (if not already enabled)

### Priority 3: Monitor
7. 📈 Set up alerts for timeouts
8. 📊 Monitor query performance daily
9. 🤖 Fix robots.txt for dev site (prevent bot crawling)

---

## 📝 NOTES

### Good News ✅
- No fatal errors or crashes
- No SQL errors or constraint violations
- No authentication/security issues
- No memory or segmentation faults
- Main production site stable
- Transaction sync working correctly

### Laravel Log Activity
- **Today:** Only 13 lines logged (3 slow queries)
- **Yesterday:** 0 errors
- **March 14:** 0 errors
- **March 13:** 0 errors

This suggests:
- Application is generally stable
- Most traffic is served successfully
- Issues are isolated to specific endpoints

---

## 🔧 RECOMMENDED IMMEDIATE FIXES

### Fix #1: ConversationRepository Parameter Order

**File 1:** `app/Repositories/ConversationRepository.php`
```php
// Line 117 - Change from:
public function getDmConversationList(Request $request, int|string $dataLimit = DEFAULT_DATA_LIMIT, int $user ,int $offset = null): Collection|LengthAwarePaginator

// To:
public function getDmConversationList(Request $request, int $user, int|string $dataLimit = DEFAULT_DATA_LIMIT, int $offset = null): Collection|LengthAwarePaginator
```

**File 2:** `app/Contracts/Repositories/ConversationRepositoryInterface.php`
```php
// Line 47 - Change from:
public function getDmConversationList(Request $request, int|string $dataLimit = DEFAULT_DATA_LIMIT, int $user,  int $offset = null ): Collection|LengthAwarePaginator;

// To:
public function getDmConversationList(Request $request, int $user, int|string $dataLimit = DEFAULT_DATA_LIMIT, int $offset = null): Collection|LengthAwarePaginator;
```

**IMPORTANT:** Update ALL calls to this method to match new signature!

### Fix #2: Add Indexes for Slow Queries

Create migration: `2026_03_16_add_indexes_for_slow_queries.php`

```php
Schema::table('delivery_histories', function (Blueprint $table) {
    $table->index(['delivery_man_id', 'time'], 'idx_dm_time');
});

Schema::table('users', function (Blueprint $table) {
    $table->index('ref_code', 'idx_ref_code');
});
```

### Fix #3: Increase Slow Query Threshold

In `.env`:
```
DB_SLOW_QUERY_TIME=2000  # Alert on queries >2 seconds
```

---

## 📈 MONITORING RECOMMENDATIONS

1. **Set up Slack/Email alerts for:**
   - PHP-FPM timeouts
   - Queries >5 seconds
   - HTTP 5xx errors >10/hour

2. **Enable Laravel Telescope** (if not already)
   - Track slow queries
   - Monitor request performance
   - Debug exceptions

3. **Add New Relic or similar APM**
   - Real-time performance monitoring
   - Database query analysis
   - Error tracking

---

## ✅ VERIFICATION CHECKLIST

After fixes applied:

- [ ] ConversationRepository deprecation warning gone
- [ ] Zero PHP deprecation warnings in logs
- [ ] Delivery history inserts <1 second
- [ ] User updates <500ms
- [ ] No PHP-FPM timeouts for 24 hours
- [ ] dev.snocart.com returns 200 OK
- [ ] Query performance improved by >50%

---

**Report Generated By:** Claude Code Analysis
**Next Review:** March 17, 2026
**Contact:** Check logs daily, fix critical issues immediately
