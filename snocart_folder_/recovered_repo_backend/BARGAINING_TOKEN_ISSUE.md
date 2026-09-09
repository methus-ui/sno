# Bargaining Mode - Token Authentication Issue

## Problem

Flutter app getting "authentication-failed" when calling `/api/v2/bargaining/status/{code}` after successfully calling `/api/v2/bargaining/initiate`.

## Root Cause

The JWT token used by the Flutter app is **NOT in the database** (`oauth_access_tokens` table).

**Investigation Results:**
```bash
✅ JWT is syntactically valid (expires 2027-03-17)
✅ Bargaining backend is working (offers generated successfully)
❌ Token NOT found in oauth_access_tokens table
❌ All API calls failing with authentication-failed
```

**Why This Happens:**
1. Token was issued in a previous session
2. Token was deleted from database (manual cleanup, migration, or app reinstall)
3. Token was revoked
4. Using test/development token in production

## Solution

### For Flutter App - Re-Login Required

The user needs to **logout and login again** to get a fresh valid token.

**Steps:**
1. In Flutter app, call logout API
2. Clear stored auth token
3. Login again with credentials
4. Use the new token for all API calls

### Quick Test with New Token

After logging in again, test the status API:

```bash
# 1. Login to get new token
curl -X POST https://new.snocart.com/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email_or_phone":"YOUR_PHONE","password":"YOUR_PASSWORD"}'

# 2. Copy the new token from response

# 3. Test status API
curl -X GET "https://new.snocart.com/api/v2/bargaining/status/BR-82LLRI" \
  -H "Authorization: Bearer NEW_TOKEN_HERE" \
  -H "Content-Type: application/json"
```

## Backend Status

✅ **Bargaining is working!**

- Request Code: BR-82LLRI
- Status: awarded
- Offers: 1 offer for ₹165 from Store 9
- All 104 stores enabled for bargaining
- Rating calculation bug fixed

## Why Initiate Worked But Status Failed

**Theory 1: Token Revoked Between Calls**
- Initiate API succeeded (token was valid at that moment)
- A few seconds later, token was invalidated/deleted
- Status polling started failing

**Theory 2: Different Middleware**
- Unlikely - both use `auth:api` middleware
- Both use Passport guards
- More likely the token was already invalid and initiate got lucky

**Theory 3: Cache Issue**
- Passport may have cached the validation for initiate
- Fresh validation for status failed

## Verification

Check if token exists in database:
```bash
php artisan tinker
$tokenHash = hash('sha256', 'YOUR_FULL_JWT_TOKEN_HERE');
$token = DB::table('oauth_access_tokens')->where('id', $tokenHash)->first();
print_r($token);
```

If `null` → Token doesn't exist, re-login required.

## Prevention

**For Production:**
1. Handle `authentication-failed` errors in Flutter app
2. Auto-retry login if token is invalid
3. Show "Session expired, please login again" message
4. Store tokens securely (encrypted shared preferences)
5. Implement refresh token flow if available

**For Development:**
1. Don't delete `oauth_access_tokens` table during testing
2. Don't run `php artisan migrate:fresh` (deletes all tokens)
3. Use long-lived tokens for testing (already set to 365 days)

## Current Status

**Backend:** ✅ Ready and working
**Frontend:** ❌ Needs fresh token from login

---

**Next Step:** Have the user **logout and login again** in the Flutter app, then retry bargaining mode.
