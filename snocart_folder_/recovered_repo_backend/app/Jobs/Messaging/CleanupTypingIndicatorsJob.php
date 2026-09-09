<?php

namespace App\Jobs\Messaging;

use App\Services\Messaging\TypingIndicatorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * CleanupTypingIndicatorsJob
 *
 * Scheduled job to cleanup expired typing indicators from Redis.
 * Although Redis TTL auto-expires, this provides additional cleanup for stale entries.
 *
 * Schedule: */10 * * * * (every 10 minutes)
 * Queue: messaging
 * Timeout: 60 seconds
 */
class CleanupTypingIndicatorsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Number of times the job may be attempted
     */
    public $tries = 1; // No retry needed for cleanup job

    /**
     * Number of seconds the job can run before timing out
     */
    public $timeout = 60;

    /**
     * Create a new job instance
     */
    public function __construct()
    {
        // Use dedicated messaging queue
        $this->onQueue('messaging');
    }

    /**
     * Execute the job
     */
    public function handle(TypingIndicatorService $typingService): void
    {
        try {
            Log::info('CleanupTypingIndicatorsJob: Starting cleanup');

            // Cleanup expired typing indicators
            $stats = $typingService->cleanupExpired();

            Log::info('CleanupTypingIndicatorsJob: Completed', $stats);

            // If many indicators were cleaned, it might indicate a problem
            if ($stats['indicators_cleaned'] > 100) {
                Log::warning('CleanupTypingIndicatorsJob: High number of stale indicators', [
                    'cleaned_count' => $stats['indicators_cleaned']
                ]);
            }

        } catch (\Exception $e) {
            Log::error('CleanupTypingIndicatorsJob: Exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Don't re-throw - we'll try again on next schedule
        }
    }

    /**
     * Handle a job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('CleanupTypingIndicatorsJob: Job failed', [
            'error' => $exception->getMessage()
        ]);
    }

    /**
     * Get the tags that should be assigned to the job
     */
    public function tags(): array
    {
        return ['messaging', 'cleanup', 'scheduled'];
    }
}
