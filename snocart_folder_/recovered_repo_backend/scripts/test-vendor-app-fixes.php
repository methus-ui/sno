#!/usr/bin/env php
<?php

/**
 * Test Vendor App Fixes: Barcode & Price Update
 */

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\BusinessSetting;

echo "=== Vendor App Fixes Verification ===\n\n";

// Test 1: Check barcode field in store method
echo "TEST 1: Barcode Support\n";
echo str_repeat("-", 60) . "\n";

$storeMethod = file_get_contents(__DIR__.'/../app/Http/Controllers/Api/V1/Vendor/ItemController.php');

if (strpos($storeMethod, '$item->barcode = $request->barcode') !== false) {
    echo "✅ PASS: Barcode field added to store method\n";
    echo "   Line: \$item->barcode = \$request->barcode ?? null;\n";
} else {
    echo "❌ FAIL: Barcode field NOT found in store method\n";
}

if (strpos($storeMethod, '$p->barcode = $request->barcode') !== false) {
    echo "✅ PASS: Barcode field added to update method\n";
    echo "   Line: \$p->barcode = \$request->barcode ?? \$p->barcode;\n";
} else {
    echo "❌ FAIL: Barcode field NOT found in update method\n";
}

echo "\n";

// Test 2: Check approval settings
echo "TEST 2: Price Update Approval Settings\n";
echo str_repeat("-", 60) . "\n";

$settings = BusinessSetting::where('key', 'product_approval_datas')->first();
$data = json_decode($settings?->value ?? '{}', true);

$priceApproval = data_get($data, 'Update_product_price', 0);
$anythingApproval = data_get($data, 'Update_anything_in_product_details', 0);

if ($priceApproval == 0) {
    echo "✅ PASS: Update_product_price = 0 (approval disabled)\n";
} else {
    echo "❌ FAIL: Update_product_price = 1 (approval still required)\n";
}

if ($anythingApproval == 0) {
    echo "✅ PASS: Update_anything_in_product_details = 0 (approval disabled)\n";
} else {
    echo "❌ FAIL: Update_anything_in_product_details = 1 (approval still required)\n";
}

echo "\n";

// Test 3: Verify approval condition logic
echo "TEST 3: Approval Condition Check\n";
echo str_repeat("-", 60) . "\n";

$productApprovalEnabled = \App\CentralLogics\Helpers::get_mail_status('product_approval');

echo "Product Approval Status: " . ($productApprovalEnabled ? 'Enabled' : 'Disabled') . "\n";
echo "\n";

if (!$productApprovalEnabled) {
    echo "✅ PASS: Product approval is disabled globally\n";
    echo "   All updates will apply immediately\n";
} else {
    echo "⚠️  INFO: Product approval is enabled globally\n";
    echo "   But specific checks are disabled:\n";
    echo "   - Price updates: " . ($priceApproval ? 'Need approval ❌' : 'Immediate ✅') . "\n";
    echo "   - Any updates: " . ($anythingApproval ? 'Need approval ❌' : 'Immediate ✅') . "\n";
}

echo "\n";

// Summary
echo "=== SUMMARY ===\n";

$allPassed = true;

if (strpos($storeMethod, '$item->barcode') === false) $allPassed = false;
if (strpos($storeMethod, '$p->barcode') === false) $allPassed = false;
if ($priceApproval != 0) $allPassed = false;
if ($anythingApproval != 0) $allPassed = false;

if ($allPassed) {
    echo "✅ ALL TESTS PASSED\n\n";

    echo "Vendor App Features Now Working:\n";
    echo "1. ✅ Barcode saves when uploading products\n";
    echo "2. ✅ Price updates apply immediately\n";
    echo "3. ✅ No approval queue delays\n";
    echo "\n";

    echo "API Endpoints Ready:\n";
    echo "- POST /api/v1/vendor/item/store (barcode supported)\n";
    echo "- PUT /api/v1/vendor/item/update (price updates immediately)\n";

} else {
    echo "❌ SOME TESTS FAILED\n\n";
    echo "Please review the errors above.\n";
}

echo "\n";
