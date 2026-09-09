<?php

namespace App\Jobs;

use App\Models\WhatsAppCampaign;
use App\Models\WaCampaignRecipient;
use App\Services\CampaignBuilderService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SendWhatsAppCampaignJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The campaign ID to process.
     *
     * @var int
     */
    protected $campaignId;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var array
     */
    public $backoff = [10, 30, 60];

    /**
     * The maximum number of unhandled exceptions to allow before failing.
     *
     * @var int
     */
    public $maxExceptions = 3;

    /**
     * Create a new job instance.
     *
     * @param int $campaignId
     * @return void
     */
    public function __construct(int $campaignId)
    {
        $this->campaignId = $campaignId;
        $this->onQueue('whatsapp');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(CampaignBuilderService $campaignBuilder)
    {
        $startTime = microtime(true);

        Log::info('WhatsApp Campaign Orchestration Started', [
            'campaign_id' => $this->campaignId,
            'job_id' => $this->job->getJobId(),
            'attempt' => $this->attempts()
        ]);

        try {
            DB::beginTransaction();

            // 1. Fetch campaign
            $campaign = WhatsAppCampaign::findOrFail($this->campaignId);

            if ($campaign->status === 'completed' || $campaign->status === 'cancelled') {
                Log::warning('Campaign already completed or cancelled', [
                    'campaign_id' => $this->campaignId,
                    'status' => $campaign->status
                ]);
                DB::rollBack();
                return;
            }

            // 2. Update campaign status to running
            $campaign->update([
                'status' => 'running',
                'started_at' => Carbon::now()
            ]);

            // 3. Fetch recipients from segments
            Log::info('Fetching recipients from segments', [
                'campaign_id' => $this->campaignId,
                'segments' => $campaign->segments
            ]);

            $recipients = $campaignBuilder->getRecipients($campaign);

            if (empty($recipients)) {
                Log::warning('No recipients found for campaign', [
                    'campaign_id' => $this->campaignId
                ]);

                $campaign->update([
                    'status' => 'completed',
                    'completed_at' => Carbon::now(),
                    'total_recipients' => 0
                ]);

                DB::commit();
                return;
            }

            // 4. Deduplicate phone numbers
            $uniqueRecipients = collect($recipients)->unique('phone')->values()->all();

            Log::info('Recipients deduplicated', [
                'campaign_id' => $this->campaignId,
                'original_count' => count($recipients),
                'unique_count' => count($uniqueRecipients)
            ]);

            // 5. Create tracking records in wa_campaign_recipients
            $recipientRecords = [];
            foreach ($uniqueRecipients as $recipient) {
                $recipientRecords[] = [
                    'campaign_id' => $this->campaignId,
                    'user_id' => $recipient['user_id'] ?? null,
                    'phone' => $recipient['phone'],
                    'customer_name' => $recipient['name'] ?? null,
                    'status' => 'pending',
                    'personalization_data' => json_encode($recipient['personalization'] ?? []),
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ];
            }

            // Batch insert for performance
            WaCampaignRecipient::insert($recipientRecords);

            // Update campaign total recipients count
            $campaign->update([
                'total_recipients' => count($uniqueRecipients)
            ]);

            DB::commit();

            // 6. Dispatch individual SendWhatsAppMessageJob for each recipient
            $dispatchedCount = 0;
            $recipientIds = WaCampaignRecipient::where('campaign_id', $this->campaignId)
                ->where('status', 'pending')
                ->pluck('id');

            foreach ($recipientIds as $recipientId) {
                SendWhatsAppMessageJob::dispatch($recipientId)
                    ->onQueue('whatsapp-messages');
                $dispatchedCount++;
            }

            Log::info('Individual message jobs dispatched', [
                'campaign_id' => $this->campaignId,
                'dispatched_count' => $dispatchedCount
            ]);

            // 7. Dispatch analytics calculation job with 5 minute delay
            CalculateCampaignAnalyticsJob::dispatch($this->campaignId)
                ->delay(now()->addMinutes(5))
                ->onQueue('whatsapp-analytics');

            // Calculate and log processing time
            $processingTime = round((microtime(true) - $startTime) * 1000, 2);

            Log::info('WhatsApp Campaign Orchestration Completed', [
                'campaign_id' => $this->campaignId,
                'total_recipients' => count($uniqueRecipients),
                'dispatched_count' => $dispatchedCount,
                'processing_time_ms' => $processingTime
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('WhatsApp Campaign Orchestration Failed', [
                'campaign_id' => $this->campaignId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'attempt' => $this->attempts()
            ]);

            // Update campaign status to failed if max retries reached
            if ($this->attempts() >= $this->tries) {
                try {
                    $campaign = WhatsAppCampaign::find($this->campaignId);
                    if ($campaign) {
                        $campaign->update([
                            'status' => 'failed',
                            'completed_at' => Carbon::now()
                        ]);
                    }
                } catch (\Exception $updateException) {
                    Log::error('Failed to update campaign status', [
                        'campaign_id' => $this->campaignId,
                        'error' => $updateException->getMessage()
                    ]);
                }
            }

            throw $e;
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
        Log::critical('WhatsApp Campaign Orchestration Job Failed Permanently', [
            'campaign_id' => $this->campaignId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
            'attempts' => $this->attempts()
        ]);

        try {
            $campaign = WhatsAppCampaign::find($this->campaignId);
            if ($campaign) {
                $campaign->update([
                    'status' => 'failed',
                    'completed_at' => Carbon::now()
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to update campaign status in failed() method', [
                'campaign_id' => $this->campaignId,
                'error' => $e->getMessage()
            ]);
        }
    }
}
