<?php

/**
 * Test Cart Add Functionality
 * Tests if items can be added to cart after the fix
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Item;
use App\Models\Cart;
use App\CentralLogics\Helpers;

echo "=== CART ADD TEST ===\n\n";

// Find a test item
$item = Item::where('status', 1)
    ->whereNotNull('store_id')
    ->with('store', 'module')
    ->first();

if (!$item) {
    echo "❌ No active items found\n";
    exit(1);
}

echo "✓ Test item: {$item->name} (ID: {$item->id})\n";
echo "✓ Store: {$item->store->name}\n";
echo "✓ Module: {$item->module->module_name}\n";
echo "✓ Category IDs: {$item->category_ids}\n\n";

// Test the formatting function directly
echo "--- Testing cart_product_data_formatting ---\n";
try {
    $formatted = Helpers::cart_product_data_formatting(
        $item,
        [],  // variation
        [],  // add_on_ids
        [],  // add_on_qtys
        false,
        'en'
    );

    if ($formatted) {
        echo "✅ Formatting successful!\n";
        echo "   - Name: {$formatted['name']}\n";
        echo "   - Store: {$formatted['store_name']}\n";
        echo "   - Categories: " . count($formatted['category_ids']) . " found\n";
    } else {
        echo "❌ Formatting returned null\n";
    }
} catch (\Exception $e) {
    echo "❌ Formatting failed: {$e->getMessage()}\n";
    echo "   File: {$e->getFile()}:{$e->getLine()}\n";
    exit(1);
}

// Test creating a cart entry
echo "\n--- Testing cart creation ---\n";
$testUserId = 99999999;  // Test user ID
$testModuleId = $item->module_id;

// Clean up any existing test carts
Cart::where('user_id', $testUserId)->delete();

try {
    $cart = new Cart();
    $cart->user_id = $testUserId;
    $cart->module_id = $testModuleId;
    $cart->item_id = $item->id;
    $cart->is_guest = 1;
    $cart->add_on_ids = json_encode([]);
    $cart->add_on_qtys = json_encode([]);
    $cart->item_type = 'Item';
    $cart->price = $item->price;
    $cart->quantity = 1;
    $cart->variation = json_encode([]);
    $cart->save();

    echo "✅ Cart entry created (ID: {$cart->id})\n";

    // Test retrieving it
    $carts = Cart::where('user_id', $testUserId)
        ->where('is_guest', 1)
        ->where('module_id', $testModuleId)
        ->get()
        ->map(function ($data) {
            if (!$data->item) {
                return null;
            }

            $data->add_on_ids = json_decode($data->add_on_ids, true);
            $data->add_on_qtys = json_decode($data->add_on_qtys, true);
            $data->variation = json_decode($data->variation, true);

            $data->item = Helpers::cart_product_data_formatting(
                $data->item,
                $data->variation,
                $data->add_on_ids,
                $data->add_on_qtys,
                false,
                'en'
            );

            return $data;
        })
        ->filter()
        ->values();

    echo "✅ Cart retrieved: " . $carts->count() . " item(s)\n";

    if ($carts->count() > 0) {
        $cartItem = $carts->first();
        echo "   - Item name: {$cartItem->item['name']}\n";
        echo "   - Price: {$cartItem->price}\n";
        echo "   - Quantity: {$cartItem->quantity}\n";
    }

    // Clean up
    Cart::where('user_id', $testUserId)->delete();
    echo "\n✅ Test cart cleaned up\n";

} catch (\Exception $e) {
    echo "❌ Cart test failed: {$e->getMessage()}\n";
    echo "   File: {$e->getFile()}:{$e->getLine()}\n";
    Cart::where('user_id', $testUserId)->delete();
    exit(1);
}

echo "\n=== ALL TESTS PASSED ✅ ===\n";
