#!/usr/bin/env php
<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

$phone = '9876543210';
$otp = '295718';

echo "=== VERIFYING OTP ===\n\n";

echo "Phone: $phone\n";
echo "OTP: $otp\n\n";

// Check verification data
$verificationData = DB::table('phone_verifications')->where('phone', $phone)->first();

if (!$verificationData) {
    echo "✗ ERROR: No OTP found for this phone number\n";
    echo "Please request a new OTP first.\n";
    exit(1);
}

echo "Found OTP record:\n";
echo "- Stored OTP: {$verificationData->token}\n";
echo "- Your OTP: $otp\n";
echo "- Match: " . ($verificationData->token == $otp ? 'YES ✓' : 'NO ✗') . "\n";
echo "- Created: {$verificationData->created_at}\n";
echo "- Age: " . Carbon::parse($verificationData->created_at)->diffForHumans() . "\n";
echo "- Hit Count: {$verificationData->otp_hit_count}/5\n";
echo "- Blocked: " . ($verificationData->is_temp_blocked ? 'YES' : 'NO') . "\n\n";

// Check if blocked
if ($verificationData->is_temp_blocked == 1) {
    if ($verificationData->temp_block_time && Carbon::parse($verificationData->temp_block_time)->diffInSeconds(now()) < 600) {
        $remainingTime = 600 - Carbon::parse($verificationData->temp_block_time)->diffInSeconds(now());
        echo "✗ ERROR: Too many failed attempts\n";
        echo "Please try again after " . ceil($remainingTime / 60) . " minutes\n";
        exit(1);
    }

    // Reset block
    DB::table('phone_verifications')->where('phone', $phone)->update([
        'otp_hit_count' => 0,
        'is_temp_blocked' => 0,
        'temp_block_time' => null,
        'updated_at' => now(),
    ]);
    echo "✓ Block reset\n\n";
}

// Verify OTP
if ($verificationData->token == $otp) {
    echo "✓✓✓ OTP VERIFIED SUCCESSFULLY! ✓✓✓\n\n";

    // Delete verification record (as the actual verification does)
    DB::table('phone_verifications')->where('phone', $phone)->delete();

    echo "✓ Verification record deleted\n";
    echo "✓ Phone number verified: $phone\n\n";

    echo "You can now complete the store registration!\n";

} else {
    echo "✗ INVALID OTP!\n\n";

    // Increment hit count
    DB::table('phone_verifications')->where('phone', $phone)->update([
        'otp_hit_count' => $verificationData->otp_hit_count + 1,
        'updated_at' => now(),
    ]);

    $remainingAttempts = 5 - ($verificationData->otp_hit_count + 1);
    echo "Remaining attempts: $remainingAttempts/5\n";

    if ($remainingAttempts <= 0) {
        DB::table('phone_verifications')->where('phone', $phone)->update([
            'is_temp_blocked' => 1,
            'temp_block_time' => now(),
            'updated_at' => now(),
        ]);
        echo "Account temporarily blocked. Please try again later.\n";
    }
}

echo "\n=== VERIFICATION COMPLETE ===\n";
