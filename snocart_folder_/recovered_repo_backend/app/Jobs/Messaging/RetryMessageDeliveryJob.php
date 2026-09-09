<?php

namespace App\Jobs\Messaging;

use App\Services\Messaging\MessageDeliveryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * RetryMessageDeliveryJob
 *
 * Scheduled job to retry failed message deliveries.
 * Runs every 5 minutes to process failed messages with exponential backoff.
 *
 * Schedule: */5 * * * * (every 5 minutes)
 * Queue: messaging
 * Timeout: 120 seconds
 */
class RetryMessageDeliveryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Number of times the job may be attempted
     */
    public $tries = 1; // No retry for the job itself (it retries messages internally)

    /**
     * Number of seconds the job can run before timing out
     */
    public $timeout = 120;

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
    public function handle(MessageDeliveryService $deliveryService): void
    {
        try {
            Log::info('RetryMessageDeliveryJob: Starting retry process');

            // Retry failed messages
            $stats = $deliveryService->retryFailedMessages();

            Log::info('RetryMessageDeliveryJob: Completed', $stats);

            // If there are still failed messages, log a warning
            if ($stats['failed'] > 0) {
                Log::warning('RetryMessageDeliveryJob: Some messages still failing', [
                    'failed_count' => $stats['failed'],
                    'max_retries_reached' => $stats['max_retries_reached']
                ]);
            }

        } catch (\Exception $e) {
            Log::error('RetryMessageDeliveryJob: Exception', [
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
        Log::error('RetryMessageDeliveryJob: Job failed', [
            'error' => $exception->getMessage()
        ]);
    }

    /**
     * Get the tags that should be assigned to the job
     */
    public function tags(): array
    {
        return ['messaging', 'retry', 'scheduled'];
    }
}
