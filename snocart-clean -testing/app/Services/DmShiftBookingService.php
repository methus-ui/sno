<?php

namespace App\Services;

use App\Models\DeliveryMan;
use App\Models\DmShiftBooking;
use App\Models\ShiftTemplate;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Unified Delivery Man Shift Booking Service
 *
 * This service manages shift bookings for delivery men using the unified
 * DmShiftBooking system.
 */
class DmShiftBookingService
{
    /**
     * Get available shift slots for a delivery man on a specific date
     *
     * @param int $dmId
     * @param string $date
     * @return array
     */
    public function getAvailableSlots(int $dmId, string $date): array
    {
        // Get all active shift templates
        $templates = ShiftTemplate::where('is_active', 1)->get();

        // Get all DM's bookings for this date
        $dmBookings = DmShiftBooking::where('delivery_man_id', $dmId)
            ->where('date', $date)
            ->whereIn('status', ['active', 'scheduled', 'self_booked'])
            ->pluck('shift_template_id')
            ->toArray();

        $slots = $templates->map(function ($template) use ($date, $dmId, $dmBookings) {
            $availableSpots = $template->getAvailableSpotsForDate($date);
            $bookedSpots = $template->getBookedSpotsForDate($date);

            // Check if THIS specific shift template is already booked by this DM
            $isBookedByDm = in_array($template->id, $dmBookings);

            // Calculate booking deadline
            $shiftDateTime = Carbon::parse($date . ' ' . $template->start_time);
            $bookingDeadline = $shiftDateTime->copy()->subHours($template->booking_deadline_hours ?? 2);

            // Determine status
            $status = 'available';
            if ($isBookedByDm) {
                $status = 'booked';
            } elseif ($availableSpots <= 0) {
                $status = 'full';
            } elseif (now()->isAfter($bookingDeadline)) {
                $status = 'closed';
            }

            return [
                'id' => $template->id,
                'name' => $template->name,
                'date' => $date,
                'start_time' => Carbon::parse($template->start_time)->format('H:i'),
                'end_time' => Carbon::parse($template->end_time)->format('H:i'),
                'zone' => $template->zone ? $template->zone->name : 'All Zones',
                'zone_id' => $template->zone_id,
                'spots_available' => $availableSpots,
                'spots_booked' => $bookedSpots,
                'total_spots' => $template->total_spots,
                'incentive_amount' => (float) ($template->incentive_amount ?? 0),
                'booking_deadline' => $bookingDeadline->toIso8601String(),
                'status' => $status,
                'can_book' => $status === 'available',
                'is_booked_by_dm' => $isBookedByDm,
            ];
        });

        // Count how many shifts are already booked by this DM
        $bookedCount = count($dmBookings);

        return [
            'already_booked' => $bookedCount > 0,
            'booked_count' => $bookedCount,
            'current_booking' => null, // Deprecated, use slot status instead
            'available_slots' => $slots->values()->all(),
        ];
    }

    /**
     * Book a shift slot for a delivery man
     *
     * @param int $dmId
     * @param int $shiftTemplateId
     * @param string $date
     * @param int|null $assignedBy Admin ID if admin-assigned, null if self-booked
     * @return DmShiftBooking
     * @throws \Exception
     */
    public function bookShift(int $dmId, int $shiftTemplateId, string $date, ?int $assignedBy = null): DmShiftBooking
    {
        return DB::transaction(function () use ($dmId, $shiftTemplateId, $date, $assignedBy) {
            // Get shift template with pessimistic locking FIRST to prevent race conditions.
            // This ensures only one transaction can check/book spots at a time, and also
            // serialises the overlap check so concurrent requests cannot both pass it.
            $template = ShiftTemplate::where('id', $shiftTemplateId)
                ->lockForUpdate()
                ->firstOrFail();

            // Check if DM already has THIS SPECIFIC shift template booked for this date
            $existingShift = DmShiftBooking::where('delivery_man_id', $dmId)
                ->where('shift_template_id', $shiftTemplateId)
                ->where('date', $date)
                ->whereIn('status', ['active', 'scheduled', 'self_booked'])
                ->lockForUpdate()
                ->first();

            if ($existingShift) {
                throw new \Exception('You have already booked this shift for this date.');
            }

            // Check for overlapping shifts (inside the lock so concurrent requests are serialised)
            $this->checkForOverlappingShifts($dmId, $shiftTemplateId, $date);

            // Validate shift is active
            if (!$template->is_active) {
                throw new \Exception('This shift is not currently available.');
            }

            // Check booking deadline (only for self-bookings)
            if (!$assignedBy) {
                $shiftDateTime = Carbon::parse($date . ' ' . $template->start_time);
                $bookingDeadline = $shiftDateTime->copy()->subHours($template->booking_deadline_hours ?? 2);

                if (now()->isAfter($bookingDeadline)) {
                    throw new \Exception('Booking deadline has passed for this shift.');
                }
            }

            // Check available spots
            $availableSpots = $template->getAvailableSpotsForDate($date);
            if ($availableSpots <= 0) {
                throw new \Exception('No spots available for this shift.');
            }

            // Determine status based on who's booking
            $status = $assignedBy ? 'scheduled' : 'self_booked';

            // Create booking
            $booking = DmShiftBooking::create([
                'delivery_man_id' => $dmId,
                'shift_template_id' => $shiftTemplateId,
                'date' => $date,
                'status' => $status,
                'booked_at' => now(),
                'assigned_by' => $assignedBy,
            ]);

            Log::info('Shift booked successfully', [
                'booking_id' => $booking->id,
                'dm_id' => $dmId,
                'shift_template_id' => $shiftTemplateId,
                'date' => $date,
                'assigned_by' => $assignedBy,
            ]);

            return $booking;
        });
    }

    /**
     * Cancel a shift booking
     *
     * @param int $bookingId
     * @param int $dmId
     * @param string|null $reason
     * @return bool
     * @throws \Exception
     */
    public function cancelBooking(int $bookingId, int $dmId, ?string $reason = null): bool
    {
        $booking = DmShiftBooking::where('id', $bookingId)
            ->where('delivery_man_id', $dmId)
            ->first();

        if (!$booking) {
            throw new \Exception('Booking not found.');
        }

        if ($booking->status === 'cancelled') {
            throw new \Exception('This booking is already cancelled.');
        }

        if ($booking->status === 'completed') {
            throw new \Exception('Cannot cancel a completed shift.');
        }

        // Check if shift has already started (optional business rule)
        $shiftTemplate = $booking->shiftTemplate;
        $shiftStartTime = Carbon::parse($booking->date . ' ' . $shiftTemplate->start_time);

        if (now()->isAfter($shiftStartTime)) {
            throw new \Exception('Cannot cancel a shift that has already started.');
        }

        return $booking->cancel($reason);
    }

    /**
     * Get upcoming shifts for a delivery man
     *
     * @param int $dmId
     * @param int $days Number of days to look ahead
     * @return array
     */
    public function getUpcomingShifts(int $dmId, int $days = 7): array
    {
        $startDate = now()->toDateString();
        $endDate = now()->addDays($days)->toDateString();

        // Get bookings from unified system
        $bookings = DmShiftBooking::where('delivery_man_id', $dmId)
            ->whereIn('status', ['active', 'scheduled', 'self_booked', 'completed'])
            ->whereBetween('date', [$startDate, $endDate])
            ->with('shiftTemplate.zone')
            ->orderBy('date')
            ->get();

        $shifts = [];

        // Format bookings
        foreach ($bookings as $booking) {
            $template = $booking->shiftTemplate;
            $shifts[] = [
                'id' => $booking->id,
                'type' => 'booking',
                'date' => $booking->date,
                'shift_name' => $template->name,
                'start_time' => Carbon::parse($template->start_time)->format('H:i'),
                'end_time' => Carbon::parse($template->end_time)->format('H:i'),
                'zone' => $template->zone ? $template->zone->name : 'All Zones',
                'status' => $booking->status,
                'incentive_amount' => (float) ($template->incentive_amount ?? 0),
                // Only self_booked shifts can be cancelled by the DM.
                // 'active' excluded: shift already started, cancelBooking() would reject it.
                // 'scheduled' excluded: admin-assigned shifts cannot be self-cancelled.
                'can_cancel' => $booking->status === 'self_booked',
                'is_off_day' => $booking->is_off_day ?? false,
            ];
        }

        return $shifts;
    }

    /**
     * Get active shift for a delivery man on a specific date
     *
     * @param int $dmId
     * @param string $date
     * @return array|null
     */
    public function getActiveShiftForDate(int $dmId, string $date): ?array
    {
        // Check booking system
        $booking = DmShiftBooking::where('delivery_man_id', $dmId)
            ->where('date', $date)
            ->whereIn('status', ['active', 'scheduled', 'self_booked', 'completed'])
            ->with('shiftTemplate')
            ->first();

        if ($booking) {
            $template = $booking->shiftTemplate;
            return [
                'id' => $booking->id,
                'type' => 'booking',
                'shift_template_id' => $booking->shift_template_id,
                'date' => $booking->date,
                'shift_name' => $template->name,
                'start_time' => Carbon::parse($template->start_time)->format('H:i'),
                'end_time' => Carbon::parse($template->end_time)->format('H:i'),
                'status' => $booking->status,
            ];
        }

        return null;
    }

    /**
     * Check if a delivery man has an active shift at a specific datetime
     * Used by order assignment system
     *
     * @param int $dmId
     * @param Carbon $datetime
     * @return bool
     */
    public function hasActiveShiftAt(int $dmId, Carbon $datetime): bool
    {
        $date = $datetime->toDateString();
        $time = $datetime->format('H:i:s');

        // Check booking system
        return DmShiftBooking::where('delivery_man_id', $dmId)
            ->where('date', $date)
            ->whereIn('status', ['active', 'scheduled', 'self_booked', 'completed'])
            ->whereHas('shiftTemplate', function ($query) use ($time) {
                $query->where('start_time', '<=', $time)
                      ->where('end_time', '>=', $time);
            })
            ->exists();
    }

    /**
     * Check for overlapping shifts
     * Prevents booking shifts that conflict in time on the same day
     *
     * @param int $dmId
     * @param int $shiftTemplateId
     * @param string $date
     * @throws \Exception
     */
    protected function checkForOverlappingShifts(int $dmId, int $shiftTemplateId, string $date): void
    {
        $newShift = ShiftTemplate::findOrFail($shiftTemplateId);
        $newStart = Carbon::parse($newShift->start_time);
        $newEnd = Carbon::parse($newShift->end_time);

        // Get all active bookings for this DM on this date
        $existingBookings = DmShiftBooking::where('delivery_man_id', $dmId)
            ->where('date', $date)
            ->whereIn('status', ['active', 'scheduled', 'self_booked'])
            ->with('shiftTemplate')
            ->get();

        foreach ($existingBookings as $booking) {
            $existingStart = Carbon::parse($booking->shiftTemplate->start_time);
            $existingEnd = Carbon::parse($booking->shiftTemplate->end_time);

            // Check if shifts overlap
            if ($newStart->lt($existingEnd) && $newEnd->gt($existingStart)) {
                throw new \Exception(
                    "This shift overlaps with your existing '{$booking->shiftTemplate->name}' shift " .
                    "({$existingStart->format('H:i')} - {$existingEnd->format('H:i')}). " .
                    "Please choose a non-overlapping shift."
                );
            }
        }
    }

    /**
     * Mark a shift as completed (called by cron job)
     *
     * @param int $bookingId
     * @return bool
     */
    public function markAsCompleted(int $bookingId): bool
    {
        $booking = DmShiftBooking::find($bookingId);

        if (!$booking) {
            return false;
        }

        $booking->status = 'completed';
        return $booking->save();
    }

    /**
     * Get shift statistics for a delivery man
     *
     * @param int $dmId
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function getShiftStats(int $dmId, string $startDate, string $endDate): array
    {
        $bookings = DmShiftBooking::where('delivery_man_id', $dmId)
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        return [
            'total_shifts' => $bookings->count(),
            'completed_shifts' => $bookings->where('status', 'completed')->count(),
            'cancelled_shifts' => $bookings->where('status', 'cancelled')->count(),
            'missed_shifts' => $bookings->where('status', 'missed')->count(),
            'active_shifts' => $bookings->whereIn('status', ['active', 'scheduled', 'self_booked'])->count(),
        ];
    }
}
