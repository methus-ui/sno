<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

trait OptimizedItemQueries
{
    /**
     * Optimized scope for available items
     * Reduces 300K row scans to indexed lookups
     */
    public function scopeAvailableOptimized($query, $zoneIds, $moduleId)
    {
        return $query
            ->select('items.*')
            ->selectRaw('(SELECT active FROM stores WHERE stores.id = items.store_id LIMIT 1) as temp_available')
            ->where('items.status', 1)
            ->where('items.is_approved', 1)
            ->where('items.module_id', $moduleId)
            ->where('items.stock', '>', 0)
            ->whereHas('store', function($q) use ($zoneIds, $moduleId) {
                $q->where('status', 1)
                  ->where('module_id', $moduleId)
                  ->whereIn('zone_id', $zoneIds)
                  ->where(function($q) {
                      $q->where('store_business_model', 'commission')
                        ->orWhereHas('store_subs', function($q) {
                            $q->where('status', 1)
                              ->where(function($q2) {
                                  $q2->where('max_order', 'unlimited')
                                     ->orWhere('max_order', '>', 0);
                              });
                        });
                  });
            });
    }

    /**
     * Get items with eager loaded relationships
     * Prevents N+1 query problem
     */
    public function scopeWithOptimizedRelations($query)
    {
        return $query->with([
            'store:id,name,active,status',
            'store.zone:id,name',
            'module:id,module_name',
            'translations' => function($q) {
                $q->select('translationable_id', 'translationable_type', 'locale', 'key', 'value');
            }
        ]);
    }

    /**
     * Optimized ordering by store availability
     * Uses indexed columns instead of subquery
     */
    public function scopeOrderByAvailability($query)
    {
        return $query
            ->join('stores', 'items.store_id', '=', 'stores.id')
            ->orderBy('stores.active', 'desc')
            ->orderBy('items.created_at', 'desc')
            ->select('items.*');
    }

    /**
     * Get cached popular items
     */
    public static function getCachedPopular($zoneIds, $moduleId, $limit = 50)
    {
        $cacheKey = 'popular_items_' . md5(json_encode($zoneIds)) . "_{$moduleId}_{$limit}";
        
        return Cache::remember($cacheKey, 600, function() use ($zoneIds, $moduleId, $limit) {
            return static::availableOptimized($zoneIds, $moduleId)
                ->withOptimizedRelations()
                ->orderBy('order_count', 'desc')
                ->limit($limit)
                ->get();
        });
    }

    /**
     * Get random items efficiently without RAND()
     */
    public static function getCachedRandom($moduleId, $limit = 12)
    {
        // Cache key changes every 5 minutes
        $cacheKey = 'random_items_' . $moduleId . '_' . floor(time() / 300);
        
        return Cache::remember($cacheKey, 300, function() use ($moduleId, $limit) {
            // Get IDs first (fast)
            $ids = static::where('is_approved', 1)
                ->where('module_id', $moduleId)
                ->where('stock', '>', 0)
                ->pluck('id')
                ->toArray();
            
            // Random selection in PHP (much faster)
            if (count($ids) > $limit) {
                $randomKeys = array_rand($ids, $limit);
                $randomIds = is_array($randomKeys) 
                    ? array_map(fn($k) => $ids[$k], $randomKeys)
                    : [$ids[$randomKeys]];
            } else {
                $randomIds = $ids;
            }
            
            // Fetch items by IDs (uses primary key, very fast)
            return static::whereIn('id', $randomIds)
                ->withOptimizedRelations()
                ->get();
        });
    }

    /**
     * Clear item-related caches
     */
    public static function clearItemCaches()
    {
        $patterns = [
            'popular_items_*',
            'random_items_*',
            'latest_items_*',
        ];

        foreach ($patterns as $pattern) {
            if (config('cache.default') === 'redis') {
                $redis = Cache::getRedis();
                $keys = $redis->keys($pattern);
                foreach ($keys as $key) {
                    Cache::forget(str_replace(config('cache.prefix') . ':', '', $key));
                }
            }
        }
    }
}
