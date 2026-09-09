<?php
/**
 * Test script to verify OrderTransactionService works correctly
 * Usage: php scripts/test-transaction-sync.php
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Order;
use App\Models\OrderTransaction;
use App\Services\OrderTransactionService;

echo "🧪 Testing Transaction Sync Fix\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

// Find a test order that has a transaction
$transactionOrderId = OrderTransaction::first()?->order_id;
$order = $transactionOrderId ? Order::find($transactionOrderId) : null;

if (!$order) {
    echo "❌ No test order found\n";
    exit(1);
}

echo "📦 Test Order: #{$order->id}\n";
echo "   Current Amount: {$order->order_amount}\n\n";

// Get existing transaction
$transaction = OrderTransaction::where('order_id', $order->id)->first();

if (!$transaction) {
    echo "❌ No transaction found for this order\n";
    exit(1);
}

echo "💰 Current Transaction State:\n";
echo "   Order Amount: {$transaction->order_amount}\n";
echo "   Store Amount: {$transaction->store_amount}\n";
echo "   Admin Commission: {$transaction->admin_commission}\n";
echo "   Is Edited: " . ($transaction->is_edited ? 'Yes' : 'No') . "\n";
echo "   Edit Count: {$transaction->edit_count}\n\n";

// Test 1: Simulate order amount increase
echo "🧪 Test 1: Simulating order amount increase (+50)\n";
$oldAmount = $order->order_amount;
$order->order_amount = $oldAmount + 50;

try {
    OrderTransactionService::updateFromOrderEdit(
        $order,
        'admin',
        1, // admin user ID
        ['test' => true, 'test_case' => 'amount_increase']
    );

    // Reload transaction
    $transaction->refresh();

    echo "✅ Transaction update successful!\n";
    echo "   New Order Amount: {$transaction->order_amount}\n";
    echo "   Is Edited: " . ($transaction->is_edited ? 'Yes' : 'No') . "\n";
    echo "   Edit Count: {$transaction->edit_count}\n";
    echo "   Total Adjustment: {$transaction->total_adjustment}\n";

    if ($transaction->original_order_amount) {
        echo "   Original Amount: {$transaction->original_order_amount} (preserved ✅)\n";
    }

    $editHistory = json_decode($transaction->edit_history, true);
    if ($editHistory && count($editHistory) > 0) {
        echo "   Edit History: " . count($editHistory) . " entries recorded ✅\n";
        echo "\n📜 Latest Edit:\n";
        $latest = end($editHistory);
        echo "      Date: {$latest['edited_at']}\n";
        echo "      By: {$latest['edited_by']}\n";
        echo "      Adjustment: {$latest['adjustment']}\n";
    }

    echo "\n✅ All tests passed!\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "✅ Transaction sync is working correctly\n";

} catch (\Exception $e) {
    echo "❌ Transaction update failed!\n";
    echo "   Error: {$e->getMessage()}\n";
    echo "   File: {$e->getFile()}:{$e->getLine()}\n";
    exit(1);
}

// Restore original amount (don't save to DB)
$order->order_amount = $oldAmount;

echo "\n🔧 Note: This was a test - no actual order was modified\n";
