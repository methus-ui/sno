<?php

namespace App\Console\Commands;

use App\Models\Item;
use App\Models\Store;
use App\Models\Module;
use App\Models\Zone;
use App\Services\ActiveStoreCache;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class WarmCaches extends Command
{
    protected $signature = 'cache:warm {--clear : Clear existing caches first}';
    protected $description = 'Warm up application caches for better performance';

    public function handle()
    {
        if ($this->option('clear')) {
            $this->info('Clearing existing caches...');
            Cache::flush();
        }

        $this->info('Warming up caches...');
        $bar = $this->output->createProgressBar(6);

        // 1. Cache active store IDs
        $bar->setMessage('Caching active stores...');
        $this->cacheActiveStores();
        $bar->advance();

        // 2. Cache popular items
        $bar->setMessage('Caching popular items...');
        $this->cachePopularItems();
        $bar->advance();

        // 3. Cache categories with counts
        $bar->setMessage('Caching categories...');
        $this->cacheCategories();
        $bar->advance();

        // 4. Cache random items
        $bar->setMessage('Caching random items...');
        $this->cacheRandomItems();
        $bar->advance();

        // 5. Cache zone data
        $bar->setMessage('Caching zones...');
        $this->cacheZones();
        $bar->advance();

        // 6. Cache module data
        $bar->setMessage('Caching modules...');
        $this->cacheModules();
        $bar->advance();

        $bar->finish();
        $this->newLine(2);
        $this->info('✅ Cache warming complete!');

        // Show cache statistics
        $this->showCacheStats();
    }

    protected function cacheActiveStores()
    {
        $modules = Module::pluck('id');
        $zones = Zone::pluck('id');

        foreach ($modules as $moduleId) {
            // Cache all active stores for this module
            ActiveStoreCache::getActiveStoreIds($moduleId, null);

            // Cache for top 5 zones
            foreach ($zones->take(5) as $zoneId) {
                ActiveStoreCache::getActiveStoreIdsByZone($moduleId, $zoneId);
            }
        }
    }

    protected function cachePopularItems()
    {
        $modules = Module::pluck('id');
        $zones = Zone::pluck('id');

        foreach ($modules as $moduleId) {
            foreach ($zones->take(3) as $zoneId) {
                $cacheKey = "popular_items_m{$moduleId}_z{$zoneId}";

                // Reduced to 2 minutes to match active store cache
                Cache::remember($cacheKey, 120, function() use ($moduleId, $zoneId) {
                    $activeStoreIds = ActiveStoreCache::getActiveStoreIdsByZone($moduleId, $zoneId);

                    return Item::where('status', 1)
                        ->where('is_approved', 1)
                        ->where('module_id', $moduleId)
                        ->whereIn('store_id', $activeStoreIds)
                        ->orderBy('order_count', 'desc')
                        ->limit(50)
                        ->pluck('id')
                        ->toArray();
                });
            }
        }
    }

    protected function cacheCategories()
    {
        $modules = Module::pluck('id');

        foreach ($modules as $moduleId) {
            $cacheKey = "categories_with_counts_m{$moduleId}";

            Cache::remember($cacheKey, 600, function() use ($moduleId) {
                return DB::table('categories')
                    ->where('module_id', $moduleId)
                    ->where('status', 1)
                    ->select('id', 'name', 'priority')
                    ->orderBy('priority', 'desc')
                    ->get();
            });
        }
    }

    protected function cacheRandomItems()
    {
        $modules = Module::pluck('id');

        foreach ($modules as $moduleId) {
            Item::getCachedRandom($moduleId, 12);
        }
    }

    protected function cacheZones()
    {
        Cache::remember('active_zones', 3600, function() {
            return Zone::where('status', 1)->get();
        });
    }

    protected function cacheModules()
    {
        Cache::remember('active_modules', 3600, function() {
            return Module::all(); // No active column, cache all modules
        });
    }

    protected function showCacheStats()
    {
        if (config('cache.default') === 'redis') {
            $redis = Cache::getRedis();
            $info = $redis->info();

            $this->table(
                ['Metric', 'Value'],
                [
                    ['Total Keys', $info['db0']['keys'] ?? 'N/A'],
                    ['Memory Used', $this->formatBytes($info['used_memory'] ?? 0)],
                    ['Hit Rate', $this->getHitRate() . '%'],
                ]
            );
        }
    }

    protected function getHitRate()
    {
        if (config('cache.default') === 'redis') {
            $redis = Cache::getRedis();
            $info = $redis->info();

            $hits = $info['keyspace_hits'] ?? 0;
            $misses = $info['keyspace_misses'] ?? 0;

            if ($hits + $misses > 0) {
                return round(($hits / ($hits + $misses)) * 100, 2);
            }
        }

        return 0;
    }

    protected function formatBytes($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
