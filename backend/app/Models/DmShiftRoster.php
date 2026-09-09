<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DmShiftRoster extends Model
{
    protected $fillable = [
        'delivery_man_id', 'shift_template_id', 'date', 'shift_start',
        'shift_end', 'status', 'assigned_by', 'is_off_day', 'notes',
    ];

    protected $casts = [
        'delivery_man_id' => 'integer',
        'shift_template_id' => 'integer',
        'is_off_day' => 'boolean',
        'date' => 'date',
    ];

    public function deliveryMan()
    {
        return $this->belongsTo(DeliveryMan::class);
    }

    public function shiftTemplate()
    {
        return $this->belongsTo(ShiftTemplate::class);
    }

    public function assignedByAdmin()
    {
        return $this->belongsTo(Admin::class, 'assigned_by');
    }
}
