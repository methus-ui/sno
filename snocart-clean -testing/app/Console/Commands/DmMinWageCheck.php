<?php

namespace App\Console\Commands;

use App\Services\DmMinWageService;
use Illuminate\Console\Command;
use Carbon\Carbon;

class DmMinWageCheck extends Command
{
    protected $signature = 'dm:min-wage-check {--date= : Date to check (defaults to yesterday)}';
    protected $description = 'Calculate and pay minimum wage adjustments for delivery men';

    public function handle()
    {
        $date = $this->option('date') ?? Carbon::yesterday()->toDateString();
        $service = new DmMinWageService();
        $count = $service->processAllAdjustments($date);
        $this->info("Processed {$count} wage adjustments for {$date}.");
    }
}
