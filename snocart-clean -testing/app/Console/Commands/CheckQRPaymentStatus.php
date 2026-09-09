<?php

namespace App\Console\Commands;

use App\Models\DeliveryQRPayment;
use App\Services\RazorpayQRService;
use Illuminate\Console\Command;

class CheckQRPaymentStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'qr:check-payment-status {qr_payment_id? : The QR payment ID to check}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check and update QR payment status from Razorpay';

    private RazorpayQRService $qrService;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(RazorpayQRService $qrService)
    {
        parent::__construct();
        $this->qrService = $qrService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $qrPaymentId = $this->argument('qr_payment_id');

        if ($qrPaymentId) {
            // Check specific payment
            return $this->checkSinglePayment($qrPaymentId);
        }

        // Check all pending payments
        return $this->checkAllPendingPayments();
    }

    /**
     * Check a single QR payment
     */
    private function checkSinglePayment($qrPaymentId)
    {
        $qrPayment = DeliveryQRPayment::find($qrPaymentId);

        if (!$qrPayment) {
            $this->error("QR Payment #$qrPaymentId not found");
            return 1;
        }

        $this->info("Checking QR Payment #$qrPaymentId (Order #{$qrPayment->order_id})");
        $this->info("Current Status: {$qrPayment->status}");
        $this->info("Razorpay QR ID: {$qrPayment->razorpay_qr_id}");

        if ($qrPayment->isPaid()) {
            $this->info("✓ Already marked as PAID");
            $this->info("Payment ID: {$qrPayment->razorpay_payment_id}");
            $this->info("Paid At: {$qrPayment->paid_at}");
            return 0;
        }

        // Check with Razorpay
        $this->info("\nChecking with Razorpay API...");
        $result = $this->qrService->checkQRStatus($qrPayment->razorpay_qr_id);

        if (!$result['success']) {
            $this->error("Failed to check status: " . ($result['message'] ?? 'Unknown error'));
            return 1;
        }

        $this->info("Razorpay Status: " . $result['status']);
        $this->info("Payments Received: " . ($result['is_paid'] ? 'YES' : 'NO'));

        if ($result['is_paid']) {
            $payments = $result['payments'];
            $latestPayment = end($payments);

            $this->info("\n✓ PAYMENT FOUND!");
            $this->info("Payment ID: {$latestPayment['id']}");
            $this->info("Amount: ₹" . ($latestPayment['amount'] / 100));

            // Update our database
            $qrPayment->markAsPaid($latestPayment['id']);

            $this->info("\n✓✓ Database updated successfully!");
            $this->info("Payment marked as PAID in database");
        } else {
            $this->warn("\n⚠ No payment received yet");
        }

        return 0;
    }

    /**
     * Check all pending QR payments
     */
    private function checkAllPendingPayments()
    {
        $this->info("Checking all pending QR payments...\n");

        $pendingPayments = DeliveryQRPayment::pending()
            ->orderBy('created_at', 'desc')
            ->get();

        if ($pendingPayments->isEmpty()) {
            $this->info("No pending QR payments found");
            return 0;
        }

        $this->info("Found {$pendingPayments->count()} pending payment(s)\n");

        $updated = 0;
        $failed = 0;

        foreach ($pendingPayments as $qrPayment) {
            $this->line("─────────────────────────────────────────────");
            $this->info("QR Payment #{$qrPayment->id} - Order #{$qrPayment->order_id}");
            $this->info("Amount: ₹{$qrPayment->amount}");
            $this->info("Created: {$qrPayment->created_at->diffForHumans()}");

            // Check if expired
            if ($qrPayment->isExpired()) {
                $this->warn("⚠ EXPIRED - Skipping");
                continue;
            }

            // Check with Razorpay
            $result = $this->qrService->checkQRStatus($qrPayment->razorpay_qr_id);

            if (!$result['success']) {
                $this->error("✗ Failed to check: " . ($result['message'] ?? 'Unknown error'));
                $failed++;
                continue;
            }

            if ($result['is_paid']) {
                $payments = $result['payments'];
                $latestPayment = end($payments);

                $qrPayment->markAsPaid($latestPayment['id']);

                $this->info("✓ PAID - Updated (Payment: {$latestPayment['id']})");
                $updated++;
            } else {
                $this->line("○ Pending - No payment yet");
            }
        }

        $this->line("─────────────────────────────────────────────");
        $this->info("\nSummary:");
        $this->info("Total Checked: {$pendingPayments->count()}");
        $this->info("Updated: $updated");
        $this->info("Failed: $failed");

        return 0;
    }
}
