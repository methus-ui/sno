<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Encoders\WebpEncoder;

class CompressImageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $imagePath;
    protected $originalPath;
    protected $quality;

    public function __construct($imagePath, $originalPath = null, $quality = 80)
    {
        $this->imagePath = $imagePath;
        $this->originalPath = $originalPath;
        $this->quality = $quality;
    }

    public function handle()
    {
        try {
            // Check if file exists
            if (!Storage::exists($this->imagePath)) {
                \Log::warning("Image not found for compression: {$this->imagePath}");
                return;
            }

            // Get file path
            $fullPath = Storage::path($this->imagePath);

            // Check if already webp
            if (pathinfo($fullPath, PATHINFO_EXTENSION) === 'webp') {
                \Log::info("Image already WebP, skipping: {$this->imagePath}");
                return;
            }

            // Initialize image manager (prefer Imagick)
            if (extension_loaded('imagick')) {
                $manager = new ImageManager(new \Intervention\Image\Drivers\Imagick\Driver());
            } else {
                $manager = new ImageManager(new \Intervention\Image\Drivers\Gd\Driver());
            }

            // Generate WebP filename
            $pathInfo = pathinfo($this->imagePath);
            $webpFilename = $pathInfo['filename'] . '.webp';
            $webpPath = $pathInfo['dirname'] . '/' . $webpFilename;

            // Convert to WebP
            $image = $manager->read($fullPath);
            $encoded = $image->encode(new WebpEncoder(quality: $this->quality));

            // Save WebP version
            Storage::put($webpPath, (string) $encoded);

            // Verify conversion
            if (!Storage::exists($webpPath)) {
                throw new \Exception("Failed to save WebP file");
            }

            \Log::info("Compressed image: {$this->imagePath} -> {$webpPath}");

            // Optional: Delete original file after conversion (if specified)
            if ($this->originalPath && Storage::exists($this->originalPath)) {
                Storage::delete($this->originalPath);
                \Log::info("Deleted original: {$this->originalPath}");
            }

        } catch (\Exception $e) {
            \Log::error("Failed to compress image {$this->imagePath}: " . $e->getMessage());
            $this->fail($e);
        }
    }
}