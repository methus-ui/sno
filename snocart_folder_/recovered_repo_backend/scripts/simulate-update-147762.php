#!/usr/bin/env php
<?php

/**
 * Simulate Update Request for Product 147762
 */

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Item;
use Illuminate\Support\Facades\Validator;

echo "=== Simulate Product Update ===\n\n";

$product = Item::with('translations')->find(147762);

// Simulate form data (minimal required fields)
$requestData = [
    'name' => [
        0 => $product->name,  // Default language
    ],
    'lang' => ['default'],  // Language array
    'description' => [
        0 => $product->description,
    ],
    'category_id' => $product->category_id,
    'price' => $product->price,
    'discount' => $product->discount ?? 0,
    'discount_type' => $product->discount_type ?? 'amount',
];

echo "Simulated Request Data:\n";
echo json_encode($requestData, JSON_PRETTY_PRINT) . "\n\n";

// Test validation (from ItemController::update)
$validator = Validator::make($requestData, [
    'name' => 'array',
    'name.0' => 'required',
    'name.*' => 'max:191',
    'category_id' => 'required',
    'price' => 'required|numeric|between:0.01,999999999999.99',
    'description.*' => 'max:1000',
    'description.0' => 'required',
    'discount' => 'required|numeric|min:0',
], [
    'name.0.required' => 'Item default name required',
    'description.0.required' => 'Item default description required',
    'category_id.required' => 'Category required',
    'description.*.max' => 'Description length warning',
]);

echo "Validation Results:\n";
if ($validator->fails()) {
    echo "❌ VALIDATION FAILED\n\n";
    echo "Errors:\n";
    foreach ($validator->errors()->all() as $error) {
        echo "  - {$error}\n";
    }
    echo "\nThis is why the update button doesn't work!\n";
} else {
    echo "✅ VALIDATION PASSED\n\n";

    // Check discount validation
    if ($requestData['discount_type'] == 'percent') {
        $dis = ($requestData['price'] / 100) * $requestData['discount'];
    } else {
        $dis = $requestData['discount'];
    }

    if ($requestData['price'] <= $dis) {
        echo "❌ PRICE VALIDATION FAILED\n";
        echo "   Discount cannot be more than or equal to price\n";
        echo "   Price: {$requestData['price']}\n";
        echo "   Discount: {$dis}\n";
    } else {
        echo "✅ PRICE VALIDATION PASSED\n";
        echo "   Update should work!\n";
    }
}

echo "\n";
