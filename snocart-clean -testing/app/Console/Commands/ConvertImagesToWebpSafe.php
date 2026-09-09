<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Intervention\Image\ImageManager;
use Intervention\Image\Encoders\WebpEncoder;

class ConvertImagesToWebpSafe extends Command
{
    protected $signature = 'images:convert-webp-safe 
                            {--update-db : Update items.image to .webp}
                            {--rollback-db : Rollback items.image back to original extension}
                            {--stats : Show breakdown of image extensions in items.image}
                            {--dry-run : Only simulate DB updates/rollbacks, no changes made}
                            {--retry-failed : Retry only missing/invalid conversions}
                            {--force : Force overwrite existing .webp files}';

    protected $description = 'Safely convert JPG/PNG images to WebP (keeps originals, with optional DB update/rollback/stats)';

    public function handle()
    {
        // ✅ Stats mode
        if ($this->option('stats')) {
            return $this->showStats();
        }

        $directory = storage_path('app/public/product');
        $allowedExtensions = ['jpg', 'jpeg', 'png'];
        $converted = 0;
        $skipped = 0;

        $this->info("🔎 Scanning directory: $directory");

        if (!is_dir($directory)) {
            $this->error("❌ Directory not found: $directory");
            return Command::FAILURE;
        }

        // ✅ Auto-detect driver: Imagick preferred, fallback to GD
        try {
            if (extension_loaded('imagick')) {
                $this->info("🖼 Using Imagick driver for conversion");
                $manager = new ImageManager(new \Intervention\Image\Drivers\Imagick\Driver());
            } else {
                $this->warn("⚠️ Imagick not found, falling back to GD driver");
                $manager = new ImageManager(new \Intervention\Image\Drivers\Gd\Driver());
            }
        } catch (\Exception $e) {
            $this->error("❌ Failed to initialize image driver: " . $e->getMessage());
            return Command::FAILURE;
        }

        foreach (File::allFiles($directory) as $file) {
            $ext = strtolower($file->getExtension());
            $originalPath = $file->getPathname();

            if (!in_array($ext, $allowedExtensions)) {
                $this->line("⏩ Skipped (unsupported extension: .$ext): " . $file->getRelativePathname());
                $skipped++;
                continue;
            }

            $mime = mime_content_type($originalPath);
            if (str_contains($mime, 'avif') || str_contains($mime, 'gif') || str_contains($mime, 'svg')) {
                $this->line("⏩ Skipped (unsupported mime: $mime): " . $file->getRelativePathname());
                $skipped++;
                continue;
            }

            $webpPath = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $originalPath);

            // ✅ Retry/force support
            if (file_exists($webpPath)) {
                $valid = mime_content_type($webpPath) === 'image/webp';

                if ($this->option('force')) {
                    $this->warn("♻️ Overwriting existing: " . $file->getRelativePathname());
                } elseif ($this->option('retry-failed') && !$valid) {
                    $this->warn("🔄 Retrying invalid .webp: " . $file->getRelativePathname());
                } else {
                    $this->line("⚠️ Skipped (already exists): " . $file->getRelativePathname());
                    $skipped++;
                    continue;
                }
            }

            try {
                $image = $manager->read($originalPath)->encode(new WebpEncoder(quality: 80));
                $image->save($webpPath);

                $converted++;
                $this->line("✔ Converted: " . $file->getRelativePathname());
            } catch (\Exception $e) {
                $this->error("❌ Error converting {$file->getFilename()}: " . $e->getMessage());
                $skipped++;
            }
        }

        // ✅ Update DB
        if ($this->option('update-db')) {
            $this->updateDatabase($this->option('dry-run'));
        }

        // ✅ Rollback DB
        if ($this->option('rollback-db')) {
            $this->rollbackDatabase($this->option('dry-run'));
        }

        // ✅ Final summary
        $this->newLine();
        $this->info("✅ Done. Converted: $converted | Skipped: $skipped");
        $this->warn("⚠️ Originals are still kept. Delete them later if all works fine.");

        return Command::SUCCESS;
    }

    /**
     * Show stats about items.image
     */
    protected function showStats()
    {
        $webp  = DB::table('items')->where('image', 'like', '%.webp')->count();
        $png   = DB::table('items')->where('image', 'like', '%.png')->count();
        $jpg   = DB::table('items')->where('image', 'like', '%.jpg')->count();
        $jpeg  = DB::table('items')->where('image', 'like', '%.jpeg')->count();
        $avif  = DB::table('items')->where('image', 'like', '%.avif')->count();

        $this->info("📊 items.image breakdown:");
        $this->line("   WEBP : $webp");
        $this->line("   PNG  : $png");
        $this->line("   JPG  : $jpg");
        $this->line("   JPEG : $jpeg");
        $this->line("   AVIF : $avif");
        $this->newLine();

        $total = $webp + $png + $jpg + $jpeg + $avif;
        $this->info("✅ Total rows: $total");

        return Command::SUCCESS;
    }

    protected function updateDatabase($dryRun = false)
    {
        $this->info("🔄 Updating database references in items.image...");

        // Fetch all items with JPG, JPEG, or PNG
        $items = DB::table('items')
            ->where(function ($q) {
                $q->where('image', 'like', '%.jpg')
                  ->orWhere('image', 'like', '%.jpeg')
                  ->orWhere('image', 'like', '%.png');
            })->get();

        $updatedCount = 0;

        foreach ($items as $item) {
            $oldFilename = basename($item->image); // e.g., "product1.png"
            $oldPath = storage_path('app/public/product/' . $oldFilename);

            // Construct the WebP path
            $webpPath = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $oldPath);

            if (file_exists($webpPath)) {
                if (!$dryRun) {
                    DB::table('items')->where('id', $item->id)
                        ->update(['image' => basename($webpPath)]);
                }
                $updatedCount++;
            }
        }

        $this->info("✅ Database update complete. Rows updated: $updatedCount");

        if ($dryRun) {
            $this->warn("⚠️ Dry-run mode: no changes made.");
        }
    }


    protected function rollbackDatabase($dryRun = false)
    {
        $this->warn("♻️ Rolling back database references in items.image...");

        if ($dryRun) {
            $this->warn("🔍 Dry-run mode: counting rows to rollback...");

            $webpCount = DB::table('items')->where('image', 'like', '%.webp')->count();
            $this->line("📊 items.image rows to rollback: $webpCount");
            $this->info("✅ Dry-run complete. No changes made.");
            return;
        }

        DB::transaction(function () {
            DB::table('items')->where('image', 'like', '%.webp')
                ->update(['image' => DB::raw("REPLACE(image, '.webp', '.png')")]);
        });

        $this->info("✅ Rollback complete. items.image reverted from .webp → .png");
    }
}
