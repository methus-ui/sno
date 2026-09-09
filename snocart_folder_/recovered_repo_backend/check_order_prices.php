<?php
// Quick database check script
require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

if ($argc < 2) {
    echo "Usage: php check_order_prices.php <order_id>\n";
    exit(1);
}

$orderId = $argv[1];

$order = \App\Models\Order::with('details.item')->find($orderId);

if (!$order) {
    echo "Order #{$orderId} not found\n";
    exit(1);
}

echo "Order #{$orderId} - Total: {$order->order_amount}\n";
echo str_repeat('=', 80) . "\n";

foreach ($order->details as $detail) {
    $itemName = $detail->item ? $detail->item->name : 'N/A';
    $itemMasterPrice = $detail->item ? $detail->item->price : 'N/A';
    
    echo sprintf(
        "Detail ID: %d | Item: %s\n  Order Detail Price: %.2f\n  Master Item Price: %s\n  Quantity: %d\n",
        $detail->id,
        $itemName,
        $detail->price,
        $itemMasterPrice,
        $detail->quantity
    );
    echo str_repeat('-', 80) . "\n";
}

echo "\nTo test: Update MRP in V2, then run this script again to verify DB changes.\n";
