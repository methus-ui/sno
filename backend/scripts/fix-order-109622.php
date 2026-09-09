<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Illuminate\Support\Facades\DB;

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Fixing Order #109622 ===\n\n";

// Get order details
$order = DB::table('orders')->where('id', 109622)->first();
$orderDetails = DB::table('order_details')->where('order_id', 109622)->get();

echo "Current Order Data:\n";
echo "- Order Amount: " . $order->order_amount . "\n";
echo "- Delivery Charge: " . $order->delivery_charge . "\n\n";

// Calculate correct item amount
$itemAmount = 0;
echo "Order Details:\n";
foreach ($orderDetails as $detail) {
    $lineTotal = $detail->price * $detail->quantity;
    $itemAmount += $lineTotal;
    echo "- Item {$detail->item_id}: ₹{$detail->price} × {$detail->quantity} = ₹{$lineTotal}\n";
}

echo "\n";
echo "Calculated Item Amount: ₹{$itemAmount}\n";

// Calculate correct order amount
$correctOrderAmount = $itemAmount; // Order amount is just item amount (delivery is separate)
echo "Correct Order Amount Should Be: ₹{$correctOrderAmount}\n";
echo "Current Order Amount: ₹{$order->order_amount}\n\n";

if ($correctOrderAmount != $order->order_amount) {
    echo "⚠️  ORDER AMOUNT MISMATCH DETECTED!\n";
    echo "Difference: ₹" . ($correctOrderAmount - $order->order_amount) . "\n\n";

    // Fix the order
    echo "Fixing order_amount...\n";
    DB::table('orders')
        ->where('id', 109622)
        ->update([
            'order_amount' => $correctOrderAmount,
            'updated_at' => now()
        ]);

    echo "✅ Order amount updated: ₹{$order->order_amount} → ₹{$correctOrderAmount}\n\n";

    // Also update order_transaction if exists
    $transaction = DB::table('order_transactions')->where('order_id', 109622)->first();
    if ($transaction) {
        echo "Updating order_transaction...\n";

        // Recalculate admin commission (18% of item amount)
        $newAdminCommission = round($correctOrderAmount * 0.18, 2);
        $newStoreAmount = $correctOrderAmount - $newAdminCommission;

        DB::table('order_transactions')
            ->where('order_id', 109622)
            ->update([
                'order_amount' => $correctOrderAmount,
                'store_amount' => $newStoreAmount,
                'admin_commission' => $newAdminCommission,
                'updated_at' => now()
            ]);

        echo "✅ Order transaction updated\n";
        echo "- Order Amount: {$transaction->order_amount} → {$correctOrderAmount}\n";
        echo "- Store Amount: {$transaction->store_amount} → {$newStoreAmount}\n";
        echo "- Admin Commission: {$transaction->admin_commission} → {$newAdminCommission}\n";
    }

    echo "\n✅ Order #109622 fixed successfully!\n";
} else {
    echo "✅ Order amount is correct!\n";
}

echo "\n=== Verification ===\n";
$fixedOrder = DB::table('orders')->where('id', 109622)->first();
echo "Final Order Amount: ₹{$fixedOrder->order_amount}\n";
echo "Item Sum: ₹{$itemAmount}\n";
echo "Match: " . ($fixedOrder->order_amount == $itemAmount ? "✅ YES" : "❌ NO") . "\n";
