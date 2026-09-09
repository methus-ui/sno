<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class OptimizeImages extends Command
{
    protected $signature = 'images:optimize {--cleanup : Also cleanup unused images}';
    protected $description = 'Compress images and optionally cleanup unused ones';

    public function handle()
    {
        $this->info('Starting image optimization process...');
        
        // First, cleanup unused images if requested
        if ($this->option('cleanup')) {
            $this->info('Starting cleanup of unused images...');
            $this->call('images:cleanup');
        }

        // Then compress remaining images
        $this->info('Starting image compression...');
        $this->call('images:compress');

        $this->info('Image optimization process completed!');
        $this->info("Make sure to run 'php artisan queue:work' to process all jobs.");
    }
}