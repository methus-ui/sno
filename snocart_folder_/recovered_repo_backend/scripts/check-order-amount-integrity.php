<?php

/**
 * Check Order Amount Integrity
 *
 * This script checks all orders to find mismatches between:
 * - order_amount in orders table
 * - Sum of (price × quantity) from order_details table
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Illuminate\Support\Facades\DB;

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Order Amount Integrity Check ===\n\n";

// Get all orders from today or recent orders
$dateFilter = isset($argv[1]) ? $argv[1] : date('Y-m-d');
echo "Checking orders from: {$dateFilter}\n\n";

$orders = DB::table('orders')
    ->whereDate('created_at', '>=', $dateFilter)
    ->orderBy('id', 'desc')
    ->limit(100)
    ->get();

echo "Total orders to check: " . $orders->count() . "\n\n";

$mismatchedOrders = [];
$totalMismatch = 0;

foreach ($orders as $order) {
    // Get order details and calculate item amount
    $orderDetails = DB::table('order_details')
        ->where('order_id', $order->id)
        ->get();

    $calculatedItemAmount = 0;
    foreach ($orderDetails as $detail) {
        $calculatedItemAmount += ($detail->price * $detail->quantity);
    }

    // Order amount should equal item amount
    // (delivery_charge is separate, not included in order_amount)
    if (abs($calculatedItemAmount - $order->order_amount) > 0.01) {
        $mismatchedOrders[] = [
            'id' => $order->id,
            'stored_amount' => $order->order_amount,
            'calculated_amount' => $calculatedItemAmount,
            'difference' => $calculatedItemAmount - $order->order_amount,
            'delivery_charge' => $order->delivery_charge,
            'created_at' => $order->created_at
        ];
        $totalMismatch++;
    }
}

if ($totalMismatch > 0) {
    echo "⚠️  Found {$totalMismatch} orders with amount mismatches:\n\n";
    echo str_pad("Order ID", 10) . str_pad("Stored", 15) . str_pad("Calculated", 15) . str_pad("Difference", 15) . "Created At\n";
    echo str_repeat("-", 70) . "\n";

    foreach ($mismatchedOrders as $mismatch) {
        echo str_pad($mismatch['id'], 10);
        echo str_pad("₹" . number_format($mismatch['stored_amount'], 2), 15);
        echo str_pad("₹" . number_format($mismatch['calculated_amount'], 2), 15);
        echo str_pad("₹" . number_format($mismatch['difference'], 2), 15);
        echo $mismatch['created_at'] . "\n";
    }

    echo "\n";
    echo "To fix these orders, run:\n";
    echo "php scripts/fix-order-amount-mismatch.php\n\n";
} else {
    echo "✅ All orders have correct amounts!\n";
}

echo "\n=== Summary ===\n";
echo "Total Orders Checked: " . $orders->count() . "\n";
echo "Mismatched Orders: {$totalMismatch}\n";
echo "Correct Orders: " . ($orders->count() - $totalMismatch) . "\n";

// Check order_transactions integrity too
echo "\n=== Checking Order Transactions ===\n";
$transactionMismatches = 0;

foreach ($orders as $order) {
    $transaction = DB::table('order_transactions')
        ->where('order_id', $order->id)
        ->first();

    if ($transaction && abs($transaction->order_amount - $order->order_amount) > 0.01) {
        $transactionMismatches++;
        echo "⚠️  Order {$order->id}: Transaction amount (₹{$transaction->order_amount}) != Order amount (₹{$order->order_amount})\n";
    }
}

if ($transactionMismatches > 0) {
    echo "\n⚠️  Found {$transactionMismatches} order_transactions with mismatches\n";
} else {
    echo "✅ All order transactions match!\n";
}

echo "\nDone!\n";
