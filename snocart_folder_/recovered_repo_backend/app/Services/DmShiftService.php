<?php

namespace App\Services;

use App\Models\DeliveryMan;
use App\Models\DmShiftRoster;
use App\Models\DmShiftPreference;
use App\Models\DmShiftSwapRequest;
use App\Models\ShiftTemplate;
use Carbon\Carbon;

/**
 * @deprecated This service is deprecated. Use DmShiftBookingService instead.
 * The dm_shift_rosters table has been migrated to dm_shift_bookings.
 * This class is kept for backward compatibility but should not be used for new code.
 */
class DmShiftService
{
    public function getAvailableShifts(int $dmId, string $date): array
    {
        $existingRoster = DmShiftRoster::where('delivery_man_id', $dmId)
            ->where('date', $date)
            ->first();

        if ($existingRoster) {
            return [
                'already_assigned' => true,
                'current_shift' => $existingRoster,
                'available_shifts' => [],
            ];
        }

        $shifts = ShiftTemplate::where('is_active', 1)->get();

        return [
            'already_assigned' => false,
            'current_shift' => null,
            'available_shifts' => $shifts->map(function ($shift) use ($date) {
                $takenCount = DmShiftRoster::where('shift_template_id', $shift->id)
                    ->where('date', $date)
                    ->count();
                return [
                    'id' => $shift->id,
                    'name' => $shift->name,
                    'start_time' => $shift->start_time,
                    'end_time' => $shift->end_time,
                    'taken_count' => $takenCount,
                ];
            }),
        ];
    }

    public function selfAssignShift(int $dmId, int $shiftTemplateId, string $date): DmShiftRoster
    {
        $existing = DmShiftRoster::where('delivery_man_id', $dmId)
            ->where('date', $date)
            ->first();

        if ($existing) {
            throw new \Exception('You already have a shift assigned for this date.');
        }

        $shift = ShiftTemplate::findOrFail($shiftTemplateId);

        return DmShiftRoster::create([
            'delivery_man_id' => $dmId,
            'shift_template_id' => $shiftTemplateId,
            'date' => $date,
            'shift_start' => $shift->start_time,
            'shift_end' => $shift->end_time,
            'status' => 'self_assigned',
            'assigned_by' => null,
        ]);
    }

    public function adminAssignShift(int $dmId, int $shiftTemplateId, string $date, int $adminId): DmShiftRoster
    {
        $existing = DmShiftRoster::where('delivery_man_id', $dmId)
            ->where('date', $date)
            ->first();

        if ($existing) {
            $existing->delete();
        }

        $shift = ShiftTemplate::findOrFail($shiftTemplateId);

        return DmShiftRoster::create([
            'delivery_man_id' => $dmId,
            'shift_template_id' => $shiftTemplateId,
            'date' => $date,
            'shift_start' => $shift->start_time,
            'shift_end' => $shift->end_time,
            'status' => 'scheduled',
            'assigned_by' => $adminId,
        ]);
    }

    public function getUpcomingShifts(int $dmId, int $days = 7): array
    {
        return DmShiftRoster::where('delivery_man_id', $dmId)
            ->where('date', '>=', now()->toDateString())
            ->where('date', '<=', now()->addDays($days)->toDateString())
            ->orderBy('date')
            ->with('shiftTemplate')
            ->get()
            ->toArray();
    }

    public function requestShiftSwap(int $requesterId, int $rosterId, ?int $targetId, string $reason): DmShiftSwapRequest
    {
        $roster = DmShiftRoster::where('id', $rosterId)
            ->where('delivery_man_id', $requesterId)
            ->firstOrFail();

        return DmShiftSwapRequest::create([
            'requester_id' => $requesterId,
            'target_id' => $targetId,
            'original_roster_id' => $rosterId,
            'request_type' => $targetId ? 'swap' : 'giveaway',
            'status' => 'pending',
            'reason' => $reason,
        ]);
    }

    public function approveSwap(int $swapRequestId, int $adminId): DmShiftSwapRequest
    {
        $swap = DmShiftSwapRequest::findOrFail($swapRequestId);
        $swap->update([
            'status' => 'approved',
            'approved_by' => $adminId,
        ]);

        $originalRoster = DmShiftRoster::find($swap->original_roster_id);

        if ($swap->request_type === 'swap' && $swap->target_id && $originalRoster) {
            $targetRoster = DmShiftRoster::where('delivery_man_id', $swap->target_id)
                ->where('date', $originalRoster->date)
                ->first();

            if ($targetRoster) {
                $tempDm = $originalRoster->delivery_man_id;
                $originalRoster->update(['delivery_man_id' => $targetRoster->delivery_man_id]);
                $targetRoster->update(['delivery_man_id' => $tempDm]);
            }
        } elseif ($swap->request_type === 'giveaway' && $originalRoster) {
            $originalRoster->update(['status' => 'cancelled']);
        }

        return $swap;
    }

    public function rejectSwap(int $swapRequestId, int $adminId): DmShiftSwapRequest
    {
        $swap = DmShiftSwapRequest::findOrFail($swapRequestId);
        $swap->update([
            'status' => 'rejected',
            'approved_by' => $adminId,
        ]);
        return $swap;
    }

    public function savePreferences(int $dmId, array $preferences): void
    {
        foreach ($preferences as $pref) {
            DmShiftPreference::updateOrCreate(
                ['delivery_man_id' => $dmId, 'day_of_week' => $pref['day_of_week']],
                [
                    'preferred_shift_template_id' => $pref['shift_template_id'] ?? null,
                    'is_available' => $pref['is_available'] ?? true,
                ]
            );
        }
    }
}
