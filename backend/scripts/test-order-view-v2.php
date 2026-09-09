#!/usr/bin/env php
<?php

/**
 * Order View V2 - Comprehensive Testing Script
 * Tests database, models, routes, controllers, and views
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "\n";
echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║         Order View V2 - Testing Script                      ║\n";
echo "║         Testing Bargaining Integration                       ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
echo "\n";

$totalTests = 0;
$passedTests = 0;
$failedTests = 0;

function test($description, $callback) {
    global $totalTests, $passedTests, $failedTests;
    $totalTests++;

    echo str_pad($description, 60, '.');

    try {
        $result = $callback();
        if ($result) {
            echo " ✅ PASS\n";
            $passedTests++;
            return true;
        } else {
            echo " ❌ FAIL\n";
            $failedTests++;
            return false;
        }
    } catch (Exception $e) {
        echo " ❌ ERROR: " . $e->getMessage() . "\n";
        $failedTests++;
        return false;
    }
}

echo "═══════════════════════════════════════════════════════════════\n";
echo "1. DATABASE SCHEMA TESTS\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

test("Column 'bargaining_request_id' exists", function() {
    $columns = DB::select("SHOW COLUMNS FROM orders LIKE 'bargaining_request_id'");
    return count($columns) === 1;
});

test("Column 'bargaining_accepted_offer_id' exists", function() {
    $columns = DB::select("SHOW COLUMNS FROM orders LIKE 'bargaining_accepted_offer_id'");
    return count($columns) === 1;
});

test("Column 'is_bargaining_order' exists", function() {
    $columns = DB::select("SHOW COLUMNS FROM orders LIKE 'is_bargaining_order'");
    return count($columns) === 1;
});

test("Foreign key constraint on bargaining_request_id", function() {
    $fks = DB::select("
        SELECT * FROM information_schema.KEY_COLUMN_USAGE
        WHERE TABLE_NAME = 'orders'
        AND COLUMN_NAME = 'bargaining_request_id'
        AND REFERENCED_TABLE_NAME = 'bargaining_requests'
    ");
    return count($fks) === 1;
});

test("Index 'idx_bargaining_request' exists", function() {
    $indexes = DB::select("SHOW INDEX FROM orders WHERE Key_name = 'idx_bargaining_request'");
    return count($indexes) >= 1;
});

test("Index 'idx_is_bargaining' exists", function() {
    $indexes = DB::select("SHOW INDEX FROM orders WHERE Key_name = 'idx_is_bargaining'");
    return count($indexes) >= 1;
});

echo "\n═══════════════════════════════════════════════════════════════\n";
echo "2. MODEL RELATIONSHIP TESTS\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

test("Order model has 'bargainingRequest' relationship", function() {
    $order = App\Models\Order::first();
    return method_exists($order, 'bargainingRequest');
});

test("Order model has 'bargainingAcceptedOffer' relationship", function() {
    $order = App\Models\Order::first();
    return method_exists($order, 'bargainingAcceptedOffer');
});

test("Order model has 'is_bargaining_order' cast", function() {
    $order = App\Models\Order::first();
    return isset($order->getCasts()['is_bargaining_order']) && $order->getCasts()['is_bargaining_order'] === 'boolean';
});

test("BargainingService has 'linkOrderToBargaining' method", function() {
    $service = new App\Services\BargainingService();
    return method_exists($service, 'linkOrderToBargaining');
});

echo "\n═══════════════════════════════════════════════════════════════\n";
echo "3. ROUTE TESTS\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

test("Admin route 'admin.order.view-v2' exists", function() {
    try {
        $url = route('admin.order.view-v2', 1);
        return strpos($url, 'view-v2') !== false;
    } catch (Exception $e) {
        return false;
    }
});

test("Vendor route 'vendor.order.view-v2' exists", function() {
    try {
        $url = route('vendor.order.view-v2', 1);
        return strpos($url, 'view-v2') !== false;
    } catch (Exception $e) {
        return false;
    }
});

echo "\n═══════════════════════════════════════════════════════════════\n";
echo "4. CONTROLLER METHOD TESTS\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

test("Admin OrderController has 'viewV2' method", function() {
    $controller = new App\Http\Controllers\Admin\OrderController();
    return method_exists($controller, 'viewV2');
});

test("Vendor OrderController has 'viewV2' method", function() {
    $controller = new App\Http\Controllers\Vendor\OrderController();
    return method_exists($controller, 'viewV2');
});

echo "\n═══════════════════════════════════════════════════════════════\n";
echo "5. VIEW FILE TESTS\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

test("Admin order-view-v2.blade.php exists", function() {
    return file_exists(resource_path('views/admin-views/order/order-view-v2.blade.php'));
});

test("Vendor order-view-v2.blade.php exists", function() {
    return file_exists(resource_path('views/vendor-views/order/order-view-v2.blade.php'));
});

test("Admin view contains 'bargaining-hero-card' class", function() {
    $content = file_get_contents(resource_path('views/admin-views/order/order-view-v2.blade.php'));
    return strpos($content, 'bargaining-hero-card') !== false;
});

test("Vendor view contains 'vendor-bargaining-card' class", function() {
    $content = file_get_contents(resource_path('views/vendor-views/order/order-view-v2.blade.php'));
    return strpos($content, 'vendor-bargaining-card') !== false;
});

echo "\n═══════════════════════════════════════════════════════════════\n";
echo "6. TRANSLATION KEY TESTS\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$keys = [
    'bargaining_order', 'won_via_bargaining', 'total_savings', 'fulfillment',
    'view_classic_v1', 'bargaining_v2', 'view_bargaining_details'
];

foreach ($keys as $key) {
    test("Translation key 'messages.$key' exists", function() use ($key) {
        $translation = __('messages.' . $key);
        return strpos($translation, 'messages.') !== 0;
    });
}

echo "\n═══════════════════════════════════════════════════════════════\n";
echo "7. DATA INTEGRITY TESTS\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

test("Check if any bargaining orders exist", function() {
    $count = DB::table('orders')->where('is_bargaining_order', true)->count();
    echo "\n   Found $count bargaining orders";
    return true; // Informational, always pass
});

test("Check if bargaining tables have data", function() {
    $requests = DB::table('bargaining_requests')->count();
    $offers = DB::table('bargaining_store_offers')->count();
    echo "\n   Bargaining requests: $requests";
    echo "\n   Store offers: $offers";
    return true; // Informational, always pass
});

test("Verify sample order loads without errors", function() {
    $order = App\Models\Order::first();
    if (!$order) {
        echo "\n   No orders in database";
        return true;
    }

    // Try to load relationships
    $order->load(['bargainingRequest', 'bargainingAcceptedOffer']);
    return true;
});

echo "\n═══════════════════════════════════════════════════════════════\n";
echo "8. BACKWARD COMPATIBILITY TESTS\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

test("V1 admin view file exists", function() {
    return file_exists(resource_path('views/admin-views/order/order-view.blade.php'));
});

test("V1 vendor view file exists", function() {
    return file_exists(resource_path('views/vendor-views/order/order-view.blade.php'));
});

test("V1 admin view has toggle button code", function() {
    $content = file_get_contents(resource_path('views/admin-views/order/order-view.blade.php'));
    return strpos($content, 'bargaining_v2') !== false && strpos($content, 'is_bargaining_order') !== false;
});

test("V1 vendor view has toggle button code", function() {
    $content = file_get_contents(resource_path('views/vendor-views/order/order-view.blade.php'));
    return strpos($content, 'bargaining_v2') !== false && strpos($content, 'is_bargaining_order') !== false;
});

echo "\n═══════════════════════════════════════════════════════════════\n";
echo "SUMMARY\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

echo "Total Tests: $totalTests\n";
echo "Passed: " . "\033[32m$passedTests\033[0m\n";
echo "Failed: " . ($failedTests > 0 ? "\033[31m$failedTests\033[0m" : "$failedTests") . "\n";
echo "Success Rate: " . round(($passedTests / $totalTests) * 100, 1) . "%\n";

echo "\n";

if ($failedTests === 0) {
    echo "╔══════════════════════════════════════════════════════════════╗\n";
    echo "║  ✅ ALL TESTS PASSED - IMPLEMENTATION VERIFIED!             ║\n";
    echo "╚══════════════════════════════════════════════════════════════╝\n";
    exit(0);
} else {
    echo "╔══════════════════════════════════════════════════════════════╗\n";
    echo "║  ⚠️  SOME TESTS FAILED - REVIEW ERRORS ABOVE                ║\n";
    echo "╚══════════════════════════════════════════════════════════════╝\n";
    exit(1);
}
