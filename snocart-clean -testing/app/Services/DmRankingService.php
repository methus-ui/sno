<?php

namespace App\Services;

use App\Models\DeliveryMan;
use App\Models\DmPerformanceTier;
use App\Models\DmLeaderboard;
use App\Models\DMReview;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DmRankingService
{
    public function calculateMetrics(DeliveryMan $dm): array
    {
        $avgRating = DMReview::where('delivery_man_id', $dm->id)->avg('rating') ?? 0;
        $totalDelivered = Order::where('delivery_man_id', $dm->id)->where('order_status', 'delivered')->count();
        $acceptanceRate = $dm->calculateAcceptanceRate();

        return [
            'avg_rating' => round($avgRating, 2),
            'total_deliveries' => $totalDelivered,
            'acceptance_rate' => $acceptanceRate,
        ];
    }

    public function assignTier(DeliveryMan $dm): ?DmPerformanceTier
    {
        $metrics = $this->calculateMetrics($dm);
        $tiers = DmPerformanceTier::active()->ordered()->get()->reverse();

        $bestTier = null;
        foreach ($tiers as $tier) {
            $qualifies = true;

            if ($tier->min_rating !== null && $metrics['avg_rating'] < $tier->min_rating) {
                $qualifies = false;
            }
            if ($tier->min_deliveries !== null && $metrics['total_deliveries'] < $tier->min_deliveries) {
                $qualifies = false;
            }
            if ($tier->min_acceptance_rate !== null && $metrics['acceptance_rate'] < $tier->min_acceptance_rate) {
                $qualifies = false;
            }

            if ($qualifies) {
                $bestTier = $tier;
                break;
            }
        }

        $dm->current_tier_id = $bestTier?->id;
        $dm->last_tier_calculated_at = now();
        $dm->saveQuietly();

        return $bestTier;
    }

    public function updateAllTiers(): int
    {
        $dms = DeliveryMan::withoutGlobalScopes()
            ->where('status', 1)
            ->where('application_status', 'approved')
            ->get();

        $count = 0;
        foreach ($dms as $dm) {
            $this->assignTier($dm);
            $count++;
        }

        return $count;
    }

    public function generateLeaderboard(string $periodType, ?int $zoneId = null): int
    {
        switch ($periodType) {
            case 'daily':
                $start = now()->startOfDay();
                $end = now()->endOfDay();
                break;
            case 'weekly':
                $start = now()->startOfWeek();
                $end = now()->endOfWeek();
                break;
            case 'monthly':
                $start = now()->startOfMonth();
                $end = now()->endOfMonth();
                break;
            default:
                return 0;
        }

        DmLeaderboard::where('period_type', $periodType)
            ->where('period_start', $start->toDateString())
            ->when($zoneId, fn($q) => $q->where('zone_id', $zoneId))
            ->delete();

        $query = DeliveryMan::withoutGlobalScopes()
            ->where('status', 1)
            ->where('application_status', 'approved');

        if ($zoneId) {
            $query->where('zone_id', $zoneId);
        }

        $dms = $query->get();
        $entries = [];

        foreach ($dms as $dm) {
            $deliveries = Order::where('delivery_man_id', $dm->id)
                ->where('order_status', 'delivered')
                ->whereBetween('delivered', [$start, $end])
                ->count();

            if ($deliveries === 0) continue;

            $earnings = Order::where('orders.delivery_man_id', $dm->id)
                ->where('orders.order_status', 'delivered')
                ->whereBetween('orders.delivered', [$start, $end])
                ->join('order_transactions', 'orders.id', '=', 'order_transactions.order_id')
                ->sum('order_transactions.delivery_charge');

            $avgRating = DMReview::where('delivery_man_id', $dm->id)
                ->whereBetween('created_at', [$start, $end])
                ->avg('rating') ?? 0;

            $entries[] = [
                'dm' => $dm,
                'deliveries' => $deliveries,
                'earnings' => $earnings,
                'avg_rating' => round($avgRating, 2),
                'acceptance_rate' => $dm->calculateAcceptanceRate(),
            ];
        }

        usort($entries, fn($a, $b) => $b['deliveries'] <=> $a['deliveries']);

        $count = 0;
        foreach ($entries as $rank => $entry) {
            DmLeaderboard::create([
                'delivery_man_id' => $entry['dm']->id,
                'period_type' => $periodType,
                'period_start' => $start->toDateString(),
                'period_end' => $end->toDateString(),
                'deliveries_completed' => $entry['deliveries'],
                'total_earnings' => $entry['earnings'],
                'avg_rating' => $entry['avg_rating'],
                'acceptance_rate' => $entry['acceptance_rate'],
                'rank_position' => $rank + 1,
                'zone_id' => $entry['dm']->zone_id,
            ]);
            $count++;
        }

        return $count;
    }

    public function getLeaderboard(string $periodType, ?int $zoneId = null, int $limit = 50): array
    {
        switch ($periodType) {
            case 'daily':
                $start = now()->startOfDay();
                break;
            case 'weekly':
                $start = now()->startOfWeek();
                break;
            case 'monthly':
                $start = now()->startOfMonth();
                break;
            default:
                return [];
        }

        return DmLeaderboard::where('period_type', $periodType)
            ->where('period_start', $start->toDateString())
            ->when($zoneId, fn($q) => $q->where('zone_id', $zoneId))
            ->orderBy('rank_position')
            ->limit($limit)
            ->with('deliveryMan:id,f_name,l_name,image,zone_id')
            ->get()
            ->toArray();
    }

    public function getDmPerformanceStats(DeliveryMan $dm): array
    {
        $metrics = $this->calculateMetrics($dm);
        $tier = $dm->currentTier;

        $todayDeliveries = Order::where('delivery_man_id', $dm->id)
            ->where('order_status', 'delivered')
            ->whereDate('delivered', now()->toDateString())
            ->count();

        $weeklyRank = DmLeaderboard::where('delivery_man_id', $dm->id)
            ->where('period_type', 'weekly')
            ->where('period_start', now()->startOfWeek()->toDateString())
            ->first();

        return [
            'tier' => $tier ? [
                'name' => $tier->name,
                'color' => $tier->color,
                'icon' => $tier->icon,
                'bonus_per_delivery' => $tier->bonus_per_delivery,
            ] : null,
            'avg_rating' => $metrics['avg_rating'],
            'total_deliveries' => $metrics['total_deliveries'],
            'acceptance_rate' => $metrics['acceptance_rate'],
            'today_deliveries' => $todayDeliveries,
            'weekly_rank' => $weeklyRank?->rank_position,
            'total_orders_accepted' => $dm->total_orders_accepted,
            'total_orders_rejected' => $dm->total_orders_rejected,
        ];
    }
}
