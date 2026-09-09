<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Admin;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class OrderFlowAnalysisService
{
    /**
     * Calculate dynamic target based on order flow
     *
     * @param string|Carbon $startDate
     * @param string|Carbon $endDate
     * @param int|null $zoneId
     * @return array
     */
    public function calculateDynamicTarget($startDate, $endDate, $zoneId = null)
    {
        $startDate = $startDate instanceof Carbon ? $startDate : Carbon::parse($startDate);
        $endDate = $endDate instanceof Carbon ? $endDate : Carbon::parse($endDate);
        $days_in_period = max(1, $startDate->diffInDays($endDate) + 1);

        // Get total orders in period
        $total_orders = Order::whereBetween('created_at', [$startDate, $endDate])
            ->when($zoneId && is_numeric($zoneId), fn($q) => $q->where('zone_id', $zoneId))
            ->count();

        // Get active employees in period
        $active_employees = Admin::where('role_id', '!=', 1)
            ->where('status', 1)
            ->when($zoneId && is_numeric($zoneId), fn($q) => $q->where('zone_id', $zoneId))
            ->whereHas('assignedOrders', function($q) use ($startDate, $endDate) {
                $q->whereBetween('created_at', [$startDate, $endDate]);
            })
            ->count();

        $active_employees = max(1, $active_employees); // Avoid division by zero

        // Calculate predicted order flow
        $predicted_flow = $this->predictOrderFlow($startDate, $endDate, $zoneId);

        // Calculate fair distribution target
        $fair_target_per_employee = round($total_orders / $active_employees);

        // Calculate daily target
        $daily_target_actual = round($total_orders / $days_in_period / $active_employees);
        $daily_target_predicted = round($predicted_flow['predicted_daily'] / $active_employees);

        // Use the higher of actual or predicted
        $base_daily_target = max($daily_target_actual, $daily_target_predicted);

        // Apply stretch factor for ambitious targets (30% increase)
        $stretch_factor = 1.30; // 30% higher than fair distribution
        $recommended_daily_target = round($base_daily_target * $stretch_factor);

        // Ensure minimum target of 50 orders/day for high performance
        $recommended_daily_target = max($recommended_daily_target, 50);

        // Calculate period target
        $period_target = $recommended_daily_target * $days_in_period;

        return [
            'total_orders_available' => $total_orders,
            'active_employees' => $active_employees,
            'days_in_period' => $days_in_period,
            'fair_target_per_employee' => $fair_target_per_employee,
            'daily_target' => $recommended_daily_target,
            'period_target' => $period_target,
            'base_daily_target' => $base_daily_target,
            'stretch_factor' => $stretch_factor,
            'actual_daily_avg' => $daily_target_actual,
            'predicted_daily_avg' => $daily_target_predicted,
            'order_flow_trend' => $predicted_flow['trend'],
            'utilization_rate' => $predicted_flow['utilization_rate'],
            'is_stretch_goal' => true,
        ];
    }

    /**
     * Predict order flow based on historical data
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param int|null $zoneId
     * @return array
     */
    private function predictOrderFlow($startDate, $endDate, $zoneId = null)
    {
        // Get historical data (last 30 days before start date)
        $historical_start = $startDate->copy()->subDays(30);
        $historical_end = $startDate->copy()->subDay();

        $historical_orders = Order::whereBetween('created_at', [$historical_start, $historical_end])
            ->when($zoneId && is_numeric($zoneId), fn($q) => $q->where('zone_id', $zoneId))
            ->count();

        $historical_daily_avg = $historical_orders / 30;

        // Get current period actual orders
        $current_orders = Order::whereBetween('created_at', [$startDate, $endDate])
            ->when($zoneId && is_numeric($zoneId), fn($q) => $q->where('zone_id', $zoneId))
            ->count();

        $days_elapsed = max(1, $startDate->diffInDays(now()->min($endDate)) + 1);
        $current_daily_avg = $current_orders / $days_elapsed;

        // Calculate trend
        $trend_percentage = 0;
        if ($historical_daily_avg > 0) {
            $trend_percentage = (($current_daily_avg - $historical_daily_avg) / $historical_daily_avg) * 100;
        }

        // Determine trend direction
        $trend = 'stable';
        if ($trend_percentage > 10) {
            $trend = 'growing';
        } elseif ($trend_percentage < -10) {
            $trend = 'declining';
        }

        // Predict daily orders (weighted average of historical and current)
        $predicted_daily = round(($historical_daily_avg * 0.3) + ($current_daily_avg * 0.7));

        // Calculate utilization rate (how many orders are being assigned)
        $assigned_orders = Order::whereBetween('created_at', [$startDate, $endDate])
            ->when($zoneId && is_numeric($zoneId), fn($q) => $q->where('zone_id', $zoneId))
            ->whereNotNull('assigned_to')
            ->count();

        $utilization_rate = $current_orders > 0 ? round(($assigned_orders / $current_orders) * 100, 2) : 0;

        return [
            'historical_daily' => round($historical_daily_avg, 1),
            'current_daily' => round($current_daily_avg, 1),
            'predicted_daily' => $predicted_daily,
            'trend' => $trend,
            'trend_percentage' => round($trend_percentage, 2),
            'utilization_rate' => $utilization_rate,
        ];
    }

    /**
     * Get order flow statistics
     *
     * @param string|Carbon $startDate
     * @param string|Carbon $endDate
     * @param int|null $zoneId
     * @return array
     */
    public function getOrderFlowStats($startDate, $endDate, $zoneId = null)
    {
        $startDate = $startDate instanceof Carbon ? $startDate : Carbon::parse($startDate);
        $endDate = $endDate instanceof Carbon ? $endDate : Carbon::parse($endDate);

        // Get daily order distribution
        $daily_distribution = Order::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->when($zoneId && is_numeric($zoneId), fn($q) => $q->where('zone_id', $zoneId))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $peak_day = $daily_distribution->sortByDesc('count')->first();
        $low_day = $daily_distribution->sortBy('count')->first();

        return [
            'daily_distribution' => $daily_distribution,
            'peak_day' => $peak_day ? ['date' => $peak_day->date, 'orders' => $peak_day->count] : null,
            'low_day' => $low_day ? ['date' => $low_day->date, 'orders' => $low_day->count] : null,
            'avg_daily' => round($daily_distribution->avg('count'), 1),
        ];
    }
}
