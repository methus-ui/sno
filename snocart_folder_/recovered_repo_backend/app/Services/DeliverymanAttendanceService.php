<?php

namespace App\Services;

use App\Models\DeliverymanAttendance;
use App\Models\DmShiftBooking;
use Carbon\Carbon;

class DeliverymanAttendanceService
{
    // Offline time threshold in minutes (default: 5 minutes)
    const OFFLINE_THRESHOLD_MINUTES = 5;

    /**
     * Get offline threshold from settings
     */
    private function getOfflineThreshold(): int
    {
        $setting = \App\Models\BusinessSetting::where('key', 'dm_offline_threshold')->first();
        return $setting ? (json_decode($setting->value, true)['minutes'] ?? self::OFFLINE_THRESHOLD_MINUTES) : self::OFFLINE_THRESHOLD_MINUTES;
    }

    /**
     * Get break time settings
     */
    private function getBreakTimeSettings(): array
    {
        $enabledSetting = \App\Models\BusinessSetting::where('key', 'dm_break_time_enabled')->first();
        $minutesSetting = \App\Models\BusinessSetting::where('key', 'dm_break_time_minutes')->first();

        return [
            'enabled' => $enabledSetting ? (json_decode($enabledSetting->value, true)['status'] ?? 0) : 0,
            'minutes' => $minutesSetting ? (json_decode($minutesSetting->value, true)['minutes'] ?? 15) : 15,
        ];
    }

    /**
     * Mark punch in for a deliveryman
     *
     * @param int $deliveryManId
     * @param string $source - Where the punch-in was triggered from (e.g., 'login', 'active_toggle')
     * @return DeliverymanAttendance|null
     */
    public function markPunchIn(int $deliveryManId, string $source = 'active_toggle'): ?DeliverymanAttendance
    {
        $today = Carbon::today()->toDateString();

        // Check if attendance record exists for today
        $attendance = DeliverymanAttendance::where('delivery_man_id', $deliveryManId)
            ->whereDate('date', $today)
            ->first();

        if (!$attendance) {
            // Create new attendance record with punch_in
            $attendance = DeliverymanAttendance::create([
                'delivery_man_id' => $deliveryManId,
                'date' => $today,
                'punch_in_time' => Carbon::now()->format('H:i:s'),
                'status' => 'partial',
                'notes' => "Auto punch-in on {$source}",
                'last_online_at' => Carbon::now(),
                'is_currently_offline' => false,
                'incentive_eligible' => true,
            ]);
        } elseif (!$attendance->punch_in_time) {
            // Update existing record if punch_in was not set
            $attendance->punch_in_time = Carbon::now()->format('H:i:s');
            $attendance->notes = "Auto punch-in on {$source}";
            $attendance->last_online_at = Carbon::now();
            $attendance->is_currently_offline = false;
            $attendance->save();
        } else {
            // 🔄 Punch in when attendance already exists = END ACTIVE BREAK
            $activeBreak = \App\Models\EmployeeBreak::where('attendance_id', $attendance->id)
                ->whereNotNull('break_start')
                ->whereNull('break_end')
                ->first();

            if ($activeBreak) {
                // End the break
                $activeBreak->break_end = Carbon::now();
                $activeBreak->calculateDuration();
                $activeBreak->save();

                $attendance->notes = ($attendance->notes ?? '') . " | Break ended on {$source}";
                $attendance->last_online_at = Carbon::now();
                $attendance->is_currently_offline = false;
                $attendance->save();

                \Log::info("Break ended - DM resumed work", [
                    'dm_id' => $deliveryManId,
                    'attendance_id' => $attendance->id,
                    'break_id' => $activeBreak->id,
                    'break_duration_minutes' => $activeBreak->duration_minutes
                ]);
            } else {
                // Just update last online time
                $attendance->last_online_at = Carbon::now();
                $attendance->is_currently_offline = false;
                $attendance->save();
            }
        }

        // Activate booked shift if within its time window
        try {
            $this->activateShiftBooking($deliveryManId);
        } catch (\Exception $e) {
            \Log::error("activateShiftBooking failed for DM#{$deliveryManId}: " . $e->getMessage());
        }

        return $attendance;
    }

    /**
     * Mark punch out for a deliveryman
     *
     * @param int $deliveryManId
     * @param string $source - Where the punch-out was triggered from (e.g., 'logout', 'active_toggle')
     * @return DeliverymanAttendance|null
     */
    public function markPunchOut(int $deliveryManId, string $source = 'active_toggle'): ?DeliverymanAttendance
    {
        $today = Carbon::today()->toDateString();

        // Find today's attendance record
        $attendance = DeliverymanAttendance::where('delivery_man_id', $deliveryManId)
            ->whereDate('date', $today)
            ->first();

        if ($attendance && $attendance->punch_in_time && !$attendance->punch_out_time) {
            // If DM was offline when punching out, close that offline session first
            if ($attendance->is_currently_offline) {
                $this->markOnline($deliveryManId);
                $attendance->refresh();
            }

            // 🕐 CHECK: Is this a break or actual shift end?
            $breakSettings = $this->getBreakTimeSettings();
            $breakThresholdMinutes = ($breakSettings['minutes'] ?? 30) + 3; // 30 + 3 = 33 minutes

            // Calculate duration since punch in
            $punchInTime = Carbon::parse($attendance->punch_in_time);
            $now = Carbon::now();
            $minutesSincePunchIn = $punchInTime->diffInMinutes($now);

            // If punch out within break threshold, treat as BREAK, not shift end
            if ($minutesSincePunchIn <= $breakThresholdMinutes) {
                \Log::info("Punch out treated as BREAK (too early)", [
                    'dm_id' => $deliveryManId,
                    'minutes_since_punch_in' => $minutesSincePunchIn,
                    'threshold' => $breakThresholdMinutes,
                    'action' => 'Creating break record, NOT ending shift'
                ]);

                // Create a break record instead of ending shift
                \App\Models\EmployeeBreak::create([
                    'attendance_id' => $attendance->id,
                    'break_start' => $now,
                    'break_end' => null, // Will be set when they "punch in" again
                    'notes' => "Auto break on {$source} (< {$breakThresholdMinutes} min)",
                ]);

                $attendance->notes = ($attendance->notes ?? '') . " | Break started on {$source} ({$minutesSincePunchIn} min since punch-in)";
                $attendance->save();

                // ❌ DO NOT credit incentives - this is just a break
                // ❌ DO NOT set punch_out_time - shift continues

                return $attendance;
            }

            // ✅ ACTUAL SHIFT END (punch out after threshold)
            \Log::info("Punch out treated as SHIFT END", [
                'dm_id' => $deliveryManId,
                'minutes_since_punch_in' => $minutesSincePunchIn,
                'threshold' => $breakThresholdMinutes,
                'action' => 'Ending shift, crediting incentives'
            ]);

            // Set punch_out time
            $attendance->punch_out_time = Carbon::now()->format('H:i:s');
            $attendance->notes = ($attendance->notes ?? '') . " | Punch-out on {$source}";
            $attendance->save();

            // Calculate working hours and update status
            $attendance->calculateWorkingHours();

            // 💰 Credit pending incentives when shift is completed
            try {
                $incentiveService = new DmIncentiveService();
                $result = $incentiveService->creditPendingIncentives($attendance->id);

                \Log::info("Punch out - Incentive crediting result", [
                    'dm_id' => $deliveryManId,
                    'attendance_id' => $attendance->id,
                    'result' => $result
                ]);
            } catch (\Exception $e) {
                \Log::error("Failed to credit incentives on punch out for DM {$deliveryManId}: " . $e->getMessage());
            }

            // ✅ Mark booked shift slot as completed
            try {
                $this->completeShiftBooking($deliveryManId);
            } catch (\Exception $e) {
                \Log::error("completeShiftBooking failed for DM#{$deliveryManId}: " . $e->getMessage());
            }
        }

        return $attendance;
    }

    /**
     * Mark delivery man as OFFLINE (went offline during shift)
     *
     * @param int $deliveryManId
     * @return DeliverymanAttendance|null
     */
    public function markOffline(int $deliveryManId): ?DeliverymanAttendance
    {
        $today = Carbon::today()->toDateString();

        $attendance = DeliverymanAttendance::where('delivery_man_id', $deliveryManId)
            ->whereDate('date', $today)
            ->first();

        if (!$attendance) {
            // No attendance record yet, create one
            $attendance = DeliverymanAttendance::create([
                'delivery_man_id' => $deliveryManId,
                'date' => $today,
                'status' => 'partial',
            ]);
        }

        // Only mark offline if currently online
        if (!$attendance->is_currently_offline) {
            $attendance->is_currently_offline = true;
            $attendance->last_offline_at = Carbon::now();
            $attendance->offline_count = ($attendance->offline_count ?? 0) + 1;
            $attendance->save();

            \Log::info("DM #{$deliveryManId} marked offline", [
                'time' => Carbon::now()->toDateTimeString(),
                'offline_count' => $attendance->offline_count
            ]);
        }

        return $attendance;
    }

    /**
     * Mark delivery man as ONLINE (came back online after being offline)
     *
     * @param int $deliveryManId
     * @return DeliverymanAttendance|null
     */
    public function markOnline(int $deliveryManId): ?DeliverymanAttendance
    {
        $today = Carbon::today()->toDateString();

        $attendance = DeliverymanAttendance::where('delivery_man_id', $deliveryManId)
            ->whereDate('date', $today)
            ->first();

        if (!$attendance) {
            // Create attendance record when coming online
            return $this->markPunchIn($deliveryManId, 'auto_online');
        }

        // Only process if currently offline
        if ($attendance->is_currently_offline && $attendance->last_offline_at) {
            $offlineStart = Carbon::parse($attendance->last_offline_at);
            $offlineEnd = Carbon::now();
            $offlineDurationMinutes = $offlineStart->diffInMinutes($offlineEnd);

            // Update total offline time
            $attendance->total_offline_minutes = ($attendance->total_offline_minutes ?? 0) + $offlineDurationMinutes;

            // Record this offline session
            $offlineSessions = $attendance->offline_sessions ? json_decode($attendance->offline_sessions, true) : [];
            $offlineSessions[] = [
                'start' => $offlineStart->toDateTimeString(),
                'end' => $offlineEnd->toDateTimeString(),
                'duration_minutes' => $offlineDurationMinutes,
            ];
            $attendance->offline_sessions = json_encode($offlineSessions);

            // Check if total offline time exceeds threshold
            $threshold = $this->getOfflineThreshold();
            if ($attendance->total_offline_minutes > $threshold) {
                $attendance->incentive_eligible = false;

                \Log::warning("DM #{$deliveryManId} exceeded offline threshold", [
                    'total_offline_minutes' => $attendance->total_offline_minutes,
                    'threshold' => $threshold,
                    'incentive_eligible' => false
                ]);
            }

            // Mark as online
            $attendance->is_currently_offline = false;
            $attendance->last_online_at = Carbon::now();
            $attendance->save();

            \Log::info("DM #{$deliveryManId} marked online", [
                'time' => Carbon::now()->toDateTimeString(),
                'offline_duration_minutes' => $offlineDurationMinutes,
                'total_offline_minutes' => $attendance->total_offline_minutes
            ]);

            // Send push notifications based on offline time
            try {
                $notificationService = new OfflineNotificationService();

                // Send "back online" notification
                $notificationService->sendBackOnlineNotification(
                    $deliveryManId,
                    $offlineDurationMinutes,
                    $attendance->total_offline_minutes
                );

                // Check and send warning notifications if approaching/exceeding threshold
                $notificationService->checkAndNotify($deliveryManId);
            } catch (\Exception $e) {
                \Log::error("Failed to send offline notifications to DM #{$deliveryManId}: " . $e->getMessage());
            }
        }

        return $attendance;
    }

    /**
     * Check if delivery man is eligible for incentives today
     *
     * @param int $deliveryManId
     * @return bool
     */
    public function isEligibleForIncentives(int $deliveryManId): bool
    {
        $today = Carbon::today()->toDateString();

        $attendance = DeliverymanAttendance::where('delivery_man_id', $deliveryManId)
            ->whereDate('date', $today)
            ->first();

        if (!$attendance) {
            return true; // No attendance record, eligible by default
        }

        return $attendance->incentive_eligible ?? true;
    }

    /**
     * Activate the DM's booked shift for today when they punch in.
     * self_booked / scheduled → active  (only if shift has already started)
     */
    private function activateShiftBooking(int $deliveryManId): void
    {
        $today = Carbon::today()->toDateString();
        $now   = Carbon::now();

        $booking = DmShiftBooking::where('delivery_man_id', $deliveryManId)
            ->where('date', $today)
            ->whereIn('status', ['self_booked', 'scheduled'])
            ->with('shiftTemplate')
            ->get()
            ->first(function ($b) use ($now) {
                // Only activate if shift has started (start_time <= now <= end_time)
                $start = Carbon::parse($b->date->toDateString() . ' ' . $b->shiftTemplate->start_time);
                $end   = Carbon::parse($b->date->toDateString() . ' ' . $b->shiftTemplate->end_time);
                return $now->gte($start) && $now->lte($end);
            });

        if ($booking) {
            $booking->status = 'active';
            $booking->save();
            \Log::info("Shift booking #{$booking->id} set to ACTIVE on punch-in", ['dm_id' => $deliveryManId]);
        }
    }

    /**
     * Complete the DM's active shift booking for today when they punch out.
     * active / self_booked / scheduled → completed  (only if shift end_time has passed)
     */
    private function completeShiftBooking(int $deliveryManId): void
    {
        $today = Carbon::today()->toDateString();
        $now   = Carbon::now();

        $bookings = DmShiftBooking::where('delivery_man_id', $deliveryManId)
            ->where('date', $today)
            ->whereIn('status', ['active', 'self_booked', 'scheduled'])
            ->with('shiftTemplate')
            ->get();

        foreach ($bookings as $booking) {
            $end = Carbon::parse($booking->date->toDateString() . ' ' . $booking->shiftTemplate->end_time);
            // Mark completed if shift end_time has passed OR DM is deliberately punching out
            if ($now->gte($end) || $booking->status === 'active') {
                $booking->status = 'completed';
                $booking->save();
                \Log::info("Shift booking #{$booking->id} set to COMPLETED on punch-out", ['dm_id' => $deliveryManId]);
            }
        }
    }

    /**
     * Get offline time summary for a delivery man today
     *
     * @param int $deliveryManId
     * @return array
     */
    public function getOfflineTimeSummary(int $deliveryManId): array
    {
        $today = Carbon::today()->toDateString();
        $threshold = $this->getOfflineThreshold();

        $attendance = DeliverymanAttendance::where('delivery_man_id', $deliveryManId)
            ->whereDate('date', $today)
            ->first();

        if (!$attendance) {
            return [
                'total_offline_minutes' => 0,
                'offline_count' => 0,
                'incentive_eligible' => true,
                'threshold_minutes' => $threshold,
                'remaining_allowed_minutes' => $threshold,
            ];
        }

        return [
            'total_offline_minutes' => $attendance->total_offline_minutes ?? 0,
            'offline_count' => $attendance->offline_count ?? 0,
            'incentive_eligible' => $attendance->incentive_eligible ?? true,
            'threshold_minutes' => $threshold,
            'remaining_allowed_minutes' => max(0, $threshold - ($attendance->total_offline_minutes ?? 0)),
            'offline_sessions' => $attendance->offline_sessions ? json_decode($attendance->offline_sessions, true) : [],
        ];
    }
}
