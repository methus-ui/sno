# Splash screen hang — `/api/v1/config` returns HTTP 500

## Symptom
App opens in Chrome, guest auth succeeds (`/api/v1/auth/guest/request` → 200), then:

```
====> API Response: [500] /api/v1/config
------------Unexpected null value.
```

and the app stays on the splash logo forever.

## Why it hangs (two problems stacked)

1. **Backend**: `/api/v1/config` throws an exception → Laravel returns HTTP 500.
   The splash screen only navigates after config loads successfully
   (`_route()` → `getConfigData()` → on failure it did *nothing*), so the app waits forever.
2. **App**: the `Unexpected null value.` Dart error is a *symptom*, not the cause.
   When the 500 arrived, `ApiChecker.checkApi()` → `showCustomSnackBar()` ran
   `ScaffoldMessenger.of(Get.context!)` before the first frame had rendered, so
   `Get.context` was still null → null-check crash. That exception was swallowed by
   `api_client.dart`'s catch (which is also why the real 500 body was never printed —
   stock 6amMart code suppresses 500 bodies on web).

## Root causes fixed (backend)

`ConfigController::configuration()` had many ways to throw on real-world DB data:

| Crash source | Fix |
|---|---|
| `Cache::rememberForever` when the cache store is broken (`.env` uses `CACHE_DRIVER=database`; pending migrations can leave the `cache` table missing) — every cache call → PDOException → 500 | New `cacheSafe()` wrapper falls back to a direct query; same guard in `Helpers::get_business_settings()` and `get_settings_status()` |
| Direct `$settings['business_name']`, `$settings['logo']`, `$settings['timeformat']`, … accesses — any missing `business_settings` row → "Undefined array key" → 500 | All replaced with `data_get($settings, 'key', default)` |
| `foreach ($languages …)` / `system_language` / `social_login` / `apple_login` over null (missing or non-JSON setting) → 500 | `is_array(...) ? ... : []` + per-row `is_array` guards + `??` defaults |
| `$cod['status']` when `cash_on_delivery` value isn't valid JSON (json_decode returns scalar/null) → 500 | Hardened to always be an array |
| Tiered-charge loop `$tier['min']` on malformed tier JSON → 500 | Skips invalid rows |
| Anything unexpected still throwing → generic "Internal Server Error" with no details | Whole endpoint wrapped: logs the exception to `laravel.log` **and** returns `{errors:[{code:'config', message:'Server error: <real reason>'}]}` which the app now displays |

## Root causes fixed (Flutter app)

- `api_client.dart`: response body is **always printed in debug** now (500 bodies were
  suppressed on web — that's why the console never showed the actual error). Long HTML
  error pages are truncated.
- `api_client.dart`: on error the real response (statusCode/statusText) is returned to
  callers instead of an empty `Response()`, so screens can show the real reason.
- `custom_snackbar.dart`: `Get.context!` → null-safe with `Get.showSnackbar` fallback.
  This is the exact line that printed `Unexpected null value.`
- `splash_screen.dart` / `splash_controller.dart`: if config fails, the splash now shows
  **the server's error message + a Retry button** instead of hanging on the logo forever.

## What YOU must do on your machine

```bash
# 1. get the latest code (this branch / PR)
git pull

# 2. clear the application cache — with CACHE_DRIVER=database, old/broken
#    rememberForever entries live in the DB `cache` table and survive restarts
cd backend && php artisan cache:clear

# 3. restart the backend and the app
php artisan serve
cd ../snocart/mobile-app && flutter run -d chrome
```

If config still fails, the Flutter console now prints the actual 500 body, the splash
screen shows the exact error message, and the full stack trace is in
`backend/storage/logs/laravel-<date>.log` — send that message and we can pinpoint it.

### Likely underlying data issues to check once the error message is visible
- `php artisan migrate:status` → run pending migrations (the `cache` table must exist
  when `CACHE_DRIVER=database`), or set `CACHE_DRIVER=file` in `.env` for local dev.
- Missing rows in `business_settings` (`business_name`, `logo`, `language`,
  `system_language`, …) — re-seed or add them via the admin panel.
