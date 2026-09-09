<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class ModuleCacheService
{
    /**
     * Clear all caches for a specific module
     * Call this when user switches modules to prevent cross-module data bleeding
     */
    public static function clearModuleCaches(?int $moduleId = null): void
    {
        if ($moduleId === null) {
            $moduleId = config('module.current_module_data')['id'] ?? null;
        }

        if ($moduleId === null) {
            return; // No module to clear
        }

        $patterns = [
            "latest_items_m{$moduleId}_*",
            "popular_products_m{$moduleId}_*",
            "popular_basic_products_m{$moduleId}_*",
            "popular_items_m{$moduleId}_*",
            "random_items_{$moduleId}_*",
            "active_stores_*_{$moduleId}*",
        ];

        if (config('cache.default') === 'redis') {
            $redis = Cache::getRedis();
            foreach ($patterns as $pattern) {
                $keys = $redis->keys($pattern);
                foreach ($keys as $key) {
                    Cache::forget(str_replace(config('cache.prefix') . ':', '', $key));
                }
            }
        } else {
            // For non-Redis cache drivers, clear all (less efficient)
            Cache::flush();
        }
    }

    /**
     * Clear all module-related caches (all modules)
     */
    public static function clearAllModuleCaches(): void
    {
        $patterns = [
            'latest_items_m*',
            'popular_products_m*',
            'popular_basic_products_m*',
            'popular_items_m*',
            'random_items_*',
            'active_stores_*',
        ];

        if (config('cache.default') === 'redis') {
            $redis = Cache::getRedis();
            foreach ($patterns as $pattern) {
                $keys = $redis->keys($pattern);
                foreach ($keys as $key) {
                    Cache::forget(str_replace(config('cache.prefix') . ':', '', $key));
                }
            }
        } else {
            Cache::flush();
        }
    }

    /**
     * Get cache key prefix for current module
     */
    public static function getModuleCachePrefix(?int $moduleId = null): string
    {
        if ($moduleId === null) {
            $moduleId = config('module.current_module_data')['id'] ?? 'all';
        }
        return "m{$moduleId}_";
    }

    /**
     * Remember with module-aware cache key
     *
     * @param string $key Base cache key
     * @param int $ttl Cache TTL in seconds
     * @param callable $callback Function to execute if cache miss
     * @param int|null $moduleId Optional module ID (uses current if null)
     * @return mixed
     */
    public static function rememberForModule(string $key, int $ttl, callable $callback, ?int $moduleId = null)
    {
        $modulePrefix = self::getModuleCachePrefix($moduleId);
        $fullKey = $modulePrefix . $key;

        return Cache::remember($fullKey, $ttl, $callback);
    }
}
