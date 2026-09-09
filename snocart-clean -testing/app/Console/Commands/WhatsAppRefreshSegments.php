<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\CustomerSegmentationService;

class WhatsAppRefreshSegments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'whatsapp:refresh-segments {--segment-id= : Refresh specific segment by ID} {--all : Refresh all segments}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh WhatsApp customer segment counts and cache';

    /**
     * The customer segmentation service instance.
     *
     * @var CustomerSegmentationService
     */
    protected $segmentationService;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(CustomerSegmentationService $segmentationService)
    {
        parent::__construct();
        $this->segmentationService = $segmentationService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try {
            $segmentId = $this->option('segment-id');
            $all = $this->option('all');

            // If no options provided, default to --all
            if (!$segmentId && !$all) {
                $all = true;
            }

            if ($segmentId) {
                return $this->refreshSingleSegment($segmentId);
            }

            if ($all) {
                return $this->refreshAllSegments();
            }

            return 0;

        } catch (\Exception $e) {
            $this->error('Error refreshing segments: ' . $e->getMessage());
            Log::error('WhatsApp segments refresh failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return 1;
        }
    }

    /**
     * Refresh a single segment by ID
     *
     * @param int $segmentId
     * @return int
     */
    protected function refreshSingleSegment($segmentId)
    {
        $segment = DB::table('wa_customer_segments')
            ->where('id', $segmentId)
            ->first();

        if (!$segment) {
            $this->error("Segment with ID {$segmentId} not found");
            return 1;
        }

        $this->info("Refreshing segment: {$segment->name} (ID: {$segment->id})");

        $startTime = microtime(true);
        $oldCount = $segment->customer_count;

        try {
            $newCount = $this->segmentationService->refreshSegmentCache($segment->id);
            $duration = round(microtime(true) - $startTime, 2);

            $this->info("✓ Segment refreshed successfully");
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Old Count', number_format($oldCount)],
                    ['New Count', number_format($newCount)],
                    ['Change', $this->formatChange($oldCount, $newCount)],
                    ['Processing Time', "{$duration}s"]
                ]
            );

            Log::info('WhatsApp segment refreshed', [
                'segment_id' => $segment->id,
                'segment_name' => $segment->name,
                'old_count' => $oldCount,
                'new_count' => $newCount,
                'duration' => $duration
            ]);

            return 0;

        } catch (\Exception $e) {
            $this->error("Failed to refresh segment: {$e->getMessage()}");
            Log::error('Segment refresh failed', [
                'segment_id' => $segment->id,
                'error' => $e->getMessage()
            ]);

            return 1;
        }
    }

    /**
     * Refresh all segments
     *
     * @return int
     */
    protected function refreshAllSegments()
    {
        $segments = DB::table('wa_customer_segments')->get();

        if ($segments->isEmpty()) {
            $this->warn('No segments found. Run "php artisan whatsapp:seed-segments" first.');
            return 1;
        }

        $this->info("Refreshing {$segments->count()} segments...");
        $this->newLine();

        $bar = $this->output->createProgressBar($segments->count());
        $bar->start();

        $results = [];
        $totalTime = 0;

        foreach ($segments as $segment) {
            $startTime = microtime(true);
            $oldCount = $segment->customer_count;

            try {
                $newCount = $this->segmentationService->refreshSegmentCache($segment->id);
                $duration = microtime(true) - $startTime;
                $totalTime += $duration;

                $results[] = [
                    'name' => $segment->name,
                    'slug' => $segment->slug,
                    'old_count' => $oldCount,
                    'new_count' => $newCount,
                    'change' => $newCount - $oldCount,
                    'duration' => $duration,
                    'status' => 'success'
                ];

            } catch (\Exception $e) {
                $results[] = [
                    'name' => $segment->name,
                    'slug' => $segment->slug,
                    'old_count' => $oldCount,
                    'new_count' => 0,
                    'change' => 0,
                    'duration' => 0,
                    'status' => 'failed',
                    'error' => $e->getMessage()
                ];

                Log::error('Segment refresh failed', [
                    'segment_id' => $segment->id,
                    'segment_name' => $segment->name,
                    'error' => $e->getMessage()
                ]);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Display results table
        $tableData = [];
        foreach ($results as $result) {
            $tableData[] = [
                $result['name'],
                $result['slug'],
                number_format($result['old_count']),
                number_format($result['new_count']),
                $this->formatChange($result['old_count'], $result['new_count']),
                round($result['duration'], 2) . 's',
                $result['status'] === 'success' ? '✓' : '✗'
            ];
        }

        $this->table(
            ['Segment', 'Slug', 'Old Count', 'New Count', 'Change', 'Time', 'Status'],
            $tableData
        );

        // Summary
        $successful = collect($results)->where('status', 'success')->count();
        $failed = collect($results)->where('status', 'failed')->count();
        $totalCustomers = collect($results)->sum('new_count');

        $this->newLine();
        $this->info("Summary:");
        $this->info("  ✓ Successful: {$successful}");
        if ($failed > 0) {
            $this->warn("  ✗ Failed: {$failed}");
        }
        $this->info("  Total customers in segments: " . number_format($totalCustomers));
        $this->info("  Total processing time: " . round($totalTime, 2) . "s");

        Log::info('WhatsApp segments bulk refresh completed', [
            'total_segments' => $segments->count(),
            'successful' => $successful,
            'failed' => $failed,
            'total_customers' => $totalCustomers,
            'total_time' => $totalTime
        ]);

        return $failed > 0 ? 1 : 0;
    }

    /**
     * Format the change between old and new counts
     *
     * @param int $oldCount
     * @param int $newCount
     * @return string
     */
    protected function formatChange($oldCount, $newCount)
    {
        $change = $newCount - $oldCount;

        if ($change == 0) {
            return '0';
        }

        $sign = $change > 0 ? '+' : '';
        return $sign . number_format($change);
    }
}
