<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DeliveryMan;
use App\CentralLogics\Helpers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SendRushTimeNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notification:rush-time {--threshold=8 : Minimum hot carts to trigger notification}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send push notification to delivery boys when there are more than 8 hot carts (rush time)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $threshold = (int) $this->option('threshold');

        $this->info("Checking for hot carts (threshold: {$threshold})...");

        // Count hot carts (carts created in last 5 minutes)
        $hotCartsCount = DB::table('carts')
            ->where('created_at', '>=', now()->subMinutes(5))
            ->whereNotNull('user_id')
            ->distinct('user_id')
            ->count('user_id');

        $this->info("Hot carts found: {$hotCartsCount}");

        if ($hotCartsCount < $threshold) {
            $this->info("Not enough hot carts for rush time notification. Skipping.");
            return 0;
        }

        // Check if we already sent a notification recently (prevent spam)
        $cacheKey = 'rush_time_notification_sent';
        if (Cache::has($cacheKey)) {
            $this->warn("Rush time notification already sent recently. Skipping to prevent spam.");
            return 0;
        }

        $this->info("Rush time detected! Sending notifications to delivery boys...");

        // Get count of pending/unassigned orders
        $pendingOrders = DB::table('orders')
            ->whereIn('order_status', ['pending', 'confirmed'])
            ->whereNull('delivery_man_id')
            ->whereDate('created_at', today())
            ->count();

        // Prepare notification data
        $notificationData = [
            'title' => 'Rush Time Alert!',
            'description' => "High demand detected! {$hotCartsCount} customers are ordering right now" .
                           ($pendingOrders > 0 ? " and {$pendingOrders} orders waiting for pickup." : ".") .
                           " Get ready for incoming orders!",
            'image' => '',
            'order_id' => '',
            'type' => 'rush_alert',
        ];

        // Get all active delivery men with FCM tokens
        $deliveryMen = DeliveryMan::where('status', 1)
            ->where('active', 1) // Only online delivery men
            ->whereNotNull('fcm_token')
            ->where('fcm_token', '!=', '')
            ->get();

        $successCount = 0;
        $failCount = 0;

        foreach ($deliveryMen as $dm) {
            try {
                if ($dm->fcm_token) {
                    $result = Helpers::send_push_notif_to_device($dm->fcm_token, $notificationData);
                    if ($result !== false) {
                        $successCount++;
                        $this->line("  ✓ Sent to DM#{$dm->id} ({$dm->f_name})");
                    } else {
                        $failCount++;
                        $this->line("  ✗ Failed DM#{$dm->id} ({$dm->f_name}) - FCM returned false");
                    }
                }
            } catch (\Exception $e) {
                $failCount++;
                $this->line("  ✗ Failed DM#{$dm->id} ({$dm->f_name}) - " . $e->getMessage());
                Log::error("Failed to send rush notification to DM#{$dm->id}: " . $e->getMessage());
            }
        }

        // Set cache to prevent sending again for 15 minutes
        Cache::put($cacheKey, true, now()->addMinutes(15));

        $this->info("Rush time notifications sent: {$successCount} success, {$failCount} failed");
        Log::info("Rush Time Alert sent. Hot carts: {$hotCartsCount}, Pending orders: {$pendingOrders}, Notified: {$successCount} delivery men");

        return 0;
    }
}
