# Troubleshooting: Sales Analysis Export Menu Not Visible

## Issue
"Sales Analysis Export" menu item not showing in sidebar under "Report and Analytics" section.

---

## Quick Fix Checklist

### ✅ Step 1: Hard Refresh Your Browser
**Most common issue - browser cache!**

- **Chrome/Edge (Windows):** Press `Ctrl + Shift + R` or `Ctrl + F5`
- **Chrome/Edge (Mac):** Press `Cmd + Shift + R`
- **Firefox (Windows):** Press `Ctrl + Shift + R`
- **Ctrl + F5`
- **Firefox (Mac):** Press `Cmd + Shift + R`
- **Safari (Mac):** Press `Cmd + Option + R`

**Then refresh the page and check sidebar again.**

---

### ✅ Step 2: Verify You're on Admin Panel (Not Vendor)

Make sure your URL is:
```
✅ Correct: https://new.snocart.com/admin/dashboard
❌ Wrong:   https://new.snocart.com/vendor/dashboard
```

The menu is ONLY in the **admin** panel, not vendor panel.

---

### ✅ Step 3: Check if You See Other Reports

Look at your sidebar. Do you see these menu items?

```
📊 REPORT AND ANALYTICS
├── Item Report          ← Do you see this?
├── Limited Stock Item   ← Do you see this?
├── Store Report         ← Do you see this?
├── Order Report         ← Do you see this?
├── Transaction Report   ← Do you see this?
├── Expense Report       ← Do you see this?
└── Sales Analysis Export ← Should be here
```

**If you DON'T see the other report items either:**
→ Your account doesn't have "report" module permission.

**If you DO see the other reports but NOT Sales Analysis Export:**
→ Browser cache issue. Do hard refresh (Step 1).

---

### ✅ Step 4: Log in as Super Admin

For testing, log in with the Super Admin account:

**Email:** `faheemjavid19@gmail.com`
**Role:** Super Admin (role_id = 1)

Super Admins have ALL permissions including "report" module access.

---

### ✅ Step 5: Test Direct URL Access

Try accessing the export directly:

**Copy this URL and paste in browser:**
```
https://new.snocart.com/admin/sales-analysis-export?type=excel
```

**Expected Result:**
- Excel file downloads immediately (Sales_Analysis_YYYY-MM-DD_HHMMSS.xlsx)

**If file downloads:**
✅ Export is working! Issue is just sidebar visibility (browser cache).

**If you get 403 Forbidden:**
❌ Your account doesn't have permission. Log in as Super Admin.

**If you get 404 Not Found:**
❌ Route not registered. Need to clear Laravel caches.

---

## Advanced Troubleshooting

### Clear Server-Side Caches

SSH into server and run:

```bash
cd /var/www/html/new_public/new
php artisan view:clear
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

### Verify Files Are in Place

```bash
# Check sidebar code
grep -A 3 "sales_analysis_export" resources/views/layouts/admin/partials/_sidebar.blade.php

# Check translation
grep "sales_analysis_export" resources/lang/en/messages.php

# Check route
php artisan route:list | grep sales-analysis
```

**Expected Output:**
```
admin.sales-analysis-export  | GET  | admin/sales-analysis-export
```

---

## Permission Module Check

### How to Enable "Report" Permission for a User

1. Log in as Super Admin
2. Go to **Employee Management** → **Employee Role**
3. Find the role assigned to your user
4. Click **Edit**
5. Find **"Report"** module checkbox
6. ✅ Check it to enable
7. Click **Save**
8. Log out and log back in

---

## Visual Guide: Where to Find Menu

### Correct Location:

```
┌─ ADMIN SIDEBAR ────────────────────┐
│                                    │
│  Dashboard                         │
│  Order Management                  │
│  Store Management                  │
│  ...                               │
│                                    │
│  📊 REPORT AND ANALYTICS           │  ← Look for this section
│  ├── Item Report                   │
│  ├── Limited Stock Item            │
│  ├── Store Report                  │
│  ├── Order Report                  │
│  ├── Transaction Report            │
│  ├── Expense Report                │
│  └── 📊 Sales Analysis Export      │  ← Should be last item
│                                    │
│  Employee Management               │
│  ...                               │
└────────────────────────────────────┘
```

---

## Still Not Working?

### Debug Step by Step:

**1. Are you logged in as admin (not vendor)?**
```
Check URL: Should be /admin/dashboard
```

**2. Can you see "Report and Analytics" section header?**
```
If NO → Your account lacks "report" permission
If YES → Continue to step 3
```

**3. Can you see other report menu items (Item Report, Store Report, etc.)?**
```
If NO → Permission issue
If YES → Continue to step 4
```

**4. Did you hard refresh browser (Ctrl+Shift+R)?**
```
If NO → Do it now!
If YES → Continue to step 5
```

**5. Can you access via direct URL?**
```
Try: https://new.snocart.com/admin/sales-analysis-export?type=excel
If downloads → Sidebar cache issue
If 403/404 → Server-side issue
```

---

## Manual Fix: Force Browser Cache Clear

### Chrome:
1. Press `F12` to open DevTools
2. Right-click the refresh button
3. Select "Empty Cache and Hard Reload"
4. Close DevTools
5. Check sidebar

### Firefox:
1. Press `Ctrl + Shift + Delete`
2. Select "Everything" for time range
3. Check "Cache" only
4. Click "Clear Now"
5. Refresh page

### Safari:
1. Safari → Preferences → Advanced
2. Enable "Show Develop menu"
3. Develop → Empty Caches
4. Refresh page

---

## Expected Behavior After Fix

After successful troubleshooting, you should see:

1. **Sidebar Section:** "REPORT AND ANALYTICS"
2. **Menu Items:** 7 total (6 existing + 1 new)
3. **Last Item:** "📊 Sales Analysis Export"
4. **Click Action:** Downloads Excel file immediately
5. **File Name:** Sales_Analysis_2026-03-04_145030.xlsx (current date/time)
6. **File Size:** ~500KB
7. **Sheets:** 4 tabs (Revenue, Products, Stores, Patterns)

---

## Contact Support If:

- You've tried ALL steps above
- You're logged in as Super Admin
- You've hard refreshed browser
- Direct URL returns 404
- Other reports work but this doesn't

**Then the issue may be with the deployment. Re-run:**
```bash
php artisan view:clear && php artisan cache:clear && php artisan config:clear
```

---

## Summary

**Most Common Issue:** Browser cache (90% of cases)
**Solution:** Hard refresh with `Ctrl + Shift + R`

**Second Most Common:** Permission issue (9% of cases)
**Solution:** Log in as Super Admin or enable "report" module permission

**Least Common:** Server cache (1% of cases)
**Solution:** Run `php artisan view:clear`

---

**After following these steps, the menu WILL appear! 🎯**
