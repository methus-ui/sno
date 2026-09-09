# Security Fixes Applied - 2026-01-28

## Summary

This document describes the security fixes applied to the application. All changes are backwards compatible and include rollback capability.

---

## Rollback Instructions

If any issues occur, run the rollback script:

```bash
cd /var/www/html/new_public/new
bash storage/security_backups/20260128/ROLLBACK.sh
```

---

## Fixes Applied

### 1. PaymentController.php - Removed eval() Vulnerability

**Risk Level:** CRITICAL
**File:** `app/Http/Controllers/PaymentController.php`

**Before:** Used `eval()` to dynamically create classes, allowing potential code execution.

**After:** Uses PHP's native trait mechanism with `use Payment;` statement.

**Impact:** None - functionality unchanged.

---

### 2. CORS Configuration - Environment-based Settings

**Risk Level:** HIGH
**File:** `config/cors.php`

**Before:** Allowed all origins with `'*'` wildcard.

**After:**
- Configurable via `CORS_ALLOWED_ORIGINS` environment variable
- Defaults to `'*'` for backwards compatibility
- Added specific allowed headers including app-specific ones (zoneId, moduleId, etc.)

**To Restrict Origins (Recommended for Production):**

Add to your `.env` file:
```
CORS_ALLOWED_ORIGINS=https://yourdomain.com,https://app.yourdomain.com,https://admin.yourdomain.com
```

**Impact:** None if env variable not set. If set, restricts API access to specified domains only.

---

### 3. Zone.php - SQL Injection Fix

**Risk Level:** HIGH
**File:** `app/Models/Zone.php`

**Before:** Used string interpolation in SQL query:
```php
whereRaw("ST_Distance_Sphere(coordinates, POINT({$abc}))")
```

**After:** Uses parameterized queries:
```php
whereRaw("ST_Distance_Sphere(coordinates, POINT(?, ?))", [$lng, $lat])
```

**Impact:** None - functionality unchanged. Now accepts both "lng,lat" string and [lng, lat] array formats.

---

### 4. CustomerAuthController.php - Test OTP Bypass Hardening

**Risk Level:** CRITICAL
**File:** `app/Http/Controllers/Api/V1/Auth/CustomerAuthController.php`

**Before:** Test OTP "123456" worked if only `APP_MODE=test`.

**After:** Test OTP requires ALL of these conditions:
- `APP_MODE=test`
- `APP_DEBUG=true`
- `APP_ENV` NOT in ['production', 'live']

Plus, all test OTP attempts are now logged for security audit.

**Impact:** Test OTP will NOT work in production (as intended). Works normally in development/test environments.

---

### 5. API Routes - External API Protection

**Risk Level:** HIGH
**Files:**
- `routes/api/v1/api.php`
- `app/Http/Middleware/ExternalApiAuth.php` (new)
- `app/Http/Kernel.php`

**Before:** Routes `external-update-data` and `transfer-mart-from-drivemond` had no middleware protection.

**After:** New `external.api` middleware provides:
- **Rate limiting:** 60 requests/minute per IP
- **IP whitelisting:** Optional via `EXTERNAL_API_ALLOWED_IPS` env variable
- **Audit logging:** All access attempts are logged
- **Parameter validation:** Required params checked before controller

**To Enable IP Whitelist (Recommended):**

Add to your `.env` file:
```
EXTERNAL_API_ALLOWED_IPS=192.168.1.100,10.0.0.50
```

**Impact:** None if env variable not set. If set, only specified IPs can access external API endpoints.

---

## New Environment Variables

Add these to your `.env` file for enhanced security:

```env
# Restrict CORS to specific domains (comma-separated)
# Leave empty or don't set to allow all origins
CORS_ALLOWED_ORIGINS=https://yourdomain.com,https://app.yourdomain.com

# Restrict external API access to specific IPs (comma-separated)
# Leave empty or don't set to allow all IPs (with rate limiting)
EXTERNAL_API_ALLOWED_IPS=

# Ensure these are set correctly for production
APP_MODE=live
APP_DEBUG=false
APP_ENV=production
```

---

## Files Modified

| File | Change Type | Backup Location |
|------|-------------|-----------------|
| `app/Http/Controllers/PaymentController.php` | Modified | `PaymentController.php.bak` |
| `config/cors.php` | Modified | `cors.php.bak` |
| `app/Models/Zone.php` | Modified | `Zone.php.bak` |
| `app/Http/Controllers/Api/V1/Auth/CustomerAuthController.php` | Modified | `CustomerAuthController.php.bak` |
| `routes/api/v1/api.php` | Modified | `api.php.bak` |
| `app/Http/Kernel.php` | Modified | `Kernel.php.bak` |
| `app/Http/Middleware/ExternalApiAuth.php` | Created | (remove if rolling back) |

---

## Testing Checklist

After applying fixes, verify:

- [ ] Payment processing works normally
- [ ] API requests from mobile apps work
- [ ] Zone detection works correctly
- [ ] User login/registration works
- [ ] OTP verification works (should fail with "123456" in production)
- [ ] External integrations (Drivemond) still work

---

## Security Recommendations (Not Yet Applied)

1. **Rotate exposed credentials** - Change database password, API keys in `.env`
2. **Remove .env from git** - Run `git rm --cached .env`
3. **Add rate limiting** - Consider adding throttle middleware to more endpoints
4. **Enable HTTPS** - Ensure all API traffic uses HTTPS
5. **Set CORS origins** - Configure `CORS_ALLOWED_ORIGINS` in production

---

## Contact

If issues occur after applying these fixes:
1. Run the rollback script immediately
2. Check Laravel logs: `storage/logs/laravel-*.log`
3. Review the backup files in this directory
