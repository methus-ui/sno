#!/usr/bin/env php
<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Order;
use App\Scopes\StoreScope;

$orderId = 100015;

echo "Testing Fixed Edit V2 Cart Loading for Order #{$orderId}\n";
echo str_repeat("=", 60) . "\n\n";

$order = Order::with(['details', 'store' => function ($query) {
    return $query->withCount('orders');
}, 'customer' => function ($query) {
    return $query->withCount('orders');
}, 'details.item' => function ($query) {
    return $query->withoutGlobalScope(StoreScope::class);
}, 'details.campaign' => function ($query) {
    return $query->withoutGlobalScope(StoreScope::class);
}])->where(['id' => $orderId])->StoreOrder()->first();

if (!$order) {
    echo "❌ Order not found!\n";
    exit(1);
}

// Simulate the cart loading logic from editV2 method
$cart = collect([]);
foreach ($order->details as $details) {
    $details['status'] = true;
    $cart->push($details);
}

// Simulate the FIXED initialCart building logic
$initialCart = [];
foreach ($cart->values() as $i => $c) {
    $item     = $c->item ?? null;
    $campaign = $c->campaign ?? null;
    $productObj = $item ?? $campaign;

    // Parse item_details JSON for fallback data (when item is deleted)
    $itemDetails = null;
    if ($c->item_details) {
        $itemDetails = is_string($c->item_details) ? json_decode($c->item_details, true) : $c->item_details;
    }

    $hasVariations = false;
    if ($productObj) {
        $fv = json_decode($productObj->food_variations ?? '[]', true);
        $co = json_decode($productObj->choice_options  ?? '[]', true);
        $hasVariations = !empty($fv) || !empty($co);
    }
    $initialCart[] = [
        'cartKey'                  => $i,
        'order_detail_id'          => $c->id ?? null,
        'item_id'                  => $c->item_id ?? null,
        'campaign_item_id'         => $c->item_campaign_id ?? null,
        'item_type'                => ($c->item_campaign_id ?? null) ? 'campaign' : 'item',
        'name'                     => $productObj ? $productObj->name : ($itemDetails['name'] ?? 'Unknown'),
        'image'                    => $productObj ? ($productObj->image_full_url ?? null) : ($itemDetails['image_full_url'] ?? null),
        'price'                    => $c->price ?? 0,
        'quantity'                 => $c->quantity ?? 1,
        'discount'                 => $c->discount_on_item ?? 0,
        'tax'                      => $c->tax_amount ?? 0,
        'addon_price'              => $c->total_add_on_price ?? 0,
        'status'                   => $c->status ?? true,
        'has_variations'           => $hasVariations,
        // Status fields
        'is_unavailable'           => (bool)($c->is_unavailable ?? false),
        'is_picked_up'             => (bool)($c->is_picked_up ?? false),
        // Outside purchase
        'is_outside_purchase'      => (bool)($c->is_outside_purchase ?? false),
        'outside_purchase_cost'    => (float)($c->outside_purchase_cost ?? 0),
        'outside_purchase_status'  => $c->outside_purchase_status ?? null,
        // MRP request
        'requested_mrp'            => $c->requested_mrp ? (float)$c->requested_mrp : null,
        'mrp_update_status'        => $c->mrp_update_status ?? null,
    ];
}

echo "✅ RESULTS:\n";
echo str_repeat("-", 60) . "\n";
foreach ($initialCart as $idx => $item) {
    $status = $item['name'] !== 'Unknown' ? '✅' : '❌';
    echo "{$status} Item #{$idx}: {$item['name']}\n";
    echo "    - Price: \${$item['price']}, Qty: {$item['quantity']}\n";
    echo "    - Image: " . ($item['image'] ? 'YES' : 'NO') . "\n";
    echo "\n";
}

echo str_repeat("=", 60) . "\n";
if (count(array_filter($initialCart, fn($i) => $i['name'] !== 'Unknown')) === count($initialCart)) {
    echo "✅ SUCCESS: All items loaded with names!\n";
} else {
    echo "⚠️  PARTIAL: Some items still showing as 'Unknown'\n";
}
