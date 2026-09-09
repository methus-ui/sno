<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Intervention\Image\ImageManager;
use Intervention\Image\Encoders\WebpEncoder;

class ConvertImagesToWebpComplete extends Command
{
    protected $signature = 'images:convert-webp-complete
                            {--update-db : Update ALL tables with image references}
                            {--rollback-db : Rollback ALL tables back to original extensions}
                            {--stats : Show comprehensive stats}
                            {--dry-run : Simulate only, no changes}
                            {--retry-failed : Retry only failed/missing conversions}
                            {--force : Force overwrite existing .webp files}
                            {--batch=500 : Batch size for database updates}
                            {--quality=80 : WebP quality (1-100)}';

    protected $description = 'Enhanced WebP converter - handles ALL directories and database tables safely';

    protected $imageColumns = [
        'items' => ['image', 'images'],
        'stores' => ['logo', 'cover_photo', 'meta_image'],
        'banners' => ['image'],
        'categories' => ['image'],
        'campaigns' => ['image'],
        'item_campaigns' => ['image'],
        'users' => ['image'],
        'admins' => ['image'],
        'delivery_men' => ['image', 'identity_image'],
        'vendor_employees' => ['image'],
        'vendors' => ['image'],
        'user_infos' => ['image'],
        'notifications' => ['image'],
        'parcel_categories' => ['image'],
        'modules' => ['icon', 'thumbnail'],
        'brands' => ['image'],
        'advertisements' => ['cover_image', 'profile_image'],
        'admin_features' => ['image'],
        'admin_promotional_banners' => ['image'],
        'admin_special_criterias' => ['image'],
        'admin_testimonials' => ['company_image', 'reviewer_image'],
        'flutter_special_criterias' => ['image'],
        'react_testimonials' => ['company_image', 'reviewer_image'],
        'email_templates' => ['image', 'logo', 'icon', 'background_image'],
        'module_wise_why_chooses' => ['image'],
        'refunds' => ['image'],
        'orders' => ['bill_image'],
        'temp_products' => ['image', 'images'],
        'dm_performance_tiers' => ['icon'],
    ];

    protected $directories = [
        'product', 'banner', 'category', 'store', 'store/cover',
        'restaurant', 'restaurant/cover', 'delivery-man', 'profile',
        'campaign', 'notification', 'admin', 'vendor', 'business',
        'parcel_category', 'module', 'advertisement', 'email_template',
        'admin_feature', 'promotional_banner', 'special_criteria',
        'reviewer_image', 'reviewer_company_image', 'why_choose',
        'order', 'conversation', 'header_banner', 'header_icon',
        'business_image', 'available_zone_image', 'contact_us_image',
        'download_user_app_image', 'earning', 'fixed_header_image',
        'payment_modules/gateway_image'
    ];

    protected $conversionLog = [];
    protected $stats = [
        'converted' => 0,
        'skipped' => 0,
        'failed' => 0,
        'already_webp' => 0
    ];

    public function handle()
    {
        $startTime = microtime(true);

        // Stats mode
        if ($this->option('stats')) {
            return $this->showComprehensiveStats();
        }

        // Rollback mode
        if ($this->option('rollback-db')) {
            return $this->rollbackAllTables($this->option('dry-run'));
        }

        $this->info("🚀 Enhanced WebP Converter Starting...");
        $this->info("Quality: {$this->option('quality')}% | Dry-run: " . ($this->option('dry-run') ? 'YES' : 'NO'));
        $this->newLine();

        // Initialize image manager
        try {
            if (extension_loaded('imagick')) {
                $this->info("🖼 Using Imagick driver");
                $manager = new ImageManager(new \Intervention\Image\Drivers\Imagick\Driver());
            } else {
                $this->warn("⚠️ Using GD driver (Imagick recommended for better quality)");
                $manager = new ImageManager(new \Intervention\Image\Drivers\Gd\Driver());
            }
        } catch (\Exception $e) {
            $this->error("❌ Failed to initialize image driver: " . $e->getMessage());
            return Command::FAILURE;
        }

        // Convert images in all directories
        $baseDir = storage_path('app/public');
        $progressBar = $this->output->createProgressBar(count($this->directories));
        $progressBar->setFormat('verbose');

        foreach ($this->directories as $dir) {
            $fullPath = $baseDir . '/' . $dir;
            if (is_dir($fullPath)) {
                $this->convertDirectory($fullPath, $manager);
            }
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Show summary
        $this->info("📊 Conversion Summary:");
        $this->table(
            ['Status', 'Count'],
            [
                ['✅ Converted', $this->stats['converted']],
                ['⏩ Skipped (already webp)', $this->stats['already_webp']],
                ['⏭ Skipped (other)', $this->stats['skipped']],
                ['❌ Failed', $this->stats['failed']]
            ]
        );

        // Update database
        if ($this->option('update-db')) {
            $this->newLine();
            $this->updateAllTables($this->option('dry-run'));
        }

        $elapsed = round(microtime(true) - $startTime, 2);
        $this->info("⏱ Time elapsed: {$elapsed}s");
        $this->warn("⚠️ Original files kept for safety. Delete manually after verification.");

        // Save conversion log
        $logPath = storage_path('logs/webp-conversion-' . date('Y-m-d-H-i-s') . '.log');
        file_put_contents($logPath, json_encode($this->conversionLog, JSON_PRETTY_PRINT));
        $this->info("📝 Conversion log saved: $logPath");

        return Command::SUCCESS;
    }

    protected function convertDirectory($directory, $manager)
    {
        if (!is_dir($directory)) {
            return;
        }

        $allowedExtensions = ['jpg', 'jpeg', 'png'];
        $quality = (int) $this->option('quality');

        foreach (File::allFiles($directory) as $file) {
            $ext = strtolower($file->getExtension());
            $originalPath = $file->getPathname();
            $relativePath = str_replace(storage_path('app/public') . '/', '', $originalPath);

            if (!in_array($ext, $allowedExtensions)) {
                if ($ext === 'webp') {
                    $this->stats['already_webp']++;
                }
                continue;
            }

            // Check mime type
            $mime = @mime_content_type($originalPath);
            if ($mime && (str_contains($mime, 'avif') || str_contains($mime, 'gif') || str_contains($mime, 'svg'))) {
                $this->stats['skipped']++;
                continue;
            }

            $webpPath = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $originalPath);

            // Check if already exists
            if (file_exists($webpPath)) {
                $valid = @mime_content_type($webpPath) === 'image/webp';

                if ($this->option('force')) {
                    // Continue to overwrite
                } elseif ($this->option('retry-failed') && !$valid) {
                    // Retry invalid webp
                } else {
                    $this->stats['already_webp']++;
                    continue;
                }
            }

            // Convert
            try {
                if (!$this->option('dry-run')) {
                    $image = $manager->read($originalPath)
                        ->encode(new WebpEncoder(quality: $quality));
                    $image->save($webpPath);

                    // Verify conversion
                    if (!file_exists($webpPath) || filesize($webpPath) === 0) {
                        throw new \Exception("WebP file is empty or not created");
                    }
                }

                $this->stats['converted']++;
                $this->conversionLog[] = [
                    'original' => $relativePath,
                    'webp' => str_replace(storage_path('app/public') . '/', '', $webpPath),
                    'original_ext' => $ext,
                    'status' => 'success'
                ];

            } catch (\Exception $e) {
                $this->stats['failed']++;
                $this->conversionLog[] = [
                    'original' => $relativePath,
                    'error' => $e->getMessage(),
                    'status' => 'failed'
                ];
            }
        }
    }

    protected function updateAllTables($dryRun = false)
    {
        $this->info("🔄 Updating database references in ALL tables...");

        $totalUpdated = 0;
        $batchSize = (int) $this->option('batch');

        $progressBar = $this->output->createProgressBar(count($this->imageColumns));

        foreach ($this->imageColumns as $table => $columns) {
            if (!Schema::hasTable($table)) {
                $progressBar->advance();
                continue;
            }

            foreach ($columns as $column) {
                if (!Schema::hasColumn($table, $column)) {
                    continue;
                }

                $columnInfo = DB::select("SHOW COLUMNS FROM $table LIKE '$column'")[0] ?? null;
                $isJson = $columnInfo && (str_contains($columnInfo->Type, 'json') || str_contains($columnInfo->Type, 'text'));

                if ($isJson) {
                    // Handle JSON/TEXT columns (arrays of images)
                    $updated = $this->updateJsonColumn($table, $column, $dryRun);
                } else {
                    // Handle simple string columns
                    $updated = $this->updateStringColumn($table, $column, $dryRun);
                }

                $totalUpdated += $updated;
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();

        $this->info("✅ Database update complete. Total rows updated: $totalUpdated");

        if ($dryRun) {
            $this->warn("⚠️ Dry-run mode: no actual changes made");
        }
    }

    protected function updateStringColumn($table, $column, $dryRun)
    {
        $updated = 0;

        $rows = DB::table($table)
            ->where(function($q) use ($column) {
                $q->where($column, 'like', '%.jpg')
                  ->orWhere($column, 'like', '%.jpeg')
                  ->orWhere($column, 'like', '%.png');
            })
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->get();

        foreach ($rows as $row) {
            $oldValue = $row->$column;
            $newValue = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $oldValue);

            // Check if webp file exists
            $webpExists = false;
            foreach ($this->directories as $dir) {
                $webpPath = storage_path("app/public/$dir/" . basename($newValue));
                if (file_exists($webpPath)) {
                    $webpExists = true;
                    break;
                }
            }

            if ($webpExists && !$dryRun) {
                DB::table($table)->where('id', $row->id)
                    ->update([$column => $newValue]);
                $updated++;
            }
        }

        return $updated;
    }

    protected function updateJsonColumn($table, $column, $dryRun)
    {
        $updated = 0;

        $rows = DB::table($table)
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->where($column, '!=', '[]')
            ->get();

        foreach ($rows as $row) {
            $value = $row->$column;

            // Try to decode JSON
            $decoded = json_decode($value, true);
            if (!is_array($decoded)) {
                continue;
            }

            $modified = false;
            array_walk_recursive($decoded, function(&$item) use (&$modified) {
                if (is_string($item) && preg_match('/\.(jpg|jpeg|png)$/i', $item)) {
                    $item = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $item);
                    $modified = true;
                }
            });

            if ($modified && !$dryRun) {
                DB::table($table)->where('id', $row->id)
                    ->update([$column => json_encode($decoded)]);
                $updated++;
            }
        }

        return $updated;
    }

    protected function rollbackAllTables($dryRun = false)
    {
        $this->warn("♻️ Rolling back ALL tables...");

        if ($dryRun) {
            $this->warn("🔍 Dry-run mode - counting affected rows...");
        }

        $totalRolled = 0;

        foreach ($this->imageColumns as $table => $columns) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            foreach ($columns as $column) {
                if (!Schema::hasColumn($table, $column)) {
                    continue;
                }

                $count = DB::table($table)
                    ->where($column, 'like', '%.webp')
                    ->whereNotNull($column)
                    ->count();

                if ($count > 0) {
                    $this->line("$table.$column: $count rows");
                    $totalRolled += $count;

                    if (!$dryRun) {
                        // Use conversion log to restore original extensions
                        $logFiles = glob(storage_path('logs/webp-conversion-*.log'));
                        $extensionMap = [];

                        foreach ($logFiles as $logFile) {
                            $log = json_decode(file_get_contents($logFile), true);
                            foreach ($log as $entry) {
                                if (isset($entry['original']) && isset($entry['original_ext'])) {
                                    $basename = basename($entry['original']);
                                    $extensionMap[$basename] = $entry['original_ext'];
                                }
                            }
                        }

                        // Rollback with correct extensions
                        $rows = DB::table($table)
                            ->where($column, 'like', '%.webp')
                            ->get();

                        foreach ($rows as $row) {
                            $webpValue = $row->$column;
                            $basename = basename($webpValue, '.webp');
                            $originalExt = $extensionMap[$basename . '.webp'] ?? 'png';
                            $originalValue = preg_replace('/\.webp$/i', ".$originalExt", $webpValue);

                            DB::table($table)->where('id', $row->id)
                                ->update([$column => $originalValue]);
                        }
                    }
                }
            }
        }

        $this->info("✅ Rollback complete. Total rows: $totalRolled");

        if ($dryRun) {
            $this->warn("⚠️ Dry-run mode: no changes made");
        }

        return Command::SUCCESS;
    }

    protected function showComprehensiveStats()
    {
        $this->info("📊 Comprehensive WebP Statistics\n");

        // File system stats
        $this->info("🗂️ File System:");
        $baseDir = storage_path('app/public');
        $totalJpg = $totalPng = $totalWebp = 0;

        foreach ($this->directories as $dir) {
            $path = "$baseDir/$dir";
            if (!is_dir($path)) continue;

            $jpg = count(glob("$path/*.{jpg,jpeg,JPG,JPEG}", GLOB_BRACE));
            $png = count(glob("$path/*.{png,PNG}", GLOB_BRACE));
            $webp = count(glob("$path/*.{webp,WEBP}", GLOB_BRACE));

            $totalJpg += $jpg;
            $totalPng += $png;
            $totalWebp += $webp;
        }

        $this->table(
            ['Type', 'Count'],
            [
                ['JPG/JPEG', number_format($totalJpg)],
                ['PNG', number_format($totalPng)],
                ['WebP ✓', number_format($totalWebp)],
                ['Total to convert', number_format($totalJpg + $totalPng)],
            ]
        );

        // Database stats
        $this->newLine();
        $this->info("💾 Database References:");

        $dbStats = [];
        foreach ($this->imageColumns as $table => $columns) {
            if (!Schema::hasTable($table)) continue;

            foreach ($columns as $column) {
                if (!Schema::hasColumn($table, $column)) continue;

                $webp = DB::table($table)->where($column, 'like', '%.webp')->whereNotNull($column)->count();
                $jpg = DB::table($table)->where($column, 'like', '%.jpg')->whereNotNull($column)->count();
                $jpeg = DB::table($table)->where($column, 'like', '%.jpeg')->whereNotNull($column)->count();
                $png = DB::table($table)->where($column, 'like', '%.png')->whereNotNull($column)->count();

                if ($webp + $jpg + $jpeg + $png > 0) {
                    $dbStats[] = [
                        "$table.$column",
                        $webp,
                        $jpg + $jpeg,
                        $png,
                        $webp + $jpg + $jpeg + $png
                    ];
                }
            }
        }

        $this->table(
            ['Table.Column', 'WebP', 'JPG/JPEG', 'PNG', 'Total'],
            $dbStats
        );

        // Conversion percentage
        $totalFiles = $totalJpg + $totalPng + $totalWebp;
        $conversionPercent = $totalFiles > 0 ? round(($totalWebp / $totalFiles) * 100, 2) : 0;

        $this->newLine();
        $this->info("📈 Overall Progress: $conversionPercent% converted to WebP");

        return Command::SUCCESS;
    }
}
