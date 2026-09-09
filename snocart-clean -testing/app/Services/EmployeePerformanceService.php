<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\Order;
use Carbon\Carbon;

class EmployeePerformanceService
{
    /**
     * Calculate performance metrics for a single employee
     *
     * @param int $employeeId
     * @param string|Carbon $startDate
     * @param string|Carbon $endDate
     * @param int|null $zoneId
     * @return array
     */
    public function calculateEmployeeMetrics($employeeId, $startDate, $endDate, $zoneId = null)
    {
        // Convert to Carbon instances if strings
        $startDate = $startDate instanceof Carbon ? $startDate : Carbon::parse($startDate);
        $endDate = $endDate instanceof Carbon ? $endDate : Carbon::parse($endDate);

        // Calculate number of days in period
        $days_in_period = max(1, $startDate->diffInDays($endDate) + 1);

        // Get dynamic target based on order flow
        $flowAnalysis = new OrderFlowAnalysisService();
        $orderFlow = $flowAnalysis->calculateDynamicTarget($startDate, $endDate, $zoneId);

        // Get all assigned orders in date range
        $orders = Order::where('assigned_to', $employeeId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->when($zoneId && is_numeric($zoneId), fn($q) => $q->where('zone_id', $zoneId))
            ->get();

        $total = $orders->count();
        $delivered = $orders->where('order_status', 'delivered')->count();
        $canceled = $orders->whereIn('order_status', ['canceled', 'failed'])->count();
        $pending = $total - $delivered - $canceled;

        // Cancellation rate
        $cancellation_rate = $total > 0 ? ($canceled / $total) * 100 : 0;

        // Average delivery time (only delivered orders)
        $delivery_times = $orders->where('order_status', 'delivered')
            ->filter(fn($o) => $o->delivered && $o->created_at)
            ->map(function($o) {
                $createdAt = $o->created_at instanceof \Carbon\Carbon ? $o->created_at : \Carbon\Carbon::parse($o->created_at);
                $deliveredAt = $o->delivered instanceof \Carbon\Carbon ? $o->delivered : \Carbon\Carbon::parse($o->delivered);
                return $createdAt->diffInMinutes($deliveredAt);
            });

        $avg_delivery_time = $delivery_times->avg() ?? 0;
        $min_delivery_time = $delivery_times->min() ?? 0;
        $max_delivery_time = $delivery_times->max() ?? 0;

        // Calculate order volume performance (dynamic target based on order flow)
        $daily_target = max($orderFlow['daily_target'], 10); // Minimum 10 orders/day
        $min_orders_target = $orderFlow['period_target']; // Period-adjusted target
        $volume_performance = $min_orders_target > 0 ? min(($total / $min_orders_target) * 100, 100) : 100;
        $volume_status = $this->getVolumeStatus($total, $min_orders_target);
        $avg_daily_orders = $days_in_period > 0 ? round($total / $days_in_period, 1) : 0;

        // Performance score (weighted with volume)
        $target_delivery_time = 40; // 40 minutes average target
        $completion_rate = $total > 0 ? ($delivered / $total) * 100 : 0;
        $on_time_orders = $delivery_times->filter(fn($time) => $time <= $target_delivery_time)->count();
        $timeliness_score = $delivered > 0 ? ($on_time_orders / $delivered) * 100 : 0;
        $low_cancellation = (1 - ($cancellation_rate / 100)) * 100;

        // Delivery time performance (closer to 40 min is better)
        $delivery_time_performance = 100;
        if ($avg_delivery_time > 0) {
            if ($avg_delivery_time <= $target_delivery_time) {
                $delivery_time_performance = 100; // Perfect if under target
            } else {
                // Penalty for exceeding target
                $delivery_time_performance = max(0, 100 - (($avg_delivery_time - $target_delivery_time) * 2));
            }
        }

        // Enhanced performance score with volume scaling
        $base_score = (
            ($completion_rate * 0.35) +      // 35% weight on completion
            ($timeliness_score * 0.30) +     // 30% weight on speed
            ($low_cancellation * 0.20) +     // 20% weight on low cancellations
            ($volume_performance * 0.15)     // 15% weight on order volume
        );

        // Apply volume multiplier (penalty if below 25 orders)
        $volume_multiplier = $total >= $min_orders_target ? 1.0 : ($total / $min_orders_target);
        $performance_score = $base_score * $volume_multiplier;

        return [
            'total_assigned' => $total,
            'delivered' => $delivered,
            'canceled' => $canceled,
            'pending' => $pending,
            'cancellation_rate' => round($cancellation_rate, 2),
            'avg_delivery_time' => round($avg_delivery_time, 2),
            'min_delivery_time' => round($min_delivery_time, 2),
            'max_delivery_time' => round($max_delivery_time, 2),
            'avg_delivery_time_formatted' => $this->formatMinutes($avg_delivery_time),
            'min_delivery_time_formatted' => $this->formatMinutes($min_delivery_time),
            'max_delivery_time_formatted' => $this->formatMinutes($max_delivery_time),
            'performance_score' => round($performance_score, 2),
            'performance_grade' => $this->getPerformanceGrade($performance_score, $total, $min_orders_target),
            'completion_rate' => round($completion_rate, 2),
            'on_time_orders' => $on_time_orders,
            'color_class' => $this->getCancellationColor($cancellation_rate),
            'volume_performance' => round($volume_performance, 2),
            'volume_status' => $volume_status,
            'min_orders_target' => $min_orders_target,
            'daily_target' => $daily_target,
            'days_in_period' => $days_in_period,
            'avg_daily_orders' => $avg_daily_orders,
            'meets_volume_target' => $total >= $min_orders_target,
            'target_delivery_time' => $target_delivery_time,
            'delivery_time_performance' => round($delivery_time_performance, 2),
            'meets_time_target' => $avg_delivery_time > 0 && $avg_delivery_time <= $target_delivery_time,
            // Order Flow Analysis
            'order_flow' => [
                'total_available' => $orderFlow['total_orders_available'],
                'active_employees' => $orderFlow['active_employees'],
                'trend' => $orderFlow['order_flow_trend'],
                'utilization_rate' => $orderFlow['utilization_rate'],
                'is_dynamic_target' => true,
            ],
        ];
    }

    /**
     * Get top performing employees
     *
     * @param int $limit
     * @param string|Carbon $startDate
     * @param string|Carbon $endDate
     * @param int|null $zoneId
     * @return \Illuminate\Support\Collection
     */
    public function getTopPerformers($limit = 5, $startDate, $endDate, $zoneId = null)
    {
        // Convert to Carbon instances if strings
        $startDate = $startDate instanceof Carbon ? $startDate : Carbon::parse($startDate);
        $endDate = $endDate instanceof Carbon ? $endDate : Carbon::parse($endDate);

        $employees = Admin::where('role_id', '!=', 1)
            ->where('status', 1)
            ->when($zoneId && is_numeric($zoneId), fn($q) => $q->where('zone_id', $zoneId))
            ->whereHas('assignedOrders', function($q) use ($startDate, $endDate) {
                $q->whereBetween('created_at', [$startDate, $endDate]);
            })
            ->with(['role'])
            ->get();

        $performers = $employees->map(function($employee) use ($startDate, $endDate, $zoneId) {
            $metrics = $this->calculateEmployeeMetrics($employee->id, $startDate, $endDate, $zoneId);

            return [
                'id' => $employee->id,
                'name' => $employee->f_name . ' ' . $employee->l_name,
                'role' => $employee->role->name ?? 'N/A',
                'image' => $employee->image_full_url ?? asset('public/assets/admin/img/admin.png'),
                'metrics' => $metrics,
            ];
        })
        ->filter(fn($emp) => $emp['metrics']['total_assigned'] > 0) // Hide 0 orders
        ->sortByDesc('metrics.performance_score')
        ->take($limit)
        ->values();

        return $performers;
    }

    /**
     * Format minutes into human-readable format
     *
     * @param float $minutes
     * @return string
     */
    private function formatMinutes($minutes)
    {
        if ($minutes < 60) {
            return round($minutes) . ' min';
        }
        $hours = floor($minutes / 60);
        $mins = round($minutes % 60);
        return $hours . 'h ' . $mins . 'm';
    }

    /**
     * Get performance grade and label
     *
     * @param float $score
     * @param int $totalOrders
     * @param int $minTarget
     * @return array
     */
    private function getPerformanceGrade($score, $totalOrders = 0, $minTarget = 50)
    {
        // Apply volume penalty label if below target
        $volumeSuffix = '';
        if ($totalOrders > 0 && $totalOrders < $minTarget) {
            $volumeSuffix = ' (Low Volume)';
        }

        if ($score >= 90) return ['grade' => 'A+', 'label' => 'Excellent' . $volumeSuffix, 'color' => 'success'];
        if ($score >= 80) return ['grade' => 'A', 'label' => 'Very Good' . $volumeSuffix, 'color' => 'info'];
        if ($score >= 70) return ['grade' => 'B', 'label' => 'Good' . $volumeSuffix, 'color' => 'primary'];
        if ($score >= 60) return ['grade' => 'C', 'label' => 'Average' . $volumeSuffix, 'color' => 'warning'];
        return ['grade' => 'D', 'label' => 'Needs Improvement' . $volumeSuffix, 'color' => 'danger'];
    }

    /**
     * Get volume status based on order count
     *
     * @param int $totalOrders
     * @param int $minTarget
     * @return array
     */
    private function getVolumeStatus($totalOrders, $minTarget = 50)
    {
        $percentage = ($totalOrders / $minTarget) * 100;

        if ($totalOrders >= $minTarget) {
            return [
                'status' => 'excellent',
                'label' => 'Target Met',
                'color' => 'success',
                'icon' => 'check-circle',
                'percentage' => min($percentage, 100)
            ];
        } elseif ($totalOrders >= ($minTarget * 0.8)) {
            return [
                'status' => 'good',
                'label' => 'Near Target',
                'color' => 'info',
                'icon' => 'arrow-up',
                'percentage' => $percentage
            ];
        } elseif ($totalOrders >= ($minTarget * 0.5)) {
            return [
                'status' => 'warning',
                'label' => 'Below Target',
                'color' => 'warning',
                'icon' => 'exclamation-triangle',
                'percentage' => $percentage
            ];
        } else {
            return [
                'status' => 'critical',
                'label' => 'Critical Low',
                'color' => 'danger',
                'icon' => 'times-circle',
                'percentage' => $percentage
            ];
        }
    }

    /**
     * Get color class based on cancellation rate
     *
     * @param float $rate
     * @return string
     */
    private function getCancellationColor($rate)
    {
        if ($rate < 10) return 'success';      // Green
        if ($rate <= 20) return 'warning';     // Yellow
        return 'danger';                       // Red
    }
}
