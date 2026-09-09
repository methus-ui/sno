<?php

namespace App\Services;

use App\Models\DeliveryMan;
use App\Models\DeliverymanAttendance;
use App\CentralLogics\Helpers;
use Illuminate\Support\Facades\Log;

class OfflineNotificationService
{
    /**
     * Get offline threshold from settings
     */
    private function getOfflineThreshold(): int
    {
        $setting = \App\Models\BusinessSetting::where('key', 'dm_offline_threshold')->first();
        return $setting ? (json_decode($setting->value, true)['minutes'] ?? 5) : 5;
    }

    /**
     * Check and send appropriate notification based on offline time
     */
    public function checkAndNotify(int $deliveryManId): void
    {
        $threshold = $this->getOfflineThreshold();
        $notificationAt60Percent = round($threshold * 0.6);
        $notificationAt80Percent = round($threshold * 0.8);
        $dm = DeliveryMan::find($deliveryManId);
        if (!$dm || !$dm->fcm_token) {
            return;
        }

        $attendance = DeliverymanAttendance::where('delivery_man_id', $deliveryManId)
            ->whereDate('date', today())
            ->first();

        if (!$attendance) {
            return;
        }

        $offlineTime = $attendance->total_offline_minutes ?? 0;

        // Check which notification to send based on offline time
        if ($offlineTime >= $threshold && $attendance->incentive_eligible) {
            // First time exceeding threshold
            $this->sendThresholdExceededNotification($dm, $offlineTime, $threshold);
        } elseif ($offlineTime >= $notificationAt80Percent && $offlineTime < $threshold) {
            // At 80% - Critical warning
            $this->send80PercentWarning($dm, $offlineTime, $threshold);
        } elseif ($offlineTime >= $notificationAt60Percent && $offlineTime < $notificationAt80Percent) {
            // At 60% - First warning
            $this->send60PercentWarning($dm, $offlineTime, $threshold);
        }
    }

    /**
     * Send notification at 60%
     */
    private function send60PercentWarning(DeliveryMan $dm, int $offlineTime, int $threshold): void
    {
        $remaining = $threshold - $offlineTime;

        $title = translate('Offline Time Warning');
        $message = translate('You have been offline for :time minutes. Only :remaining minutes remaining before incentive eligibility is lost.', [
            'time' => $offlineTime,
            'remaining' => $remaining
        ]);

        $this->sendPushNotification($dm, $title, $message, 'warning', [
            'type' => 'offline_warning',
            'level' => '60_percent',
            'offline_minutes' => $offlineTime,
            'remaining_minutes' => $remaining,
            'threshold' => $threshold,
        ]);

        Log::info("Sent 60% warning to DM #{$dm->id}", [
            'offline_time' => $offlineTime,
            'remaining' => $remaining
        ]);
    }

    /**
     * Send notification at 80%
     */
    private function send80PercentWarning(DeliveryMan $dm, int $offlineTime, int $threshold): void
    {
        $remaining = $threshold - $offlineTime;

        $title = translate('Critical: Offline Time Alert');
        $message = translate('⚠️ URGENT: You have only :remaining minute(s) of offline time left! Come back online now to stay eligible for incentives.', [
            'remaining' => $remaining
        ]);

        $this->sendPushNotification($dm, $title, $message, 'critical', [
            'type' => 'offline_warning',
            'level' => '80_percent',
            'offline_minutes' => $offlineTime,
            'remaining_minutes' => $remaining,
            'threshold' => $threshold,
        ]);

        Log::warning("Sent 80% critical warning to DM #{$dm->id}", [
            'offline_time' => $offlineTime,
            'remaining' => $remaining
        ]);
    }

    /**
     * Send notification when threshold exceeded
     */
    private function sendThresholdExceededNotification(DeliveryMan $dm, int $offlineTime, int $threshold): void
    {
        $title = translate('Incentive Eligibility Lost');
        $message = translate('❌ You have exceeded the offline time limit (:time minutes). You are no longer eligible for today\'s incentives. Total offline time: :total minutes.', [
            'time' => $threshold,
            'total' => $offlineTime
        ]);

        $this->sendPushNotification($dm, $title, $message, 'exceeded', [
            'type' => 'offline_exceeded',
            'offline_minutes' => $offlineTime,
            'threshold' => $threshold,
            'incentive_eligible' => false,
        ]);

        Log::error("Sent threshold exceeded notification to DM #{$dm->id}", [
            'offline_time' => $offlineTime,
            'threshold' => $threshold
        ]);
    }

    /**
     * Send push notification via FCM
     */
    private function sendPushNotification(DeliveryMan $dm, string $title, string $message, string $priority, array $data = []): void
    {
        try {
            // Use the existing Helpers::send_push_notif_to_device method if available
            // or implement FCM directly

            if (method_exists(Helpers::class, 'send_push_notif_to_device')) {
                Helpers::send_push_notif_to_device($dm->fcm_token, [
                    'title' => $title,
                    'body' => $message,
                    'data' => array_merge($data, [
                        'priority' => $priority,
                        'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                        'sound' => 'default',
                    ])
                ]);
            } else {
                // Fallback to direct FCM call
                $this->sendFcmNotification($dm->fcm_token, $title, $message, $data);
            }

            // Also store in database for notification history
            $this->storeNotificationHistory($dm->id, $title, $message, $priority, $data);

        } catch (\Exception $e) {
            Log::error("Failed to send notification to DM #{$dm->id}: " . $e->getMessage());
        }
    }

    /**
     * Send FCM notification directly
     */
    private function sendFcmNotification(string $fcmToken, string $title, string $message, array $data): void
    {
        $url = 'https://fcm.googleapis.com/fcm/send';
        $serverKey = env('FCM_SERVER_KEY');

        if (!$serverKey) {
            Log::warning('FCM_SERVER_KEY not configured');
            return;
        }

        $notification = [
            'title' => $title,
            'body' => $message,
            'sound' => 'default',
            'badge' => 1,
        ];

        $fields = [
            'to' => $fcmToken,
            'notification' => $notification,
            'data' => $data,
            'priority' => 'high',
        ];

        $headers = [
            'Authorization: key=' . $serverKey,
            'Content-Type: application/json',
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));

        $result = curl_exec($ch);

        if ($result === false) {
            Log::error('FCM notification failed: ' . curl_error($ch));
        } else {
            Log::info('FCM notification sent', ['response' => $result]);
        }

        curl_close($ch);
    }

    /**
     * Store notification in database for history
     */
    private function storeNotificationHistory(int $deliveryManId, string $title, string $message, string $priority, array $data): void
    {
        try {
            \DB::table('user_notifications')->insert([
                'data' => json_encode([
                    'title' => $title,
                    'description' => $message,
                    'type' => 'offline_warning',
                    'priority' => $priority,
                    'data' => $data,
                ]),
                'delivery_man_id' => $deliveryManId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::warning("Failed to store notification history: " . $e->getMessage());
        }
    }

    /**
     * Send summary notification at end of day
     */
    public function sendDailySummary(int $deliveryManId): void
    {
        $dm = DeliveryMan::find($deliveryManId);
        if (!$dm || !$dm->fcm_token) {
            return;
        }

        $attendance = DeliverymanAttendance::where('delivery_man_id', $deliveryManId)
            ->whereDate('date', today())
            ->first();

        if (!$attendance) {
            return;
        }

        $offlineTime = $attendance->total_offline_minutes ?? 0;
        $eligible = $attendance->incentive_eligible ?? true;

        if ($eligible) {
            $title = translate('Daily Summary - Good Performance! ✅');
            $message = translate('You stayed online today! Total offline time: :time minutes. You are eligible for all incentives.', [
                'time' => $offlineTime
            ]);
        } else {
            $title = translate('Daily Summary - Incentives Lost ❌');
            $message = translate('You exceeded offline limit today. Total offline time: :time minutes. Incentives were not awarded.', [
                'time' => $offlineTime
            ]);
        }

        $this->sendPushNotification($dm, $title, $message, 'summary', [
            'type' => 'daily_summary',
            'offline_minutes' => $offlineTime,
            'incentive_eligible' => $eligible,
            'offline_count' => $attendance->offline_count ?? 0,
        ]);
    }

    /**
     * Send notification when DM comes back online after being offline
     */
    public function sendBackOnlineNotification(int $deliveryManId, int $sessionDuration, int $totalOfflineTime): void
    {
        $dm = DeliveryMan::find($deliveryManId);
        if (!$dm || !$dm->fcm_token) {
            return;
        }

        $threshold = $this->getOfflineThreshold();
        $remaining = $threshold - $totalOfflineTime;

        if ($remaining > 0) {
            $title = translate('Back Online');
            $message = translate('Welcome back! You were offline for :session minutes. Total offline today: :total minutes. You have :remaining minutes remaining.', [
                'session' => $sessionDuration,
                'total' => $totalOfflineTime,
                'remaining' => $remaining
            ]);

            $this->sendPushNotification($dm, $title, $message, 'info', [
                'type' => 'back_online',
                'session_duration' => $sessionDuration,
                'total_offline_minutes' => $totalOfflineTime,
                'remaining_minutes' => $remaining,
            ]);
        } else {
            $title = translate('Back Online - Limit Exceeded');
            $message = translate('You are back online. However, you have exceeded the offline limit (:total minutes). Incentives will not be awarded today.', [
                'total' => $totalOfflineTime
            ]);

            $this->sendPushNotification($dm, $title, $message, 'warning', [
                'type' => 'back_online_exceeded',
                'total_offline_minutes' => $totalOfflineTime,
                'incentive_eligible' => false,
            ]);
        }
    }
}
