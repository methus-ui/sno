<?php

namespace App\Services;

use App\Models\DmRushIncentive;
use App\Models\DmRushActivation;
use App\Models\DmZoneNotificationLog;
use App\Models\DeliveryMan;
use App\Models\Order;
use App\Models\Zone;
use App\CentralLogics\Helpers;
use Carbon\Carbon;

class DmRushMonitorService
{
    public function checkAllZonesForRush(): void
    {
        $rushRules = DmRushIncentive::active()->get();

        foreach ($rushRules as $rule) {
            if ($rule->zone_id) {
                $this->checkZoneForRush($rule->zone_id, $rule);
            } else {
                $zones = Zone::all();
                foreach ($zones as $zone) {
                    $this->checkZoneForRush($zone->id, $rule);
                }
            }
        }
    }

    public function checkZoneForRush(int $zoneId, DmRushIncentive $rule): void
    {
        $alreadyActive = DmRushActivation::where('rush_incentive_id', $rule->id)
            ->where('zone_id', $zoneId)
            ->currentlyActive()
            ->exists();

        if ($alreadyActive) return;

        $pendingCount = Order::where('zone_id', $zoneId)
            ->whereNull('delivery_man_id')
            ->whereIn('order_status', ['pending', 'confirmed', 'processing'])
            ->count();

        if ($rule->trigger_type === 'order_count' && $pendingCount >= $rule->min_threshold) {
            if ($rule->max_threshold && $pendingCount > $rule->max_threshold) return;
            $this->activateRush($rule, $zoneId, $pendingCount);
        }
    }

    public function activateRush(DmRushIncentive $rule, int $zoneId, int $pendingCount): void
    {
        $activation = DmRushActivation::create([
            'rush_incentive_id' => $rule->id,
            'zone_id' => $zoneId,
            'started_at' => now(),
            'pending_orders_count' => $pendingCount,
            'dm_notified' => false,
        ]);

        $this->notifyDmsAboutRush($zoneId, $activation, $rule);

        $activation->update(['dm_notified' => true]);
    }

    public function notifyDmsAboutRush(int $zoneId, DmRushActivation $activation, DmRushIncentive $rule): void
    {
        $dms = DeliveryMan::withoutGlobalScopes()
            ->where('zone_id', $zoneId)
            ->where('active', 1)
            ->where('notify_rush_active', true)
            ->whereNotNull('fcm_token')
            ->get();

        $data = [
            'title' => 'Rush Mode Active!',
            'description' => "{$rule->title} - Earn extra bonus per delivery! {$activation->pending_orders_count} orders waiting.",
            'type' => 'rush_active',
            'rush_activation_id' => $activation->id,
        ];

        foreach ($dms as $dm) {
            try {
                Helpers::send_push_notif_to_device($dm->fcm_token, $data);
            } catch (\Exception $e) {
                \Log::error("Rush notification failed for DM {$dm->id}: " . $e->getMessage());
            }
        }

        DmZoneNotificationLog::create([
            'zone_id' => $zoneId,
            'notification_type' => 'rush_active',
            'dms_notified_count' => $dms->count(),
            'notification_payload' => $data,
            'sent_at' => now(),
        ]);
    }

    public function deactivateExpiredRushes(): int
    {
        $count = 0;
        $activeRushes = DmRushActivation::currentlyActive()->with('rushIncentive')->get();

        foreach ($activeRushes as $activation) {
            if (!$activation->rushIncentive) {
                $activation->endSurge();
                $count++;
                continue;
            }

            $expiresAt = Carbon::parse($activation->started_at)
                ->addMinutes($activation->rushIncentive->duration_minutes);

            if (now()->gte($expiresAt)) {
                $activation->endSurge();
                $count++;
            }
        }

        return $count;
    }

    public function getRushStatusForZone(int $zoneId): ?array
    {
        $activation = DmRushActivation::currentlyActive()
            ->where('zone_id', $zoneId)
            ->with('rushIncentive')
            ->first();

        if (!$activation) return null;

        $expiresAt = Carbon::parse($activation->started_at)
            ->addMinutes($activation->rushIncentive->duration_minutes);

        return [
            'active' => true,
            'title' => $activation->rushIncentive->title,
            'bonus_type' => $activation->rushIncentive->bonus_type,
            'bonus_value' => $activation->rushIncentive->bonus_value,
            'pending_orders' => $activation->pending_orders_count,
            'started_at' => $activation->started_at->toDateTimeString(),
            'expires_at' => $expiresAt->toDateTimeString(),
            'remaining_minutes' => now()->diffInMinutes($expiresAt, false),
        ];
    }
}
