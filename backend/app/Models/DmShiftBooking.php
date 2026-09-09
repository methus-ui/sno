<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DmShiftBooking extends Model
{
    protected $fillable = [
        'delivery_man_id',
        'shift_template_id',
        'date',
        'status',
        'booked_at',
        'cancelled_at',
        'cancellation_reason',
        'assigned_by',
        'is_off_day',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'booked_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'is_off_day' => 'boolean',
    ];

    public function deliveryMan(): BelongsTo
    {
        return $this->belongsTo(DeliveryMan::class);
    }

    public function shiftTemplate(): BelongsTo
    {
        return $this->belongsTo(ShiftTemplate::class);
    }

    public function assignedByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'assigned_by');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForDate($query, $date)
    {
        return $query->whereDate('date', $date);
    }

    public function cancel(string $reason = null): bool
    {
        $this->status = 'cancelled';
        $this->cancelled_at = now();
        $this->cancellation_reason = $reason;
        return $this->save();
    }
}
