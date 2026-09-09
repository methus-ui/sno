<?php

namespace App\Console\Commands;

use App\Models\DeliveryQRPayment;
use App\Models\DeliveryMan;
use App\Models\DeliveryManWallet;
use App\Models\AccountTransaction;
use App\Models\Notification;
use App\Models\Order;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessQRPaymentManually extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'qr:process-payment {qr_payment_id : The QR payment ID to process}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manually process QR payment and update delivery man wallet';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $qrPaymentId = $this->argument('qr_payment_id');

        $qrPayment = DeliveryQRPayment::find($qrPaymentId);

        if (!$qrPayment) {
            $this->error("QR Payment #$qrPaymentId not found");
            return 1;
        }

        if (!$qrPayment->isPaid()) {
            $this->error("QR Payment #$qrPaymentId is not paid yet");
            return 1;
        }

        $this->info("Processing QR Payment #$qrPaymentId");
        $this->info("Order: #{$qrPayment->order_id}");
        $this->info("Delivery Man: #{$qrPayment->delivery_man_id}");
        $this->info("Amount: ₹{$qrPayment->amount}");
        $this->info("Paid At: {$qrPayment->paid_at}");

        if (!$this->confirm('Do you want to process this payment?', true)) {
            $this->info('Cancelled');
            return 0;
        }

        try {
            DB::beginTransaction();

            $deliveryMan = DeliveryMan::find($qrPayment->delivery_man_id);
            $order = Order::find($qrPayment->order_id);

            if (!$deliveryMan) {
                throw new \Exception("Delivery man not found");
            }

            // Check if already processed
            $existing = AccountTransaction::where('from_type', 'deliveryman')
                ->where('from_id', $deliveryMan->id)
                ->where('method', 'qr_payment')
                ->where('ref', 'LIKE', '%Order #' . $qrPayment->order_id . '%')
                ->first();

            if ($existing) {
                $this->warn("⚠ This payment appears to have already been processed!");
                $this->warn("Transaction ID: {$existing->id}");
                $this->warn("Created: {$existing->created_at}");

                if (!$this->confirm('Process anyway?', false)) {
                    $this->info('Cancelled');
                    DB::rollBack();
                    return 0;
                }
            }

            // 1. Update delivery man wallet
            $wallet = DeliveryManWallet::firstOrCreate(
                ['delivery_man_id' => $deliveryMan->id],
                [
                    'total_earning' => 0,
                    'total_withdrawn' => 0,
                    'pending_withdraw' => 0,
                    'collected_cash' => 0,
                ]
            );

            $oldEarning = $wallet->total_earning ?? 0;
            $wallet->total_earning = $oldEarning + $qrPayment->amount;
            $wallet->pending_withdraw = ($wallet->pending_withdraw ?? 0) + $qrPayment->amount;
            $wallet->save();

            $this->info("✓ Wallet updated:");
            $this->info("  Old earning: ₹{$oldEarning}");
            $this->info("  New earning: ₹{$wallet->total_earning}");

            // 2. Create account transaction
            $transaction = AccountTransaction::create([
                'from_type' => 'deliveryman',
                'from_id' => $deliveryMan->id,
                'current_balance' => $wallet->total_earning - $wallet->total_withdrawn,
                'amount' => $qrPayment->amount,
                'method' => 'qr_payment',
                'ref' => 'QR Payment for Order #' . $qrPayment->order_id,
                'type' => 'collected',
                'created_by' => 'manual_command',
            ]);

            $this->info("✓ Transaction created (ID: {$transaction->id})");

            // 3. Update order payment method and status
            if ($order) {
                $oldMethod = $order->payment_method;
                $oldStatus = $order->payment_status;

                $order->payment_method = 'qr_payment_at_delivery';
                $order->payment_status = 'paid';
                $order->save();

                $this->info("✓ Order updated:");
                $this->info("  Payment Method: {$oldMethod} → qr_payment_at_delivery");
                $this->info("  Payment Status: {$oldStatus} → paid");
            }

            // 4. Send notification
            try {
                $notification = Notification::create([
                    'title' => 'Payment Received',
                    'description' => 'QR payment of ₹' . number_format($qrPayment->amount, 2) . ' received for Order #' . $qrPayment->order_id,
                    'image' => null,
                    'status' => 1,
                    'module_id' => $order->module_id ?? null,
                    'zone_id' => $deliveryMan->zone_id ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('user_notifications')->insert([
                    'data' => json_encode([
                        'title' => 'Payment Received',
                        'description' => 'QR payment of ₹' . number_format($qrPayment->amount, 2) . ' received for Order #' . $qrPayment->order_id,
                        'image' => null,
                        'order_id' => $qrPayment->order_id,
                        'type' => 'qr_payment',
                    ]),
                    'delivery_man_id' => $deliveryMan->id,
                    'notification_id' => $notification->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $this->info("✓ Notification sent (ID: {$notification->id})");

            } catch (\Exception $e) {
                $this->warn("⚠ Failed to send notification: " . $e->getMessage());
            }

            DB::commit();

            $this->info("\n✓✓ Payment processed successfully!");
            $this->info("Delivery man #{$deliveryMan->id} wallet updated with ₹{$qrPayment->amount}");

            return 0;

        } catch (\Exception $e) {
            DB::rollBack();

            $this->error("✗ Failed to process payment:");
            $this->error($e->getMessage());

            Log::error('Manual QR payment processing failed', [
                'qr_payment_id' => $qrPaymentId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return 1;
        }
    }
}
