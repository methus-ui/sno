<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryTrackingStat extends Model
{
    use HasFactory;

    protected $table = 'delivery_tracking_stats';

    protected $fillable = [
        'order_id',
        'delivery_man_id',
        'idle_start_time',
        'total_idle_seconds',
        'idle_count',
        'store_arrival_time',
        'store_departure_time',
        'store_duration_seconds',
        'customer_arrival_time',
        'customer_departure_time',
        'customer_duration_seconds',
        'is_at_store',
        'is_at_customer',
        'is_idle',
        'last_latitude',
        'last_longitude',
        'last_speed',
        'movement_state',
    ];

    protected $casts = [
        'order_id' => 'integer',
        'delivery_man_id' => 'integer',
        'idle_start_time' => 'datetime',
        'total_idle_seconds' => 'integer',
        'idle_count' => 'integer',
        'store_arrival_time' => 'datetime',
        'store_departure_time' => 'datetime',
        'store_duration_seconds' => 'integer',
        'customer_arrival_time' => 'datetime',
        'customer_departure_time' => 'datetime',
        'customer_duration_seconds' => 'integer',
        'is_at_store' => 'boolean',
        'is_at_customer' => 'boolean',
        'is_idle' => 'boolean',
        'last_latitude' => 'decimal:7',
        'last_longitude' => 'decimal:7',
        'last_speed' => 'decimal:2',
    ];

    // Relationships
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function deliveryMan()
    {
        return $this->belongsTo(DeliveryMan::class, 'delivery_man_id');
    }

    // Helper: Get or create stats for an order
    public static function getOrCreateForOrder($orderId, $deliveryManId)
    {
        return self::firstOrCreate(
            ['order_id' => $orderId],
            ['delivery_man_id' => $deliveryManId]
        );
    }

    // Helper: Format seconds to human readable
    public function getFormattedIdleTimeAttribute()
    {
        return self::formatDuration($this->total_idle_seconds);
    }

    public function getFormattedStoreTimeAttribute()
    {
        return self::formatDuration($this->store_duration_seconds);
    }

    public function getFormattedCustomerTimeAttribute()
    {
        return self::formatDuration($this->customer_duration_seconds);
    }

    public static function formatDuration($seconds)
    {
        if ($seconds < 60) {
            return $seconds . 's';
        } elseif ($seconds < 3600) {
            $mins = floor($seconds / 60);
            $secs = $seconds % 60;
            return $mins . 'm ' . $secs . 's';
        } else {
            $hours = floor($seconds / 3600);
            $mins = floor(($seconds % 3600) / 60);
            return $hours . 'h ' . $mins . 'm';
        }
    }

    // Update location state based on coordinates
    // OPTIMIZED: Only saves to DB if significant state changes occur
    public function updateLocationState($dmLat, $dmLng, $storeLat, $storeLng, $customerLat, $customerLng, $speed = 0)
    {
        $now = now();
        $proximityRadius = 0.1; // 100 meters in km
        $needsSave = false;

        // Calculate distances
        $distToStore = $this->calculateDistance($dmLat, $dmLng, $storeLat, $storeLng);
        $distToCustomer = $this->calculateDistance($dmLat, $dmLng, $customerLat, $customerLng);

        // Check store proximity
        $wasAtStore = $this->is_at_store;
        $isAtStore = $distToStore <= $proximityRadius;

        if ($isAtStore && !$wasAtStore) {
            // Arrived at store
            $this->store_arrival_time = $now;
            $this->is_at_store = true;
            $needsSave = true;
        } elseif (!$isAtStore && $wasAtStore) {
            // Left store - calculate duration
            if ($this->store_arrival_time) {
                $duration = $now->diffInSeconds($this->store_arrival_time);
                $this->store_duration_seconds += $duration;
                $this->store_departure_time = $now;
            }
            $this->is_at_store = false;
            $needsSave = true;
        }

        // Check customer proximity
        $wasAtCustomer = $this->is_at_customer;
        $isAtCustomer = $distToCustomer <= $proximityRadius;

        if ($isAtCustomer && !$wasAtCustomer) {
            // Arrived at customer
            $this->customer_arrival_time = $now;
            $this->is_at_customer = true;
            $needsSave = true;
        } elseif (!$isAtCustomer && $wasAtCustomer) {
            // Left customer - calculate duration
            if ($this->customer_arrival_time) {
                $duration = $now->diffInSeconds($this->customer_arrival_time);
                $this->customer_duration_seconds += $duration;
                $this->customer_departure_time = $now;
            }
            $this->is_at_customer = false;
            $needsSave = true;
        }

        // Check idle state
        $wasIdle = $this->is_idle;
        $isIdle = $speed < 1;
        $movementState = $this->determineMovementState($speed);

        if ($isIdle && !$wasIdle) {
            // Started being idle
            $this->idle_start_time = $now;
            $this->is_idle = true;
            $this->idle_count++;
            $needsSave = true;
        } elseif (!$isIdle && $wasIdle) {
            // Stopped being idle - calculate duration
            if ($this->idle_start_time) {
                $duration = $now->diffInSeconds($this->idle_start_time);
                $this->total_idle_seconds += $duration;
            }
            $this->is_idle = false;
            $this->idle_start_time = null;
            $needsSave = true;
        }

        // OPTIMIZATION: Only update position if it changed significantly (>10 meters) or state changed
        $positionChanged = $this->hasPositionChangedSignificantly($dmLat, $dmLng);

        if ($positionChanged || $needsSave) {
            // Update position and state
            $this->last_latitude = $dmLat;
            $this->last_longitude = $dmLng;
            $this->last_speed = $speed;
            $this->movement_state = $movementState;

            // Use update() instead of save() for better performance
            $this->update([
                'last_latitude' => $dmLat,
                'last_longitude' => $dmLng,
                'last_speed' => $speed,
                'movement_state' => $movementState,
                'is_at_store' => $this->is_at_store,
                'is_at_customer' => $this->is_at_customer,
                'is_idle' => $this->is_idle,
                'idle_count' => $this->idle_count,
                'total_idle_seconds' => $this->total_idle_seconds,
                'store_arrival_time' => $this->store_arrival_time,
                'store_departure_time' => $this->store_departure_time,
                'store_duration_seconds' => $this->store_duration_seconds,
                'customer_arrival_time' => $this->customer_arrival_time,
                'customer_departure_time' => $this->customer_departure_time,
                'customer_duration_seconds' => $this->customer_duration_seconds,
                'idle_start_time' => $this->idle_start_time,
            ]);
        }

        return $this;
    }

    // Helper: Check if position changed significantly (>10 meters)
    private function hasPositionChangedSignificantly($newLat, $newLng)
    {
        if (!$this->last_latitude || !$this->last_longitude) {
            return true;
        }

        $distance = $this->calculateDistance(
            $this->last_latitude,
            $this->last_longitude,
            $newLat,
            $newLng
        );

        // Only update if moved more than 10 meters (0.01 km)
        return $distance > 0.01;
    }

    private function determineMovementState($speed)
    {
        if ($speed >= 5) {
            return 'moving';
        } elseif ($speed >= 1) {
            return 'slow';
        } elseif ($this->idle_count >= 3) {
            return 'stopped';
        } else {
            return 'idle';
        }
    }

    private function calculateDistance($lat1, $lng1, $lat2, $lng2)
    {
        if (!$lat1 || !$lng1 || !$lat2 || !$lng2) return 999;

        $earthRadius = 6371; // km
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLng / 2) * sin($dLng / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }

    // Finalize stats when order is completed/cancelled
    public function finalizeStats()
    {
        $now = now();

        // Finalize store duration if still at store
        if ($this->is_at_store && $this->store_arrival_time) {
            $duration = $now->diffInSeconds($this->store_arrival_time);
            $this->store_duration_seconds += $duration;
            $this->store_departure_time = $now;
            $this->is_at_store = false;
        }

        // Finalize customer duration if still at customer
        if ($this->is_at_customer && $this->customer_arrival_time) {
            $duration = $now->diffInSeconds($this->customer_arrival_time);
            $this->customer_duration_seconds += $duration;
            $this->customer_departure_time = $now;
            $this->is_at_customer = false;
        }

        // Finalize idle duration if still idle
        if ($this->is_idle && $this->idle_start_time) {
            $duration = $now->diffInSeconds($this->idle_start_time);
            $this->total_idle_seconds += $duration;
            $this->is_idle = false;
            $this->idle_start_time = null;
        }

        $this->save();
        return $this;
    }

    // Scope: Get ranking by idle time
    public function scopeRankByIdleTime($query, $direction = 'desc')
    {
        return $query->orderBy('total_idle_seconds', $direction);
    }

    // Scope: Get stats for a delivery man
    public function scopeForDeliveryMan($query, $deliveryManId)
    {
        return $query->where('delivery_man_id', $deliveryManId);
    }

    // Get current duration including ongoing time
    public function getCurrentIdleSeconds()
    {
        $total = $this->total_idle_seconds;
        if ($this->is_idle && $this->idle_start_time) {
            $total += now()->diffInSeconds($this->idle_start_time);
        }
        return $total;
    }

    public function getCurrentStoreDurationSeconds()
    {
        $total = $this->store_duration_seconds;
        if ($this->is_at_store && $this->store_arrival_time) {
            $total += now()->diffInSeconds($this->store_arrival_time);
        }
        return $total;
    }

    public function getCurrentCustomerDurationSeconds()
    {
        $total = $this->customer_duration_seconds;
        if ($this->is_at_customer && $this->customer_arrival_time) {
            $total += now()->diffInSeconds($this->customer_arrival_time);
        }
        return $total;
    }
}
