<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DeliveryMan;
use App\Models\DeliveryTrackingStat;
use App\CentralLogics\Helpers;
use Illuminate\Support\Facades\Log;

class SendDeliveryBoyOfDayNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notification:delivery-boy-of-day';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send push notification announcing Delivery Boy of the Day to all delivery boys';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Finding Delivery Boy of the Day...');

        // Get delivery boy with least idle time today (at least 1 order)
        $topPerformer = DeliveryTrackingStat::select('delivery_man_id')
            ->selectRaw('SUM(total_idle_seconds) as total_idle')
            ->selectRaw('COUNT(*) as order_count')
            ->whereDate('created_at', today())
            ->groupBy('delivery_man_id')
            ->having('order_count', '>=', 1)
            ->orderBy('total_idle', 'asc')
            ->first();

        if (!$topPerformer) {
            $this->warn('No delivery tracking data found for today. Skipping notification.');
            Log::info('Delivery Boy of the Day: No tracking data found for today');
            return 0;
        }

        $deliveryBoyOfDay = DeliveryMan::find($topPerformer->delivery_man_id);

        if (!$deliveryBoyOfDay) {
            $this->error('Delivery man not found.');
            return 1;
        }

        $winnerName = $deliveryBoyOfDay->f_name . ' ' . $deliveryBoyOfDay->l_name;
        $idleTime = DeliveryTrackingStat::formatDuration($topPerformer->total_idle);
        $orderCount = $topPerformer->order_count;

        $this->info("Delivery Boy of the Day: {$winnerName}");
        $this->info("Orders: {$orderCount}, Idle Time: {$idleTime}");

        // Get current hour for context
        $currentHour = now()->format('g A'); // e.g., "2 PM"

        // Prepare notification data
        $notificationData = [
            'title' => "Top Performer Update - {$currentHour}",
            'description' => "{$winnerName} is currently leading as today's top performer! Keep pushing everyone!",
            'image' => '',
            'order_id' => '',
            'type' => 'announcement',
        ];

        // Special message for the winner
        $winnerNotification = [
            'title' => "You're Leading! - {$currentHour}",
            'description' => "Great job {$deliveryBoyOfDay->f_name}! You're currently the top performer today with {$orderCount} orders and only {$idleTime} idle time. Keep it up!",
            'image' => '',
            'order_id' => '',
            'type' => 'achievement',
        ];

        // Get all active delivery men
        $deliveryMen = DeliveryMan::where('status', 1)
            ->whereNotNull('fcm_token')
            ->where('fcm_token', '!=', '')
            ->get();

        $successCount = 0;
        $failCount = 0;

        foreach ($deliveryMen as $dm) {
            try {
                // Send special message to winner, regular to others
                $data = ($dm->id == $deliveryBoyOfDay->id) ? $winnerNotification : $notificationData;

                if ($dm->fcm_token) {
                    Helpers::send_push_notif_to_device($dm->fcm_token, $data);
                    $successCount++;

                    // Save notification to database
                    \DB::table('user_notifications')->insert([
                        'data' => json_encode($data),
                        'user_id' => null,
                        'delivery_man_id' => $dm->id,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
            } catch (\Exception $e) {
                $failCount++;
                Log::error("Failed to send notification to DM#{$dm->id}: " . $e->getMessage());
            }
        }

        $this->info("Notifications sent: {$successCount} success, {$failCount} failed");
        Log::info("Delivery Boy of the Day notification sent. Winner: {$winnerName}, Sent: {$successCount}, Failed: {$failCount}");

        return 0;
    }
}
