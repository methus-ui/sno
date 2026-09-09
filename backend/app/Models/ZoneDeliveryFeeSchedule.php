<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Class ZoneDeliveryFeeSchedule
 *
 * @property int $id
 * @property int $zone_id
 * @property string $title
 * @property int $day
 * @property string $start_time
 * @property string $end_time
 * @property float $fee_percentage
 * @property string|null $message
 * @property bool $status
 * @property int $priority
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class ZoneDeliveryFeeSchedule extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'zone_id',
        'title',
        'day',
        'start_time',
        'end_time',
        'fee_percentage',
        'message',
        'status',
        'priority',
    ];

    /**
     * @var string[]
     */
    protected $casts = [
        'zone_id' => 'integer',
        'day' => 'integer',
        'fee_percentage' => 'float',
        'status' => 'boolean',
        'priority' => 'integer',
    ];

    /**
     * Day names for display
     */
    public const DAY_NAMES = [
        0 => 'Sunday',
        1 => 'Monday',
        2 => 'Tuesday',
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
        6 => 'Saturday',
    ];

    /**
     * Get the zone that owns this schedule.
     */
    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }

    /**
     * Scope for active schedules.
     */
    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    /**
     * Scope for a specific day.
     */
    public function scopeForDay($query, int $day)
    {
        return $query->where('day', $day);
    }

    /**
     * Scope for schedules that cover a specific time.
     */
    public function scopeForTime($query, string $time)
    {
        return $query->where('start_time', '<=', $time)
                     ->where('end_time', '>=', $time);
    }

    /**
     * Get the day name attribute.
     */
    public function getDayNameAttribute(): string
    {
        return self::DAY_NAMES[$this->day] ?? 'Unknown';
    }

    /**
     * Get the active schedule for a zone at a specific date/time.
     * Returns the highest priority schedule if multiple match.
     *
     * @param int $zoneId
     * @param Carbon|null $dateTime
     * @return self|null
     */
    public static function getActiveScheduleForZone(int $zoneId, ?Carbon $dateTime = null): ?self
    {
        $dateTime = $dateTime ?? Carbon::now();
        $dayOfWeek = $dateTime->dayOfWeek; // 0=Sunday, 6=Saturday
        $time = $dateTime->format('H:i:s');

        return self::where('zone_id', $zoneId)
            ->active()
            ->forDay($dayOfWeek)
            ->forTime($time)
            ->orderByDesc('priority')
            ->first();
    }

    /**
     * Check if the schedule is currently active based on day and time.
     */
    public function isCurrentlyActive(?Carbon $dateTime = null): bool
    {
        if (!$this->status) {
            return false;
        }

        $dateTime = $dateTime ?? Carbon::now();
        $dayOfWeek = $dateTime->dayOfWeek;
        $time = $dateTime->format('H:i:s');

        return $this->day === $dayOfWeek
            && $this->start_time <= $time
            && $this->end_time >= $time;
    }
}
