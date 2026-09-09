#!/usr/bin/env php
<?php

/**
 * Disable Product Approval Requirement for Price Updates
 *
 * This allows vendors to update prices immediately without admin approval
 */

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\BusinessSetting;

echo "=== Disable Price Update Approval ===\n\n";

$setting = BusinessSetting::where('key', 'product_approval_datas')->first();

if (!$setting) {
    echo "❌ Product approval settings not found\n";
    exit(1);
}

$data = json_decode($setting->value ?? '{}', true);

echo "Current Settings:\n";
echo "- Product Approval: " . (\App\CentralLogics\Helpers::get_mail_status('product_approval') ? 'Enabled' : 'Disabled') . "\n";
echo "- Update Product Price Approval: " . (data_get($data, 'Update_product_price') ? 'Required' : 'Not Required') . "\n";
echo "- Update Product Variation Approval: " . (data_get($data, 'Update_product_variation') ? 'Required' : 'Not Required') . "\n";
echo "- Update Anything Approval: " . (data_get($data, 'Update_anything_in_product_details') ? 'Required' : 'Not Required') . "\n\n";

// Disable price update approval
$data['Update_product_price'] = 0;

$setting->value = json_encode($data);
$setting->save();

echo "✅ Price update approval DISABLED\n\n";

echo "New Settings:\n";
echo "- Vendors can now update prices immediately\n";
echo "- No admin approval needed for price changes\n";
echo "- Other approval settings unchanged\n\n";

echo "=== SUMMARY ===\n";
echo "✅ Price updates will now apply immediately\n";
echo "✅ No more waiting for admin approval\n";
