<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\WalletPayment;
use App\Models\WalletTransaction;
use App\Models\BusinessSetting;
use App\CentralLogics\CustomerLogic;

echo "\n=== WALLET ADD FUNDS DIAGNOSTIC ===\n\n";

// Check 1: Digital payment enabled
echo "✓ Check 1: Digital Payment Status\n";
$digital_payment = \App\CentralLogics\Helpers::get_business_settings('digital_payment');
echo "  Status: " . ($digital_payment['status'] ?? 'not set') . "\n";
if (!isset($digital_payment['status']) || $digital_payment['status'] == 0) {
    echo "  ❌ ERROR: Digital payment is DISABLED\n";
    echo "  FIX: Enable digital payment in business settings\n\n";
} else {
    echo "  ✅ Digital payment is enabled\n\n";
}

// Check 2: CustomerLogic class exists
echo "✓ Check 2: CustomerLogic Class\n";
if (class_exists('App\CentralLogics\CustomerLogic')) {
    echo "  ✅ CustomerLogic class exists\n";

    // Check if create_wallet_transaction method exists
    if (method_exists('App\CentralLogics\CustomerLogic', 'create_wallet_transaction')) {
        echo "  ✅ create_wallet_transaction method exists\n\n";
    } else {
        echo "  ❌ ERROR: create_wallet_transaction method NOT FOUND\n\n";
    }
} else {
    echo "  ❌ ERROR: CustomerLogic class NOT FOUND\n\n";
}

// Check 3: Wallet callback functions exist
echo "✓ Check 3: Wallet Callback Functions\n";
if (function_exists('wallet_success')) {
    echo "  ✅ wallet_success function exists\n";
} else {
    echo "  ❌ ERROR: wallet_success function NOT FOUND\n";
}

if (function_exists('wallet_failed')) {
    echo "  ✅ wallet_failed function exists\n\n";
} else {
    echo "  ❌ ERROR: wallet_failed function NOT FOUND\n\n";
}

// Check 4: Recent wallet payments
echo "✓ Check 4: Recent Wallet Payments (Last 10)\n";
$recent_payments = WalletPayment::orderBy('created_at', 'desc')->limit(10)->get();
if ($recent_payments->count() > 0) {
    echo "  Total recent payments: " . $recent_payments->count() . "\n";
    foreach ($recent_payments as $payment) {
        $status_emoji = $payment->payment_status == 'success' ? '✅' :
                       ($payment->payment_status == 'pending' ? '⏳' : '❌');
        echo "  {$status_emoji} ID: {$payment->id} | Amount: {$payment->amount} | Status: {$payment->payment_status} | Method: {$payment->payment_method} | Created: {$payment->created_at}\n";
    }

    // Check for stuck pending payments
    $stuck_pending = WalletPayment::where('payment_status', 'pending')
        ->where('created_at', '<', now()->subHours(1))
        ->count();

    if ($stuck_pending > 0) {
        echo "\n  ⚠️  WARNING: {$stuck_pending} payments stuck in 'pending' status for >1 hour\n";
        echo "  This suggests payment callback is not working\n";
    }
} else {
    echo "  ℹ️  No wallet payments found\n";
}
echo "\n";

// Check 5: Recent wallet transactions
echo "✓ Check 5: Recent Add Fund Transactions (Last 10)\n";
$recent_transactions = WalletTransaction::where('transaction_type', 'add_fund')
    ->orderBy('created_at', 'desc')
    ->limit(10)
    ->get();

if ($recent_transactions->count() > 0) {
    echo "  Total recent add_fund transactions: " . $recent_transactions->count() . "\n";
    foreach ($recent_transactions as $txn) {
        echo "  ✅ User: {$txn->user_id} | Amount: {$txn->credit} | Method: {$txn->reference} | Created: {$txn->created_at}\n";
    }
} else {
    echo "  ⚠️  WARNING: No add_fund transactions found\n";
    echo "  This suggests wallet_success callback is not being called\n";
}
echo "\n";

// Check 6: Payment gateway configuration
echo "✓ Check 6: Payment Gateway Configuration\n";
$payment_methods = ['ssl_commerz', 'paypal', 'stripe', 'razor_pay', 'senang_pay', 'paytabs', 'paystack', 'paymob'];
$active_methods = [];

foreach ($payment_methods as $method) {
    $config = BusinessSetting::where('key', $method)->first();
    if ($config && isset($config->value)) {
        $data = json_decode($config->value, true);
        if (isset($data['status']) && $data['status'] == 1) {
            $active_methods[] = $method;
        }
    }
}

if (count($active_methods) > 0) {
    echo "  ✅ Active payment methods: " . implode(', ', $active_methods) . "\n";
} else {
    echo "  ❌ ERROR: No payment methods are active\n";
    echo "  FIX: Enable at least one payment gateway\n";
}
echo "\n";

// Check 7: Test wallet_success function
echo "✓ Check 7: Test wallet_success Function\n";
try {
    // Create a test wallet payment
    $test_payment = new WalletPayment();
    $test_payment->user_id = 1; // Using user ID 1 for test
    $test_payment->amount = 100;
    $test_payment->payment_status = 'pending';
    $test_payment->payment_method = 'test';
    $test_payment->save();

    echo "  Created test payment ID: {$test_payment->id}\n";

    // Create mock data object
    $mock_data = new stdClass();
    $mock_data->attribute_id = $test_payment->id;
    $mock_data->payment_method = 'test';
    $mock_data->payer_id = 1;
    $mock_data->payment_amount = 100;

    // Test wallet_success
    try {
        wallet_success($mock_data);

        // Check if payment status updated
        $updated_payment = WalletPayment::find($test_payment->id);
        if ($updated_payment->payment_status == 'success') {
            echo "  ✅ wallet_success function works correctly\n";

            // Check if transaction was created
            $transaction = WalletTransaction::where('user_id', 1)
                ->where('reference', 'test')
                ->where('transaction_type', 'add_fund')
                ->latest()
                ->first();

            if ($transaction) {
                echo "  ✅ Wallet transaction created successfully\n";
                echo "  Transaction ID: {$transaction->id} | Amount: {$transaction->credit}\n";
            } else {
                echo "  ❌ ERROR: Wallet transaction was NOT created\n";
            }
        } else {
            echo "  ❌ ERROR: Payment status not updated (still: {$updated_payment->payment_status})\n";
        }

        // Cleanup
        $test_payment->delete();
        if (isset($transaction)) {
            $transaction->delete();
        }

    } catch (\Exception $e) {
        echo "  ❌ ERROR: wallet_success function failed\n";
        echo "  Error: " . $e->getMessage() . "\n";
        echo "  File: " . $e->getFile() . ":" . $e->getLine() . "\n";
        $test_payment->delete();
    }

} catch (\Exception $e) {
    echo "  ❌ ERROR: Test failed\n";
    echo "  Error: " . $e->getMessage() . "\n";
}
echo "\n";

// Summary
echo "=== DIAGNOSTIC SUMMARY ===\n";
$stuck_count = WalletPayment::where('payment_status', 'pending')
    ->where('created_at', '<', now()->subHours(1))
    ->count();

if ($stuck_count > 0) {
    echo "⚠️  ISSUE FOUND: {$stuck_count} payments stuck in pending\n";
    echo "Likely causes:\n";
    echo "1. Payment gateway callback URL not configured correctly\n";
    echo "2. Payment gateway webhook not reaching the server\n";
    echo "3. wallet_success/wallet_failed functions not being called\n\n";

    echo "To fix:\n";
    echo "1. Check payment gateway webhook settings\n";
    echo "2. Verify callback URLs are accessible\n";
    echo "3. Check Laravel logs for payment errors\n";
} else {
    echo "✅ No obvious issues found with wallet add funds functionality\n";
}

echo "\n";
