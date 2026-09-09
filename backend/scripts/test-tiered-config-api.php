<?php

/**
 * Test Script for Tiered Additional Charge Config API
 *
 * This script tests that the config API returns tiered charge data
 * so customer apps can display the correct fee dynamically
 *
 * Usage: php scripts/test-tiered-config-api.php
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\BusinessSetting;

echo "========================================\n";
echo "Tiered Config API Test\n";
echo "========================================\n\n";

// Test 1: Check if tiered settings exist in database
echo "Test 1: Database Settings\n";
echo "-------------------------\n";

$tiered_enabled = BusinessSetting::where('key', 'additional_charge_tiered_enabled')->first();
$tiered_data = BusinessSetting::where('key', 'additional_charge_tiers')->first();
$charge_status = BusinessSetting::where('key', 'additional_charge_status')->first();

echo "Additional Charge Status: " . ($charge_status ? $charge_status->value : 'NOT SET') . "\n";
echo "Tiered Enabled: " . ($tiered_enabled ? $tiered_enabled->value : 'NOT SET') . "\n";
echo "Tiered Data: " . ($tiered_data ? $tiered_data->value : 'NOT SET') . "\n\n";

if ($tiered_enabled && $tiered_enabled->value == 1) {
    $tiers = json_decode($tiered_data->value, true);
    echo "Tier Breakdown:\n";
    foreach ($tiers as $index => $tier) {
        $max = isset($tier['max']) && $tier['max'] !== null ? '₹' . $tier['max'] : '∞';
        echo "  Tier " . ($index + 1) . ": ₹{$tier['min']} - {$max} → Charge: ₹{$tier['charge']}\n";
    }
    echo "\n";
}

// Test 2: Simulate API call
echo "Test 2: Config API Response (simulated)\n";
echo "----------------------------------------\n";

try {
    // Call the config controller method
    $controller = new \App\Http\Controllers\Api\V1\ConfigController();
    $response = $controller->configuration();
    $data = $response->getData();

    echo "✓ API call successful\n";
    echo "✓ Response contains " . count((array)$data) . " configuration keys\n\n";

    // Check tiered fields
    if (isset($data->additional_charge_status)) {
        echo "additional_charge_status: " . $data->additional_charge_status . "\n";
    }

    if (isset($data->additional_charge_tiered_enabled)) {
        echo "additional_charge_tiered_enabled: " . $data->additional_charge_tiered_enabled . "\n";
    }

    if (isset($data->additional_charge_tiers)) {
        echo "additional_charge_tiers: " . json_encode($data->additional_charge_tiers, JSON_PRETTY_PRINT) . "\n\n";
    }

    if (isset($data->additional_charge)) {
        echo "additional_charge (fallback): " . $data->additional_charge . "\n\n";
    }

} catch (Exception $e) {
    echo "✗ API call failed: " . $e->getMessage() . "\n\n";
}

// Test 3: Customer app integration guide
echo "Test 3: Customer App Integration\n";
echo "---------------------------------\n";
echo "The customer app should:\n";
echo "1. Call GET /api/v1/config on app launch\n";
echo "2. Check if additional_charge_tiered_enabled = 1\n";
echo "3. If YES: Use additional_charge_tiers array to calculate fee dynamically\n";
echo "4. If NO: Use flat additional_charge value\n\n";

echo "Example cart calculation (Flutter/React):\n";
echo "```\n";
echo "double calculateAdditionalCharge(double cartTotal) {\n";
echo "  if (!config.additional_charge_tiered_enabled) {\n";
echo "    return config.additional_charge; // Flat fee\n";
echo "  }\n\n";
echo "  // Find matching tier\n";
echo "  for (var tier in config.additional_charge_tiers) {\n";
echo "    if (cartTotal >= tier.min_amount) {\n";
echo "      if (tier.max_amount == null || cartTotal <= tier.max_amount) {\n";
echo "        return tier.charge;\n";
echo "      }\n";
echo "    }\n";
echo "  }\n\n";
echo "  return 0.0; // No tier matched\n";
echo "}\n";
echo "```\n\n";

// Test 4: Example calculations
echo "Test 4: Example Fee Calculations\n";
echo "---------------------------------\n";

if ($tiered_enabled && $tiered_enabled->value == 1 && $tiered_data) {
    $tiers = json_decode($tiered_data->value, true);

    $test_amounts = [250, 499, 500, 750, 999, 1000, 1500, 2000];

    foreach ($test_amounts as $amount) {
        $charge = 0;
        foreach ($tiers as $tier) {
            if ($amount >= $tier['min']) {
                if (!isset($tier['max']) || $tier['max'] === null || $amount <= $tier['max']) {
                    $charge = $tier['charge'];
                }
            }
        }
        echo "Cart Total: ₹{$amount} → Additional Charge: ₹{$charge}\n";
    }
}

echo "\n========================================\n";
echo "Test Complete!\n";
echo "========================================\n";
