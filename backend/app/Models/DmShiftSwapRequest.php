<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DmShiftSwapRequest extends Model
{
    protected $fillable = [
        'requester_id', 'target_id', 'original_roster_id',
        'request_type', 'status', 'approved_by', 'reason',
    ];

    protected $casts = [
        'requester_id' => 'integer',
        'target_id' => 'integer',
        'original_roster_id' => 'integer',
    ];

    public function requester()
    {
        return $this->belongsTo(DeliveryMan::class, 'requester_id');
    }

    public function target()
    {
        return $this->belongsTo(DeliveryMan::class, 'target_id');
    }

    public function originalRoster()
    {
        return $this->belongsTo(DmShiftRoster::class, 'original_roster_id');
    }

    public function originalBooking()
    {
        // Alias for the new booking system - uses same field for backwards compatibility
        return $this->belongsTo(DmShiftBooking::class, 'original_roster_id');
    }

    public function approvedByAdmin()
    {
        return $this->belongsTo(Admin::class, 'approved_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
