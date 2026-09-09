<?php

namespace App\Console\Commands;

use App\Services\DmRushMonitorService;
use Illuminate\Console\Command;

class DeactivateExpiredRush extends Command
{
    protected $signature = 'dm:deactivate-expired-rush';
    protected $description = 'Deactivate rush incentives that have exceeded their duration';

    public function handle()
    {
        $service = new DmRushMonitorService();
        $count = $service->deactivateExpiredRushes();
        $this->info("Deactivated {$count} expired rush activations.");
    }
}
