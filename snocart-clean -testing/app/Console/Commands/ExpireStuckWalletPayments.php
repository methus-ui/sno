<?php

namespace App\Console\Commands;

use App\Models\WalletPayment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ExpireStuckWalletPayments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wallet:expire-stuck-payments {--timeout=30 : Timeout in minutes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Auto-expire wallet payments stuck in pending status for more than specified timeout';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $timeoutMinutes = (int) $this->option('timeout');
        $cutoffTime = Carbon::now()->subMinutes($timeoutMinutes);

        $this->info("Checking for wallet payments pending since before: {$cutoffTime}");

        // Find stuck pending payments
        $stuckPayments = WalletPayment::where('payment_status', 'pending')
            ->where('created_at', '<', $cutoffTime)
            ->get();

        if ($stuckPayments->isEmpty()) {
            $this->info('✅ No stuck payments found');
            return 0;
        }

        $this->warn("Found {$stuckPayments->count()} stuck payments");

        $expiredCount = 0;
        $failedCount = 0;

        foreach ($stuckPayments as $payment) {
            try {
                // Calculate how long payment has been stuck
                $stuckDuration = Carbon::parse($payment->created_at)->diffInMinutes(Carbon::now());

                // Update payment status to expired
                $payment->payment_status = 'expired';
                $payment->save();

                $expiredCount++;

                $this->line("  ⏱️  Expired payment ID {$payment->id} (stuck for {$stuckDuration} minutes)");

                Log::info('Wallet payment auto-expired', [
                    'payment_id' => $payment->id,
                    'user_id' => $payment->user_id,
                    'amount' => $payment->amount,
                    'stuck_duration_minutes' => $stuckDuration,
                    'created_at' => $payment->created_at
                ]);

            } catch (\Exception $e) {
                $this->error("  ❌ Failed to expire payment ID {$payment->id}: {$e->getMessage()}");
                $failedCount++;

                Log::error('Failed to expire stuck wallet payment', [
                    'payment_id' => $payment->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        $this->newLine();
        $this->info("Summary:");
        $this->info("  ✅ Expired: {$expiredCount}");

        if ($failedCount > 0) {
            $this->warn("  ❌ Failed: {$failedCount}");
        }

        return 0;
    }
}
