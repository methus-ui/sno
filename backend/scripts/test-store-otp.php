#!/usr/bin/env php
<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use App\CentralLogics\SMS_module;

echo "=== STORE REGISTRATION OTP TEST ===\n\n";

// Test 1: Check SMS gateway configuration
echo "1. Checking SMS gateway configuration...\n";
$smsGateways = DB::table('addon_settings')
    ->whereIn('key_name', ['twilio', '2factor', 'nexmo', 'msg91', 'alphanet_sms'])
    ->get(['key_name', 'live_values']);

$enabledGateway = null;
foreach ($smsGateways as $gateway) {
    $values = json_decode($gateway->live_values, true);
    if (isset($values['status']) && $values['status'] == '1') {
        $enabledGateway = $gateway->key_name;
        echo "   ✓ Enabled gateway: " . $gateway->key_name . "\n";
        echo "   API Key: " . (isset($values['api_key']) && !empty($values['api_key']) ? 'Configured' : 'NOT configured') . "\n";
    }
}

if (!$enabledGateway) {
    echo "   ✗ ERROR: No SMS gateway enabled!\n";
    echo "\n   Fix: Enable SMS gateway in Admin Panel > Third Party > SMS Config\n";
    exit(1);
}

echo "\n";

// Test 2: Check phone_verifications table exists
echo "2. Checking phone_verifications table...\n";
try {
    $tableExists = DB::select("SHOW TABLES LIKE 'phone_verifications'");
    if (empty($tableExists)) {
        echo "   ✗ ERROR: phone_verifications table does not exist!\n";
        echo "\n   Creating table...\n";
        DB::statement("
            CREATE TABLE IF NOT EXISTS `phone_verifications` (
                `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                `phone` varchar(255) NOT NULL,
                `token` varchar(255) NOT NULL,
                `otp_hit_count` int(11) NOT NULL DEFAULT 0,
                `is_temp_blocked` tinyint(1) NOT NULL DEFAULT 0,
                `temp_block_time` timestamp NULL DEFAULT NULL,
                `created_at` timestamp NULL DEFAULT NULL,
                `updated_at` timestamp NULL DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `phone_verifications_phone_unique` (`phone`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
        echo "   ✓ Table created successfully\n";
    } else {
        echo "   ✓ Table exists\n";
    }
} catch (\Exception $e) {
    echo "   ✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n";

// Test 3: Test OTP sending (dry run - won't actually send SMS)
echo "3. Testing OTP generation and storage...\n";
$testPhone = '9876543210'; // Test phone
$testOtp = rand(100000, 999999);

try {
    // Clean up any existing test data
    DB::table('phone_verifications')->where('phone', $testPhone)->delete();

    // Insert test OTP
    DB::table('phone_verifications')->insert([
        'phone' => $testPhone,
        'token' => $testOtp,
        'otp_hit_count' => 0,
        'is_temp_blocked' => 0,
        'temp_block_time' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Verify it was inserted
    $record = DB::table('phone_verifications')->where('phone', $testPhone)->first();
    if ($record && $record->token == $testOtp) {
        echo "   ✓ OTP storage working correctly\n";
        echo "   Test OTP: $testOtp (for phone: $testPhone)\n";
    } else {
        echo "   ✗ ERROR: OTP storage failed\n";
        exit(1);
    }

    // Clean up
    DB::table('phone_verifications')->where('phone', $testPhone)->delete();

} catch (\Exception $e) {
    echo "   ✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n";

// Test 4: Check routes
echo "4. Checking OTP routes...\n";
$routes = [
    'restaurant.send-otp' => 'POST /store/send-otp',
    'restaurant.verify-otp' => 'POST /store/verify-otp',
    'restaurant.resend-otp' => 'POST /store/resend-otp',
];

foreach ($routes as $name => $path) {
    try {
        $url = route($name);
        echo "   ✓ $name -> $url\n";
    } catch (\Exception $e) {
        echo "   ✗ ERROR: Route '$name' not found\n";
    }
}

echo "\n";

// Test 5: Check controller methods exist
echo "5. Checking VendorController methods...\n";
$methods = ['sendOtp', 'verifyOtp', 'resendOtp'];
$controller = new \App\Http\Controllers\VendorController();

foreach ($methods as $method) {
    if (method_exists($controller, $method)) {
        echo "   ✓ VendorController::$method() exists\n";
    } else {
        echo "   ✗ ERROR: VendorController::$method() NOT found\n";
    }
}

echo "\n";

// Test 6: Live test with actual phone (requires manual confirmation)
echo "6. Live OTP Test\n";
echo "   Enter a phone number to test (or press Enter to skip): ";
$handle = fopen("php://stdin", "r");
$livePhone = trim(fgets($handle));

if (!empty($livePhone)) {
    echo "\n   Sending OTP to: $livePhone\n";

    // Generate OTP
    $liveOtp = rand(100000, 999999);

    // Store in database
    DB::table('phone_verifications')->updateOrInsert(
        ['phone' => $livePhone],
        [
            'token' => $liveOtp,
            'otp_hit_count' => 0,
            'is_temp_blocked' => 0,
            'temp_block_time' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]
    );

    // Send SMS
    $result = SMS_module::send($livePhone, $liveOtp);

    if ($result === 'success') {
        echo "   ✓ SMS sent successfully!\n";
        echo "   OTP: $liveOtp (check your phone)\n";
    } else if ($result === 'not_found') {
        echo "   ✗ ERROR: SMS gateway not configured\n";
    } else {
        echo "   ✗ ERROR: SMS sending failed - $result\n";
    }
} else {
    echo "   Skipped\n";
}

echo "\n=== TEST COMPLETE ===\n";
echo "\nIf all tests passed, OTP functionality should work.\n";
echo "If SMS is not being received, check:\n";
echo "  1. Admin Panel > Third Party > SMS Config - verify gateway credentials\n";
echo "  2. Check if phone number format is correct (with country code)\n";
echo "  3. Check SMS gateway dashboard for delivery logs\n";
echo "  4. Check storage/logs/laravel-*.log for errors\n";
