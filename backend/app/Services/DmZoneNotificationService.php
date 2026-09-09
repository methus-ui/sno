<?php

namespace App\Services;

use App\Models\DeliveryMan;
use App\Models\DmZoneNotificationLog;
use App\Models\Order;
use App\CentralLogics\Helpers;

class DmZoneNotificationService
{
    public function notifyZoneDmsAboutNewOrder(Order $order): int
    {
        if (!$order->zone_id) return 0;

        $dms = $this->getAvailableDmsInZone($order->zone_id);

        if ($dms->isEmpty()) return 0;

        $data = [
            'title' => 'New Order Available!',
            'description' => "A new order #{$order->id} is available in your zone. Accept now!",
            'type' => 'new_order',
            'order_id' => $order->id,
            'order_type' => $order->order_type,
        ];

        $notifiedCount = 0;
        foreach ($dms as $dm) {
            if (!$dm->fcm_token) continue;
            try {
                Helpers::send_push_notif_to_device($dm->fcm_token, $data);
                $notifiedCount++;
            } catch (\Exception $e) {
                \Log::error("Zone order notification failed for DM {$dm->id}: " . $e->getMessage());
            }
        }

        DmZoneNotificationLog::create([
            'zone_id' => $order->zone_id,
            'order_id' => $order->id,
            'notification_type' => 'new_order',
            'dms_notified_count' => $notifiedCount,
            'notification_payload' => $data,
            'sent_at' => now(),
        ]);

        return $notifiedCount;
    }

    public function getAvailableDmsInZone(int $zoneId)
    {
        return DeliveryMan::withoutGlobalScopes()
            ->where('zone_id', $zoneId)
            ->where('active', 1)
            ->where('status', 1)
            ->where('application_status', 'approved')
            ->where('type', 'zone_wise')
            ->where('notify_new_orders', true)
            ->where('current_orders', '<', config('dm_maximum_orders', 1))
            ->get();
    }

    public function broadcastToZone(int $zoneId, array $data): int
    {
        $dms = DeliveryMan::withoutGlobalScopes()
            ->where('zone_id', $zoneId)
            ->where('active', 1)
            ->whereNotNull('fcm_token')
            ->get();

        $count = 0;
        foreach ($dms as $dm) {
            try {
                Helpers::send_push_notif_to_device($dm->fcm_token, $data);
                $count++;
            } catch (\Exception $e) {
                \Log::error("Zone broadcast failed for DM {$dm->id}: " . $e->getMessage());
            }
        }

        DmZoneNotificationLog::create([
            'zone_id' => $zoneId,
            'notification_type' => 'new_order',
            'dms_notified_count' => $count,
            'notification_payload' => $data,
            'sent_at' => now(),
        ]);

        return $count;
    }
}
