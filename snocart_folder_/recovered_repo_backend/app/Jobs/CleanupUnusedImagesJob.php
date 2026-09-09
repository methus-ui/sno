<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class CleanupUnusedImagesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        // Get all image files from storage
        $allImages = Storage::allFiles('public');
        $imageFiles = array_filter($allImages, function($file) {
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            return in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
        });

        $unusedImages = [];

        foreach ($imageFiles as $imagePath) {
            $filename = basename($imagePath);
            
            // Check if image is referenced in database
            $isUsed = $this->isImageUsed($filename);
            
            if (!$isUsed) {
                $unusedImages[] = $imagePath;
            }
        }

        // Delete unused images
        foreach ($unusedImages as $unusedImage) {
            Storage::delete($unusedImage);
            \Log::info("Deleted unused image: {$unusedImage}");
        }

        \Log::info("Cleanup completed. Deleted " . count($unusedImages) . " unused images.");
    }

    private function isImageUsed($filename)
    {
        // Add your database tables and columns that store image references
        // CUSTOMIZE THIS ACCORDING TO YOUR DATABASE STRUCTURE
        $tables = [
            'users' => ['avatar', 'profile_image'],
            'posts' => ['featured_image', 'thumbnail'],
            'products' => ['image', 'gallery'],
            'categories' => ['image', 'banner'],
            // Add more tables as needed based on your app structure
        ];

        foreach ($tables as $table => $columns) {
            foreach ($columns as $column) {
                $exists = DB::table($table)
                    ->where($column, 'like', "%{$filename}%")
                    ->exists();
                
                if ($exists) {
                    return true;
                }
            }
        }

        return false;
    }
}