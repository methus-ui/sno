<?php

namespace App\Console\Commands;

use App\Services\DmRankingService;
use Illuminate\Console\Command;

class UpdateDmPerformanceTiers extends Command
{
    protected $signature = 'dm:update-tiers';
    protected $description = 'Recalculate performance tiers for all delivery men';

    public function handle()
    {
        $service = new DmRankingService();
        $count = $service->updateAllTiers();
        $this->info("Updated tiers for {$count} delivery men.");
    }
}
