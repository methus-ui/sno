<?php

namespace App\Observers;

use App\Models\Store;
use App\Services\ActiveStoreCache;
use Illuminate\Support\Facades\Cache;

class StoreObserver
{
    /**
     * Handle the Store "updated" event.
     * Clear cache when store active status changes
     */
    public function updated(Store $store)
    {
        // Check if 'active' status changed
        if ($store->isDirty('active') || $store->isDirty('status')) {
            $this->clearStoreCaches($store);
        }
    }

    /**
     * Handle the Store "created" event.
     */
    public function created(Store $store)
    {
        $this->clearStoreCaches($store);
    }

    /**
     * Handle the Store "deleted" event.
     */
    public function deleted(Store $store)
    {
        $this->clearStoreCaches($store);
    }

    /**
     * Clear all caches related to this store
     */
    protected function clearStoreCaches(Store $store)
    {
        // Clear active store caches
        ActiveStoreCache::clearCache();

        // Clear popular items cache for this zone
        $cacheKey = "popular_items_m{$store->module_id}_z{$store->zone_id}";
        Cache::forget($cacheKey);

        // Clear random items cache
        $randomCacheKey = 'random_items_' . $store->module_id . '_*';
        if (config('cache.default') === 'redis') {
            $redis = Cache::getRedis();
            $keys = $redis->keys($randomCacheKey);
            foreach ($keys as $key) {
                Cache::forget(str_replace(config('cache.prefix') . ':', '', $key));
            }
        }
    }
}
