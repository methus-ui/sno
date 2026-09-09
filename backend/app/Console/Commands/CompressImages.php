<?php

namespace App\Console\Commands;

use App\Jobs\CompressImageJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CompressImages extends Command
{
    protected $signature = 'images:compress {--batch-size=50 : Number of images to process per batch}';
    protected $description = 'Compress existing images to WebP format';

    public function handle()
    {
        $batchSize = $this->option('batch-size');
        
        // Get all image files from storage
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        $allImages = [];
        
        foreach ($imageExtensions as $ext) {
            $files = Storage::allFiles('public');
            $images = array_filter($files, function($file) use ($ext) {
                return strtolower(pathinfo($file, PATHINFO_EXTENSION)) === $ext;
            });
            $allImages = array_merge($allImages, $images);
        }
        
        $totalImages = count($allImages);
        $this->info("Found {$totalImages} images to compress");
        
        if ($totalImages === 0) {
            $this->info("No images found to compress.");
            return;
        }
        
        // Process in batches
        $chunks = array_chunk($allImages, $batchSize);
        $processed = 0;
        
        foreach ($chunks as $chunk) {
            foreach ($chunk as $imagePath) {
                CompressImageJob::dispatch($imagePath);
                $processed++;
            }
            
            $this->info("Queued batch: {$processed}/{$totalImages}");
        }
        
        $this->info("All compression jobs queued successfully!");
        $this->info("Run 'php artisan queue:work' to process the jobs.");
    }
}