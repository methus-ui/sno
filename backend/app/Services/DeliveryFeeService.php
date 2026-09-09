<?php

namespace App\Services;

use App\Models\Zone;
use App\Models\ZoneDeliveryFeeSchedule;
use App\Models\WeatherSurgeRule;
use App\Models\DemandSurgeLevel;
use App\Models\DeliveryMan;
use App\Models\Order;
use App\Services\WeatherService;
use Illuminate\Support\Carbon;

class DeliveryFeeService
{
    /**
     * Get the delivery fee adjustment for a zone.
     * Checks time-based schedules first, then falls back to static zone setting.
     *
     * @param Zone $zone
     * @param Carbon|null $dateTime
     * @return array ['percentage' => float, 'message' => string|null, 'source' => string]
     */
    public function getDeliveryFeeAdjustment(Zone $zone, ?Carbon $dateTime = null): array
    {
        $dateTime = $dateTime ?? Carbon::now();

        // Check weather-based surge (highest priority)
        $weatherSurge = $this->getWeatherSurge($zone);
        if ($weatherSurge) {
            return $weatherSurge;
        }

        // Check demand-based surge
        $demandSurge = $this->getDemandSurge($zone);
        if ($demandSurge) {
            return $demandSurge;
        }

        // First, check for active time-based schedule
        $schedule = ZoneDeliveryFeeSchedule::getActiveScheduleForZone($zone->id, $dateTime);

        if ($schedule) {
            return [
                'percentage' => $schedule->fee_percentage,
                'message' => $schedule->message,
                'source' => 'schedule',
                'schedule_id' => $schedule->id,
                'schedule_title' => $schedule->title,
            ];
        }

        // Fall back to static zone setting
        if ($zone->increased_delivery_fee_status == 1 && $zone->increased_delivery_fee > 0) {
            return [
                'percentage' => $zone->increased_delivery_fee,
                'message' => $zone->increase_delivery_charge_message,
                'source' => 'static',
                'schedule_id' => null,
                'schedule_title' => null,
            ];
        }

        // No adjustment
        return [
            'percentage' => 0,
            'message' => null,
            'source' => 'none',
            'schedule_id' => null,
            'schedule_title' => null,
        ];
    }

    /**
     * Apply percentage adjustment to a base delivery charge.
     * Supports both positive (increase) and negative (decrease) percentages.
     *
     * @param float $baseCharge
     * @param float $percentage
     * @return float
     */
    public function applyAdjustment(float $baseCharge, float $percentage): float
    {
        if ($baseCharge <= 0 || $percentage == 0) {
            return $baseCharge;
        }

        $adjustmentAmount = ($baseCharge * $percentage) / 100;
        $adjustedCharge = $baseCharge + $adjustmentAmount;

        // Ensure charge doesn't go below zero
        return max(0, $adjustedCharge);
    }

    /**
     * Calculate the adjusted delivery charge for a zone.
     *
     * @param Zone $zone
     * @param float $baseCharge
     * @param Carbon|null $dateTime
     * @return array ['charge' => float, 'adjustment' => array]
     */
    public function calculateAdjustedCharge(Zone $zone, float $baseCharge, ?Carbon $dateTime = null): array
    {
        $adjustment = $this->getDeliveryFeeAdjustment($zone, $dateTime);
        $adjustedCharge = $this->applyAdjustment($baseCharge, $adjustment['percentage']);

        return [
            'charge' => $adjustedCharge,
            'adjustment' => $adjustment,
        ];
    }

    /**
     * Get the current active schedule info for API response.
     *
     * @param Zone $zone
     * @param Carbon|null $dateTime
     * @return array|null
     */
    public function getActiveScheduleInfo(Zone $zone, ?Carbon $dateTime = null): ?array
    {
        $dateTime = $dateTime ?? Carbon::now();
        $schedule = ZoneDeliveryFeeSchedule::getActiveScheduleForZone($zone->id, $dateTime);

        if (!$schedule) {
            return null;
        }

        return [
            'id' => $schedule->id,
            'title' => $schedule->title,
            'fee_percentage' => $schedule->fee_percentage,
            'message' => $schedule->message,
            'start_time' => $schedule->start_time,
            'end_time' => $schedule->end_time,
            'day' => $schedule->day,
            'day_name' => $schedule->day_name,
        ];
    }

    /**
     * Check for weather-based surge
     */
    private function getWeatherSurge(Zone $zone): ?array
    {
        if (!$zone->weather_surge_enabled) {
            return null;
        }

        $weatherService = new WeatherService();
        $weather = $weatherService->getConditionForZone($zone);

        if (!$weather || $weather['condition'] === 'clear') {
            return null;
        }

        $query = WeatherSurgeRule::enabled()
            ->forZone($zone->id)
            ->where('weather_condition', $weather['condition']);

        $rule = $query->orderByDesc('surge_percentage')->first();

        if (!$rule) {
            return null;
        }

        // Check temperature bounds for extreme_heat
        if ($rule->weather_condition === 'extreme_heat' && $weather['temperature'] !== null) {
            if ($rule->min_temp !== null && $weather['temperature'] < $rule->min_temp) return null;
            if ($rule->max_temp !== null && $weather['temperature'] > $rule->max_temp) return null;
        }

        return [
            'percentage' => $rule->surge_percentage,
            'message' => $rule->message,
            'source' => 'weather',
            'schedule_id' => null,
            'schedule_title' => null,
            'weather_condition' => $weather['condition'],
            'temperature' => $weather['temperature'],
        ];
    }

    /**
     * Check for demand-based surge
     */
    private function getDemandSurge(Zone $zone): ?array
    {
        if (!$zone->demand_surge_enabled) {
            return null;
        }

        $pendingOrders = Order::where('zone_id', $zone->id)
            ->whereNull('delivery_man_id')
            ->whereIn('order_status', ['pending', 'confirmed', 'processing'])
            ->count();

        $activeDms = DeliveryMan::where('zone_id', $zone->id)
            ->where('active', 1)
            ->where('application_status', 'approved')
            ->count();

        $level = DemandSurgeLevel::enabled()
            ->forZone($zone->id)
            ->where('min_pending_orders', '<=', $pendingOrders)
            ->where('max_available_dms', '>=', $activeDms)
            ->orderByDesc('surge_percentage')
            ->first();

        if (!$level) {
            return null;
        }

        return [
            'percentage' => $level->surge_percentage,
            'message' => $level->message,
            'source' => 'demand',
            'schedule_id' => null,
            'schedule_title' => null,
            'pending_orders' => $pendingOrders,
            'active_dms' => $activeDms,
        ];
    }
}
