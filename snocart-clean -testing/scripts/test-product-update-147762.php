#!/usr/bin/env php
<?php

/**
 * Test Product 147762 Update Issues
 */

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Item;
use App\Models\Store;

echo "=== Product Update Diagnostic Test ===\n\n";

$productId = 147762;
$product = Item::find($productId);

if (!$product) {
    echo "❌ Product {$productId} not found\n";
    exit(1);
}

echo "Product Details:\n";
echo "  ID: {$product->id}\n";
echo "  Name: {$product->name}\n";
echo "  Store ID: {$product->store_id}\n";
echo "  Price: {$product->price}\n";
echo "  Barcode: " . ($product->barcode ?? 'NULL') . "\n";
echo "  Images: " . (is_array($product->images) ? json_encode($product->images) : 'NULL') . "\n";
echo "  Status: {$product->status}\n";
echo "  Approved: " . ($product->is_approved ? 'YES' : 'NO') . "\n\n";

// Check if product has NULL images (causes 500 error)
if ($product->images === null) {
    echo "⚠️  WARNING: Product has NULL images (can cause edit page errors)\n";
    echo "   Fixing now...\n";
    $product->images = [];
    $product->save();
    echo "   ✅ Fixed!\n\n";
}

// Check store permissions
$store = Store::find($product->store_id);
if (!$store) {
    echo "❌ Store not found for product\n";
    exit(1);
}

echo "Store Details:\n";
echo "  ID: {$store->id}\n";
echo "  Name: {$store->name}\n";
echo "  Item Section: " . ($store->item_section ? 'Enabled ✅' : 'Disabled ❌') . "\n";
echo "  Status: " . ($store->status ? 'Active ✅' : 'Inactive ❌') . "\n\n";

if (!$store->item_section) {
    echo "❌ ISSUE FOUND: Store item_section is DISABLED\n";
    echo "   This prevents product updates!\n";
    echo "   Enable it in store settings.\n\n";
}

// Check product approval settings
$approvalSettings = \App\Models\BusinessSetting::where('key', 'product_approval_datas')->first();
$approvalData = json_decode($approvalSettings?->value ?? '{}', true);

echo "Product Approval Settings:\n";
echo "  Product Approval Enabled: " . (\App\CentralLogics\Helpers::get_mail_status('product_approval') ? 'YES' : 'NO') . "\n";
echo "  Update Price: " . (data_get($approvalData, 'Update_product_price') ? 'Needs Approval ❌' : 'Immediate ✅') . "\n";
echo "  Update Anything: " . (data_get($approvalData, 'Update_anything_in_product_details') ? 'Needs Approval ❌' : 'Immediate ✅') . "\n";
echo "  Update Variation: " . (data_get($approvalData, 'Update_product_variation') ? 'Needs Approval' : 'Immediate') . "\n\n";

// Check route
$updateRoute = route('vendor.item.update', $product->id);
echo "Update Route:\n";
echo "  URL: {$updateRoute}\n";
echo "  Method: POST\n\n";

// Simulate required fields check
echo "Required Fields Check:\n";
$requiredFields = [
    'name' => $product->name,
    'category_id' => $product->category_id,
    'price' => $product->price,
    'discount' => $product->discount ?? 0,
];

$missingFields = [];
foreach ($requiredFields as $field => $value) {
    if (empty($value) && $value !== 0) {
        $missingFields[] = $field;
        echo "  ❌ {$field}: EMPTY\n";
    } else {
        echo "  ✅ {$field}: {$value}\n";
    }
}

if (!empty($missingFields)) {
    echo "\n❌ ISSUE FOUND: Missing required fields!\n";
    echo "   Fields: " . implode(', ', $missingFields) . "\n\n";
}

echo "\n=== SUMMARY ===\n";

$issues = [];

if ($product->images === null) {
    $issues[] = "NULL images (FIXED)";
}

if (!$store->item_section) {
    $issues[] = "Store item_section disabled";
}

if (!empty($missingFields)) {
    $issues[] = "Missing required fields: " . implode(', ', $missingFields);
}

if (empty($issues)) {
    echo "✅ NO ISSUES FOUND - Update should work!\n\n";
    echo "If button still not working, check:\n";
    echo "1. Browser console for JavaScript errors\n";
    echo "2. Network tab for AJAX errors\n";
    echo "3. Click the button and watch for error messages\n";
} else {
    echo "❌ ISSUES FOUND:\n";
    foreach ($issues as $issue) {
        echo "   - {$issue}\n";
    }
    echo "\nFix these issues to enable updates.\n";
}

echo "\n";
