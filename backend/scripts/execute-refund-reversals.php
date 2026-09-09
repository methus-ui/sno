<?php

/**
 * Execute Wallet Refund Reversals
 *
 * ⚠️ WARNING: This script will DEDUCT money from customer wallets!
 * ⚠️ Only run this after reviewing the analysis report!
 *
 * This script:
 * 1. Reads the reversal recommendations from the analysis
 * 2. Shows a summary and asks for confirmation
 * 3. Executes the reversals with full transaction safety
 * 4. Creates audit log of all actions
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

echo "\n";
echo "╔══════════════════════════════════════════════════════════╗\n";
echo "║  WALLET REFUND REVERSAL EXECUTION                        ║\n";
echo "║  ⚠️  WARNING: This will DEDUCT money from wallets!      ║\n";
echo "╚══════════════════════════════════════════════════════════╝\n";
echo "\n";

// Find the most recent analysis report
$reportsDir = storage_path('logs');
$analysisFiles = glob($reportsDir . '/wallet_refund_analysis_*.json');

if (empty($analysisFiles)) {
    echo "❌ ERROR: No analysis report found!\n";
    echo "   Please run: php scripts/analyze-wrong-refunds.php first\n\n";
    exit(1);
}

// Get the most recent report
$latestReport = end($analysisFiles);
$reportData = json_decode(file_get_contents($latestReport), true);

echo "📄 Using analysis report: " . basename($latestReport) . "\n";
echo "   Generated: {$reportData['generated_at']}\n\n";

// Display summary
$summary = $reportData['summary'];
echo "═══════════════════════════════════════════════════════════\n";
echo "REVERSAL SUMMARY\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "  Transactions to reverse: {$summary['transactions_to_reverse']}\n";
echo "  Total amount: ₹{$summary['total_to_reverse']}\n";
echo "═══════════════════════════════════════════════════════════\n\n";

if ($summary['transactions_to_reverse'] == 0) {
    echo "✅ No reversals needed!\n\n";
    exit(0);
}

// Show details of each reversal
echo "Reversals to execute:\n\n";
foreach ($reportData['reversals'] as $idx => $reversal) {
    echo "  " . ($idx + 1) . ". User #{$reversal['user_id']}\n";
    echo "     Amount: ₹{$reversal['amount']}\n";
    echo "     Reason: {$reversal['reason']}\n";
    echo "     Original: {$reversal['original_reference']} ({$reversal['original_date']})\n\n";
}

// Ask for confirmation
echo "═══════════════════════════════════════════════════════════\n";
echo "⚠️  CONFIRMATION REQUIRED\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "This will:\n";
echo "  1. Deduct ₹{$summary['total_to_reverse']} from {$summary['transactions_to_reverse']} customers\n";
echo "  2. Create reversal transactions in wallet_transactions table\n";
echo "  3. Update customer wallet balances\n";
echo "  4. Create audit log\n\n";

echo "Type 'YES' to proceed with reversals (or anything else to cancel): ";
$confirmation = trim(fgets(STDIN));

if (strtoupper($confirmation) !== 'YES') {
    echo "\n❌ Reversal cancelled by user.\n\n";
    exit(0);
}

echo "\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "EXECUTING REVERSALS...\n";
echo "═══════════════════════════════════════════════════════════\n\n";

DB::beginTransaction();

try {
    $successCount = 0;
    $errors = [];
    $auditLog = [];

    foreach ($reportData['reversals'] as $idx => $reversal) {
        echo "Processing reversal " . ($idx + 1) . "/{$summary['transactions_to_reverse']}...\n";

        // Get current user wallet balance
        $user = DB::table('users')->where('id', $reversal['user_id'])->first();

        if (!$user) {
            $errors[] = "User #{$reversal['user_id']} not found!";
            echo "   ❌ ERROR: User not found\n";
            continue;
        }

        $currentBalance = $user->wallet_balance;
        $newBalance = $currentBalance - $reversal['amount'];

        if ($newBalance < 0) {
            echo "   ⚠️  WARNING: User #{$reversal['user_id']} will have negative balance: ₹{$newBalance}\n";
            // Continue anyway - negative balance is acceptable in this case
        }

        // Create reversal transaction
        $transactionId = Str::uuid();
        $reference = "REVERSAL: {$reversal['reason']} | Original ref: {$reversal['original_reference']}";

        DB::table('wallet_transactions')->insert([
            'user_id' => $reversal['user_id'],
            'transaction_id' => $transactionId,
            'credit' => 0,
            'debit' => $reversal['amount'],
            'admin_bonus' => 0,
            'balance' => $newBalance,
            'transaction_type' => 'refund_reversal',
            'reference' => $reference,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Update user wallet balance
        DB::table('users')
            ->where('id', $reversal['user_id'])
            ->update(['wallet_balance' => $newBalance]);

        $successCount++;
        $auditLog[] = [
            'user_id' => $reversal['user_id'],
            'amount_reversed' => $reversal['amount'],
            'old_balance' => $currentBalance,
            'new_balance' => $newBalance,
            'transaction_id' => $transactionId,
            'original_transaction' => $reversal['wallet_transaction_id'],
            'reason' => $reversal['reason'],
            'executed_at' => now()->toDateTimeString(),
        ];

        echo "   ✅ Reversed ₹{$reversal['amount']} (Balance: ₹{$currentBalance} → ₹{$newBalance})\n";
    }

    if (count($errors) > 0) {
        echo "\n⚠️  ERRORS ENCOUNTERED:\n";
        foreach ($errors as $error) {
            echo "   - $error\n";
        }
        echo "\nType 'COMMIT' to commit anyway, or anything else to rollback: ";
        $commitConfirm = trim(fgets(STDIN));

        if (strtoupper($commitConfirm) !== 'COMMIT') {
            DB::rollBack();
            echo "\n❌ Transaction rolled back.\n\n";
            exit(1);
        }
    }

    DB::commit();

    echo "\n";
    echo "═══════════════════════════════════════════════════════════\n";
    echo "✅ SUCCESS!\n";
    echo "═══════════════════════════════════════════════════════════\n";
    echo "  Reversals executed: $successCount\n";
    echo "  Total amount reversed: ₹{$summary['total_to_reverse']}\n";
    echo "  Errors: " . count($errors) . "\n";
    echo "═══════════════════════════════════════════════════════════\n\n";

    // Save audit log
    $auditFile = storage_path('logs/refund_reversal_audit_' . date('Y-m-d_His') . '.json');
    file_put_contents($auditFile, json_encode([
        'executed_at' => now(),
        'analysis_report' => basename($latestReport),
        'summary' => $summary,
        'reversals_executed' => $successCount,
        'errors' => $errors,
        'audit_log' => $auditLog,
    ], JSON_PRETTY_PRINT));

    echo "📄 Audit log saved to: $auditFile\n\n";

    echo "═══════════════════════════════════════════════════════════\n";
    echo "NEXT STEPS:\n";
    echo "═══════════════════════════════════════════════════════════\n";
    echo "1. ✅ Reversals complete\n";
    echo "2. Run migration: php artisan migrate\n";
    echo "3. Clear caches: php artisan cache:clear && php artisan config:clear\n";
    echo "4. Test automatic refunds on a test order\n";
    echo "5. Monitor logs for any issues\n\n";

} catch (\Exception $e) {
    DB::rollBack();

    echo "\n";
    echo "═══════════════════════════════════════════════════════════\n";
    echo "❌ FATAL ERROR!\n";
    echo "═══════════════════════════════════════════════════════════\n";
    echo "  {$e->getMessage()}\n";
    echo "  File: {$e->getFile()}:{$e->getLine()}\n";
    echo "═══════════════════════════════════════════════════════════\n\n";
    echo "Transaction has been rolled back. No changes were made.\n\n";

    exit(1);
}
