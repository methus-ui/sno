<?php

namespace App\Jobs;

use App\Models\WhatsAppCampaign;
use App\Models\WaCampaignAnalytics;
use App\Services\CampaignAnalyticsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CalculateCampaignAnalyticsJob implements ShouldQueue
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
    public $tries = 2;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var array
     */
    public $backoff = [60, 180];

    /**
     * The maximum number of unhandled exceptions to allow before failing.
     *
     * @var int
     */
    public $maxExceptions = 2;

    /**
     * Create a new job instance.
     *
     * @param int $campaignId
     * @return void
     */
    public function __construct(int $campaignId)
    {
        $this->campaignId = $campaignId;
        $this->onQueue('whatsapp-analytics');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(CampaignAnalyticsService $analyticsService)
    {
        $startTime = microtime(true);

        Log::info('Campaign Analytics Calculation Started', [
            'campaign_id' => $this->campaignId,
            'job_id' => $this->job->getJobId(),
            'attempt' => $this->attempts()
        ]);

        try {
            DB::beginTransaction();

            // 1. Fetch campaign
            $campaign = WhatsAppCampaign::findOrFail($this->campaignId);

            Log::info('Processing campaign analytics', [
                'campaign_id' => $this->campaignId,
                'campaign_name' => $campaign->name,
                'total_recipients' => $campaign->total_recipients
            ]);

            // 2. Calculate campaign metrics
            Log::info('Calculating campaign metrics', [
                'campaign_id' => $this->campaignId
            ]);

            $metrics = $analyticsService->calculateMetrics($campaign);

            Log::info('Campaign metrics calculated', [
                'campaign_id' => $this->campaignId,
                'sent_count' => $metrics['sent_count'] ?? 0,
                'delivered_count' => $metrics['delivered_count'] ?? 0,
                'failed_count' => $metrics['failed_count'] ?? 0,
                'delivery_rate' => $metrics['delivery_rate'] ?? 0
            ]);

            // 3. Track re-engagement
            Log::info('Tracking re-engagement', [
                'campaign_id' => $this->campaignId
            ]);

            $reEngagement = $analyticsService->trackReEngagement($campaign);

            Log::info('Re-engagement tracked', [
                'campaign_id' => $this->campaignId,
                'click_count' => $reEngagement['click_count'] ?? 0,
                'response_count' => $reEngagement['response_count'] ?? 0,
                'engagement_rate' => $reEngagement['engagement_rate'] ?? 0
            ]);

            // 4. Calculate ROI
            Log::info('Calculating ROI', [
                'campaign_id' => $this->campaignId
            ]);

            $roi = $analyticsService->calculateROI($campaign);

            Log::info('ROI calculated', [
                'campaign_id' => $this->campaignId,
                'total_revenue' => $roi['total_revenue'] ?? 0,
                'total_cost' => $roi['total_cost'] ?? 0,
                'roi_percentage' => $roi['roi_percentage'] ?? 0,
                'attributed_orders' => $roi['attributed_orders'] ?? 0
            ]);

            // 5. Store results in wa_campaign_analytics table
            $analyticsData = [
                'campaign_id' => $this->campaignId,

                // Message metrics
                'total_sent' => $metrics['sent_count'] ?? 0,
                'total_delivered' => $metrics['delivered_count'] ?? 0,
                'total_failed' => $metrics['failed_count'] ?? 0,
                'total_read' => $metrics['read_count'] ?? 0,
                'delivery_rate' => $metrics['delivery_rate'] ?? 0,
                'read_rate' => $metrics['read_rate'] ?? 0,

                // Engagement metrics
                'total_clicks' => $reEngagement['click_count'] ?? 0,
                'total_responses' => $reEngagement['response_count'] ?? 0,
                'engagement_rate' => $reEngagement['engagement_rate'] ?? 0,
                'ctr' => $reEngagement['click_through_rate'] ?? 0,

                // Revenue metrics
                'attributed_orders' => $roi['attributed_orders'] ?? 0,
                'total_revenue' => $roi['total_revenue'] ?? 0,
                'total_cost' => $roi['total_cost'] ?? 0,
                'roi_percentage' => $roi['roi_percentage'] ?? 0,
                'roas' => $roi['roas'] ?? 0,
                'cost_per_order' => $roi['cost_per_order'] ?? 0,
                'average_order_value' => $roi['average_order_value'] ?? 0,

                // Additional metrics
                'unique_customers_reached' => $metrics['unique_customers'] ?? 0,
                'bounce_rate' => $metrics['bounce_rate'] ?? 0,
                'opt_out_count' => $metrics['opt_out_count'] ?? 0,

                'calculated_at' => Carbon::now(),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ];

            // Upsert analytics record
            WaCampaignAnalytics::updateOrCreate(
                ['campaign_id' => $this->campaignId],
                $analyticsData
            );

            Log::info('Analytics data stored', [
                'campaign_id' => $this->campaignId
            ]);

            // 6. Update campaign status to completed
            $campaign->update([
                'status' => 'completed',
                'completed_at' => Carbon::now()
            ]);

            Log::info('Campaign status updated to completed', [
                'campaign_id' => $this->campaignId
            ]);

            DB::commit();

            // Calculate processing time
            $processingTime = round((microtime(true) - $startTime) * 1000, 2);

            Log::info('Campaign Analytics Calculation Completed', [
                'campaign_id' => $this->campaignId,
                'processing_time_ms' => $processingTime,
                'analytics_summary' => [
                    'delivery_rate' => $analyticsData['delivery_rate'],
                    'engagement_rate' => $analyticsData['engagement_rate'],
                    'roi_percentage' => $analyticsData['roi_percentage'],
                    'total_revenue' => $analyticsData['total_revenue'],
                    'attributed_orders' => $analyticsData['attributed_orders']
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Campaign Analytics Calculation Failed', [
                'campaign_id' => $this->campaignId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'attempt' => $this->attempts()
            ]);

            // Don't update campaign status on retry
            if ($this->attempts() >= $this->tries) {
                try {
                    // Update campaign to completed anyway (analytics optional)
                    $campaign = WhatsAppCampaign::find($this->campaignId);
                    if ($campaign && $campaign->status === 'running') {
                        $campaign->update([
                            'status' => 'completed',
                            'completed_at' => Carbon::now()
                        ]);

                        Log::warning('Campaign marked as completed despite analytics failure', [
                            'campaign_id' => $this->campaignId
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
        Log::critical('Campaign Analytics Job Failed Permanently', [
            'campaign_id' => $this->campaignId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
            'attempts' => $this->attempts()
        ]);

        try {
            // Mark campaign as completed even if analytics failed
            $campaign = WhatsAppCampaign::find($this->campaignId);
            if ($campaign && $campaign->status === 'running') {
                $campaign->update([
                    'status' => 'completed',
                    'completed_at' => Carbon::now()
                ]);

                Log::warning('Campaign marked as completed in failed() - analytics unavailable', [
                    'campaign_id' => $this->campaignId
                ]);
            }

            // Create partial analytics record with error flag
            WaCampaignAnalytics::updateOrCreate(
                ['campaign_id' => $this->campaignId],
                [
                    'campaign_id' => $this->campaignId,
                    'calculation_error' => substr($exception->getMessage(), 0, 500),
                    'calculated_at' => Carbon::now(),
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ]
            );

        } catch (\Exception $e) {
            Log::error('Failed to handle analytics job failure', [
                'campaign_id' => $this->campaignId,
                'error' => $e->getMessage()
            ]);
        }
    }
}
