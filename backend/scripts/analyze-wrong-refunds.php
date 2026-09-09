<?php

/**
 * Analyze Wrong Wallet Refund Transactions
 *
 * This script identifies:
 * 1. Manual refunds (add_fund_by_admin) that reference orders
 * 2. Orders that should have gotten automatic refunds but didn't
 * 3. Duplicate refunds for the same order
 * 4. Incorrect refund amounts
 *
 * Generates a detailed report for review before reversal
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

echo "\n========================================\n";
echo "WALLET REFUND ANALYSIS\n";
echo "========================================\n\n";

// 1. Find all manual refunds with order references
echo "📋 Step 1: Finding manual refunds with order references...\n";

$manualRefunds = DB::table('wallet_transactions')
    ->where('transaction_type', 'add_fund_by_admin')
    ->where(function($query) {
        $query->where('reference', 'REGEXP', '[0-9]{5,}') // Contains order ID pattern
              ->orWhere('reference', 'LIKE', '%order%')
              ->orWhere('reference', 'LIKE', '%Replacement%')
              ->orWhere('reference', 'LIKE', '%Price Change%')
              ->orWhere('reference', 'LIKE', '%refund%');
    })
    ->orderBy('created_at', 'DESC')
    ->get();

echo "   Found: " . count($manualRefunds) . " manual refund transactions\n\n";

// 2. Extract order IDs from references
echo "📋 Step 2: Extracting order IDs from references...\n";

$manualRefundsByOrder = [];
$extractedCount = 0;

foreach ($manualRefunds as $refund) {
    // Try to extract order ID from reference
    if (preg_match('/\b(\d{5,})\b/', $refund->reference, $matches)) {
        $orderId = $matches[1];

        // Verify order exists
        $order = DB::table('orders')->where('id', $orderId)->first();

        if ($order) {
            if (!isset($manualRefundsByOrder[$orderId])) {
                $manualRefundsByOrder[$orderId] = [];
            }
            $manualRefundsByOrder[$orderId][] = $refund;
            $extractedCount++;
        }
    }
}

echo "   Extracted: $extractedCount refunds linked to " . count($manualRefundsByOrder) . " orders\n\n";

// 3. Check which orders should have gotten automatic refunds
echo "📋 Step 3: Checking orders that should have automatic refunds...\n";

$ordersWithReductions = DB::table('orders')
    ->where('adjustment_amount', '<', 0)
    ->where('created_at', '>=', '2025-01-01') // Only recent orders
    ->select('id', 'user_id', 'order_amount', 'original_order_amount', 'adjustment_amount',
             'wallet_refund_processed', 'payment_method', 'created_at', 'updated_at')
    ->get();

echo "   Found: " . count($ordersWithReductions) . " orders with reduced amounts\n";

$shouldHaveAutoRefund = [];
$missingAutoRefunds = 0;

foreach ($ordersWithReductions as $order) {
    $autoRefund = DB::table('wallet_transactions')
        ->where('transaction_type', 'order_refund')
        ->where('reference', $order->id)
        ->first();

    if (!$autoRefund) {
        $shouldHaveAutoRefund[$order->id] = $order;
        $missingAutoRefunds++;
    }
}

echo "   Missing automatic refunds: $missingAutoRefunds\n\n";

// 4. Identify duplicates and issues
echo "📋 Step 4: Identifying issues...\n\n";

$issues = [
    'duplicates' => [],
    'wrong_amounts' => [],
    'unnecessary_manual' => [],
    'missing_auto' => []
];

foreach ($manualRefundsByOrder as $orderId => $refunds) {
    $order = DB::table('orders')->where('id', $orderId)->first();

    if (!$order) continue;

    $autoRefund = DB::table('wallet_transactions')
        ->where('transaction_type', 'order_refund')
        ->where('reference', $orderId)
        ->first();

    // Calculate expected refund amount
    $expectedRefund = abs($order->adjustment_amount);
    $totalManualRefunds = array_sum(array_column($refunds, 'credit'));
    $autoRefundAmount = $autoRefund ? $autoRefund->credit : 0;
    $totalRefunded = $totalManualRefunds + $autoRefundAmount;

    // Check for duplicates (multiple manual refunds for same order)
    if (count($refunds) > 1) {
        $issues['duplicates'][$orderId] = [
            'order' => $order,
            'refunds' => $refunds,
            'count' => count($refunds),
            'total_manual' => $totalManualRefunds,
            'auto_refund' => $autoRefundAmount
        ];
    }

    // Check if automatic refund exists - manual one is unnecessary
    if ($autoRefund && $totalManualRefunds > 0) {
        $issues['unnecessary_manual'][$orderId] = [
            'order' => $order,
            'manual_refunds' => $refunds,
            'auto_refund' => $autoRefund,
            'total_manual' => $totalManualRefunds,
            'auto_amount' => $autoRefundAmount
        ];
    }

    // Check for wrong amounts
    if ($expectedRefund > 0 && abs($totalRefunded - $expectedRefund) > 1) {
        $issues['wrong_amounts'][$orderId] = [
            'order' => $order,
            'expected' => $expectedRefund,
            'actual' => $totalRefunded,
            'difference' => $totalRefunded - $expectedRefund,
            'refunds' => $refunds,
            'auto_refund' => $autoRefund
        ];
    }
}

// Add missing auto refunds
$issues['missing_auto'] = $shouldHaveAutoRefund;

// 5. Generate detailed report
echo "========================================\n";
echo "ANALYSIS RESULTS\n";
echo "========================================\n\n";

echo "🔴 DUPLICATE MANUAL REFUNDS: " . count($issues['duplicates']) . "\n";
if (count($issues['duplicates']) > 0) {
    foreach ($issues['duplicates'] as $orderId => $issue) {
        echo "   Order #$orderId: {$issue['count']} manual refunds totaling ₹{$issue['total_manual']}\n";
        foreach ($issue['refunds'] as $idx => $refund) {
            echo "      " . ($idx + 1) . ". ₹{$refund->credit} on {$refund->created_at} - {$refund->reference}\n";
        }
    }
}
echo "\n";

echo "🔴 UNNECESSARY MANUAL REFUNDS (Auto refund exists): " . count($issues['unnecessary_manual']) . "\n";
if (count($issues['unnecessary_manual']) > 0) {
    foreach ($issues['unnecessary_manual'] as $orderId => $issue) {
        echo "   Order #$orderId: Auto refund ₹{$issue['auto_amount']} exists, but also manual ₹{$issue['total_manual']}\n";
        echo "      🔹 Total over-refunded: ₹" . ($issue['total_manual'] + $issue['auto_amount'] - abs($issue['order']->adjustment_amount)) . "\n";
    }
}
echo "\n";

echo "🔴 WRONG REFUND AMOUNTS: " . count($issues['wrong_amounts']) . "\n";
if (count($issues['wrong_amounts']) > 0) {
    foreach ($issues['wrong_amounts'] as $orderId => $issue) {
        echo "   Order #$orderId: Expected ₹{$issue['expected']}, Got ₹{$issue['actual']} (Diff: ₹{$issue['difference']})\n";
    }
}
echo "\n";

echo "🔴 MISSING AUTOMATIC REFUNDS: " . count($issues['missing_auto']) . "\n";
if (count($issues['missing_auto']) > 0) {
    $total = 0;
    foreach ($issues['missing_auto'] as $orderId => $order) {
        $amount = abs($order->adjustment_amount);
        $total += $amount;
    }
    echo "   Total missing refunds: ₹$total across " . count($issues['missing_auto']) . " orders\n";
}
echo "\n";

// 6. Generate reversal recommendations
echo "========================================\n";
echo "REVERSAL RECOMMENDATIONS\n";
echo "========================================\n\n";

$reversalTransactions = [];
$totalToReverse = 0;

// Reverse all duplicate manual refunds (keep only the first one)
foreach ($issues['duplicates'] as $orderId => $issue) {
    for ($i = 1; $i < count($issue['refunds']); $i++) {
        $refund = $issue['refunds'][$i];
        $reversalTransactions[] = [
            'wallet_transaction_id' => $refund->id,
            'user_id' => $refund->user_id,
            'amount' => $refund->credit,
            'reason' => "Duplicate manual refund for order #$orderId (keeping first refund only)",
            'original_reference' => $refund->reference,
            'original_date' => $refund->created_at
        ];
        $totalToReverse += $refund->credit;
    }
}

// Reverse unnecessary manual refunds where auto refund exists
foreach ($issues['unnecessary_manual'] as $orderId => $issue) {
    foreach ($issue['manual_refunds'] as $refund) {
        $reversalTransactions[] = [
            'wallet_transaction_id' => $refund->id,
            'user_id' => $refund->user_id,
            'amount' => $refund->credit,
            'reason' => "Manual refund unnecessary - automatic refund already processed for order #$orderId",
            'original_reference' => $refund->reference,
            'original_date' => $refund->created_at
        ];
        $totalToReverse += $refund->credit;
    }
}

echo "💰 TOTAL TO REVERSE: ₹$totalToReverse\n";
echo "📝 TRANSACTIONS TO REVERSE: " . count($reversalTransactions) . "\n\n";

if (count($reversalTransactions) > 0) {
    echo "Transactions to be reversed:\n";
    foreach ($reversalTransactions as $idx => $rev) {
        echo "   " . ($idx + 1) . ". User #{$rev['user_id']} - ₹{$rev['amount']} - {$rev['reason']}\n";
    }
}

// 7. Save report to file
$reportFile = storage_path('logs/wallet_refund_analysis_' . date('Y-m-d_His') . '.json');
file_put_contents($reportFile, json_encode([
    'generated_at' => now(),
    'summary' => [
        'total_manual_refunds' => count($manualRefunds),
        'manual_with_order_ref' => $extractedCount,
        'orders_with_reductions' => count($ordersWithReductions),
        'missing_auto_refunds' => $missingAutoRefunds,
        'duplicate_refunds' => count($issues['duplicates']),
        'unnecessary_manual' => count($issues['unnecessary_manual']),
        'wrong_amounts' => count($issues['wrong_amounts']),
        'total_to_reverse' => $totalToReverse,
        'transactions_to_reverse' => count($reversalTransactions)
    ],
    'issues' => $issues,
    'reversals' => $reversalTransactions
], JSON_PRETTY_PRINT));

echo "\n📄 Full report saved to: $reportFile\n";

// 8. Generate reversal script
$reversalScriptFile = storage_path('logs/reversal_script_' . date('Y-m-d_His') . '.sql');
$sql = "-- WALLET REFUND REVERSAL SCRIPT\n";
$sql .= "-- Generated: " . now() . "\n";
$sql .= "-- Total to reverse: ₹$totalToReverse\n";
$sql .= "-- Transactions: " . count($reversalTransactions) . "\n\n";
$sql .= "START TRANSACTION;\n\n";

foreach ($reversalTransactions as $idx => $rev) {
    $sql .= "-- Reversal #" . ($idx + 1) . ": {$rev['reason']}\n";
    $sql .= "-- Original: {$rev['original_reference']} on {$rev['original_date']}\n";

    // Create reversal transaction
    $transactionId = \Illuminate\Support\Str::uuid();
    $reference = "REVERSAL of duplicate refund (Original: {$rev['original_reference']})";

    $sql .= "INSERT INTO wallet_transactions (user_id, transaction_id, credit, debit, admin_bonus, transaction_type, reference, created_at, updated_at)\n";
    $sql .= "SELECT {$rev['user_id']}, '$transactionId', 0, {$rev['amount']}, 0, 'refund_reversal', '$reference', NOW(), NOW();\n\n";

    // Update user wallet balance
    $sql .= "UPDATE users SET wallet_balance = wallet_balance - {$rev['amount']} WHERE id = {$rev['user_id']};\n\n";

    // Update wallet_transactions.balance for this reversal
    $sql .= "UPDATE wallet_transactions SET balance = (SELECT wallet_balance FROM users WHERE id = {$rev['user_id']}) WHERE transaction_id = '$transactionId';\n\n";
}

$sql .= "COMMIT;\n";

file_put_contents($reversalScriptFile, $sql);

echo "📄 Reversal SQL script saved to: $reversalScriptFile\n";

echo "\n========================================\n";
echo "⚠️  NEXT STEPS:\n";
echo "========================================\n";
echo "1. Review the full report: $reportFile\n";
echo "2. Review the reversal SQL: $reversalScriptFile\n";
echo "3. Run the reversal script if everything looks correct\n";
echo "4. Apply the database migration to fix wallet_refund_processed column\n";
echo "5. Update OrderController to fix automatic refund logic\n";
echo "6. Add validation to CustomerWalletController\n\n";

echo "✅ Analysis complete!\n\n";
