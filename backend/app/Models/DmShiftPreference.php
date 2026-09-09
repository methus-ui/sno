<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DmShiftPreference extends Model
{
    protected $fillable = [
        'delivery_man_id', 'day_of_week', 'preferred_shift_template_id', 'is_available',
    ];

    protected $casts = [
        'delivery_man_id' => 'integer',
        'day_of_week' => 'integer',
        'preferred_shift_template_id' => 'integer',
        'is_available' => 'boolean',
    ];

    public function deliveryMan()
    {
        return $this->belongsTo(DeliveryMan::class);
    }

    public function shiftTemplate()
    {
        return $this->belongsTo(ShiftTemplate::class, 'preferred_shift_template_id');
    }
}
