#!/usr/bin/env php
<?php

/**
 * Delivery Stats V2 - Quick Verification Script
 * Tests that the revamp is working correctly
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  Delivery Stats V2 - Verification Script                  ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";
echo "\n";

$tests = [
    'V2 Blade Template Exists',
    'Old Blade Template Backed Up',
    'V2 JavaScript Exists',
    'Old JavaScript Backed Up',
    'Controller Method Exists',
    'Main Route Exists',
    'AJAX Routes Commented',
    'DeliveryStatsService Exists',
    'Database Indexes Exist',
    'Cache Working'
];

$passed = 0;
$failed = 0;

// Test 1: V2 Blade Template Exists
echo "Test 1: V2 Blade Template Exists... ";
if (file_exists(__DIR__ . '/../resources/views/admin-views/delivery-stats-v2.blade.php')) {
    echo "✅ PASS\n";
    $passed++;
} else {
    echo "❌ FAIL\n";
    $failed++;
}

// Test 2: Old Blade Template Backed Up
echo "Test 2: Old Blade Template Backed Up... ";
if (file_exists(__DIR__ . '/../resources/views/admin-views/delivery-stats-old.blade.php')) {
    echo "✅ PASS\n";
    $passed++;
} else {
    echo "⚠️  WARNING (not critical)\n";
}

// Test 3: V2 JavaScript Exists
echo "Test 3: V2 JavaScript Exists... ";
if (file_exists(__DIR__ . '/../public/assets/admin/js/delivery-stats-simple.js')) {
    echo "✅ PASS\n";
    $passed++;
} else {
    echo "❌ FAIL\n";
    $failed++;
}

// Test 4: Old JavaScript Backed Up
echo "Test 4: Old JavaScript Backed Up... ";
$oldChartsExists = file_exists(__DIR__ . '/../public/assets/admin/js/delivery-stats-charts-old.js');
$oldFiltersExists = file_exists(__DIR__ . '/../public/assets/admin/js/delivery-stats-filters-old.js');
if ($oldChartsExists && $oldFiltersExists) {
    echo "✅ PASS\n";
    $passed++;
} else {
    echo "⚠️  WARNING (not critical)\n";
}

// Test 5: Controller Method Exists
echo "Test 5: Controller Method Exists... ";
if (method_exists('App\Http\Controllers\Admin\DashboardController', 'delivery_stats')) {
    echo "✅ PASS\n";
    $passed++;
} else {
    echo "❌ FAIL\n";
    $failed++;
}

// Test 6: Main Route Exists
echo "Test 6: Main Route Exists... ";
try {
    $route = \Illuminate\Support\Facades\Route::getRoutes()->getByName('admin.delivery-stats');
    if ($route) {
        echo "✅ PASS\n";
        $passed++;
    } else {
        echo "❌ FAIL\n";
        $failed++;
    }
} catch (Exception $e) {
    echo "❌ FAIL (" . $e->getMessage() . ")\n";
    $failed++;
}

// Test 7: AJAX Routes Commented
echo "Test 7: AJAX Routes Commented... ";
$routesFile = file_get_contents(__DIR__ . '/../routes/admin.php');
$ajaxRoutesCommented = strpos($routesFile, '// Route::get(\'/delivery-stats/chart/hourly\'') !== false;
if ($ajaxRoutesCommented) {
    echo "✅ PASS\n";
    $passed++;
} else {
    echo "⚠️  WARNING (not critical)\n";
}

// Test 8: DeliveryStatsService Exists
echo "Test 8: DeliveryStatsService Exists... ";
if (class_exists('App\Services\DeliveryStatsService')) {
    echo "✅ PASS\n";
    $passed++;
} else {
    echo "❌ FAIL\n";
    $failed++;
}

// Test 9: Database Indexes Exist
echo "Test 9: Database Indexes Exist... ";
try {
    $indexes = \Illuminate\Support\Facades\DB::select("
        SELECT COUNT(*) as count
        FROM information_schema.statistics
        WHERE table_schema = DATABASE()
        AND table_name = 'orders'
        AND index_name IN ('idx_zone_status', 'idx_status_created', 'idx_created_at', 'idx_delivered', 'idx_schedule_at', 'idx_refund_requested', 'idx_delivery_time_calc')
    ");
    $indexCount = $indexes[0]->count ?? 0;
    if ($indexCount >= 7) {
        echo "✅ PASS ($indexCount/7 indexes found)\n";
        $passed++;
    } else {
        echo "⚠️  WARNING ($indexCount/7 indexes found)\n";
    }
} catch (Exception $e) {
    echo "⚠️  WARNING (could not check: " . $e->getMessage() . ")\n";
}

// Test 10: Cache Working
echo "Test 10: Cache Working... ";
try {
    \Illuminate\Support\Facades\Cache::put('test_delivery_stats_v2', 'working', 10);
    $value = \Illuminate\Support\Facades\Cache::get('test_delivery_stats_v2');
    if ($value === 'working') {
        echo "✅ PASS\n";
        $passed++;
        \Illuminate\Support\Facades\Cache::forget('test_delivery_stats_v2');
    } else {
        echo "⚠️  WARNING (cache not working as expected)\n";
    }
} catch (Exception $e) {
    echo "⚠️  WARNING (cache error: " . $e->getMessage() . ")\n";
}

echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  Test Results                                              ║\n";
echo "╠════════════════════════════════════════════════════════════╣\n";
printf("║  Passed: %2d / %2d                                           ║\n", $passed, count($tests));
printf("║  Failed: %2d / %2d                                           ║\n", $failed, count($tests));
echo "╚════════════════════════════════════════════════════════════╝\n";
echo "\n";

if ($failed === 0) {
    echo "✅ All critical tests passed! V2 is ready.\n";
    echo "\n";
    echo "📋 Next Steps:\n";
    echo "   1. Visit: https://new.snocart.com/admin/delivery-stats\n";
    echo "   2. Verify dark theme is applied\n";
    echo "   3. Check that all 4 charts display\n";
    echo "   4. Test filters (zone, date range)\n";
    echo "   5. Click refresh button\n";
    echo "\n";
    exit(0);
} else {
    echo "❌ Some tests failed. Please review the output above.\n";
    echo "\n";
    exit(1);
}
