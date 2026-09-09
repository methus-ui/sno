<?php
/**
 * Test Product Gallery Replicate API Fix
 * Tests that the unit column issue is resolved
 */

$baseUrl = 'https://new.snocart.com';
$authToken = 'YOUR_VENDOR_TOKEN_HERE'; // Replace with actual vendor token

echo "🧪 Product Gallery Replicate API Test\n";
echo str_repeat("=", 60) . "\n\n";

// Step 1: Browse for a product to replicate
echo "1️⃣  Browsing for products...\n";
$browseUrl = "$baseUrl/api/v1/vendor/product-gallery/browse?search=maggi&limit=1";

$ch = curl_init($browseUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $authToken,
    'Accept: application/json'
]);
$browseResponse = curl_exec($ch);
$browseHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($browseHttpCode !== 200) {
    echo "   ❌ Browse API failed with HTTP $browseHttpCode\n";
    echo "   Response: $browseResponse\n";
    exit(1);
}

$browseData = json_decode($browseResponse, true);
if (empty($browseData['data']['products'])) {
    echo "   ❌ No products found to replicate\n";
    exit(1);
}

$sourceProduct = $browseData['data']['products'][0];
$sourceProductId = $sourceProduct['id'];
echo "   ✅ Found product: {$sourceProduct['name']} (ID: $sourceProductId)\n";
echo "   📦 Unit: " . ($sourceProduct['unit'] ?? 'N/A') . "\n\n";

// Step 2: Test replicate endpoint
echo "2️⃣  Testing replicate endpoint...\n";
$replicateUrl = "$baseUrl/api/v1/vendor/product-gallery/replicate";

$postData = [
    'source_product_id' => $sourceProductId,
    'customize' => [
        'price' => 50.00,
        'stock' => 100,
        'copy_images' => true,
        'copy_variations' => true,
        'copy_addons' => true,
        'copy_attributes' => true
    ]
];

$ch = curl_init($replicateUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $authToken,
    'Content-Type: application/json',
    'Accept: application/json'
]);
$replicateResponse = curl_exec($ch);
$replicateHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "   HTTP Status: $replicateHttpCode\n";

if ($replicateHttpCode === 200 || $replicateHttpCode === 201) {
    $replicateData = json_decode($replicateResponse, true);
    if ($replicateData['success']) {
        echo "   ✅ Replication successful!\n";
        echo "   📦 New Product ID: {$replicateData['data']['new_product_id']}\n";
        echo "   📝 Name: {$replicateData['data']['name']}\n";
        echo "   📊 Items copied:\n";
        foreach ($replicateData['data']['items_copied'] as $key => $count) {
            echo "      • $key: $count\n";
        }
    } else {
        echo "   ❌ Replication failed: {$replicateData['message']}\n";
    }
} else {
    echo "   ❌ API request failed\n";
    $errorData = json_decode($replicateResponse, true);
    echo "   Response: " . json_encode($errorData, JSON_PRETTY_PRINT) . "\n";
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "✅ Test complete!\n\n";

echo "📌 Note: If you see a 409 error (product already exists), that's normal.\n";
echo "📌 The fix ensures no SQL error about missing 'unit' column.\n";
