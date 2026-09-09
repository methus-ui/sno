<?php

namespace App\Jobs;

use App\Services\WhatsApp\WebhookHandlerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessWhatsAppWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The webhook payload.
     *
     * @var array
     */
    protected array $payload;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 5;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var array
     */
    public $backoff = [10, 30, 60, 120, 300];

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 120;

    /**
     * Create a new job instance.
     *
     * @param array $payload
     * @return void
     */
    public function __construct(array $payload)
    {
        $this->payload = $payload;
        $this->onQueue('whatsapp-webhooks'); // Dedicated queue for webhooks
    }

    /**
     * Execute the job.
     *
     * @param WebhookHandlerService $service
     * @return void
     */
    public function handle(WebhookHandlerService $service): void
    {
        try {
            Log::info('Processing WhatsApp webhook job', [
                'attempt' => $this->attempts(),
                'entries_count' => count($this->payload['entry'] ?? []),
            ]);

            $success = $service->handleWebhook($this->payload);

            if ($success) {
                Log::info('WhatsApp webhook processed successfully');
            } else {
                Log::warning('WhatsApp webhook processing returned false');
            }
        } catch (\Exception $e) {
            Log::error('WhatsApp webhook job failed', [
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Re-throw to trigger retry mechanism
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception): void
    {
        Log::critical('WhatsApp webhook job failed after all retries', [
            'error' => $exception->getMessage(),
            'payload' => $this->payload,
            'trace' => $exception->getTraceAsString(),
        ]);

        // TODO: Send alert to admin/developer
        // TODO: Store in failed_jobs table for manual retry
    }

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array
     */
    public function tags(): array
    {
        return ['whatsapp', 'webhook', 'inbound'];
    }
}
