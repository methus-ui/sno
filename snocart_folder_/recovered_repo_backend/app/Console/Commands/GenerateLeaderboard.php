<?php

namespace App\Console\Commands;

use App\Services\DmRankingService;
use Illuminate\Console\Command;

class GenerateLeaderboard extends Command
{
    protected $signature = 'dm:generate-leaderboard {--type=daily}';
    protected $description = 'Generate delivery man leaderboard';

    public function handle()
    {
        $type = $this->option('type');
        $service = new DmRankingService();
        $count = $service->generateLeaderboard($type);
        $this->info("Generated {$type} leaderboard with {$count} entries.");
    }
}
