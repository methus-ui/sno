#!/usr/bin/env php
<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Order;
use App\Scopes\StoreScope;

$orderId = 100015;

$order = Order::with(['details', 'store' => function ($query) {
    return $query->withCount('orders');
}, 'customer' => function ($query) {
    return $query->withCount('orders');
}, 'details.item' => function ($query) {
    return $query->withoutGlobalScope(StoreScope::class);
}, 'details.campaign' => function ($query) {
    return $query->withoutGlobalScope(StoreScope::class);
}])->where(['id' => $orderId])->StoreOrder()->first();

echo "=== Testing Relationship Loading ===\n\n";

echo "1. Check relationships on order->details BEFORE loop:\n";
$firstDetail = $order->details->first();
echo "   - First detail ID: {$firstDetail->id}\n";
echo "   - Has item relationship: " . (isset($firstDetail->item) ? 'YES' : 'NO') . "\n";
if (isset($firstDetail->item)) {
    echo "   - Item name: {$firstDetail->item->name}\n";
}
echo "\n";

echo "2. Check relationships DURING loop (before modifying):\n";
foreach ($order->details as $idx => $details) {
    echo "   Detail #{$idx}:\n";
    echo "     - Has item: " . (isset($details->item) ? 'YES' : 'NO') . "\n";
    if (isset($details->item)) {
        echo "     - Item name: {$details->item->name}\n";
    }
    if ($idx == 0) {
        echo "\n3. Check AFTER setting status attribute:\n";
        $details['status'] = true;
        echo "     - Has item (after status=true): " . (isset($details->item) ? 'YES' : 'NO') . "\n";
        if (isset($details->item)) {
            echo "     - Item name: {$details->item->name}\n";
        }
    }
    echo "\n";
}

echo "\n4. Check relationships when pushing to collection:\n";
$cart = collect([]);
foreach ($order->details as $details) {
    echo "   BEFORE push - Has item: " . (isset($details->item) ? 'YES' : 'NO') . "\n";
    $details['status'] = true;
    $cart->push($details);
    echo "   AFTER push to cart - Cart count: {$cart->count()}\n";
}

echo "\n5. Check relationships AFTER being in collection:\n";
foreach ($cart as $idx => $c) {
    echo "   Cart item #{$idx}:\n";
    echo "     - Has item: " . (isset($c->item) ? 'YES' : 'NO') . "\n";
    if (isset($c->item)) {
        echo "     - Item name: {$c->item->name}\n";
    }
    echo "\n";
}
