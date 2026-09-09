<?php

namespace App\Console\Commands;

use App\Traits\NotificationTrait;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendWeeklyBroadcastNotification extends Command
{
    use NotificationTrait;

    protected $signature = 'notification:weekly-broadcast';
    protected $description = 'Send weekly broadcast notification to all customers via topic';

    public function handle()
    {
        Log::info('SendWeeklyBroadcastNotification triggered at ' . now());

        $data = [
            'title' => 'Weekend is here! 🎉',
            'description' => "Check out this week's top picks and treat yourself to something delicious!",
            'image' => '',
        ];

        try {
            self::sendPushNotificationToTopic($data, 'all_zone_customer', 'weekly_broadcast');
            $this->info('Weekly broadcast sent to topic: all_zone_customer');
            Log::info('Weekly broadcast notification sent successfully');
        } catch (\Exception $e) {
            $this->error('Failed: ' . $e->getMessage());
            Log::error('Weekly broadcast failed: ' . $e->getMessage());
        }
    }
}
