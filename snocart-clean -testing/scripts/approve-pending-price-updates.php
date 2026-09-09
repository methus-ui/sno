#!/usr/bin/env php
<?php

/**
 * Auto-approve pending price updates for Store 10
 */

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\TempProduct;
use App\Models\Item;
use Illuminate\Support\Facades\DB;

echo "=== Auto-Approve Pending Price Updates ===\n\n";

$storeId = 10; // Change this if needed

$pending = TempProduct::where('store_id', $storeId)
    ->where('is_rejected', 0)
    ->whereNotNull('item_id')
    ->get();

if ($pending->isEmpty()) {
    echo "No pending updates found.\n";
    exit(0);
}

echo "Found {$pending->count()} pending updates:\n\n";

$approved = 0;
$failed = 0;

foreach ($pending as $temp) {
    try {
        DB::beginTransaction();

        $item = Item::find($temp->item_id);

        if (!$item) {
            echo "❌ Temp ID {$temp->id}: Item {$temp->item_id} not found\n";
            $failed++;
            DB::rollBack();
            continue;
        }

        $oldPrice = $item->price;

        // Update the main item with new price
        $item->price = $temp->price;
        $item->discount = $temp->discount;
        $item->discount_type = $temp->discount_type;
        $item->save();

        // Delete the temp record
        $temp->delete();

        DB::commit();

        echo "✅ Approved: {$item->name}\n";
        echo "   Price: {$oldPrice} → {$item->price}\n\n";
        $approved++;

    } catch (\Exception $e) {
        DB::rollBack();
        echo "❌ Failed: {$temp->name} - {$e->getMessage()}\n";
        $failed++;
    }
}

echo "\n=== SUMMARY ===\n";
echo "✅ Approved: {$approved}\n";
echo "❌ Failed: {$failed}\n";
echo "\nAll pending price updates have been applied!\n";
