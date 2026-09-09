#!/usr/bin/env php
<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "🔄 Reverting MRP approvals...\n\n";

// Find recently approved MRP requests (approved in last hour)
$recentlyApproved = DB::table('order_details')
    ->where('mrp_update_status', 'approved')
    ->where('updated_at', '>', now()->subHour())
    ->whereNotNull('requested_mrp')
    ->get();

if ($recentlyApproved->isEmpty()) {
    echo "❌ No recently approved MRP requests found to revert.\n";
    exit(0);
}

echo "Found {$recentlyApproved->count()} recently approved MRP requests\n";
echo "Reverting status back to 'pending'...\n\n";

$reverted = 0;

foreach ($recentlyApproved as $detail) {
    // Get original price from items or item_campaigns table
    $originalPrice = null;

    if ($detail->item_id) {
        $item = DB::table('items')->find($detail->item_id);
        if ($item) {
            $originalPrice = $item->price;
        }
    } elseif ($detail->item_campaign_id) {
        $campaign = DB::table('item_campaigns')->find($detail->item_campaign_id);
        if ($campaign) {
            $originalPrice = $campaign->price;
        }
    }

    // Revert to pending and restore original price if found
    $updateData = [
        'mrp_update_status' => 'pending',
        'updated_at' => now()
    ];

    // Only restore price if we found an original price and it's different from current
    if ($originalPrice !== null && $originalPrice != $detail->price) {
        $updateData['price'] = $originalPrice;
        echo "  ✓ ID {$detail->id}: Status → pending, Price {$detail->price} → {$originalPrice}\n";
    } else {
        echo "  ✓ ID {$detail->id}: Status → pending\n";
    }

    DB::table('order_details')
        ->where('id', $detail->id)
        ->update($updateData);

    $reverted++;
}

echo "\n✅ Successfully reverted {$reverted} MRP approvals back to pending!\n";
