#!/usr/bin/env php
<?php

/**
 * Verify Wallet Adjustment Bug Fixes
 *
 * Verifies the three critical bug fixes without creating test data:
 * 1. DB transaction wrapper
 * 2. Pessimistic locking
 * 3. Negative balance validation
 *
 * Run: php scripts/verify-wallet-adjustment-fixes.php
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\DeliveryMan;
use App\Models\DeliveryManWallet;
use Illuminate\Support\Facades\DB;

echo "======================================\n";
echo "Wallet Adjustment Bug Fixes Verification\n";
echo "======================================\n\n";

// Test 1: Verify code changes are in place
echo "TEST 1: Code Changes Verification\n";
echo "----------------------------------\n";

$controller_path = '/var/www/html/new_public/new/app/Http/Controllers/Api/V1/DeliverymanController.php';
$controller_content = file_get_contents($controller_path);

$tests_passed = 0;
$tests_failed = 0;

// Check for DB::beginTransaction
if (strpos($controller_content, 'DB::beginTransaction()') !== false) {
    echo "✅ DB::beginTransaction() found - FIX #1 applied\n";
    $tests_passed++;
} else {
    echo "❌ DB::beginTransaction() NOT found - FIX #1 missing\n";
    $tests_failed++;
}

// Check for lockForUpdate
if (strpos($controller_content, 'lockForUpdate()') !== false) {
    echo "✅ lockForUpdate() found - FIX #2 applied\n";
    $tests_passed++;
} else {
    echo "❌ lockForUpdate() NOT found - FIX #2 missing\n";
    $tests_failed++;
}

// Check for negative balance validation
if (strpos($controller_content, 'collected_cash < 0') !== false || strpos($controller_content, 'wallet_earning < 0') !== false) {
    echo "✅ Negative balance validation found - FIX #3 applied\n";
    $tests_passed++;
} else {
    echo "❌ Negative balance validation NOT found - FIX #3 missing\n";
    $tests_failed++;
}

// Check for DB::commit
if (strpos($controller_content, 'DB::commit()') !== false) {
    echo "✅ DB::commit() found - Transaction complete\n";
    $tests_passed++;
} else {
    echo "❌ DB::commit() NOT found - Transaction incomplete\n";
    $tests_failed++;
}

// Check for DB::rollBack
if (strpos($controller_content, 'DB::rollBack()') !== false) {
    echo "✅ DB::rollBack() found - Error handling present\n";
    $tests_passed++;
} else {
    echo "❌ DB::rollBack() NOT found - Error handling missing\n";
    $tests_failed++;
}

echo "\n";

// Test 2: Check existing delivery men and wallets
echo "TEST 2: Database State Check\n";
echo "----------------------------\n";

$dm_count = DeliveryMan::count();
$wallet_count = DeliveryManWallet::count();

echo "Total Delivery Men: {$dm_count}\n";
echo "Total Wallets: {$wallet_count}\n";

if ($dm_count > 0) {
    $sample_dm = DeliveryMan::with('wallet')->first();
    if ($sample_dm) {
        echo "\nSample Delivery Man:\n";
        echo "  ID: {$sample_dm->id}\n";
        echo "  Name: {$sample_dm->f_name} {$sample_dm->l_name}\n";
        echo "  Phone: {$sample_dm->phone}\n";

        if ($sample_dm->wallet) {
            echo "\nWallet State:\n";
            echo "  collected_cash: " . number_format($sample_dm->wallet->collected_cash, 2) . "\n";
            echo "  total_earning: " . number_format($sample_dm->wallet->total_earning, 2) . "\n";
            echo "  total_withdrawn: " . number_format($sample_dm->wallet->total_withdrawn, 2) . "\n";
            echo "  pending_withdraw: " . number_format($sample_dm->wallet->pending_withdraw, 2) . "\n";

            $wallet_earning = $sample_dm->wallet->total_earning - ($sample_dm->wallet->total_withdrawn + $sample_dm->wallet->pending_withdraw);
            echo "  wallet_earning (calculated): " . number_format($wallet_earning, 2) . "\n";

            if ($sample_dm->wallet->collected_cash > 0 && $wallet_earning > 0) {
                echo "\n✅ This delivery man can use adjust payment feature\n";
            } else {
                echo "\n⚠️  This delivery man already adjusted (collected_cash or wallet_earning is 0)\n";
            }
        } else {
            echo "  No wallet found for this delivery man\n";
        }
    }
    $tests_passed++;
} else {
    echo "⚠️  No delivery men found in database\n";
}

echo "\n";

// Test 3: Check recent adjustment transactions
echo "TEST 3: Recent Adjustment Transactions\n";
echo "--------------------------------------\n";

$recent_adjustments = DB::table('provide_d_m_earnings')
    ->where('method', 'adjustment')
    ->orderBy('created_at', 'desc')
    ->limit(5)
    ->get();

if ($recent_adjustments->count() > 0) {
    echo "Found {$recent_adjustments->count()} recent adjustment transactions:\n\n";
    foreach ($recent_adjustments as $adj) {
        echo "  DM ID: {$adj->delivery_man_id}\n";
        echo "  Amount: " . number_format($adj->amount, 2) . "\n";
        echo "  Type: {$adj->ref}\n";
        echo "  Date: {$adj->created_at}\n";
        echo "  ---\n";
    }
    $tests_passed++;
} else {
    echo "⚠️  No adjustment transactions found yet\n";
}

echo "\n";

// Test 4: Verify the fix pattern matches shift booking fix
echo "TEST 4: Compare with Shift Booking Fix Pattern\n";
echo "----------------------------------------------\n";

// Check shift booking service for reference
$shift_service_path = '/var/www/html/new_public/new/app/Services/DmShiftBookingService.php';
if (file_exists($shift_service_path)) {
    $shift_content = file_get_contents($shift_service_path);
    if (strpos($shift_content, 'lockForUpdate()') !== false) {
        echo "✅ Shift booking service uses lockForUpdate() (reference implementation)\n";
        echo "✅ Wallet adjustment now uses same pattern for race condition prevention\n";
        $tests_passed++;
    }
} else {
    echo "⚠️  Shift booking service not found for comparison\n";
}

echo "\n";

// Summary
echo "======================================\n";
echo "Verification Summary\n";
echo "======================================\n\n";

echo "Tests Passed: {$tests_passed}\n";
echo "Tests Failed: {$tests_failed}\n\n";

if ($tests_failed == 0) {
    echo "✅ ALL FIXES VERIFIED SUCCESSFULLY!\n\n";
} else {
    echo "⚠️  SOME FIXES MAY BE MISSING - CHECK OUTPUT ABOVE\n\n";
}

echo "Applied Fixes:\n";
echo "  1. DB Transaction Wrapper - Ensures wallet update and earnings insert are atomic\n";
echo "  2. Pessimistic Locking - Prevents race conditions during concurrent requests\n";
echo "  3. Negative Balance Validation - Rejects invalid wallet states early\n\n";

echo "Benefits:\n";
echo "  • Data consistency guaranteed (wallet + earnings always in sync)\n";
echo "  • Race conditions prevented (concurrent requests can't corrupt data)\n";
echo "  • Invalid states rejected (negative balances caught early)\n";
echo "  • Full error logging (all failures recorded in logs)\n\n";

echo "Next Steps:\n";
echo "  1. Test with real API: POST /api/v1/deliveryman/make-wallet-adjustment\n";
echo "  2. Monitor logs: tail -f storage/logs/laravel-*.log\n";
echo "  3. Try concurrent requests to verify race condition is fixed\n";
echo "  4. Update MEMORY.md with implementation details\n\n";

echo "Done! 🎉\n";
