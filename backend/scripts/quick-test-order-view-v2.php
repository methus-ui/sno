#!/usr/bin/env php
<?php

/**
 * Quick Test - Order View V2
 * Fast automated verification of all components
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "\n";
echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║          Order View V2 - Quick Test Suite                   ║\n";
echo "║          Fast verification of all components                ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
echo "\n";

$passed = 0;
$failed = 0;

function test($name, $condition, $details = '') {
    global $passed, $failed;

    if ($condition) {
        echo "✅ PASS: $name\n";
        if ($details) echo "   → $details\n";
        $passed++;
    } else {
        echo "❌ FAIL: $name\n";
        if ($details) echo "   → $details\n";
        $failed++;
    }
}

echo "SECTION 1: Database Verification\n";
echo "════════════════════════════════════════════════════════════════\n";

// Check columns exist
$columns = DB::select("SHOW COLUMNS FROM orders WHERE Field IN ('bargaining_request_id', 'bargaining_accepted_offer_id', 'is_bargaining_order')");
test("Bargaining columns exist", count($columns) === 3, count($columns) . " columns found");

// Check indexes
$indexes = DB::select("SHOW INDEXES FROM orders WHERE Key_name LIKE 'idx_bargaining%'");
test("Bargaining indexes exist", count($indexes) >= 2, count($indexes) . " indexes found");

// Check foreign keys
$fks = DB::select("
    SELECT CONSTRAINT_NAME
    FROM information_schema.KEY_COLUMN_USAGE
    WHERE TABLE_NAME = 'orders'
      AND TABLE_SCHEMA = DATABASE()
      AND CONSTRAINT_NAME LIKE '%bargaining%'
");
test("Foreign keys exist", count($fks) >= 2, count($fks) . " FKs found");

echo "\n";
echo "SECTION 2: Models & Relationships\n";
echo "════════════════════════════════════════════════════════════════\n";

// Check Order model has relationships
$order = new App\Models\Order();
test("Order::bargainingRequest() exists", method_exists($order, 'bargainingRequest'));
test("Order::bargainingAcceptedOffer() exists", method_exists($order, 'bargainingAcceptedOffer'));

// Check BargainingService has linking method
$service = new App\Services\BargainingService();
test("BargainingService::linkOrderToBargaining() exists", method_exists($service, 'linkOrderToBargaining'));

echo "\n";
echo "SECTION 3: Routes\n";
echo "════════════════════════════════════════════════════════════════\n";

// Check admin route
$adminRoute = collect(\Illuminate\Support\Facades\Route::getRoutes())->first(function($route) {
    return $route->getName() === 'admin.order.view-v2';
});
test("Admin view-v2 route exists", $adminRoute !== null, $adminRoute ? $adminRoute->uri() : 'Not found');

// Check vendor route
$vendorRoute = collect(\Illuminate\Support\Facades\Route::getRoutes())->first(function($route) {
    return $route->getName() === 'vendor.order.view-v2';
});
test("Vendor view-v2 route exists", $vendorRoute !== null, $vendorRoute ? $vendorRoute->uri() : 'Not found');

// Check API route
$apiRoute = collect(\Illuminate\Support\Facades\Route::getRoutes())->first(function($route) {
    return str_contains($route->uri(), 'customer/order/details-v2');
});
test("Customer API route exists", $apiRoute !== null, $apiRoute ? $apiRoute->uri() : 'Not found');

echo "\n";
echo "SECTION 4: Controllers\n";
echo "════════════════════════════════════════════════════════════════\n";

// Check Admin controller method
$adminController = new App\Http\Controllers\Admin\OrderController();
test("Admin::viewV2() exists", method_exists($adminController, 'viewV2'));

// Check Vendor controller method
$vendorController = new App\Http\Controllers\Vendor\OrderController();
test("Vendor::viewV2() exists", method_exists($vendorController, 'viewV2'));

// Check API controller method
$apiController = new App\Http\Controllers\Api\V1\OrderController();
test("API::getOrderDetailsWithBargaining() exists", method_exists($apiController, 'getOrderDetailsWithBargaining'));

echo "\n";
echo "SECTION 5: Views\n";
echo "════════════════════════════════════════════════════════════════\n";

// Check admin view file
$adminView = resource_path('views/admin-views/order/order-view-v2.blade.php');
test("Admin V2 view file exists", file_exists($adminView), $adminView);

// Check vendor view file
$vendorView = resource_path('views/vendor-views/order/order-view-v2.blade.php');
test("Vendor V2 view file exists", file_exists($vendorView), $vendorView);

// Check V1 views have toggle buttons
$adminV1 = file_get_contents(resource_path('views/admin-views/order/order-view.blade.php'));
test("Admin V1 has toggle button", str_contains($adminV1, 'view-v2'));

$vendorV1 = file_get_contents(resource_path('views/vendor-views/order/order-view.blade.php'));
test("Vendor V1 has toggle button", str_contains($vendorV1, 'view-v2'));

echo "\n";
echo "SECTION 6: Data Verification\n";
echo "════════════════════════════════════════════════════════════════\n";

// Find bargaining orders
$bargainingOrders = App\Models\Order::where('is_bargaining_order', true)->count();
echo "   Found $bargainingOrders bargaining orders in database\n";

// Find regular orders
$regularOrders = App\Models\Order::where('is_bargaining_order', false)->count();
echo "   Found $regularOrders regular orders in database\n";

// Check if we have test data
if ($bargainingOrders > 0) {
    $testOrder = App\Models\Order::where('is_bargaining_order', true)->first();
    test("Bargaining order has request_id", $testOrder->bargaining_request_id !== null, "Order #{$testOrder->id}");
    test("Bargaining order has accepted_offer_id", $testOrder->bargaining_accepted_offer_id !== null);

    // Try to load relationships
    try {
        $testOrder->load('bargainingRequest', 'bargainingAcceptedOffer');
        test("Relationships load successfully", true, "All relationships loaded");
    } catch (\Exception $e) {
        test("Relationships load successfully", false, $e->getMessage());
    }
} else {
    echo "   ⚠️  No bargaining orders found - create one to test fully\n";
}

echo "\n";
echo "SECTION 7: Translation Keys\n";
echo "════════════════════════════════════════════════════════════════\n";

// Check key translation keys exist
$keys = [
    'bargaining_order',
    'won_via_bargaining',
    'total_savings',
    'fulfillment',
    'winning_rank',
    'request_code',
    'competing_offers'
];

$translationFile = file_get_contents(resource_path('lang/en/messages.php'));
$missingKeys = [];
foreach ($keys as $key) {
    if (!str_contains($translationFile, "'$key'")) {
        $missingKeys[] = $key;
    }
}

test("Critical translation keys exist", empty($missingKeys),
    empty($missingKeys) ? "All keys found" : "Missing: " . implode(', ', $missingKeys)
);

echo "\n";
echo "SECTION 8: URL Generation\n";
echo "════════════════════════════════════════════════════════════════\n";

// Try to generate URLs
try {
    $testOrderId = App\Models\Order::first()->id ?? 100001;

    $adminUrl = route('admin.order.view-v2', $testOrderId);
    test("Can generate admin V2 URL", true, $adminUrl);

    $vendorUrl = route('vendor.order.view-v2', $testOrderId);
    test("Can generate vendor V2 URL", true, $vendorUrl);

    echo "   API URL: " . config('app.url') . "/api/v1/customer/order/details-v2/{$testOrderId}\n";

} catch (\Exception $e) {
    test("Can generate URLs", false, $e->getMessage());
}

echo "\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "                         TEST SUMMARY                           \n";
echo "════════════════════════════════════════════════════════════════\n";
echo "\n";

$total = $passed + $failed;
$percentage = $total > 0 ? round(($passed / $total) * 100, 1) : 0;

echo "Total Tests: $total\n";
echo "✅ Passed: $passed\n";
echo "❌ Failed: $failed\n";
echo "Success Rate: $percentage%\n";
echo "\n";

if ($failed === 0) {
    echo "╔══════════════════════════════════════════════════════════════╗\n";
    echo "║  🎉 ALL TESTS PASSED - SYSTEM READY FOR TESTING!           ║\n";
    echo "╚══════════════════════════════════════════════════════════════╝\n";
    echo "\n";
    echo "Next steps:\n";
    echo "1. Create a bargaining order (see TESTING_ORDER_VIEW_V2_GUIDE.md)\n";
    echo "2. Test admin panel V2 view\n";
    echo "3. Test vendor panel V2 view\n";
    echo "4. Test customer app API\n";
    echo "5. Test all edge cases\n";
    echo "\n";
} else {
    echo "╔══════════════════════════════════════════════════════════════╗\n";
    echo "║  ⚠️  SOME TESTS FAILED - REVIEW ERRORS ABOVE               ║\n";
    echo "╚══════════════════════════════════════════════════════════════╝\n";
    echo "\n";
    echo "Common fixes:\n";
    echo "1. Run migration: php artisan migrate\n";
    echo "2. Clear caches: php artisan view:clear && php artisan route:clear\n";
    echo "3. Check file permissions\n";
    echo "4. Review error messages above\n";
    echo "\n";
}

exit($failed > 0 ? 1 : 0);
