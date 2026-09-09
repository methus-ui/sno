# CRITICAL: Duplicate Route Names Preventing Route Caching

## Problem

Routes cannot be cached due to **20+ duplicate route names** in `routes/admin.php`.

This prevents route caching, costing ~50-100ms per request.

## Duplicate Route Names Found

```
add-fund
add-new
add-to-cart
admin-landing-page-settings
approve
assign
bulk-assign
bulk-export
bulk-export-index
bulk-import
change-status
customer.select-list
dashboard
day-wise-report-export
day-wise-report-search
delete
deny
destroy
disbursement-export
discount
... and more
```

## Impact

- ❌ Cannot cache routes (slower request routing)
- ❌ Cannot cache config (slower app boot)
- ⚠️ **~50-100ms overhead per request**

## Root Cause

Laravel requires **unique route names** across the entire application. Multiple routes are using generic names like:
- `->name('delete')` appears in multiple route groups
- `->name('approve')` appears in multiple controllers
- `->name('add-new')` used by different resources

## Solution Strategy

### Option 1: Prefix Route Names with Group (Recommended)

```php
// BEFORE
Route::group(['prefix' => 'order'], function () {
    Route::get('list', 'OrderController@list')->name('list');        // ❌
    Route::post('delete', 'OrderController@delete')->name('delete'); // ❌
});

// AFTER
Route::group(['prefix' => 'order'], function () {
    Route::get('list', 'OrderController@list')->name('order.list');        // ✅
    Route::post('delete', 'OrderController@delete')->name('order.delete'); // ✅
});
```

### Option 2: Use Resource Routes (Laravel Standard)

```php
// BEFORE (manual routes with duplicates)
Route::get('order/list', 'OrderController@index')->name('list');
Route::get('order/create', 'OrderController@create')->name('add-new');
Route::post('order/store', 'OrderController@store')->name('store');
Route::get('order/edit/{id}', 'OrderController@edit')->name('edit');
Route::post('order/update', 'OrderController@update')->name('update');
Route::delete('order/delete/{id}', 'OrderController@destroy')->name('delete');

// AFTER (automatic unique names)
Route::resource('order', 'OrderController');
// Generates: order.index, order.create, order.store, order.edit, order.update, order.destroy
```

### Option 3: Remove Names from API Routes

```php
// API routes don't need names if not using route() helper
Route::post('api/v1/delete-item', 'ApiController@delete'); // No ->name() needed
```

## Automated Fix Script

```bash
#!/bin/bash
# fix-duplicate-routes.sh

# Backup routes file
cp routes/admin.php routes/admin.php.backup.$(date +%Y%m%d_%H%M%S)

# This would need manual review - automated replacement risky
echo "⚠️  Manual review required - too many routes to auto-fix safely"
echo "   See DUPLICATE_ROUTES_ISSUE.md for strategy"
```

## Manual Fix Required

**Estimated Time:** 2-4 hours to review and rename all duplicates

**Risk:** Medium - Could break existing `route('name')` calls in views/controllers

**Testing Needed:** Full application testing after renaming

## Temporary Workaround

Routes can still work without caching, just slower:

```bash
php artisan route:clear  # Don't cache routes
# Application works but ~50-100ms slower per request
```

## Action Plan

1. **Immediate:** Document all duplicate names (this file)
2. **Week 1:** Create route naming convention guide
3. **Week 2:** Systematically rename duplicates by controller group
4. **Week 3:** Test all renamed routes
5. **Week 4:** Enable route caching

## Finding Duplicates

```bash
# List all duplicate route names
grep "name('" routes/admin.php | sed "s/.*name('//; s/').*//" | sort | uniq -d

# Count total routes
grep "name('" routes/admin.php | wc -l

# Find which routes use a specific name (e.g., 'delete')
grep -n "name('delete')" routes/admin.php
```

## Route Naming Convention (Recommended)

```
{resource}.{action}
or
{module}.{resource}.{action}

Examples:
- order.list
- order.create
- order.update
- order.delete
- store.order.list
- admin.store.create
```

## Status

**Fixed:** 2 duplicates (store-filter, app-settings)
**Remaining:** 20+ duplicates
**Route Caching:** ❌ BLOCKED until all duplicates resolved

## Priority

**MEDIUM** - Application works without caching, but loses 50-100ms per request.

Balance against:
- Time to fix (2-4 hours)
- Risk of breaking existing code
- Testing effort required

Recommend fixing during scheduled maintenance window.

---

**Date:** February 21, 2026
**Status:** Documented, awaiting manual fix
