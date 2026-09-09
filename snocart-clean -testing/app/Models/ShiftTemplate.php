<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShiftTemplate extends Model
{
    protected $fillable = [
        'name',
        'start_time',
        'end_time',
        'is_active',
        'created_by',
        'total_spots',
        'incentive_amount',
        'booking_deadline_hours',
        'zone_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'total_spots' => 'integer',
        'incentive_amount' => 'decimal:2',
        'booking_deadline_hours' => 'integer',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    public function shiftRosters(): HasMany
    {
        return $this->hasMany(ShiftRoster::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(DmShiftBooking::class);
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getBookedSpotsForDate(string $date): int
    {
        // Count active bookings from unified booking system
        return $this->bookings()
            ->where('date', $date)
            ->whereIn('status', ['active', 'scheduled', 'self_booked', 'completed'])
            ->count();
    }

    public function getAvailableSpotsForDate(string $date): int
    {
        $booked = $this->getBookedSpotsForDate($date);
        return max(0, $this->total_spots - $booked);
    }
}
