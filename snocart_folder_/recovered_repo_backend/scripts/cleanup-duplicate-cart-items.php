#!/usr/bin/env php
<?php

/**
 * Cleanup Script: Merge Duplicate Cart Items
 *
 * Problem: Multiple cart entries exist for the same user/item/variation combination
 * Solution: Keep the oldest cart item and merge quantities from duplicates
 *
 * Usage: php scripts/cleanup-duplicate-cart-items.php
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Cart;
use Illuminate\Support\Facades\DB;

echo "🔍 Finding duplicate cart items...\n\n";

// Find all duplicate groups (ignoring variations/addons for now)
$duplicates = Cart::select('user_id', 'item_id', 'item_type', 'is_guest', 'module_id', DB::raw('COUNT(*) as count'))
    ->groupBy('user_id', 'item_id', 'item_type', 'is_guest', 'module_id')
    ->having('count', '>', 1)
    ->get();

echo "Found " . $duplicates->count() . " duplicate groups\n\n";

if ($duplicates->isEmpty()) {
    echo "✅ No duplicates found! Cart is clean.\n";
    exit(0);
}

$totalMerged = 0;
$totalDeleted = 0;

DB::beginTransaction();

try {
    foreach ($duplicates as $duplicate) {
        echo "Processing user_id: {$duplicate->user_id}, item_id: {$duplicate->item_id}\n";

        // Get all cart items for this user/item combination
        $cartItems = Cart::where('user_id', $duplicate->user_id)
            ->where('item_id', $duplicate->item_id)
            ->where('item_type', $duplicate->item_type)
            ->where('is_guest', $duplicate->is_guest)
            ->where('module_id', $duplicate->module_id)
            ->orderBy('created_at', 'asc') // Keep the oldest one
            ->get();

        if ($cartItems->count() <= 1) {
            continue;
        }

        // Group by normalized variation and add_on_ids
        $grouped = $cartItems->groupBy(function($item) {
            $variation = json_decode($item->variation, true) ?: [];
            $addons = json_decode($item->add_on_ids, true) ?: [];
            return md5(json_encode($variation) . json_encode($addons));
        });

        // Process each variation group
        foreach ($grouped as $hash => $group) {
            if ($group->count() <= 1) {
                continue;
            }

            // Keep the first (oldest) cart item
            $primaryCart = $group->first();
            $totalQuantity = $group->sum('quantity');

            echo "  - Found {$group->count()} duplicates with total quantity: $totalQuantity\n";
            echo "  - Variation: " . $primaryCart->variation . "\n";
            echo "  - Keeping cart ID: {$primaryCart->id} (created: {$primaryCart->created_at})\n";

            // Update the primary cart with the total quantity
            $primaryCart->quantity = $totalQuantity;
            $primaryCart->save();

            // Delete all duplicates
            $duplicatesToDelete = $group->slice(1); // Skip the first one
            foreach ($duplicatesToDelete as $cartToDelete) {
                echo "  - Deleting cart ID: {$cartToDelete->id} (quantity: {$cartToDelete->quantity})\n";
                $cartToDelete->delete();
                $totalDeleted++;
            }

            $totalMerged++;
            echo "  ✅ Merged successfully\n\n";
        }
    }

    DB::commit();

    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "✅ CLEANUP COMPLETE\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "   Groups merged: $totalMerged\n";
    echo "   Items deleted: $totalDeleted\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

} catch (\Exception $e) {
    DB::rollBack();
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "   Transaction rolled back. No changes made.\n";
    exit(1);
}
