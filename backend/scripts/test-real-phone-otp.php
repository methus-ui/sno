#!/usr/bin/env php
<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use App\CentralLogics\SMS_module;
use App\Models\Vendor;

$phone = '9876543210';

echo "=== TESTING OTP FOR PHONE: $phone ===\n\n";

// Step 1: Check if phone already registered
echo "1. Checking if phone is already registered...\n";
$existingVendor = Vendor::where('phone', $phone)->first();
if ($existingVendor) {
    echo "   ✗ ERROR: This phone number is already registered!\n";
    echo "   Vendor ID: {$existingVendor->id}\n";
    echo "   Name: {$existingVendor->f_name} {$existingVendor->l_name}\n";
    echo "   Email: {$existingVendor->email}\n\n";
    echo "   You cannot send OTP to an already registered number.\n";
    echo "   Please use a different phone number.\n";
    exit(1);
} else {
    echo "   ✓ Phone number is available\n\n";
}

// Step 2: Check for existing OTP
echo "2. Checking for existing OTP...\n";
$existingOtp = DB::table('phone_verifications')->where('phone', $phone)->first();
if ($existingOtp) {
    echo "   Found existing OTP:\n";
    echo "   Phone: {$existingOtp->phone}\n";
    echo "   OTP: {$existingOtp->token}\n";
    echo "   Created: {$existingOtp->created_at}\n";
    echo "   Hit Count: {$existingOtp->otp_hit_count}\n";
    echo "   Blocked: " . ($existingOtp->is_temp_blocked ? 'Yes' : 'No') . "\n";

    // Delete old OTP
    echo "   Deleting old OTP...\n";
    DB::table('phone_verifications')->where('phone', $phone)->delete();
    echo "   ✓ Old OTP deleted\n\n";
} else {
    echo "   No existing OTP found\n\n";
}

// Step 3: Generate and send new OTP
echo "3. Generating new OTP...\n";
$otp = rand(100000, 999999);
echo "   Generated OTP: $otp\n\n";

// Step 4: Store OTP in database
echo "4. Storing OTP in database...\n";
try {
    DB::table('phone_verifications')->insert([
        'phone' => $phone,
        'token' => $otp,
        'otp_hit_count' => 0,
        'is_temp_blocked' => 0,
        'temp_block_time' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    echo "   ✓ OTP stored successfully\n\n";
} catch (\Exception $e) {
    echo "   ✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Step 5: Send SMS
echo "5. Sending SMS via 2Factor gateway...\n";
echo "   Phone: $phone\n";
echo "   OTP: $otp\n\n";

try {
    $result = SMS_module::send($phone, $otp);

    echo "   SMS Send Result: $result\n\n";

    if ($result === 'success') {
        echo "   ✓✓✓ SMS SENT SUCCESSFULLY! ✓✓✓\n\n";
        echo "   ================================================\n";
        echo "   CHECK YOUR PHONE: $phone\n";
        echo "   YOUR OTP IS: $otp\n";
        echo "   ================================================\n\n";

        // Show verification details
        $record = DB::table('phone_verifications')->where('phone', $phone)->first();
        echo "   Database Record:\n";
        echo "   - Phone: {$record->phone}\n";
        echo "   - OTP: {$record->token}\n";
        echo "   - Created: {$record->created_at}\n";
        echo "   - Valid for: 10 minutes\n\n";

        echo "   Next Steps:\n";
        echo "   1. Check your phone for SMS\n";
        echo "   2. Go to: https://new.snocart.com/store/apply\n";
        echo "   3. Enter phone: $phone\n";
        echo "   4. Click 'Send OTP'\n";
        echo "   5. Enter OTP: $otp\n";
        echo "   6. Click 'Verify OTP'\n\n";

    } else if ($result === 'not_found') {
        echo "   ✗ ERROR: SMS gateway not configured properly\n";
        echo "   Please check Admin Panel > Third Party > SMS Config\n\n";
    } else {
        echo "   ✗ ERROR: SMS sending failed\n";
        echo "   Result: $result\n\n";
        echo "   Possible reasons:\n";
        echo "   - Invalid phone number format\n";
        echo "   - SMS gateway API key expired\n";
        echo "   - Insufficient credits in 2Factor account\n";
        echo "   - Phone number not in DND-free list\n\n";
    }

} catch (\Exception $e) {
    echo "   ✗ EXCEPTION: " . $e->getMessage() . "\n";
    echo "   Stack trace:\n";
    echo $e->getTraceAsString() . "\n\n";
}

// Step 6: Verify the stored OTP
echo "6. Verifying stored OTP in database...\n";
$verifyRecord = DB::table('phone_verifications')->where('phone', $phone)->first();
if ($verifyRecord) {
    echo "   ✓ OTP record found:\n";
    echo "   Phone: {$verifyRecord->phone}\n";
    echo "   OTP: {$verifyRecord->token}\n";
    echo "   Hit Count: {$verifyRecord->otp_hit_count}\n";
    echo "   Blocked: " . ($verifyRecord->is_temp_blocked ? 'Yes' : 'No') . "\n";
} else {
    echo "   ✗ ERROR: OTP record not found!\n";
}

echo "\n=== TEST COMPLETE ===\n";
