# Global Search Troubleshooting Guide

## Issues Fixed (2026-02-22)

1. ✅ **Missing search.svg icon** - Copied from dev environment
2. ✅ **Missing translations** - Added 10 translation keys:
   - Search_or
   - Search_by_keyword
   - Search Result
   - Recent Search
   - No result found
   - And 5 more...

3. ✅ **Cleared all caches**

---

## How to Test

### Option 1: Simple Test Page
Visit: `https://new.snocart.com/test-search.html`

This is a standalone test page to verify:
- Modal opens
- Ctrl+K works
- Search input functions

### Option 2: Test in Admin Panel

1. **Login to admin panel**: `https://new.snocart.com/admin`
2. **Look for search button** in the top navigation (to the left of messages icon)
3. **Try these tests:**

#### Test A: Click Button
- Click the search button
- Modal should open
- Search input should be focused

#### Test B: Keyboard Shortcut
- Press `Ctrl+K` (or `Cmd+K` on Mac)
- Modal should open

#### Test C: Search Functionality
- Type any keyword (minimum 1 character)
- Results should appear
- Try: `settings`, `order`, `dashboard`

#### Test D: Recent Searches
- If search box is empty, should show recent searches
- Click any recent search to navigate

---

## Debugging Checklist

If it's still not working, check these:

### 1. Browser Console (Press F12)
**Look for JavaScript errors:**
```
Console tab → Look for red errors
```

**Common errors:**
- `$ is not defined` → jQuery not loaded
- `route is not defined` → Translation issue
- `modal is not a function` → Bootstrap not loaded

### 2. Network Tab (F12 → Network)
**Check AJAX requests:**
- When you type in search, you should see:
  - `POST /admin/search-routing`
- Response should be JSON with results

### 3. Check Page Source
**Right-click → View Page Source**

Search for these:
- `id="modalOpener"` → Search button exists
- `id="staticBackdrop"` → Modal exists
- `searchForm` → JavaScript is loaded

### 4. PHP Errors
**Check Laravel logs:**
```bash
tail -50 /var/www/html/new_public/new/storage/logs/laravel.log
```

---

## Quick Fixes

### If modal doesn't open:
```bash
# Clear caches
cd /var/www/html/new_public/new
php artisan cache:clear
php artisan view:clear
php artisan config:clear

# Check if Bootstrap JS is loaded
# Open browser console and type:
typeof $.fn.modal
# Should return "function"
```

### If search doesn't work:
```bash
# Check routes
php artisan route:list | grep search

# Should show:
# admin.search.routing
# admin.recent.search
# admin.store.clicked.route
```

### If translations missing:
```bash
# Check messages.php
grep "Search_or" resources/lang/en/messages.php

# Should return:
# 'Search_or' => 'Search or',
```

---

## What to Report

If it's still not working, please provide:

1. **What happens when you click the search button?**
   - Nothing?
   - Error message?
   - Page reloads?

2. **Browser console errors** (F12 → Console tab)
   - Copy/paste any red errors

3. **Network errors** (F12 → Network tab)
   - When you type to search, is there a POST request?
   - What's the response?

4. **Laravel log errors**
   ```bash
   tail -50 storage/logs/laravel.log
   ```

---

## Manual Verification Commands

Run these from `/var/www/html/new_public/new`:

```bash
# 1. Check controller exists
ls -lah app/Http/Controllers/Admin/SearchRoutingController.php

# 2. Check routes registered
php artisan route:list | grep "search.routing"

# 3. Check model exists
ls -lah app/Models/RecentSearch.php

# 4. Check database table
php artisan tinker
>>> \App\Models\RecentSearch::count()
>>> exit

# 5. Check JSON file
ls -lah public/admin_formatted_routes.json

# 6. Check header has modal
grep -c "modalOpener" resources/views/layouts/admin/partials/_header.blade.php
# Should return: 1

# 7. Check JavaScript added
grep -c "searchForm" resources/views/layouts/admin/app.blade.php
# Should return: 3 or more
```

---

## Files to Check

If you need to manually verify:

1. **Header with button & modal:**
   `resources/views/layouts/admin/partials/_header.blade.php`
   - Line 145: Search button with id="modalOpener"
   - Line 327: Modal with id="staticBackdrop"

2. **JavaScript:**
   `resources/views/layouts/admin/app.blade.php`
   - Around line 890-1060: Search JavaScript code

3. **CSS:**
   `public/assets/admin/css/style.css`
   - End of file: Search styles (.search-list-item, .modal-content__search, etc.)

4. **Routes:**
   `routes/admin.php`
   - Line 26-28: Search routes

5. **Controller:**
   `app/Http/Controllers/Admin/SearchRoutingController.php`
   - 2,251 lines of search logic

---

## Success Indicators

✅ Search button visible in header
✅ Clicking button opens modal
✅ Ctrl+K opens modal
✅ Typing shows results
✅ Results are clickable
✅ No console errors
✅ No PHP errors in logs

---

## Contact

If all else fails, provide:
- Screenshot of error
- Browser console log
- Laravel error log
- What browser you're using
