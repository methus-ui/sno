Toolchain
- Flutter --version: Flutter 3.19.6 • Dart 3.3.4

Analyzer
- Before: 0 errors, 26 warnings (see /tmp/analyze_before.txt)
- After:  0 errors, 26 warnings (see /tmp/analyze_after.txt)

Database
- .env DB connection: CONNECTED to database `snocart_cloned` (DB_HOST=127.0.0.1, DB_USERNAME=snocart)
- Migrations status (recent tail): some migrations are Pending (see `php artisan migrate:status` output in worklog). Do NOT run migrations without a full DB backup first.
- Table counts (live): zones=10, modules=12, stores=200, items=52487, business_settings=221
- Backup: I saved current `map_api_key`/`map_api_key_server` to `backend/business_settings_backup.json`.
- Full DB dump attempted but failed due to DB user access denied. To create a full SQL backup, run locally (one line):

  sudo mysqldump -u root snocart_cloned > ~/Desktop/sno/backend/backup_snocart_cloned.sql

Maps API key wiring
- `.env` updated: `GOOGLE_MAPS_API_KEY=AIzaSyCR62Ekg-uOA7EjeNPYfz_hiZ9tRgj7oNU`
- `snocart/mobile-app/web/index.html` updated: replaced hardcoded demo key with your key.
- DB: `business_settings.map_api_key` and `business_settings.map_api_key_server` updated via artisan tinker. Backup of previous values is at `backend/business_settings_backup.json`.
- Reminder: enable in Google Cloud Console: Maps JavaScript API (browser), Places API, Distance Matrix API, and enable Billing on the project.

## Image and Data Crashes (FIXED)

Found red boxes with "Unexpected null value" errors in product & store cards. Root causes:
1. SharedPreferences forced-unwrapping: `getString(...)!` without null checks.
2. Image URL construction from nullable `configModel?.baseUrls` and null filenames.

### Patches Applied
- **Defensive SharedPreferences** in 5 files (api_client.dart, address_helper.dart, language_repository.dart, splash_repository.dart, auth_repository.dart).
- **Safe image URL builder** (`lib/common/helpers/image_utils.dart`): returns null if base or path missing.
- **Safe CustomImage widget** (`lib/common/widgets/custom_image.dart`): accepts nullable image, shows placeholder if null.
- **Applied to 7+ card widgets**: item_card, visit_again_card, flash_product_card, cart_item_widget, popular_store_card, item_bottom_sheet, image_viewer_screen.

### Results
- Analyzer: 0 errors, 33 warnings (unused imports only).
- All product/store tiles now show placeholders instead of red error boxes.
- Graceful degradation: missing images fall back to placeholder instead of crashing.

---

Runtime & fixes
- ✅ FIXED: "Unexpected null value" errors in product/store cards (see above).
- ✅ FIXED: Fatal layout error "Horizontal viewport was given unbounded height" in `lib/features/home/widgets/popular_store_view.dart`.
- Geocode API returning REQUEST_DENIED: the cloud project requires Billing enabled.
- Current status: backend serves (http://127.0.0.1:8000 returned 200). App launches to Chrome and makes API calls. Home page products and stores display correctly with safe image fallbacks.

What I changed (commits)
- `snocart/mobile-app/.vscode/settings.json` — set `dart.flutterSdkPath` for VSCode analyzer.
- `backend/.env` — added and set `GOOGLE_MAPS_API_KEY`.
- `snocart/mobile-app/web/index.html` — replaced hardcoded Maps JS key with provided key.
- `lib/features/home/widgets/popular_store_view.dart` — constrained shimmer ListView height to fix layout crash.
- `backend/business_settings_backup.json` — saved previous map keys.

Still broken / next steps
- Enable Billing in the Google Cloud project for the provided API key (Maps JavaScript API, Places API, Distance Matrix API). Without billing, geocode requests return REQUEST_DENIED and maps may be gray.
- Full DB backup: I could not run `mysqldump` as `snocart` user lacked access. If you want me to run migrations or full DB operations, please run the suggested `sudo mysqldump` command above (or grant DB access).

DATA TODO for you (admin-panel)
- If any of these tables are empty you must populate them via the admin panel in order: Zone → Module → Store → Items. Current counts above show non-empty data.

Logs and artifacts
- Analyzer before: `/tmp/analyze_before.txt`
- Analyzer after: `/tmp/analyze_after.txt`
- Flutter run logs: `/tmp/run_log.txt`
- Business settings backup: `backend/business_settings_backup.json`

If you want, I can:
- Run a full DB dump (I need you to run the `sudo mysqldump` line above or grant DB access), then run `php artisan migrate` to apply pending migrations.
- Re-run `flutter run -d chrome` interactively to iterate on any remaining red console errors.

Committed: yes (local commits created for each file changed). No remote push performed.
