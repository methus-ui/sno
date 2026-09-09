<?php

namespace App\Jobs;

use App\Models\DeliveryHistory;
use App\Models\DeliveryTrackingStat;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class RecordDeliveryLocationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 10; // 10 seconds max
    public int $backoff = 1; // Retry after 1 second

    public function __construct(
        public int $dmId,
        public float $latitude,
        public float $longitude,
        public ?string $location,
        public float $speed = 0,
    ) {}

    public function handle(): void
    {
        // PERFORMANCE FIX: Use cache-based throttling instead of GET_LOCK
        // This reduces database lock contention significantly
        $lockName = "dm_location_{$this->dmId}";
        $cacheKey = "dm_updating_{$this->dmId}";

        // Check if an update is already in progress (throttling)
        if (Cache::has($cacheKey)) {
            // Update is in progress or too recent, cache this update
            $this->cacheLocationUpdate();
            return;
        }

        try {
            // Set cache flag to prevent concurrent updates (1 second)
            Cache::put($cacheKey, true, 1);

            // ✅ CALCULATE SPEED from previous location if not provided
            if ($this->speed == 0) {
                $calculatedSpeed = $this->calculateSpeedFromPreviousLocation();
                \Log::info("Speed calculation for DM {$this->dmId}: {$calculatedSpeed} km/h");
                $this->speed = $calculatedSpeed;
            }

            // Try to acquire advisory lock with shorter timeout
            $lockTimeout = 0; // Don't wait - fail fast
            $lockAcquired = DB::selectOne(
                "SELECT GET_LOCK(?, ?) as lock_result",
                [$lockName, $lockTimeout]
            )->lock_result;

            if (!$lockAcquired) {
                // If we can't get lock immediately, cache the update and skip
                // This prevents job failures when updates are too frequent
                $this->cacheLocationUpdate();
                return;
            }

            $now = now();

            // Use raw INSERT...ON DUPLICATE KEY UPDATE with minimal lock time
            // This is faster than Laravel's upsert() for single row updates
            DB::statement("
                INSERT INTO delivery_histories
                    (delivery_man_id, latitude, longitude, speed, location, time, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    latitude = VALUES(latitude),
                    longitude = VALUES(longitude),
                    speed = VALUES(speed),
                    location = VALUES(location),
                    time = VALUES(time),
                    updated_at = VALUES(updated_at)
            ", [
                $this->dmId,
                $this->latitude,
                $this->longitude,
                $this->speed,
                $this->location,
                $now,
                $now,
                $now,
            ]);

            // Update tracking stats asynchronously to avoid blocking
            $this->updateTrackingStatsAsync();

            // ✅ Broadcast location update via WebSocket
            $this->broadcastLocationUpdate();

        } catch (\Exception $e) {
            \Log::error("Failed to record delivery location", [
                'dm_id' => $this->dmId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e; // Re-throw to trigger retry
        } finally {
            // Always release the lock
            DB::statement("SELECT RELEASE_LOCK(?)", [$lockName]);
        }
    }

    /**
     * Calculate speed from previous location update
     * Uses Haversine formula to calculate distance, then speed = distance/time
     *
     * @return float Speed in km/h
     */
    private function calculateSpeedFromPreviousLocation(): float
    {
        try {
            // Get previous location for this delivery man
            // NOTE: There's a UNIQUE constraint on delivery_man_id, so there's only 1 record per DM
            $previous = DeliveryHistory::where('delivery_man_id', $this->dmId)
                ->first(['latitude', 'longitude', 'updated_at']);

            if (!$previous) {
                // No previous location, can't calculate speed
                \Log::info("No previous location found for DM {$this->dmId}");
                return 0.0;
            }

            // Check if this is actually a different location (not the same record)
            if (abs($previous->latitude - $this->latitude) < 0.00001 &&
                abs($previous->longitude - $this->longitude) < 0.00001) {
                // Same location, use cached previous location if available
                $cached = Cache::get("dm_prev_location_{$this->dmId}");
                if ($cached) {
                    $previous = (object) $cached;
                } else {
                    \Log::info("Same location, no cached previous for DM {$this->dmId}");
                    return 0.0;
                }
            } else {
                // Different location, cache current DB values before overwriting
                Cache::put("dm_prev_location_{$this->dmId}", [
                    'latitude' => $previous->latitude,
                    'longitude' => $previous->longitude,
                    'updated_at' => $previous->updated_at,
                ], 600); // Cache for 10 minutes
            }

            // Calculate time difference in seconds
            $prevTime = $previous->updated_at instanceof \Carbon\Carbon
                ? $previous->updated_at
                : \Carbon\Carbon::parse($previous->updated_at);
            $timeDiff = now()->diffInSeconds($prevTime);

            // Ignore if time difference is too small (< 3 seconds) or too large (> 300 seconds)
            if ($timeDiff < 3 || $timeDiff > 300) {
                return 0.0;
            }

            // Calculate distance using Haversine formula
            $distance = $this->haversineDistance(
                $previous->latitude,
                $previous->longitude,
                $this->latitude,
                $this->longitude
            );

            // Convert distance to meters
            $distanceMeters = $distance * 1000;

            // Ignore if distance is too small (< 5 meters) - likely GPS noise
            if ($distanceMeters < 5) {
                return 0.0;
            }

            // Calculate speed: distance (km) / time (hours) = km/h
            $timeHours = $timeDiff / 3600;
            $speed = $distance / $timeHours;

            // Cap speed at 200 km/h (invalid GPS reading if higher)
            if ($speed > 200) {
                return 0.0;
            }

            return round($speed, 2);

        } catch (\Exception $e) {
            \Log::warning("Failed to calculate speed for DM {$this->dmId}: " . $e->getMessage());
            return 0.0;
        }
    }

    /**
     * Haversine formula to calculate distance between two coordinates
     *
     * @param float $lat1 Latitude 1
     * @param float $lon1 Longitude 1
     * @param float $lat2 Latitude 2
     * @param float $lon2 Longitude 2
     * @return float Distance in kilometers
     */
    private function haversineDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371; // Earth's radius in kilometers

        $latFrom = deg2rad($lat1);
        $lonFrom = deg2rad($lon1);
        $latTo = deg2rad($lat2);
        $lonTo = deg2rad($lon2);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(
            pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)
        ));

        return $angle * $earthRadius;
    }

    /**
     * Cache location update when lock can't be acquired
     * The next successful update will use the latest cached data
     */
    private function cacheLocationUpdate(): void
    {
        Cache::put(
            "dm_pending_location_{$this->dmId}",
            [
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
                'location' => $this->location,
                'speed' => $this->speed,
                'time' => now(),
            ],
            60 // Cache for 60 seconds
        );
    }

    /**
     * Broadcast location update via WebSocket to admin panel
     * Sends real-time updates to all connected clients
     */
    private function broadcastLocationUpdate(): void
    {
        try {
            event(new \App\Events\DeliveryManLocationUpdated(
                $this->dmId,
                [
                    'latitude' => $this->latitude,
                    'longitude' => $this->longitude,
                    'speed' => $this->speed,
                    'location' => $this->location,
                    'timestamp' => now()->toIso8601String(),
                ]
            ));

            \Log::info("📡 WebSocket broadcast sent for DM {$this->dmId} - Speed: {$this->speed} km/h");

        } catch (\Exception $e) {
            // Log but don't fail - broadcasting is not critical
            \Log::warning("Failed to broadcast location update for DM {$this->dmId}: " . $e->getMessage());
        }
    }

    /**
     * Update tracking stats for active orders in a separate process
     * This runs after the location is saved to avoid holding the lock
     */
    private function updateTrackingStatsAsync(): void
    {
        try {
            // Get active orders with minimal query
            $activeOrders = Order::select(['id', 'delivery_man_id', 'order_status', 'store_id', 'delivery_address'])
                ->where('delivery_man_id', $this->dmId)
                ->whereIn('order_status', ['accepted', 'confirmed', 'processing', 'handover', 'picked_up'])
                ->with(['store:id,latitude,longitude'])
                ->get();

            if ($activeOrders->isEmpty()) {
                return;
            }

            foreach ($activeOrders as $order) {
                $storeLat = $order->store->latitude ?? null;
                $storeLng = $order->store->longitude ?? null;

                $deliveryAddress = json_decode($order->delivery_address, true);
                $customerLat = $deliveryAddress['latitude'] ?? null;
                $customerLng = $deliveryAddress['longitude'] ?? null;

                // Use firstOrNew instead of getOrCreateForOrder to avoid extra queries
                $stats = DeliveryTrackingStat::firstOrNew(
                    ['order_id' => $order->id],
                    ['delivery_man_id' => $this->dmId]
                );

                $stats->updateLocationState(
                    $this->latitude,
                    $this->longitude,
                    $storeLat,
                    $storeLng,
                    $customerLat,
                    $customerLng,
                    $this->speed
                );
            }
        } catch (\Exception $e) {
            // Log but don't fail the job - tracking stats are secondary
            \Log::warning("Failed to update tracking stats", [
                'dm_id' => $this->dmId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        \Log::error("RecordDeliveryLocationJob failed permanently", [
            'dm_id' => $this->dmId,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);
    }
}
