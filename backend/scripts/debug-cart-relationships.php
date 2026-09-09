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

echo "Order loaded: #{$order->id}\n\n";

$cart = collect([]);
foreach ($order->details as $details) {
    $details['status'] = true;
    $cart->push($details);
}

echo "Cart items: " . $cart->count() . "\n\n";

foreach ($cart as $idx => $c) {
    echo "=== Cart Item #{$idx} ===\n";
    echo "Type: " . get_class($c) . "\n";
    echo "Has 'item' relationship loaded: " . (isset($c->item) ? 'YES' : 'NO') . "\n";

    if (isset($c->item)) {
        echo "Item object type: " . get_class($c->item) . "\n";
        echo "Item name: " . ($c->item->name ?? 'NULL') . "\n";
        echo "Item ID: " . ($c->item->id ?? 'NULL') . "\n";
    } else {
        echo "Item is NULL\n";
    }

    echo "\nRelationships loaded:\n";
    echo "  - item: " . (isset($c->item) ? 'YES' : 'NO') . "\n";
    echo "  - campaign: " . (isset($c->campaign) ? 'YES' : 'NO') . "\n";

    echo "\nArray access test:\n";
    echo "  - c['item'] exists: " . (isset($c['item']) ? 'YES' : 'NO') . "\n";
    echo "  - c->item exists: " . (isset($c->item) ? 'YES' : 'NO') . "\n";

    echo "\nItem detail JSON (first 500 chars):\n";
    $itemDetails = $c->item_details ?? 'NULL';
    if (is_string($itemDetails)) {
        $decoded = json_decode($itemDetails, true);
        if ($decoded && isset($decoded['name'])) {
            echo "  Name from item_details: " . $decoded['name'] . "\n";
        }
    }
    echo "\n\n";
}
