<?php

namespace App\Services;

use App\Models\DeliveryMan;
use App\Models\DeliveryManWallet;
use App\Models\DeliverymanAttendance;
use App\Models\DmDailyIncentiveRule;
use App\Models\DmTimeBasedIncentive;
use App\Models\DmRushActivation;
use App\Models\DmPerformanceTier;
use App\Models\ProvideDMEarning;
use App\Models\AccountTransaction;
use App\Models\Order;
use App\Models\DmFuelIncentive;
use App\Models\UserNotification;
use App\CentralLogics\Helpers;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DmIncentiveService
{
    public function processAllIncentives(DeliveryMan $dm, Order $order): array
    {
        // CHECK: Verify DM is eligible for incentives (not offline too long)
        $attendanceService = new DeliverymanAttendanceService();
        $isEligible = $attendanceService->isEligibleForIncentives($dm->id);

        if (!$isEligible) {
            $offlineSummary = $attendanceService->getOfflineTimeSummary($dm->id);

            \Log::warning("DM #{$dm->id} NOT eligible for incentives - exceeded offline threshold", [
                'order_id' => $order->id,
                'total_offline_minutes' => $offlineSummary['total_offline_minutes'],
                'threshold' => $offlineSummary['threshold_minutes'],
            ]);

            // Return empty array - no incentives awarded
            return [
                'ineligible' => true,
                'reason' => 'Exceeded offline time threshold',
                'offline_minutes' => $offlineSummary['total_offline_minutes'],
                'threshold_minutes' => $offlineSummary['threshold_minutes'],
            ];
        }

        $awarded = [];

        $milestone = $this->calculateDailyMilestoneIncentive($dm);
        if ($milestone) $awarded[] = $milestone;

        $timeBased = $this->calculateTimeBasedIncentive($order, $dm);
        if ($timeBased) $awarded[] = $timeBased;

        $rush = $this->checkAndApplyRushIncentive($order, $dm);
        if ($rush) $awarded[] = $rush;

        $tier = $this->calculateTierBonus($order, $dm);
        if ($tier) $awarded[] = $tier;

        $fuel = $this->calculateFuelIncentive($order, $dm);
        if ($fuel) $awarded[] = $fuel;

        return $awarded;
    }

    public function calculateDailyMilestoneIncentive(DeliveryMan $dm): ?array
    {
        $todaysDeliveries = Order::where('delivery_man_id', $dm->id)
            ->where('order_status', 'delivered')
            ->whereDate('delivered', now()->toDateString())
            ->count();

        $rule = DmDailyIncentiveRule::active()
            ->where('delivery_count', $todaysDeliveries)
            ->first();

        if (!$rule) return null;

        $alreadyGiven = ProvideDMEarning::where('delivery_man_id', $dm->id)
            ->where('incentive_type', 'daily_milestone')
            ->where('incentive_rule_id', $rule->id)
            ->whereDate('created_at', now()->toDateString())
            ->exists();

        if ($alreadyGiven) return null;

        $this->awardIncentive($dm, $rule->bonus_amount, 'daily_milestone', $rule->id, null, [
            'delivery_count' => $todaysDeliveries,
            'rule_title' => $rule->title,
        ]);

        return [
            'type' => 'daily_milestone',
            'amount' => $rule->bonus_amount,
            'message' => "Completed {$todaysDeliveries} deliveries today! Earned bonus.",
        ];
    }

    public function calculateTimeBasedIncentive(Order $order, DeliveryMan $dm): ?array
    {
        $incentives = DmTimeBasedIncentive::active()->get();
        $totalBonus = 0;
        $appliedRules = [];

        foreach ($incentives as $incentive) {
            if (!$incentive->isActiveNow()) continue;

            if ($incentive->zone_id && $incentive->zone_id != $dm->zone_id) continue;
            if ($incentive->module_id && $order->module_id && $incentive->module_id != $order->module_id) continue;

            $bonus = $incentive->calculateBonus($order->order_amount);

            $alreadyGiven = ProvideDMEarning::where('delivery_man_id', $dm->id)
                ->where('incentive_type', 'time_based')
                ->where('incentive_rule_id', $incentive->id)
                ->where('order_id', $order->id)
                ->exists();

            if ($alreadyGiven) continue;

            $this->awardIncentive($dm, $bonus, 'time_based', $incentive->id, $order->id, [
                'rule_title' => $incentive->title,
                'time_window' => $incentive->time_from . ' - ' . $incentive->time_to,
            ]);

            $totalBonus += $bonus;
            $appliedRules[] = $incentive->title;
        }

        if ($totalBonus <= 0) return null;

        return [
            'type' => 'time_based',
            'amount' => $totalBonus,
            'message' => 'Peak hour bonus earned: ' . implode(', ', $appliedRules),
        ];
    }

    public function checkAndApplyRushIncentive(Order $order, DeliveryMan $dm): ?array
    {
        $activeRush = DmRushActivation::currentlyActive()
            ->where('zone_id', $dm->zone_id)
            ->with('rushIncentive')
            ->first();

        if (!$activeRush || !$activeRush->rushIncentive) return null;

        $bonus = $activeRush->rushIncentive->calculateBonus($order->order_amount);

        $alreadyGiven = ProvideDMEarning::where('delivery_man_id', $dm->id)
            ->where('incentive_type', 'rush')
            ->where('order_id', $order->id)
            ->exists();

        if ($alreadyGiven) return null;

        $this->awardIncentive($dm, $bonus, 'rush', $activeRush->rush_incentive_id, $order->id, [
            'rush_activation_id' => $activeRush->id,
            'rule_title' => $activeRush->rushIncentive->title,
        ]);

        return [
            'type' => 'rush',
            'amount' => $bonus,
            'message' => 'Rush bonus earned: ' . $activeRush->rushIncentive->title,
        ];
    }

    public function calculateTierBonus(Order $order, DeliveryMan $dm): ?array
    {
        if (!$dm->current_tier_id) return null;

        $tier = $dm->currentTier;
        if (!$tier || $tier->bonus_per_delivery <= 0) return null;

        $alreadyGiven = ProvideDMEarning::where('delivery_man_id', $dm->id)
            ->where('incentive_type', 'tier_bonus')
            ->where('order_id', $order->id)
            ->exists();

        if ($alreadyGiven) return null;

        $this->awardIncentive($dm, $tier->bonus_per_delivery, 'tier_bonus', $tier->id, $order->id, [
            'tier_name' => $tier->name,
        ]);

        return [
            'type' => 'tier_bonus',
            'amount' => $tier->bonus_per_delivery,
            'message' => $tier->name . ' tier bonus earned.',
        ];
    }

    public function calculateFuelIncentive(Order $order, DeliveryMan $dm): ?array
    {
        $fuelRule = DmFuelIncentive::active()
            ->where(function ($q) use ($dm) {
                $q->whereNull('zone_id')->orWhere('zone_id', $dm->zone_id);
            })
            ->first();

        if (!$fuelRule || $fuelRule->rate_per_km <= 0) return null;

        $alreadyGiven = ProvideDMEarning::where('delivery_man_id', $dm->id)
            ->where('incentive_type', 'fuel')
            ->where('order_id', $order->id)
            ->exists();

        if ($alreadyGiven) return null;

        // Calculate distance using store and customer coordinates
        $store = $order->store;
        if (!$store) return null;

        $storeLat = $store->latitude;
        $storeLng = $store->longitude;
        $custLat = $order->delivery_address ? ($order->delivery_address['latitude'] ?? null) : null;
        $custLng = $order->delivery_address ? ($order->delivery_address['longitude'] ?? null) : null;

        if (!$storeLat || !$storeLng || !$custLat || !$custLng) return null;

        $distanceKm = $this->haversineDistance($storeLat, $storeLng, $custLat, $custLng);
        $bonus = round($distanceKm * $fuelRule->rate_per_km, 2);

        if ($bonus <= 0) return null;

        $this->awardIncentive($dm, $bonus, 'fuel', $fuelRule->id, $order->id, [
            'distance_km' => round($distanceKm, 2),
            'rate_per_km' => $fuelRule->rate_per_km,
            'rule_title' => $fuelRule->title,
        ]);

        return [
            'type' => 'fuel',
            'amount' => $bonus,
            'message' => "Fuel incentive: {$distanceKm}km x ₹{$fuelRule->rate_per_km}/km",
        ];
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

    public function awardIncentive(DeliveryMan $dm, float $amount, string $type, ?int $ruleId, ?int $orderId, array $metadata = []): void
    {
        // 🎁 NEW JOINER BONUS: Apply multiplier based on account age and quality
        $originalAmount = $amount;
        $multiplier = $this->getNewJoinerMultiplier($dm);
        $amount = round($amount * $multiplier, 2);

        // Add multiplier info to metadata
        if ($multiplier != 1.0) {
            $metadata['new_joiner_multiplier'] = $multiplier;
            $metadata['original_amount'] = $originalAmount;
            $metadata['bonus_type'] = $this->getNewJoinerBonusType($dm);
        }

        // 🔧 NEW LOGIC: Check if DM has active shift/attendance
        $todayAttendance = DeliverymanAttendance::where('delivery_man_id', $dm->id)
            ->whereDate('date', now()->toDateString())
            ->whereNotNull('punch_in_time')
            ->first();

        // ⏳ If shift is active (punched in but not punched out), mark as PENDING
        $isPending = $todayAttendance && !$todayAttendance->punch_out_time;

        // Create earnings record with pending status
        ProvideDMEarning::create([
            'delivery_man_id' => $dm->id,
            'amount' => $amount,
            'ref' => "incentive_{$type}_" . ($ruleId ?? 'none'),
            'method' => 'incentive',
            'incentive_type' => $type,
            'incentive_rule_id' => $ruleId,
            'order_id' => $orderId,
            'metadata' => $metadata,
            'status' => $isPending ? 'pending' : 'credited', // NEW: Mark as pending if shift active
            'attendance_id' => $todayAttendance ? $todayAttendance->id : null, // NEW: Link to shift
            'credited_at' => $isPending ? null : now(), // NEW: Only set if credited immediately
        ]);

        // ⚠️ IMPORTANT CHANGE: Only credit to wallet if NOT pending
        if (!$isPending) {
            $wallet = DeliveryManWallet::firstOrCreate(
                ['delivery_man_id' => $dm->id],
                ['collected_cash' => 0, 'total_earning' => 0, 'total_withdrawn' => 0, 'pending_withdraw' => 0, 'incentive_earning' => 0]
            );

            $wallet->increment('incentive_earning', $amount);
            $wallet->increment('total_earning', $amount);

            AccountTransaction::create([
                'from_id' => $dm->id,
                'from_type' => 'deliveryman',
                'current_balance' => $wallet->total_earning,
                'amount' => $amount,
                'method' => 'incentive',
                'ref' => ucfirst(str_replace('_', ' ', $type)) . " incentive",
                'type' => 'incentive',
                'created_by' => 'system',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            // 📝 Log that incentive is pending
            \Log::info("Incentive marked as PENDING (will credit on shift completion)", [
                'dm_id' => $dm->id,
                'amount' => $amount,
                'type' => $type,
                'order_id' => $orderId,
                'attendance_id' => $todayAttendance->id
            ]);
        }

        // Send notification based on status
        if ($dm->fcm_token && ($dm->notify_incentives ?? true)) {
            $title = $isPending ? 'Incentive Accrued!' : 'Incentive Earned!';
            $description = $isPending
                ? "Bonus of ₹{$amount} will be credited when shift ends. " . ($metadata['rule_title'] ?? '')
                : "You earned a bonus of ₹{$amount}. " . ($metadata['rule_title'] ?? '');

            $data = [
                'title' => $title,
                'description' => $description,
                'type' => 'incentive',
                'amount' => $amount,
                'status' => $isPending ? 'pending' : 'credited',
                'image' => asset('public/assets/admin/img/dashboard/statistics/1.png'),
                'order_id' => '',
            ];
            try {
                Helpers::send_push_notif_to_device($dm->fcm_token, $data);
            } catch (\Exception $e) {
                \Log::error("Incentive notification failed for DM {$dm->id}: " . $e->getMessage());
            }
        }
    }

    public function getIncentiveBreakdown(DeliveryMan $dm, ?string $period = 'today'): array
    {
        // Set date range based on period
        $startDate = now()->startOfDay();
        $endDate = now()->endOfDay();
        $periodLabel = 'Today';

        switch ($period) {
            case 'week':
                $startDate = now()->startOfWeek();
                $endDate = now()->endOfWeek();
                $periodLabel = 'This Week';
                break;
            case 'month':
                $startDate = now()->startOfMonth();
                $endDate = now()->endOfMonth();
                $periodLabel = 'This Month';
                break;
        }

        // Get all earnings for the period
        $earnings = ProvideDMEarning::where('delivery_man_id', $dm->id)
            ->where('method', 'incentive')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        $totalEarned = $earnings->sum('amount');

        // Get delivery count for the period
        $deliveryCount = Order::where('delivery_man_id', $dm->id)
            ->where('order_status', 'delivered')
            ->whereBetween('delivered', [$startDate, $endDate])
            ->count();

        $items = [];

        // 1. Daily Milestone Incentives
        $dailyRules = DmDailyIncentiveRule::active()->orderBy('delivery_count')->get();
        foreach ($dailyRules as $rule) {
            $alreadyEarned = $earnings->where('incentive_type', 'daily_milestone')
                ->where('incentive_rule_id', $rule->id)
                ->sum('amount');

            $items[] = [
                'type' => 'delivery',
                'label' => $rule->title ?? "Complete {$rule->delivery_count} Deliveries",
                'description' => $rule->description ?? "Deliver {$rule->delivery_count} orders " . strtolower($periodLabel),
                'amount' => (float) $rule->bonus_amount,
                'earned' => $deliveryCount,
                'target' => $rule->delivery_count,
                'is_completed' => $deliveryCount >= $rule->delivery_count,
                'earned_amount' => (float) $alreadyEarned,
            ];
        }

        // 2. Time-Based Incentives
        $timeIncentives = DmTimeBasedIncentive::active()
            ->where(function ($q) use ($dm) {
                $q->whereNull('zone_id')->orWhere('zone_id', $dm->zone_id);
            })
            ->get();

        foreach ($timeIncentives as $incentive) {
            $earnedCount = $earnings->where('incentive_type', 'time_based')
                ->where('incentive_rule_id', $incentive->id)
                ->count();

            $earnedAmount = $earnings->where('incentive_type', 'time_based')
                ->where('incentive_rule_id', $incentive->id)
                ->sum('amount');

            $items[] = [
                'type' => 'peak_hours',
                'label' => $incentive->title ?? "Peak Hour Deliveries",
                'description' => $incentive->description ?? "Deliver during {$incentive->time_from} - {$incentive->time_to}",
                'amount' => (float) ($incentive->type === 'fixed' ? $incentive->amount : 0),
                'earned' => $earnedCount,
                'target' => null,
                'is_completed' => false,
                'earned_amount' => (float) $earnedAmount,
            ];
        }

        // 3. Rush Hour Incentives
        $activeRush = DmRushActivation::currentlyActive()
            ->where('zone_id', $dm->zone_id)
            ->with('rushIncentive')
            ->first();

        if ($activeRush && $activeRush->rushIncentive) {
            $rushEarned = $earnings->where('incentive_type', 'rush')->sum('amount');
            $rushCount = $earnings->where('incentive_type', 'rush')->count();

            $items[] = [
                'type' => 'rush_hour',
                'label' => $activeRush->rushIncentive->title ?? "Rush Hour Bonus",
                'description' => $activeRush->rushIncentive->description ?? "Active rush hour in your zone",
                'amount' => (float) $activeRush->rushIncentive->amount,
                'earned' => $rushCount,
                'target' => null,
                'is_completed' => false,
                'earned_amount' => (float) $rushEarned,
            ];
        }

        // 4. Performance Tier Bonus
        if ($dm->current_tier_id && $dm->currentTier) {
            $tier = $dm->currentTier;
            $tierEarned = $earnings->where('incentive_type', 'tier_bonus')->sum('amount');
            $tierCount = $earnings->where('incentive_type', 'tier_bonus')->count();

            $items[] = [
                'type' => 'tier_bonus',
                'label' => $tier->name . " Tier Bonus",
                'description' => "Earn ₹{$tier->bonus_per_delivery} per delivery",
                'amount' => (float) $tier->bonus_per_delivery,
                'earned' => $tierCount,
                'target' => null,
                'is_completed' => false,
                'earned_amount' => (float) $tierEarned,
            ];
        }

        // 5. Fuel Incentive
        $fuelRule = DmFuelIncentive::active()
            ->where(function ($q) use ($dm) {
                $q->whereNull('zone_id')->orWhere('zone_id', $dm->zone_id);
            })
            ->first();

        if ($fuelRule) {
            $fuelEarned = $earnings->where('incentive_type', 'fuel')->sum('amount');
            $fuelCount = $earnings->where('incentive_type', 'fuel')->count();

            $items[] = [
                'type' => 'fuel',
                'label' => $fuelRule->title ?? "Fuel Allowance",
                'description' => $fuelRule->description ?? "₹{$fuelRule->rate_per_km} per kilometer traveled",
                'amount' => (float) $fuelRule->rate_per_km,
                'earned' => $fuelCount,
                'target' => null,
                'is_completed' => false,
                'earned_amount' => (float) $fuelEarned,
            ];
        }

        // Calculate total pending (potential earnings not yet achieved)
        $totalPending = 0;
        foreach ($items as $item) {
            if (!$item['is_completed'] && $item['target'] !== null) {
                $totalPending += $item['amount'];
            }
        }

        return [
            'total_earned' => (float) $totalEarned,
            'total_pending' => (float) $totalPending,
            'period' => $periodLabel,
            'delivery_count' => $deliveryCount,
            'items' => $items,
        ];
    }

    /**
     * Credit all pending incentives when shift is completed
     * Called when DM punches out / ends shift
     */
    public function creditPendingIncentives(int $attendanceId): array
    {
        $attendance = DeliverymanAttendance::find($attendanceId);
        if (!$attendance) {
            return ['success' => false, 'message' => 'Attendance not found'];
        }

        $dm = $attendance->deliveryMan;
        if (!$dm) {
            return ['success' => false, 'message' => 'Delivery man not found'];
        }

        // Get all pending incentives for this shift
        $pendingIncentives = ProvideDMEarning::where('attendance_id', $attendanceId)
            ->where('status', 'pending')
            ->get();

        if ($pendingIncentives->isEmpty()) {
            return [
                'success' => true,
                'message' => 'No pending incentives to credit',
                'total_credited' => 0,
                'count' => 0
            ];
        }

        $wallet = DeliveryManWallet::firstOrCreate(
            ['delivery_man_id' => $dm->id],
            ['collected_cash' => 0, 'total_earning' => 0, 'total_withdrawn' => 0, 'pending_withdraw' => 0, 'incentive_earning' => 0]
        );

        $totalAmount = 0;
        $creditedCount = 0;

        DB::beginTransaction();
        try {
            foreach ($pendingIncentives as $incentive) {
                // Update incentive status
                $incentive->status = 'credited';
                $incentive->credited_at = now();
                $incentive->save();

                // Credit to wallet
                $wallet->increment('incentive_earning', $incentive->amount);
                $wallet->increment('total_earning', $incentive->amount);

                // Create account transaction
                AccountTransaction::create([
                    'from_id' => $dm->id,
                    'from_type' => 'deliveryman',
                    'current_balance' => $wallet->fresh()->total_earning,
                    'amount' => $incentive->amount,
                    'method' => 'incentive',
                    'ref' => ucfirst(str_replace('_', ' ', $incentive->incentive_type)) . " incentive (shift completed)",
                    'type' => 'incentive',
                    'created_by' => 'system',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $totalAmount += $incentive->amount;
                $creditedCount++;
            }

            DB::commit();

            // Send summary notification
            if ($dm->fcm_token && ($dm->notify_incentives ?? true)) {
                $data = [
                    'title' => 'Shift Completed - Incentives Credited!',
                    'description' => "All pending incentives (₹{$totalAmount}) have been credited to your wallet. Great work!",
                    'type' => 'shift_incentive_credit',
                    'amount' => $totalAmount,
                    'count' => $creditedCount,
                    'image' => asset('public/assets/admin/img/dashboard/statistics/1.png'),
                    'order_id' => '',
                ];
                try {
                    Helpers::send_push_notif_to_device($dm->fcm_token, $data);
                } catch (\Exception $e) {
                    \Log::error("Shift incentive credit notification failed for DM {$dm->id}: " . $e->getMessage());
                }
            }

            \Log::info("Pending incentives credited on shift completion", [
                'dm_id' => $dm->id,
                'attendance_id' => $attendanceId,
                'total_amount' => $totalAmount,
                'count' => $creditedCount
            ]);

            return [
                'success' => true,
                'message' => "Credited ₹{$totalAmount} from {$creditedCount} pending incentives",
                'total_credited' => $totalAmount,
                'count' => $creditedCount
            ];

        } catch (\Exception $e) {
            DB::rollback();
            \Log::error("Failed to credit pending incentives", [
                'dm_id' => $dm->id,
                'attendance_id' => $attendanceId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to credit incentives: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Wave off (cancel) pending incentives for incomplete shifts
     * Called when DM doesn't punch out properly or shift is incomplete
     *
     * @param int $attendanceId
     * @return array
     */
    public function waveOffPendingIncentives(int $attendanceId): array
    {
        $attendance = DeliverymanAttendance::find($attendanceId);
        if (!$attendance) {
            return ['success' => false, 'message' => 'Attendance not found'];
        }

        $dm = $attendance->deliveryMan;
        if (!$dm) {
            return ['success' => false, 'message' => 'Delivery man not found'];
        }

        // Get all pending incentives for this shift
        $pendingIncentives = ProvideDMEarning::where('attendance_id', $attendanceId)
            ->where('status', 'pending')
            ->get();

        if ($pendingIncentives->isEmpty()) {
            return [
                'success' => true,
                'message' => 'No pending incentives to wave off',
                'total_waved_off' => 0,
                'count' => 0
            ];
        }

        $totalAmount = 0;
        $wavedOffCount = 0;

        DB::beginTransaction();
        try {
            foreach ($pendingIncentives as $incentive) {
                // Mark as waved off (delete or update status)
                // Store the amount for logging
                $totalAmount += $incentive->amount;
                $wavedOffCount++;

                // Delete the pending incentive record
                // (Alternative: add a 'waved_off' status to the enum and update instead of delete)
                $incentive->delete();
            }

            DB::commit();

            // Send notification about waved off incentives
            if ($dm->fcm_token && ($dm->notify_incentives ?? true)) {
                $data = [
                    'title' => 'Shift Incomplete - Incentives Not Credited',
                    'description' => "Pending incentives (₹{$totalAmount}) were not credited because the shift was not completed properly. Please remember to punch out at the end of your shift.",
                    'type' => 'shift_incentive_waved_off',
                    'amount' => $totalAmount,
                    'count' => $wavedOffCount,
                    'image' => asset('public/assets/admin/img/dashboard/statistics/1.png'),
                    'order_id' => '',
                ];
                try {
                    Helpers::send_push_notif_to_device($dm->fcm_token, $data);
                } catch (\Exception $e) {
                    \Log::error("Shift incentive wave-off notification failed for DM {$dm->id}: " . $e->getMessage());
                }
            }

            \Log::info("Pending incentives waved off for incomplete shift", [
                'dm_id' => $dm->id,
                'attendance_id' => $attendanceId,
                'total_amount' => $totalAmount,
                'count' => $wavedOffCount,
                'reason' => 'Shift not completed properly (no punch out)'
            ]);

            return [
                'success' => true,
                'message' => "Waved off ₹{$totalAmount} from {$wavedOffCount} pending incentives",
                'total_waved_off' => $totalAmount,
                'count' => $wavedOffCount
            ];

        } catch (\Exception $e) {
            DB::rollback();
            \Log::error("Failed to wave off pending incentives", [
                'dm_id' => $dm->id,
                'attendance_id' => $attendanceId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to wave off incentives: ' . $e->getMessage()
            ];
        }
    }

    /**
     * 🎁 NEW JOINER BONUS: Calculate multiplier for new delivery boys
     *
     * Safeguard #4: Completion Milestone Unlock
     * Safeguard #2: Minimum Quality Standards
     *
     * @param DeliveryMan $dm
     * @return float Multiplier (0.5 to 2.25)
     */
    private function getNewJoinerMultiplier(DeliveryMan $dm): float
    {
        // ================================
        // SAFEGUARD #2: QUALITY STANDARDS
        // ================================

        // Check security deposit - MUST be paid to get bonus
        if ($dm->security_deposit_status !== 'paid') {
            \Log::info("DM {$dm->id} gets 50% incentives - security deposit not paid");
            return 0.5; // Penalty: Only 50% of regular incentives
        }

        // Check acceptance rate - Must maintain 70%+ to get bonus
        if ($dm->acceptance_rate < 70) {
            \Log::info("DM {$dm->id} gets regular incentives - acceptance rate too low ({$dm->acceptance_rate}%)");
            return 1.0; // No bonus if poor acceptance rate
        }

        // Check customer rating - Must maintain 4.0+ to get full bonus
        $avgRating = $dm->average_rating ?? 5.0;
        if ($avgRating < 4.0) {
            \Log::info("DM {$dm->id} gets reduced bonus - low rating ({$avgRating})");
            return 1.0; // No bonus if poor customer rating
        }

        // ==========================================
        // SAFEGUARD #4: COMPLETION MILESTONE UNLOCK
        // ==========================================

        $totalDeliveries = $dm->total_completed_deliveries ?? 0;

        // Phase 1: Learning (Deliveries 1-2) - Regular incentives
        if ($totalDeliveries < 3) {
            return 1.0; // 100% - Learn the system
        }

        // Phase 2: Getting Started (Deliveries 3-10) - Small boost
        if ($totalDeliveries <= 10) {
            return 1.5; // 150% incentives
        }

        // Phase 3: Full Boost (Deliveries 11-30) - Maximum boost
        if ($totalDeliveries <= 30) {
            return 2.0; // 200% incentives (DOUBLED!)
        }

        // =============================================
        // TIME-BASED BONUS (After 30 deliveries)
        // =============================================

        // Check if still in new joiner period (90 days)
        if (!$dm->new_joiner_bonus_ends_at) {
            // Legacy DM - no bonus period set
            return 1.0;
        }

        if (now()->isAfter($dm->new_joiner_bonus_ends_at)) {
            // Bonus period expired
            return 1.0;
        }

        // Calculate days since joining
        $accountAgeDays = now()->diffInDays($dm->created_at);

        // Week 1-4 (Days 1-30): 150% if completed 30+ deliveries
        if ($accountAgeDays <= 30) {
            return 1.5; // 150% incentives
        }

        // Week 5-12 (Days 31-90): 125%
        if ($accountAgeDays <= 90) {
            return 1.25; // 125% incentives
        }

        // After 90 days: Regular incentives
        return 1.0;
    }

    /**
     * Get the type of new joiner bonus being applied
     *
     * @param DeliveryMan $dm
     * @return string
     */
    private function getNewJoinerBonusType(DeliveryMan $dm): string
    {
        if ($dm->security_deposit_status !== 'paid') {
            return 'penalty_no_deposit';
        }

        if ($dm->acceptance_rate < 70) {
            return 'no_bonus_low_acceptance';
        }

        $totalDeliveries = $dm->total_completed_deliveries ?? 0;

        if ($totalDeliveries < 3) {
            return 'learning_phase';
        }

        if ($totalDeliveries <= 10) {
            return 'getting_started_150%';
        }

        if ($totalDeliveries <= 30) {
            return 'full_boost_200%';
        }

        $accountAgeDays = now()->diffInDays($dm->created_at);

        if ($accountAgeDays <= 30) {
            return 'week_1-4_retention_150%';
        }

        if ($accountAgeDays <= 90) {
            return 'week_5-12_graduation_125%';
        }

        return 'regular';
    }
}
