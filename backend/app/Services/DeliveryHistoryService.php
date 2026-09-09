<?php

namespace App\Services;

use App\Models\DeliveryHistory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeliveryHistoryService
{
    /**
     * Batch update delivery histories to reduce lock contention
     *
     * @param array $updates Array of delivery man updates
     * @return int Number of updates processed
     */
    public function batchUpdate(array $updates): int
    {
        $processed = 0;

        try {
            DB::beginTransaction();

            foreach ($updates as $update) {
                $this->updateDeliveryHistory(
                    $update['delivery_man_id'],
                    $update['latitude'],
                    $update['longitude'],
                    $update['location'] ?? null,
                    $update['time'] ?? now()
                );
                $processed++;
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Delivery history batch update failed', [
                'error' => $e->getMessage(),
                'processed' => $processed,
                'total' => count($updates)
            ]);
        }

        return $processed;
    }

    /**
     * Update single delivery history with optimized locking
     *
     * @param int $deliveryManId
     * @param float $latitude
     * @param float $longitude
     * @param string|null $location
     * @param mixed $time
     * @return bool
     */
    public function updateDeliveryHistory(
        int $deliveryManId,
        float $latitude,
        float $longitude,
        ?string $location = null,
        $time = null
    ): bool {
        $time = $time ?? now();

        try {
            // Use INSERT ... ON DUPLICATE KEY UPDATE for better performance
            // This is atomic and reduces lock time
            DB::statement("
                INSERT INTO delivery_histories
                    (delivery_man_id, latitude, longitude, location, time, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    latitude = VALUES(latitude),
                    longitude = VALUES(longitude),
                    location = VALUES(location),
                    time = VALUES(time),
                    updated_at = VALUES(updated_at)
            ", [
                $deliveryManId,
                $latitude,
                $longitude,
                $location,
                $time,
                now(),
                now()
            ]);

            // Clear cache for this delivery man
            Cache::forget("delivery_location_{$deliveryManId}");

            return true;
        } catch (\Exception $e) {
            Log::error('Delivery history update failed', [
                'delivery_man_id' => $deliveryManId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get delivery man location with caching
     *
     * @param int $deliveryManId
     * @return array|null
     */
    public function getLocation(int $deliveryManId): ?array
    {
        return Cache::remember(
            "delivery_location_{$deliveryManId}",
            60, // Cache for 1 minute
            function () use ($deliveryManId) {
                $history = DeliveryHistory::where('delivery_man_id', $deliveryManId)
                    ->latest('time')
                    ->first();

                return $history ? [
                    'latitude' => $history->latitude,
                    'longitude' => $history->longitude,
                    'location' => $history->location,
                    'time' => $history->time,
                ] : null;
            }
        );
    }

    /**
     * Queue delivery history update for batch processing
     *
     * @param int $deliveryManId
     * @param float $latitude
     * @param float $longitude
     * @param string|null $location
     * @return void
     */
    public function queueUpdate(
        int $deliveryManId,
        float $latitude,
        float $longitude,
        ?string $location = null
    ): void {
        $cacheKey = 'delivery_updates_queue';

        $queue = Cache::get($cacheKey, []);
        $queue[] = [
            'delivery_man_id' => $deliveryManId,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'location' => $location,
            'time' => now(),
        ];

        Cache::put($cacheKey, $queue, 30); // Hold for 30 seconds

        // If queue size reaches 10, process immediately
        if (count($queue) >= 10) {
            $this->processQueue();
        }
    }

    /**
     * Process queued updates in batch
     *
     * @return int Number processed
     */
    public function processQueue(): int
    {
        $cacheKey = 'delivery_updates_queue';
        $queue = Cache::pull($cacheKey, []); // Get and remove

        if (empty($queue)) {
            return 0;
        }

        return $this->batchUpdate($queue);
    }

    /**
     * Get statistics about delivery history updates
     *
     * @return array
     */
    public function getStats(): array
    {
        return [
            'total_records' => DeliveryHistory::count(),
            'updated_last_hour' => DeliveryHistory::where('updated_at', '>=', now()->subHour())->count(),
            'unique_delivery_men' => DeliveryHistory::distinct('delivery_man_id')->count(),
            'queue_size' => count(Cache::get('delivery_updates_queue', [])),
        ];
    }
}
