#!/usr/bin/env php
<?php

/**
 * Verification Script: Order Edit V2 Duplicate Fix
 *
 * This script verifies that the JavaScript fix for the duplicate items bug is in place.
 *
 * Bug: When editing existing order items, changing quantity created duplicates.
 * Fix: Added order_details_id to the AJAX request in updateQuantity function.
 */

echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  Order Edit V2 - Duplicate Items Fix Verification             ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "\n";

$jsFile = __DIR__ . '/../public/assets/admin/js/order-edit-v2.js';

if (!file_exists($jsFile)) {
    echo "❌ ERROR: JavaScript file not found!\n";
    echo "   Expected: $jsFile\n";
    exit(1);
}

$content = file_get_contents($jsFile);

// Check 1: Verify the fix is present
echo "📋 Checking for the fix...\n\n";

$fixPattern = '/order_details_id\s*:\s*item\.order_detail_id/';
$hasFix = preg_match($fixPattern, $content);

if ($hasFix) {
    echo "   ✅ FIX FOUND: order_details_id is being sent in AJAX request\n";
} else {
    echo "   ❌ FIX MISSING: order_details_id is NOT being sent\n";
    echo "   This means the duplicate bug still exists!\n";
    exit(1);
}

// Check 2: Verify it's in the updateQuantity function
echo "\n📋 Verifying fix location...\n\n";

$updateQtyPattern = '/function updateQuantity.*?{.*?order_details_id\s*:\s*item\.order_detail_id.*?}/s';
$inUpdateQty = preg_match($updateQtyPattern, $content);

if ($inUpdateQty) {
    echo "   ✅ LOCATION CORRECT: Fix is in updateQuantity function\n";
} else {
    echo "   ⚠️  WARNING: order_details_id found but not in updateQuantity function\n";
    echo "   The fix might not work correctly.\n";
}

// Check 3: Verify the AJAX data structure
echo "\n📋 Verifying AJAX data structure...\n\n";

$requiredFields = [
    '_token' => false,
    'id' => false,
    'item_type' => false,
    'quantity' => false,
    'cart_item_key' => false,
    'order_id' => false,
    'order_details_id' => false,
];

foreach ($requiredFields as $field => $found) {
    if (strpos($content, $field) !== false) {
        $requiredFields[$field] = true;
    }
}

$allFieldsPresent = !in_array(false, $requiredFields, true);

if ($allFieldsPresent) {
    echo "   ✅ ALL REQUIRED FIELDS PRESENT:\n";
    foreach ($requiredFields as $field => $found) {
        echo "      ✓ $field\n";
    }
} else {
    echo "   ❌ MISSING REQUIRED FIELDS:\n";
    foreach ($requiredFields as $field => $found) {
        if (!$found) {
            echo "      ✗ $field (missing)\n";
        }
    }
}

// Check 4: Look for the specific bug pattern (should NOT exist)
echo "\n📋 Checking for bug pattern (should not exist)...\n\n";

$bugPattern = '/url:\s*cfg\.urls\.addToCart.*?data:\s*{[^}]*?quantity:\s*newQty[^}]*?}(?!.*?order_details_id)/s';
$hasBug = preg_match($bugPattern, $content);

if (!$hasBug) {
    echo "   ✅ NO BUG PATTERN FOUND: Code looks correct\n";
} else {
    echo "   ❌ BUG PATTERN DETECTED: updateQuantity might be missing order_details_id\n";
}

// Summary
echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  VERIFICATION SUMMARY                                          ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "\n";

$allChecksPassed = $hasFix && $inUpdateQty && $allFieldsPresent && !$hasBug;

if ($allChecksPassed) {
    echo "✅ ALL CHECKS PASSED!\n\n";
    echo "The duplicate items bug fix is properly implemented.\n";
    echo "Order quantity updates should now work correctly without creating duplicates.\n\n";
    echo "NEXT STEPS:\n";
    echo "1. Clear browser cache (Ctrl+Shift+R)\n";
    echo "2. Test editing an order\n";
    echo "3. Change quantity of existing item\n";
    echo "4. Save and verify NO duplicates appear\n\n";
    exit(0);
} else {
    echo "❌ VERIFICATION FAILED!\n\n";
    echo "Some checks did not pass. Please review the fix.\n\n";
    exit(1);
}
