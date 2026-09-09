#!/usr/bin/env php
<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\TempProduct;
use App\Models\Item;
use App\Scopes\StoreScope;
use App\CentralLogics\Helpers;

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║          Bulk Approve New Item Requests                       ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

// Get all pending temp products
$tempProducts = TempProduct::withoutGlobalScope(StoreScope::class)
    ->where('is_rejected', 0)
    ->orWhereNull('is_rejected')
    ->orderBy('created_at', 'desc')
    ->get();

$total = $tempProducts->count();

if ($total === 0) {
    echo "✅ No pending item approval requests!\n";
    exit(0);
}

echo "Found {$total} pending new item requests\n";
echo "Starting bulk approval...\n\n";

$approved = 0;
$failed = 0;
$errors = [];

foreach ($tempProducts as $data) {
    try {
        DB::beginTransaction();

        // Find the corresponding item
        $item = Item::withoutGlobalScope(StoreScope::class)
            ->withoutGlobalScope('translate')
            ->with('translations')
            ->findOrFail($data->item_id);

        // Update item with temp product data
        $item->name = $data->name;
        $item->description = $data->description;

        // Handle images
        if ($item->image) {
            Helpers::check_and_delete('product/', $item->image);
        }

        if ($item->images) {
            foreach ($item->images as $value) {
                $value = is_array($value) ? $value : ['img' => $value, 'storage' => 'public'];
                Helpers::check_and_delete('product/', $value['img']);
            }
        }

        $item->image = $data->image;
        $item->images = $data->images;
        $item->store_id = $data->store_id;
        $item->module_id = $data->module_id;
        $item->unit_id = $data->unit_id;
        $item->category_id = $data->category_id;
        $item->category_ids = $data->category_ids;
        $item->choice_options = $data->choice_options;
        $item->food_variations = $data->food_variations;
        $item->variations = $data->variations;
        $item->add_ons = $data->add_ons;
        $item->attributes = $data->attributes;
        $item->price = $data->price;
        $item->discount = $data->discount;
        $item->discount_type = $data->discount_type;
        $item->available_time_starts = $data->available_time_starts;
        $item->available_time_ends = $data->available_time_ends;
        $item->veg = $data->veg;
        $item->recommended = $data->recommended;
        $item->stock = $data->stock;
        $item->maximum_cart_quantity = $data->maximum_cart_quantity;
        $item->organic = $data->organic ?? 0;
        $item->barcode = $data->barcode;

        // Pharmacy specific fields
        if ($data->pharmacy_item_details) {
            $item->pharmacy_item_details()->delete();
            $item->pharmacy_item_details()->create(json_decode($data->pharmacy_item_details, true));
        }

        // Ecommerce specific fields
        if ($data->ecommerce_item_details) {
            $item->ecommerce_item_details()->delete();
            $item->ecommerce_item_details()->create(json_decode($data->ecommerce_item_details, true));
        }

        $item->save();

        // Delete the temp product
        $data->delete();

        DB::commit();

        $approved++;
        echo sprintf("  ✓ [%d/%d] Approved: %s (ID: %d)\n",
            $approved + $failed,
            $total,
            substr($item->name, 0, 50),
            $data->id
        );

    } catch (\Exception $e) {
        DB::rollBack();
        $failed++;
        $errors[] = [
            'id' => $data->id,
            'name' => $data->name ?? 'Unknown',
            'error' => $e->getMessage()
        ];

        echo sprintf("  ✗ [%d/%d] FAILED: %s (ID: %d) - %s\n",
            $approved + $failed,
            $total,
            substr($data->name ?? 'Unknown', 0, 40),
            $data->id,
            substr($e->getMessage(), 0, 50)
        );
    }
}

echo "\n═══════════════════════════════════════════════════════════════\n";
echo "SUMMARY:\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "  ✅ Approved: {$approved}\n";
echo "  ❌ Failed: {$failed}\n";
echo "  📊 Total: {$total}\n";

if (!empty($errors)) {
    echo "\n⚠️  ERRORS:\n";
    foreach ($errors as $error) {
        echo sprintf("  - ID %d (%s): %s\n",
            $error['id'],
            substr($error['name'], 0, 30),
            $error['error']
        );
    }
}

echo "\n✅ Bulk approval complete!\n";
