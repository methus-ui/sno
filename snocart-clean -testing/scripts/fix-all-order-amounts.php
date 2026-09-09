<?php

/**
 * Fix Order Amount for All Affected Orders
 *
 * This script recalculates order_amount for all orders where it was incorrectly calculated
 * order_amount should be: sum of (item price × quantity) + tax
 * NOT including delivery_charge (which is a separate field)
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Illuminate\Support\Facades\DB;

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Fix Order Amounts - Batch Update ===\n\n";

$dateFilter = isset($argv[1]) ? $argv[1] : '2026-03-12';
echo "Processing orders from: {$dateFilter}\n\n";

// Get all orders from the specified date
$orders = DB::table('orders')
    ->whereDate('created_at', '>=', $dateFilter)
    ->orderBy('id', 'asc')
    ->get();

echo "Total orders to process: " . $orders->count() . "\n\n";

$fixed = 0;
$skipped = 0;
$errors = 0;

foreach ($orders as $order) {
    try {
        // Calculate correct item amount from order_details
        $orderDetails = DB::table('order_details')
            ->where('order_id', $order->id)
            ->get();

        $itemAmount = 0;
        foreach ($orderDetails as $detail) {
            $itemAmount += ($detail->price * $detail->quantity);
        }

        // Correct order_amount should be: item_amount + tax (NOT including delivery)
        $correctOrderAmount = $itemAmount + $order->total_tax_amount;

        // Check if fix is needed
        if (abs($correctOrderAmount - $order->order_amount) > 0.01) {
            $oldAmount = $order->order_amount;

            // Update order
            DB::table('orders')
                ->where('id', $order->id)
                ->update([
                    'order_amount' => round($correctOrderAmount, 2),
                    'updated_at' => now()
                ]);

            // Update order_transaction if exists
            $transaction = DB::table('order_transactions')
                ->where('order_id', $order->id)
                ->first();

            if ($transaction) {
                // Recalculate commission (18% of order_amount)
                $newAdminCommission = round($correctOrderAmount * 0.18, 2);
                $newStoreAmount = $correctOrderAmount - $newAdminCommission;

                DB::table('order_transactions')
                    ->where('order_id', $order->id)
                    ->update([
                        'order_amount' => round($correctOrderAmount, 2),
                        'store_amount' => round($newStoreAmount, 2),
                        'admin_commission' => round($newAdminCommission, 2),
                        'updated_at' => now()
                    ]);
            }

            $fixed++;
            echo "✅ Order #{$order->id}: ₹{$oldAmount} → ₹{$correctOrderAmount} (Δ " . round($correctOrderAmount - $oldAmount, 2) . ")\n";
        } else {
            $skipped++;
        }

    } catch (\Exception $e) {
        $errors++;
        echo "❌ Error fixing order #{$order->id}: " . $e->getMessage() . "\n";
    }
}

echo "\n";
echo "=== Summary ===\n";
echo "Total Orders: " . $orders->count() . "\n";
echo "Fixed: {$fixed}\n";
echo "Skipped (already correct): {$skipped}\n";
echo "Errors: {$errors}\n\n";

if ($fixed > 0) {
    echo "✅ Successfully fixed {$fixed} orders!\n";
    echo "\nVerify by visiting: https://new.snocart.com/admin/users/delivery-man/preview/11/day_close\n";
} else {
    echo "✅ All orders already have correct amounts!\n";
}
