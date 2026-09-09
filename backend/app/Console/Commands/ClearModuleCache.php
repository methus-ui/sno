<?php

namespace App\Console\Commands;

use App\Services\ModuleCacheService;
use Illuminate\Console\Command;

class ClearModuleCache extends Command
{
    protected $signature = 'cache:clear-module {module_id? : Module ID to clear (optional)}';
    protected $description = 'Clear cache for a specific module or all modules';

    public function handle()
    {
        $moduleId = $this->argument('module_id');

        if ($moduleId) {
            $this->info("Clearing cache for module {$moduleId}...");
            ModuleCacheService::clearModuleCaches((int)$moduleId);
            $this->info("✅ Cache cleared for module {$moduleId}");
        } else {
            $this->info('Clearing cache for all modules...');
            ModuleCacheService::clearAllModuleCaches();
            $this->info('✅ Cache cleared for all modules');
        }
    }
}
