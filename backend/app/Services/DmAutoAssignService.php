<?php

namespace App\Services;

use App\Models\DeliveryMan;
use App\Models\DmAutoAssignmentSetting;
use App\Models\Order;
use App\Models\DeliveryHistory;
use App\CentralLogics\Helpers;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DmAutoAssignService
{
    public function findBestDm(Order $order): ?DeliveryMan
    {
        $setting = DmAutoAssignmentSetting::enabled()
            ->where(function ($q) use ($order) {
                $q->whereNull('zone_id')->orWhere('zone_id', $order->zone_id);
            })
            ->first();

        if (!$setting) return null;

        $store = $order->store;
        if (!$store) return null;

        $storeLat = $store->latitude;
        $storeLng = $store->longitude;
        $maxRadius = $setting->max_radius_km;
        $weights = $setting->getWeights();

        // Get active DMs in zone who aren't at max capacity
        $dms = DeliveryMan::where('zone_id', $order->zone_id)
            ->where('active', 1)
            ->where('application_status', 'approved')
            ->where('type', 'zone_wise')
            ->where('current_orders', '<', $setting->max_orders_per_dm)
            ->get();

        if ($dms->isEmpty()) return null;

        $bestDm = null;
        $bestScore = -1;

        foreach ($dms as $dm) {
            // OPTIONAL: Check if DM has an active shift (for bonus scoring)
            // Shift booking is NOT required for order assignment
            $shiftService = new DmShiftBookingService();
            $hasActiveShift = $shiftService->hasActiveShiftAt($dm->id, now());

            // Log shift status for analytics
            if (!$hasActiveShift) {
                Log::info("DM {$dm->id} receiving orders without active shift", [
                    'dm_id' => $dm->id,
                    'order_id' => $order->id,
                ]);
            }

            // Get last known location
            $lastLocation = DeliveryHistory::where('delivery_man_id', $dm->id)
                ->orderBy('created_at', 'desc')
                ->first();

            if (!$lastLocation) continue;

            $distance = $this->haversineDistance(
                $storeLat, $storeLng,
                $lastLocation->latitude, $lastLocation->longitude
            );

            if ($distance > $maxRadius) continue;

            // Calculate scores
            $distanceScore = max(0, 1 - ($distance / $maxRadius));
            $ratingScore = ($dm->avg_rating ?? 0) / 5;
            $acceptanceScore = ($dm->acceptance_rate ?? 50) / 100;

            $tierScore = 0;
            if ($dm->currentTier) {
                $tierScore = ($dm->currentTier->bonus_per_delivery ?? 0) / 20; // Normalize against max tier bonus
            }

            // Bonus for DMs with active shifts (incentivize shift booking)
            $shiftBonus = $hasActiveShift ? 0.1 : 0;

            $score = ($distanceScore * ($weights['distance_weight'] ?? 0.4))
                + ($ratingScore * ($weights['rating_weight'] ?? 0.25))
                + ($acceptanceScore * ($weights['acceptance_rate_weight'] ?? 0.2))
                + ($tierScore * ($weights['tier_weight'] ?? 0.15))
                + $shiftBonus; // Small bonus for having active shift

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestDm = $dm;
            }
        }

        return $bestDm;
    }

    public function assignOrderToDm(Order $order, DeliveryMan $dm): bool
    {
        try {
            DB::beginTransaction();

            $order->delivery_man_id = $dm->id;
            $order->order_status = 'accepted';
            $order->accepted = now();
            $order->save();

            $dm->increment('current_orders');
            $dm->increment('total_orders_accepted');

            DB::commit();

            // Notify DM
            if ($dm->fcm_token) {
                $data = [
                    'title' => 'New Order Assigned',
                    'description' => "Order #{$order->id} has been assigned to you.",
                    'type' => 'order_auto_assigned',
                    'order_id' => $order->id,
                ];
                try {
                    Helpers::send_push_notif_to_device($dm->fcm_token, $data);
                } catch (\Exception $e) {
                    Log::error("Auto-assign notification failed for DM {$dm->id}: " . $e->getMessage());
                }
            }

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Auto-assign failed for Order {$order->id}: " . $e->getMessage());
            return false;
        }
    }

    public function processUnassignedOrders(): int
    {
        $batchingService = new OrderBatchingService();
        $assigned = 0;

        // Get zones with auto-assignment enabled
        $settings = DmAutoAssignmentSetting::enabled()->get();
        $enabledZoneIds = $settings->pluck('zone_id')->filter()->toArray();
        $hasGlobal = $settings->whereNull('zone_id')->isNotEmpty();

        $orders = Order::whereNull('delivery_man_id')
            ->whereIn('order_type', ['delivery', 'parcel'])
            ->whereNotIn('order_status', ['delivered', 'failed', 'canceled', 'refunded'])
            ->where('order_status', 'pending')
            ->where('created_at', '>=', now()->subMinutes(30))
            ->when(!$hasGlobal, function ($q) use ($enabledZoneIds) {
                $q->whereIn('zone_id', $enabledZoneIds);
            })
            ->orderBy('created_at', 'asc')
            ->get();

        // Try batching first
        foreach ($settings as $setting) {
            $zoneId = $setting->zone_id;
            if ($zoneId) {
                $batchingService->findAndCreateBatches($zoneId);
            }
        }

        // Assign remaining unassigned orders individually
        foreach ($orders as $order) {
            $order->refresh();
            if ($order->delivery_man_id) continue; // Already assigned by batching

            $dm = $this->findBestDm($order);
            if ($dm) {
                if ($this->assignOrderToDm($order, $dm)) {
                    $assigned++;
                }
            } else {
                // Check if fallback time exceeded
                $setting = $settings->where('zone_id', $order->zone_id)->first()
                    ?? $settings->whereNull('zone_id')->first();

                if ($setting && $order->created_at->diffInSeconds(now()) > $setting->fallback_to_manual_after_seconds) {
                    Log::warning("Order #{$order->id} could not be auto-assigned after {$setting->fallback_to_manual_after_seconds}s. Falling back to manual.");
                }
            }
        }

        return $assigned;
    }

    private function haversineDistance($lat1, $lon1, $lat2, $lon2): float
    {
        $earthRadius = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * asin(sqrt($a));
        return $earthRadius * $c;
    }
}
