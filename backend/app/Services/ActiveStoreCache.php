<?php

namespace App\Services;

use App\Models\Store;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ActiveStoreCache
{
    /**
     * Get active store IDs with caching (5 minutes)
     * This replaces complex whereHas subqueries with a simple whereIn
     */
    public static function getActiveStoreIds(?int $moduleId = null, ?array $zoneIds = null): array
    {
        $cacheKey = 'active_stores_' . ($moduleId ?? 'all') . '_' . md5(json_encode($zoneIds ?? []));

        // Reduced cache time to 2 minutes for real-time open/closed status
        return Cache::remember($cacheKey, 120, function () use ($moduleId, $zoneIds) {
            $query = Store::where('status', 1)
                ->where('active', 1) // ✅ FIX: Check if store is currently open
                ->where(function($q) {
                    $q->where('store_business_model', 'commission')
                      ->orWhereHas('store_sub', function($query) {
                          $query->where(function($q) {
                              $q->where('max_order', 'unlimited')
                                ->orWhere('max_order', '>', 0);
                          })
                          ->where('status', 1);
                      });
                });

            if ($moduleId) {
                $query->where('module_id', $moduleId);
            }

            if ($zoneIds && is_array($zoneIds)) {
                $query->whereIn('zone_id', $zoneIds);
            }

            return $query->pluck('id')->toArray();
        });
    }

    /**
     * Get active store IDs for a specific module and zone (even faster - 10 min cache)
     */
    public static function getActiveStoreIdsByZone(int $moduleId, int $zoneId): array
    {
        $cacheKey = "active_stores_m{$moduleId}_z{$zoneId}";

        // Reduced cache time to 2 minutes for real-time open/closed status
        return Cache::remember($cacheKey, 120, function () use ($moduleId, $zoneId) {
            return Store::where('status', 1)
                ->where('active', 1) // ✅ FIX: Check if store is currently open
                ->where('module_id', $moduleId)
                ->where('zone_id', $zoneId)
                ->where(function($q) {
                    $q->where('store_business_model', 'commission')
                      ->orWhereHas('store_sub', function($query) {
                          $query->where(function($q) {
                              $q->where('max_order', 'unlimited')
                                ->orWhere('max_order', '>', 0);
                          })
                          ->where('status', 1);
                      });
                })
                ->pluck('id')
                ->toArray();
        });
    }

    /**
     * Clear all active store caches
     */
    public static function clearCache(): void
    {
        // Pattern-based cache clearing
        $keys = Cache::get('active_store_cache_keys', []);
        foreach ($keys as $key) {
            Cache::forget($key);
        }
        Cache::forget('active_store_cache_keys');
    }

    /**
     * Warmup cache for common queries
     */
    public static function warmup(): void
    {
        // Get all modules and zones
        $modules = DB::table('modules')->pluck('id');
        $zones = DB::table('zones')->pluck('id')->take(5); // Top 5 zones

        foreach ($modules as $moduleId) {
            foreach ($zones as $zoneId) {
                static::getActiveStoreIdsByZone($moduleId, $zoneId);
            }
        }
    }
}
