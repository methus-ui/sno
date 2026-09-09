<?php

namespace App\Console\Commands;

use App\Services\DmRushMonitorService;
use Illuminate\Console\Command;

class CheckRushConditions extends Command
{
    protected $signature = 'dm:check-rush-conditions';
    protected $description = 'Check zones for rush conditions and activate surge incentives';

    public function handle()
    {
        $service = new DmRushMonitorService();
        $service->checkAllZonesForRush();
        $this->info('Rush conditions checked for all zones.');
    }
}
