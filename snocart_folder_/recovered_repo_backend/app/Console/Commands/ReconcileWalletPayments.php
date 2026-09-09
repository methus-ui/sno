<?php

namespace App\Console\Commands;

use App\Models\WalletPayment;
use App\Models\PaymentRequest;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Razorpay\Api\Api;
use Carbon\Carbon;

class ReconcileWalletPayments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wallet:reconcile-payments {--days=1 : Number of days to look back} {--limit=50 : Max payments to check}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reconcile pending wallet payments with Razorpay API';

    private ?Api $api = null;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Initialize Razorpay API
        if (!$this->initializeRazorpayApi()) {
            $this->error('❌ Failed to initialize Razorpay API');
            return 1;
        }

        $days = (int) $this->option('days');
        $limit = (int) $this->option('limit');
        $cutoffDate = Carbon::now()->subDays($days);

        $this->info("Reconciling wallet payments from the last {$days} day(s)");
        $this->info("Checking payments created after: {$cutoffDate}");

        // Find pending wallet payments
        $pendingPayments = WalletPayment::where('payment_status', 'pending')
            ->where('created_at', '>=', $cutoffDate)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        if ($pendingPayments->isEmpty()) {
            $this->info('✅ No pending wallet payments found to reconcile');
            return 0;
        }

        $this->warn("Found {$pendingPayments->count()} pending payments to reconcile");

        $successCount = 0;
        $failedCount = 0;
        $expiredCount = 0;
        $unchangedCount = 0;

        $progressBar = $this->output->createProgressBar($pendingPayments->count());
        $progressBar->start();

        foreach ($pendingPayments as $payment) {
            try {
                $result = $this->reconcilePayment($payment);

                switch ($result) {
                    case 'success':
                        $successCount++;
                        break;
                    case 'failed':
                        $failedCount++;
                        break;
                    case 'expired':
                        $expiredCount++;
                        break;
                    default:
                        $unchangedCount++;
                }

                $progressBar->advance();

            } catch (\Exception $e) {
                $this->error("\n  ❌ Error reconciling payment ID {$payment->id}: {$e->getMessage()}");

                Log::error('Wallet payment reconciliation failed', [
                    'payment_id' => $payment->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->info("Reconciliation Summary:");
        $this->info("  ✅ Marked as Success: {$successCount}");
        $this->info("  ❌ Marked as Failed: {$failedCount}");
        $this->info("  ⏱️  Marked as Expired: {$expiredCount}");
        $this->info("  ➖ Unchanged (still pending): {$unchangedCount}");

        return 0;
    }

    /**
     * Initialize Razorpay API client
     */
    private function initializeRazorpayApi(): bool
    {
        try {
            $config = \DB::table('addon_settings')
                ->where('key_name', 'razor_pay')
                ->where('settings_type', 'payment_config')
                ->first();

            if (!$config) {
                return false;
            }

            $razor = false;
            if ($config->mode == 'live') {
                $razor = json_decode($config->live_values);
            } elseif ($config->mode == 'test') {
                $razor = json_decode($config->test_values);
            }

            if ($razor && isset($razor->api_key) && isset($razor->api_secret)) {
                $this->api = new Api($razor->api_key, $razor->api_secret);
                return true;
            }

            return false;

        } catch (\Exception $e) {
            Log::error('Razorpay API initialization failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Reconcile a single wallet payment
     */
    private function reconcilePayment(WalletPayment $payment): string
    {
        // Find corresponding payment request
        $paymentRequest = PaymentRequest::where('attribute', 'wallet_payments')
            ->where('attribute_id', $payment->id)
            ->first();

        if (!$paymentRequest || !$paymentRequest->transaction_id) {
            $this->line("\n  ⚠️  Payment ID {$payment->id} has no Razorpay order ID - skipping");
            return 'unchanged';
        }

        $razorpayOrderId = $paymentRequest->transaction_id;

        try {
            // Fetch order from Razorpay
            $razorpayOrder = $this->api->order->fetch($razorpayOrderId);

            // Check order status
            $status = $razorpayOrder['status'] ?? 'unknown';
            $amountPaid = ($razorpayOrder['amount_paid'] ?? 0) / 100; // Convert paise to rupees

            switch ($status) {
                case 'paid':
                    // Payment was successful - mark as success
                    $payment->payment_status = 'success';
                    $payment->save();

                    // Call success hook
                    if (function_exists('wallet_success')) {
                        wallet_success((object)[
                            'attribute_id' => $payment->id,
                            'payment_method' => 'razor_pay',
                            'payer_id' => $payment->user_id,
                            'payment_amount' => $payment->amount
                        ]);
                    }

                    $this->line("\n  ✅ Payment ID {$payment->id} marked as SUCCESS (amount: ₹{$amountPaid})");

                    Log::info('Wallet payment reconciled as success', [
                        'payment_id' => $payment->id,
                        'razorpay_order_id' => $razorpayOrderId,
                        'amount' => $amountPaid
                    ]);

                    return 'success';

                case 'attempted':
                    // Payment was attempted but not completed
                    $attempts = $razorpayOrder['attempts'] ?? 0;
                    $this->line("\n  ⏳ Payment ID {$payment->id} has {$attempts} attempts - keeping as pending");
                    return 'unchanged';

                case 'created':
                    // Order created but no payment attempt yet
                    $ageMinutes = Carbon::parse($payment->created_at)->diffInMinutes(Carbon::now());

                    if ($ageMinutes > 30) {
                        // Expire if older than 30 minutes
                        $payment->payment_status = 'expired';
                        $payment->save();

                        $this->line("\n  ⏱️  Payment ID {$payment->id} expired (no attempt after {$ageMinutes} minutes)");
                        return 'expired';
                    }

                    return 'unchanged';

                default:
                    $this->line("\n  ⚠️  Payment ID {$payment->id} has unknown status: {$status}");
                    return 'unchanged';
            }

        } catch (\Razorpay\Api\Errors\BadRequestError $e) {
            // Order not found or invalid
            $payment->payment_status = 'failed';
            $payment->save();

            // Call failure hook
            if (function_exists('wallet_failed')) {
                wallet_failed((object)[
                    'attribute_id' => $payment->id,
                    'payment_method' => 'razor_pay'
                ]);
            }

            $this->line("\n  ❌ Payment ID {$payment->id} marked as FAILED (order not found in Razorpay)");

            Log::warning('Wallet payment reconciled as failed', [
                'payment_id' => $payment->id,
                'razorpay_order_id' => $razorpayOrderId,
                'error' => $e->getMessage()
            ]);

            return 'failed';

        } catch (\Exception $e) {
            $this->error("\n  ❌ API error for payment ID {$payment->id}: {$e->getMessage()}");
            throw $e;
        }
    }
}
