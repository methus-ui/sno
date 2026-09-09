<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class DeliverymanAttendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'delivery_man_id',
        'date',
        'punch_in_time',
        'punch_out_time',
        'working_hours',
        'status',
        'notes',
        'offline_count',
        'total_offline_minutes',
        'last_online_at',
        'last_offline_at',
        'is_currently_offline',
        'incentive_eligible',
        'offline_sessions'
    ];

    protected $casts = [
        'date' => 'date',
        'punch_in_time' => 'datetime:H:i:s',
        'punch_out_time' => 'datetime:H:i:s',
        'working_hours' => 'decimal:2',
        'offline_count' => 'integer',
        'total_offline_minutes' => 'integer',
        'last_online_at' => 'datetime',
        'last_offline_at' => 'datetime',
        'is_currently_offline' => 'boolean',
        'incentive_eligible' => 'boolean',
        'offline_sessions' => 'array'
    ];

    public function deliveryMan()
    {
        return $this->belongsTo(DeliveryMan::class, 'delivery_man_id');
    }

    public function calculateWorkingHours()
    {
        if ($this->punch_in_time && $this->punch_out_time) {
            $punchIn = Carbon::parse($this->punch_in_time);
            $punchOut = Carbon::parse($this->punch_out_time);
            $hours = $punchOut->diffInMinutes($punchIn) / 60;
            $this->working_hours = round($hours, 2);
            
            // Update status based on working hours
            if ($hours >= 8) {
                $this->status = 'present';
            } elseif ($hours > 0) {
                $this->status = 'partial';
            } else {
                $this->status = 'absent';
            }
            
            $this->save();
        }
    }

    public static function getAttendanceReport($deliveryManId = null, $startDate = null, $endDate = null)
    {
        $query = self::with('deliveryMan');

        if ($deliveryManId) {
            $query->where('delivery_man_id', $deliveryManId);
        }

        if ($startDate) {
            $query->where('date', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('date', '<=', $endDate);
        }

        return $query->orderBy('date', 'desc')->get();
    }

    public function getFormattedWorkingHoursAttribute()
    {
        $hours = floor($this->working_hours);
        $minutes = ($this->working_hours - $hours) * 60;
        return sprintf('%02d:%02d', $hours, $minutes);
    }

}