<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class QueryCacheHelper
{
    /**
     * Cache duration in seconds
     */
    const CACHE_DURATION = [
        'popular_items' => 600,      // 10 minutes
        'latest_items' => 300,       // 5 minutes
        'featured_stores' => 600,    // 10 minutes
        'categories' => 1800,        // 30 minutes
        'banners' => 900,            // 15 minutes
        'random_items' => 300,       // 5 minutes
    ];

    /**
     * Get cached query result or execute and cache
     *
     * @param string $key Cache key
     * @param callable $callback Query callback
     * @param int|null $duration Cache duration in seconds
     * @return mixed
     */
    public static function remember(string $key, callable $callback, ?int $duration = null)
    {
        $duration = $duration ?? self::CACHE_DURATION['popular_items'];

        return Cache::remember($key, $duration, function () use ($callback, $key) {
            $startTime = microtime(true);
            $result = $callback();
            $executionTime = (microtime(true) - $startTime) * 1000;

            if ($executionTime > 1000) {
                Log::info("Query executed and cached", [
                    'key' => $key,
                    'execution_time' => round($executionTime, 2) . 'ms'
                ]);
            }

            return $result;
        });
    }

    /**
     * Clear specific cache key
     *
     * @param string $key
     * @return bool
     */
    public static function forget(string $key): bool
    {
        return Cache::forget($key);
    }

    /**
     * Clear all query caches by pattern
     *
     * @param string $pattern
     * @return void
     */
    public static function forgetByPattern(string $pattern): void
    {
        // This works with Redis
        if (config('cache.default') === 'redis') {
            $keys = Cache::getRedis()->keys($pattern);
            foreach ($keys as $key) {
                Cache::forget(str_replace(config('cache.prefix') . ':', '', $key));
            }
        } else {
            // For file/database cache, log warning
            Log::warning("Pattern-based cache clearing not supported for " . config('cache.default'));
        }
    }

    /**
     * Generate cache key for zone-based queries
     *
     * @param string $type
     * @param int|array $zoneId
     * @param array $params
     * @return string
     */
    public static function generateKey(string $type, $zoneId, array $params = []): string
    {
        $zoneKey = is_array($zoneId) ? implode('_', $zoneId) : $zoneId;
        $paramsKey = empty($params) ? '' : '_' . md5(json_encode($params));

        return "query_{$type}_zone_{$zoneKey}{$paramsKey}";
    }

    /**
     * Get popular items with caching
     *
     * @param int $zoneId
     * @param int $moduleId
     * @param int $limit
     * @return mixed
     */
    public static function getPopularItems(int $zoneId, int $moduleId, int $limit = 50)
    {
        $key = self::generateKey('popular_items', $zoneId, ['module' => $moduleId, 'limit' => $limit]);

        return self::remember($key, function () use ($zoneId, $moduleId, $limit) {
            return \App\Models\Item::where('status', 1)
                ->where('is_approved', 1)
                ->where('module_id', $moduleId)
                ->where('stock', '>', 0)
                ->orderBy('order_count', 'desc')
                ->limit($limit)
                ->get();
        }, self::CACHE_DURATION['popular_items']);
    }

    /**
     * Get random items efficiently (without RAND())
     *
     * @param int $moduleId
     * @param int $limit
     * @return mixed
     */
    public static function getRandomItems(int $moduleId, int $limit = 12)
    {
        $key = "random_items_module_{$moduleId}_" . date('YmdHi'); // Changes every minute

        return self::remember($key, function () use ($moduleId, $limit) {
            // First, get all eligible IDs (fast)
            $ids = \App\Models\Item::where('is_approved', 1)
                ->where('module_id', $moduleId)
                ->where('stock', '>', 0)
                ->pluck('id')
                ->toArray();

            // Randomly select IDs in PHP (much faster than SQL RAND())
            if (count($ids) > $limit) {
                $randomIds = array_rand(array_flip($ids), $limit);
            } else {
                $randomIds = $ids;
            }

            // Fetch the actual items
            return \App\Models\Item::whereIn('id', $randomIds)->get();
        }, self::CACHE_DURATION['random_items']);
    }

    /**
     * Clear cache when items are updated
     *
     * @param int $storeId
     * @param int|null $moduleId
     * @return void
     */
    public static function clearItemCaches(int $storeId, ?int $moduleId = null): void
    {
        // Clear all item-related caches
        self::forgetByPattern('query_popular_items_*');
        self::forgetByPattern('query_latest_items_*');
        self::forgetByPattern('query_random_items_*');

        // Clear ProductLogic caches
        self::forgetByPattern('popular_products_*');
        self::forgetByPattern('latest_items_*');
        self::forgetByPattern('popular_basic_products_*');

        if ($moduleId) {
            self::forget("random_items_module_{$moduleId}_" . date('YmdHi'));
        }

        Log::info("Cleared item caches for store {$storeId}");
    }

    /**
     * Get cache statistics
     *
     * @return array
     */
    public static function getStats(): array
    {
        $stats = [
            'driver' => config('cache.default'),
            'keys_cached' => 0,
            'memory_used' => 0,
        ];

        if (config('cache.default') === 'redis') {
            try {
                $redis = Cache::getRedis();
                $stats['keys_cached'] = count($redis->keys('*'));
                $stats['memory_used'] = $redis->info('memory')['used_memory_human'] ?? 'N/A';
            } catch (\Exception $e) {
                $stats['error'] = $e->getMessage();
            }
        }

        return $stats;
    }
}
