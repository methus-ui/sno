<?php

namespace App\Console\Commands;

use App\Services\DmAutoAssignService;
use Illuminate\Console\Command;

class AutoAssignOrders extends Command
{
    protected $signature = 'dm:auto-assign-orders';
    protected $description = 'Auto-assign pending orders to best available delivery men';

    public function handle()
    {
        $service = new DmAutoAssignService();
        $assigned = $service->processUnassignedOrders();
        $this->info("Auto-assigned {$assigned} orders.");
    }
}
