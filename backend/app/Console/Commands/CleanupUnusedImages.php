<?php

namespace App\Console\Commands;

use App\Jobs\CleanupUnusedImagesJob;
use Illuminate\Console\Command;

class CleanupUnusedImages extends Command
{
    protected $signature = 'images:cleanup';
    protected $description = 'Remove unused images from storage';

    public function handle()
    {
        $this->info('Starting unused image cleanup...');
        
        if ($this->confirm('This will delete unused images permanently. Are you sure?')) {
            CleanupUnusedImagesJob::dispatch();
            
            $this->info('Cleanup job queued successfully!');
            $this->info("Run 'php artisan queue:work' to process the job.");
        } else {
            $this->info('Cleanup cancelled.');
        }
    }
}