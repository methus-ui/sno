<?php

namespace App\Jobs;

use App\Models\WaCampaignRecipient;
use App\Models\WaCustomerJourney;
use App\Services\WhatsAppApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SendWhatsAppMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The recipient ID to process.
     *
     * @var int
     */
    protected $recipientId;

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
     * The maximum number of unhandled exceptions to allow before failing.
     *
     * @var int
     */
    public $maxExceptions = 5;

    /**
     * Delete the job if its models no longer exist.
     *
     * @var bool
     */
    public $deleteWhenMissingModels = true;

    /**
     * Create a new job instance.
     *
     * @param int $recipientId
     * @return void
     */
    public function __construct(int $recipientId)
    {
        $this->recipientId = $recipientId;
        $this->onQueue('whatsapp-messages');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(WhatsAppApiService $whatsappService)
    {
        $startTime = microtime(true);

        Log::info('WhatsApp Message Job Started', [
            'recipient_id' => $this->recipientId,
            'job_id' => $this->job->getJobId(),
            'attempt' => $this->attempts()
        ]);

        try {
            // 1. Fetch recipient record
            $recipient = WaCampaignRecipient::findOrFail($this->recipientId);

            // Skip if already sent or delivered
            if (in_array($recipient->status, ['sent', 'delivered', 'read'])) {
                Log::info('Message already sent/delivered', [
                    'recipient_id' => $this->recipientId,
                    'status' => $recipient->status
                ]);
                return;
            }

            // Fetch campaign details
            $campaign = $recipient->campaign;

            if (!$campaign) {
                throw new \Exception('Campaign not found for recipient');
            }

            if ($campaign->status === 'cancelled') {
                Log::warning('Campaign cancelled, skipping message', [
                    'recipient_id' => $this->recipientId,
                    'campaign_id' => $campaign->id
                ]);
                $recipient->update([
                    'status' => 'cancelled',
                    'updated_at' => Carbon::now()
                ]);
                return;
            }

            // 2. Check rate limit
            $rateLimitCheck = $whatsappService->checkRateLimit();

            if (!$rateLimitCheck['allowed']) {
                Log::warning('Rate limit reached, retrying later', [
                    'recipient_id' => $this->recipientId,
                    'retry_after' => $rateLimitCheck['retry_after'] ?? 60
                ]);

                // Release the job back to queue with delay
                $this->release($rateLimitCheck['retry_after'] ?? 60);
                return;
            }

            // 3. Prepare message data
            $personalizationData = json_decode($recipient->personalization_data, true) ?? [];

            $messageData = [
                'phone' => $recipient->phone,
                'template_name' => $campaign->template_name,
                'template_language' => $campaign->template_language ?? 'en',
                'parameters' => $personalizationData,
                'media_url' => $campaign->media_url ?? null,
                'campaign_id' => $campaign->id,
                'recipient_id' => $this->recipientId
            ];

            Log::info('Sending WhatsApp message', [
                'recipient_id' => $this->recipientId,
                'phone' => $recipient->phone,
                'template' => $campaign->template_name,
                'attempt' => $this->attempts()
            ]);

            // 4. Call WhatsApp API service
            $response = $whatsappService->sendMessage($messageData);

            // 5. Update recipient status based on response
            if ($response['success']) {
                $recipient->update([
                    'status' => 'sent',
                    'sent_at' => Carbon::now(),
                    'message_id' => $response['message_id'] ?? null,
                    'api_response' => json_encode($response),
                    'updated_at' => Carbon::now()
                ]);

                Log::info('WhatsApp message sent successfully', [
                    'recipient_id' => $this->recipientId,
                    'message_id' => $response['message_id'] ?? null,
                    'phone' => $recipient->phone
                ]);

                // 6. Record in wa_customer_journey
                $this->recordCustomerJourney($recipient, $campaign, 'message_sent', $response);

            } else {
                // API returned error
                throw new \Exception($response['error'] ?? 'Unknown API error');
            }

            // Calculate processing time
            $processingTime = round((microtime(true) - $startTime) * 1000, 2);

            Log::info('WhatsApp Message Job Completed', [
                'recipient_id' => $this->recipientId,
                'processing_time_ms' => $processingTime
            ]);

        } catch (\Exception $e) {
            Log::error('WhatsApp Message Job Failed', [
                'recipient_id' => $this->recipientId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'attempt' => $this->attempts()
            ]);

            // Update recipient with error if max retries reached
            if ($this->attempts() >= $this->tries) {
                try {
                    $recipient = WaCampaignRecipient::find($this->recipientId);
                    if ($recipient) {
                        $recipient->update([
                            'status' => 'failed',
                            'failure_reason' => substr($e->getMessage(), 0, 255),
                            'failed_at' => Carbon::now(),
                            'updated_at' => Carbon::now()
                        ]);

                        // Record failure in customer journey
                        $this->recordCustomerJourney($recipient, $recipient->campaign, 'message_failed', [
                            'error' => $e->getMessage()
                        ]);
                    }
                } catch (\Exception $updateException) {
                    Log::error('Failed to update recipient status', [
                        'recipient_id' => $this->recipientId,
                        'error' => $updateException->getMessage()
                    ]);
                }
            }

            throw $e;
        }
    }

    /**
     * Record event in customer journey.
     *
     * @param WaCampaignRecipient $recipient
     * @param mixed $campaign
     * @param string $eventType
     * @param array $metadata
     * @return void
     */
    protected function recordCustomerJourney($recipient, $campaign, string $eventType, array $metadata = [])
    {
        try {
            WaCustomerJourney::create([
                'user_id' => $recipient->user_id,
                'campaign_id' => $campaign->id,
                'event_type' => $eventType,
                'event_data' => json_encode($metadata),
                'phone' => $recipient->phone,
                'message_id' => $metadata['message_id'] ?? null,
                'created_at' => Carbon::now()
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to record customer journey', [
                'recipient_id' => $this->recipientId,
                'event_type' => $eventType,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle a job failure.
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception)
    {
        Log::critical('WhatsApp Message Job Failed Permanently', [
            'recipient_id' => $this->recipientId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
            'attempts' => $this->attempts()
        ]);

        try {
            $recipient = WaCampaignRecipient::find($this->recipientId);
            if ($recipient) {
                $recipient->update([
                    'status' => 'failed',
                    'failure_reason' => 'Job failed after ' . $this->tries . ' attempts: ' . substr($exception->getMessage(), 0, 200),
                    'failed_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ]);

                // Record permanent failure in customer journey
                if ($recipient->campaign) {
                    $this->recordCustomerJourney($recipient, $recipient->campaign, 'message_failed_permanent', [
                        'error' => $exception->getMessage(),
                        'attempts' => $this->tries
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to update recipient status in failed() method', [
                'recipient_id' => $this->recipientId,
                'error' => $e->getMessage()
            ]);
        }
    }
}
