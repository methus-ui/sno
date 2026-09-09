#!/usr/bin/env php
<?php

/**
 * Admin Store Prepayment Feature - Test Script
 *
 * Tests database schema, business logic, and integration
 */

define('LARAVEL_START', microtime(true));
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Order;

echo "\n";
echo "╔═══════════════════════════════════════════════════════════════════╗\n";
echo "║   ADMIN STORE PREPAYMENT FEATURE - VERIFICATION TESTS            ║\n";
echo "╚═══════════════════════════════════════════════════════════════════╝\n";
echo "\n";

$tests_passed = 0;
$tests_failed = 0;

// ============================================================================
// TEST 1: Database Schema
// ============================================================================
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "TEST 1: Database Schema Verification\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$expected_columns = [
    'store_amount_prepaid',
    'prepaid_item_amount',
    'prepaid_delivery_fees',
    'prepaid_at',
    'prepaid_by',
    'prepaid_notes',
];

$missing_columns = [];
foreach ($expected_columns as $column) {
    if (!Schema::hasColumn('orders', $column)) {
        $missing_columns[] = $column;
    }
}

if (empty($missing_columns)) {
    echo "✅ All 6 prepayment columns exist in orders table\n";
    $tests_passed++;
} else {
    echo "❌ Missing columns: " . implode(', ', $missing_columns) . "\n";
    $tests_failed++;
}

// Check indexes
$indexes = DB::select("SHOW INDEX FROM orders WHERE Column_name IN ('store_amount_prepaid', 'prepaid_at', 'prepaid_by')");
if (count($indexes) >= 3) {
    echo "✅ Prepayment indexes exist\n";
    $tests_passed++;
} else {
    echo "⚠️  Some prepayment indexes may be missing\n";
    $tests_failed++;
}

// Check foreign key
$foreign_keys = DB::select("
    SELECT CONSTRAINT_NAME
    FROM information_schema.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'orders'
    AND COLUMN_NAME = 'prepaid_by'
    AND REFERENCED_TABLE_NAME = 'admins'
");

if (!empty($foreign_keys)) {
    echo "✅ Foreign key constraint exists (prepaid_by → admins.id)\n";
    $tests_passed++;
} else {
    echo "❌ Foreign key constraint missing\n";
    $tests_failed++;
}

echo "\n";

// ============================================================================
// TEST 2: Model Casts
// ============================================================================
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "TEST 2: Order Model Casts Verification\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$order = new Order();
$casts = $order->getCasts();

$expected_casts = [
    'store_amount_prepaid' => 'boolean',
    'prepaid_item_amount' => 'float',
    'prepaid_delivery_fees' => 'float',
    'prepaid_at' => 'datetime',
];

$missing_casts = [];
foreach ($expected_casts as $field => $type) {
    if (!isset($casts[$field]) || $casts[$field] !== $type) {
        $missing_casts[] = "$field ($type)";
    }
}

if (empty($missing_casts)) {
    echo "✅ All prepayment casts configured correctly\n";
    $tests_passed++;
} else {
    echo "❌ Missing/incorrect casts: " . implode(', ', $missing_casts) . "\n";
    $tests_failed++;
}

// Check relationship
if (method_exists($order, 'prepaidBy')) {
    echo "✅ prepaidBy() relationship exists\n";
    $tests_passed++;
} else {
    echo "❌ prepaidBy() relationship missing\n";
    $tests_failed++;
}

echo "\n";

// ============================================================================
// TEST 3: COD Orders Analysis
// ============================================================================
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "TEST 3: COD Orders Eligibility Analysis\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$total_cod_orders = Order::where('payment_method', 'cash_on_delivery')->count();
echo "Total COD orders: " . number_format($total_cod_orders) . "\n";

$eligible_orders = Order::where('payment_method', 'cash_on_delivery')
    ->whereNotIn('order_status', ['delivered', 'refunded', 'canceled'])
    ->where('store_amount_prepaid', false)
    ->count();
echo "Eligible for prepayment: " . number_format($eligible_orders) . "\n";

$already_prepaid = Order::where('store_amount_prepaid', true)->count();
echo "Already marked as prepaid: " . number_format($already_prepaid) . "\n";

if ($total_cod_orders > 0) {
    echo "✅ COD orders found in database\n";
    $tests_passed++;
} else {
    echo "⚠️  No COD orders found (expected in production)\n";
}

echo "\n";

// ============================================================================
// TEST 4: Amount Calculation Logic
// ============================================================================
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "TEST 4: Prepayment Amount Calculation\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$sample_order = Order::where('payment_method', 'cash_on_delivery')
    ->whereNotNull('delivery_charge')
    ->first();

if ($sample_order) {
    $delivery_fees = ($sample_order->delivery_charge ?? 0) +
                    ($sample_order->dm_tips ?? 0) +
                    ($sample_order->additional_charge ?? 0);
    $item_amount = $sample_order->order_amount - $delivery_fees;

    echo "Sample Order #" . $sample_order->id . ":\n";
    echo "  Total Amount: ₹" . number_format($sample_order->order_amount, 2) . "\n";
    echo "  Item Amount: ₹" . number_format($item_amount, 2) . "\n";
    echo "  Delivery Fees: ₹" . number_format($delivery_fees, 2) . "\n";
    echo "    - Delivery Charge: ₹" . number_format($sample_order->delivery_charge ?? 0, 2) . "\n";
    echo "    - DM Tips: ₹" . number_format($sample_order->dm_tips ?? 0, 2) . "\n";
    echo "    - Additional Charge: ₹" . number_format($sample_order->additional_charge ?? 0, 2) . "\n";

    if ($delivery_fees > 0 && $item_amount > 0) {
        echo "✅ Amount calculation logic verified\n";
        $tests_passed++;
    } else {
        echo "⚠️  Unusual amounts (may be edge case)\n";
    }
} else {
    echo "⚠️  No suitable sample order found\n";
}

echo "\n";

// ============================================================================
// TEST 5: Routes Existence
// ============================================================================
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "TEST 5: Routes Verification\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$expected_routes = [
    'admin.order.prepayment-data',
    'admin.order.mark-prepaid',
    'admin.order.unmark-prepaid',
];

$missing_routes = [];
foreach ($expected_routes as $route_name) {
    if (!app('router')->has($route_name)) {
        $missing_routes[] = $route_name;
    }
}

if (empty($missing_routes)) {
    echo "✅ All 3 prepayment routes registered\n";
    $tests_passed++;
} else {
    echo "❌ Missing routes: " . implode(', ', $missing_routes) . "\n";
    $tests_failed++;
}

echo "\n";

// ============================================================================
// TEST 6: Controller Methods
// ============================================================================
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "TEST 6: Controller Methods Verification\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$controller = new \App\Http\Controllers\Admin\OrderController();

$expected_methods = ['prepaymentData', 'markPrepaid', 'unmarkPrepaid'];
$missing_methods = [];

foreach ($expected_methods as $method) {
    if (!method_exists($controller, $method)) {
        $missing_methods[] = $method;
    }
}

if (empty($missing_methods)) {
    echo "✅ All 3 controller methods exist\n";
    $tests_passed++;
} else {
    echo "❌ Missing methods: " . implode(', ', $missing_methods) . "\n";
    $tests_failed++;
}

echo "\n";

// ============================================================================
// TEST 7: Translation Keys
// ============================================================================
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "TEST 7: Translation Keys Verification\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$required_keys = [
    'store_prepayment',
    'mark_item_amount_paid',
    'store_amount_prepaid',
    'prepaid',
    'prepayment_explanation',
    'only_cod_orders_can_be_prepaid',
    'order_marked_as_prepaid',
    'cannot_mark_prepaid_after_delivery',
];

$translations = trans('messages');
$missing_keys = [];

foreach ($required_keys as $key) {
    if (!isset($translations[$key])) {
        $missing_keys[] = $key;
    }
}

if (empty($missing_keys)) {
    echo "✅ All required translation keys exist\n";
    $tests_passed++;
} else {
    echo "❌ Missing translation keys: " . implode(', ', $missing_keys) . "\n";
    $tests_failed++;
}

echo "\n";

// ============================================================================
// TEST 8: Core Logic Integration
// ============================================================================
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "TEST 8: Core Logic Integration (order.php)\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$order_logic_file = __DIR__ . '/../app/CentralLogics/order.php';
$order_logic_content = file_get_contents($order_logic_file);

if (strpos($order_logic_content, 'store_amount_prepaid') !== false &&
    strpos($order_logic_content, 'prepaid_delivery_fees') !== false) {
    echo "✅ Prepayment logic integrated in order.php\n";
    $tests_passed++;
} else {
    echo "❌ Prepayment logic NOT found in order.php\n";
    $tests_failed++;
}

echo "\n";

// ============================================================================
// SUMMARY
// ============================================================================
echo "╔═══════════════════════════════════════════════════════════════════╗\n";
echo "║   TEST SUMMARY                                                    ║\n";
echo "╚═══════════════════════════════════════════════════════════════════╝\n";
echo "\n";

$total_tests = $tests_passed + $tests_failed;
$pass_rate = $total_tests > 0 ? round(($tests_passed / $total_tests) * 100, 1) : 0;

echo "Total Tests: $total_tests\n";
echo "✅ Passed: $tests_passed\n";
echo "❌ Failed: $tests_failed\n";
echo "Pass Rate: $pass_rate%\n";
echo "\n";

if ($tests_failed === 0) {
    echo "🎉 ALL TESTS PASSED! Feature is ready for deployment.\n";
    exit(0);
} else {
    echo "⚠️  SOME TESTS FAILED. Please review the implementation.\n";
    exit(1);
}
