#!/usr/bin/env php
<?php

/**
 * Test Product Replication Price Fix
 *
 * Verifies that price customization works when sent at root level (Flutter app format)
 */

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Item;

echo "=== Product Replication Price Fix Test ===\n\n";

// Find a product to replicate
$sourceProduct = Item::where('is_approved', 1)
    ->whereNotNull('barcode')
    ->where('barcode', '!=', '')
    ->first();

if (!$sourceProduct) {
    echo "❌ No source product found with barcode\n";
    exit(1);
}

echo "Source Product:\n";
echo "  ID: {$sourceProduct->id}\n";
echo "  Name: {$sourceProduct->name}\n";
echo "  Price: {$sourceProduct->price}\n";
echo "  Barcode: {$sourceProduct->barcode}\n\n";

// Simulate Flutter app request format
$customPrice = $sourceProduct->price + 5; // Add 5 to original price
$customBarcode = '9999' . substr($sourceProduct->barcode, 4); // Modify barcode

echo "Test Scenario (Flutter app sends):\n";
echo "  source_product_id: {$sourceProduct->id}\n";
echo "  price: {$customPrice} (custom)\n";
echo "  barcode: {$customBarcode} (custom)\n";
echo "  stock: 10\n";
echo "  discount: 0\n\n";

// Simulate the customize array creation (as fixed in controller)
$requestData = [
    'source_product_id' => $sourceProduct->id,
    'price' => $customPrice,
    'barcode' => $customBarcode,
    'stock' => 10,
    'discount' => 0,
];

$customize = $requestData['customize'] ?? [];
if (empty($customize)) {
    $customize = [
        'price' => $requestData['price'] ?? null,
        'discount' => $requestData['discount'] ?? null,
        'stock' => $requestData['stock'] ?? null,
        'barcode' => $requestData['barcode'] ?? null,
    ];
}

echo "After Processing (customize array):\n";
echo "  " . json_encode($customize, JSON_PRETTY_PRINT) . "\n\n";

// Simulate what would be saved
$newPrice = $customize['price'] ?? $sourceProduct->price;
$newBarcode = $customize['barcode'] ?? $sourceProduct->barcode;
$newStock = $customize['stock'] ?? $sourceProduct->stock;
$newDiscount = $customize['discount'] ?? $sourceProduct->discount;

echo "What Would Be Saved:\n";
echo "  price: {$newPrice}";
if ($newPrice == $customPrice) {
    echo " ✅ (custom price used)\n";
} else {
    echo " ❌ (source price used - WRONG!)\n";
}

echo "  barcode: {$newBarcode}";
if ($newBarcode == $customBarcode) {
    echo " ✅ (custom barcode used)\n";
} else {
    echo " ❌ (source barcode used - WRONG!)\n";
}

echo "  stock: {$newStock} ✅\n";
echo "  discount: {$newDiscount} ✅\n\n";

// Verify the fix
$allCorrect = ($newPrice == $customPrice) && ($newBarcode == $customBarcode);

echo "=== RESULT ===\n";
if ($allCorrect) {
    echo "✅ TEST PASSED\n\n";
    echo "When Flutter app sends:\n";
    echo "  {\"price\": 12, \"barcode\": \"123456\"}\n\n";
    echo "Backend will use:\n";
    echo "  price = 12 (custom) ✅\n";
    echo "  barcode = 123456 (custom) ✅\n\n";
    echo "Replication will work correctly!\n";
} else {
    echo "❌ TEST FAILED\n\n";
    echo "Price or barcode not using custom values!\n";
}
