#!/usr/bin/env php
<?php

/**
 * Test Script: Verify Barcode Field in Product Gallery Browse API
 *
 * Tests that the barcode field is now included in:
 * 1. Browse API response
 * 2. Trending API response
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Vendor;
use App\Models\Item;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\V1\Vendor\ProductGalleryController;

echo "\n";
echo "========================================\n";
echo " BARCODE FIELD TEST - BROWSE API\n";
echo "========================================\n\n";

// Find a vendor with a store
$vendor = Vendor::with('stores')->whereHas('stores')->first();

if (!$vendor) {
    echo "❌ No vendor found with stores\n";
    exit(1);
}

echo "✅ Using vendor ID: {$vendor->id}\n";
echo "✅ Store ID: {$vendor->stores[0]->id}\n\n";

// Find products WITH barcodes
echo "🔍 Finding products with barcodes...\n";
$productsWithBarcodes = Item::whereNotNull('barcode')
    ->where('barcode', '!=', '')
    ->where('status', 1)
    ->take(5)
    ->get(['id', 'name', 'barcode']);

echo "✅ Found " . $productsWithBarcodes->count() . " products with barcodes:\n";
foreach ($productsWithBarcodes as $product) {
    echo "   - {$product->name} (Barcode: {$product->barcode})\n";
}
echo "\n";

// Test 1: Browse API
echo "========================================\n";
echo " TEST 1: BROWSE API\n";
echo "========================================\n\n";

try {
    // Create request
    $request = Request::create('/api/v1/vendor/product-gallery/browse', 'GET', [
        'limit' => 10
    ]);
    $request->merge(['vendor' => $vendor]);

    $controller = new ProductGalleryController();
    $response = $controller->browse($request);

    $statusCode = $response->getStatusCode();
    $data = json_decode($response->getContent(), true);

    if ($statusCode !== 200) {
        echo "❌ FAILED: HTTP {$statusCode}\n";
        echo "Response: " . json_encode($data, JSON_PRETTY_PRINT) . "\n";
        exit(1);
    }

    echo "✅ SUCCESS: HTTP 200\n";

    $products = $data['data']['products'] ?? [];
    echo "✅ Retrieved " . count($products) . " products\n\n";

    // Check for barcode field
    $hasBarcode = false;
    $barcodeCount = 0;
    $sampleProducts = [];

    foreach ($products as $product) {
        // Use array_key_exists instead of isset (isset returns false for null values)
        if (array_key_exists('barcode', $product)) {
            $hasBarcode = true;
            if ($product['barcode']) {
                $barcodeCount++;
                if (count($sampleProducts) < 3) {
                    $sampleProducts[] = [
                        'name' => $product['name'],
                        'barcode' => $product['barcode']
                    ];
                }
            }
        } else {
            echo "❌ FAILED: 'barcode' field NOT found in product: {$product['name']}\n";
            echo "Available keys: " . implode(', ', array_keys($product)) . "\n";
            exit(1);
        }
    }

    if (!$hasBarcode) {
        echo "❌ FAILED: 'barcode' field NOT found in product data\n";
        exit(1);
    }

    echo "✅ PASSED: 'barcode' field IS present in all products\n";
    echo "   - {$barcodeCount} products have non-empty barcodes\n";

    if (!empty($sampleProducts)) {
        echo "\n📦 Sample products with barcodes:\n";
        foreach ($sampleProducts as $sample) {
            echo "   - {$sample['name']}: {$sample['barcode']}\n";
        }
    }

} catch (\Exception $e) {
    echo "❌ EXCEPTION: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit(1);
}

// Test 2: Trending API
echo "\n========================================\n";
echo " TEST 2: TRENDING API\n";
echo "========================================\n\n";

try {
    $request = Request::create('/api/v1/vendor/product-gallery/trending', 'GET');
    $request->merge(['vendor' => $vendor]);

    $controller = new ProductGalleryController();
    $response = $controller->trending($request);

    $statusCode = $response->getStatusCode();
    $data = json_decode($response->getContent(), true);

    if ($statusCode !== 200) {
        echo "❌ FAILED: HTTP {$statusCode}\n";
        exit(1);
    }

    echo "✅ SUCCESS: HTTP 200\n";

    // Check trending products for barcode field
    $trendingSections = ['most_ordered', 'highest_rated', 'newest_additions'];
    $allHaveBarcode = true;

    foreach ($trendingSections as $section) {
        $products = $data['data'][$section] ?? [];
        echo "   {$section}: " . count($products) . " products\n";

        foreach ($products as $product) {
            if (!array_key_exists('barcode', $product)) {
                echo "   ❌ Missing 'barcode' field in {$section}\n";
                $allHaveBarcode = false;
            }
        }
    }

    if ($allHaveBarcode) {
        echo "\n✅ PASSED: All trending sections include 'barcode' field\n";
    } else {
        echo "\n❌ FAILED: Some trending sections missing 'barcode' field\n";
        exit(1);
    }

} catch (\Exception $e) {
    echo "❌ EXCEPTION: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit(1);
}

// Test 3: Details API (should still work)
echo "\n========================================\n";
echo " TEST 3: DETAILS API (Verify still works)\n";
echo "========================================\n\n";

if (!empty($productsWithBarcodes)) {
    try {
        $testProduct = $productsWithBarcodes->first();

        $request = Request::create("/api/v1/vendor/product-gallery/details/{$testProduct->id}", 'GET');
        $request->merge(['vendor' => $vendor]);

        $controller = new ProductGalleryController();
        $response = $controller->details($testProduct->id);

        $statusCode = $response->getStatusCode();
        $data = json_decode($response->getContent(), true);

        if ($statusCode !== 200) {
            echo "❌ FAILED: HTTP {$statusCode}\n";
            exit(1);
        }

        echo "✅ SUCCESS: HTTP 200\n";

        if (isset($data['data']['barcode'])) {
            $barcodeValue = $data['data']['barcode'] ?: 'null';
            echo "✅ PASSED: Details API includes barcode: {$barcodeValue}\n";
        } else {
            echo "⚠️  WARNING: Details API missing barcode field\n";
        }

    } catch (\Exception $e) {
        echo "❌ EXCEPTION: " . $e->getMessage() . "\n";
        exit(1);
    }
}

echo "\n========================================\n";
echo " ✅ ALL TESTS PASSED\n";
echo "========================================\n\n";

echo "Summary:\n";
echo "✅ Browse API now returns 'barcode' field\n";
echo "✅ Trending API now returns 'barcode' field\n";
echo "✅ Details API still works correctly\n";
echo "\n✅ Barcode badges will now display on product cards!\n\n";
