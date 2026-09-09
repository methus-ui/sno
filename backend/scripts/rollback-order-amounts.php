<?php

/**
 * ROLLBACK: Restore original order amounts
 * order_amount SHOULD include delivery_charge + additional charges
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Illuminate\Support\Facades\DB;

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== ROLLBACK: Restoring Order Amounts ===\n\n";

$dateFilter = '2026-03-12';
echo "Processing orders from: {$dateFilter}\n\n";

$orders = DB::table('orders')
    ->whereDate('created_at', '>=', $dateFilter)
    ->orderBy('id', 'asc')
    ->get();

echo "Total orders to process: " . $orders->count() . "\n\n";

$fixed = 0;

foreach ($orders as $order) {
    try {
        // Calculate item amount
        $orderDetails = DB::table('order_details')
            ->where('order_id', $order->id)
            ->get();

        $itemAmount = 0;
        foreach ($orderDetails as $detail) {
            $itemAmount += ($detail->price * $detail->quantity);
        }

        // CORRECT formula: order_amount = item_amount + tax + delivery_charge + additional charges + tips
        $correctOrderAmount = $itemAmount
            + $order->total_tax_amount
            + $order->delivery_charge
            + ($order->additional_charge ?? 0)
            + ($order->extra_packaging_amount ?? 0)
            + ($order->dm_tips ?? 0);

        if (abs($correctOrderAmount - $order->order_amount) > 0.01) {
            $oldAmount = $order->order_amount;

            DB::table('orders')
                ->where('id', $order->id)
                ->update([
                    'order_amount' => round($correctOrderAmount, 2),
                    'updated_at' => now()
                ]);

            // Update transaction
            $transaction = DB::table('order_transactions')
                ->where('order_id', $order->id)
                ->first();

            if ($transaction) {
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
            echo "✅ Order #{$order->id}: ₹{$oldAmount} → ₹{$correctOrderAmount}\n";
        }

    } catch (\Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "\n";
    }
}

echo "\n✅ Rollback complete! Fixed {$fixed} orders.\n";
