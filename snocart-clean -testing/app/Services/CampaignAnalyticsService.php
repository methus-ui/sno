<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CampaignAnalyticsService
{
    /**
     * Calculate comprehensive campaign metrics
     *
     * @param int $campaignId Campaign ID
     * @return array All campaign metrics
     */
    public function calculateMetrics($campaignId)
    {
        try {
            $campaign = DB::table('wa_campaigns')->find($campaignId);

            if (!$campaign) {
                throw new \Exception('Campaign not found');
            }

            // Get recipient stats
            $recipientStats = DB::table('wa_campaign_recipients')
                ->where('campaign_id', $campaignId)
                ->select('status', DB::raw('COUNT(*) as count'))
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray();

            $totalRecipients = $campaign->total_recipients;
            $sent = $recipientStats['sent'] ?? 0;
            $delivered = $recipientStats['delivered'] ?? 0;
            $read = $recipientStats['read'] ?? 0;
            $failed = $recipientStats['failed'] ?? 0;

            // Calculate rates
            $deliveryRate = $sent > 0 ? round(($delivered / $sent) * 100, 2) : 0;
            $readRate = $delivered > 0 ? round(($read / $delivered) * 100, 2) : 0;
            $failureRate = $sent > 0 ? round(($failed / $sent) * 100, 2) : 0;

            // Calculate conversion rates
            $conversionMetrics = $this->calculateConversionRates($campaignId);

            // Calculate ROI
            $roiData = $this->calculateROI($campaignId);

            // Re-engagement metrics
            $reEngagement = $this->trackReEngagement($campaignId);

            $metrics = [
                'campaign_id' => $campaignId,
                'campaign_name' => $campaign->name,
                'status' => $campaign->status,

                // Delivery metrics
                'total_recipients' => $totalRecipients,
                'sent' => $sent,
                'delivered' => $delivered,
                'read' => $read,
                'failed' => $failed,
                'pending' => $recipientStats['pending'] ?? 0,

                // Performance rates
                'delivery_rate' => $deliveryRate,
                'read_rate' => $readRate,
                'failure_rate' => $failureRate,

                // Conversion metrics
                'conversions_24h' => $conversionMetrics['conversions_24h'],
                'conversions_7d' => $conversionMetrics['conversions_7d'],
                'conversions_30d' => $conversionMetrics['conversions_30d'],
                'conversion_rate_24h' => $conversionMetrics['conversion_rate_24h'],
                'conversion_rate_7d' => $conversionMetrics['conversion_rate_7d'],
                'conversion_rate_30d' => $conversionMetrics['conversion_rate_30d'],

                // Revenue metrics
                'revenue_24h' => $conversionMetrics['revenue_24h'],
                'revenue_7d' => $conversionMetrics['revenue_7d'],
                'revenue_30d' => $conversionMetrics['revenue_30d'],

                // ROI metrics
                'estimated_cost' => $roiData['cost'],
                'actual_cost' => $roiData['actual_cost'],
                'total_revenue' => $roiData['revenue'],
                'roi_percentage' => $roiData['roi'],
                'profit' => $roiData['profit'],

                // Re-engagement metrics
                'reengaged_customers' => $reEngagement['reengaged_count'],
                'reengagement_rate' => $reEngagement['reengagement_rate'],

                // Timestamps
                'sent_at' => $campaign->sent_at,
                'completed_at' => $campaign->completed_at,
                'calculated_at' => now()->toDateTimeString(),
            ];

            // Store metrics in database
            $this->storeMetrics($campaignId, $metrics);

            return [
                'success' => true,
                'metrics' => $metrics,
            ];

        } catch (\Exception $e) {
            Log::error('Failed to calculate campaign metrics', [
                'campaign_id' => $campaignId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Track customer re-engagement after campaign
     *
     * @param int $campaignId Campaign ID
     * @return array Re-engagement statistics
     */
    public function trackReEngagement($campaignId)
    {
        try {
            $campaign = DB::table('wa_campaigns')->find($campaignId);

            if (!$campaign || !$campaign->sent_at) {
                return [
                    'reengaged_count' => 0,
                    'reengagement_rate' => 0,
                ];
            }

            $sentAt = Carbon::parse($campaign->sent_at);

            // Find customers who:
            // 1. Received the campaign
            // 2. Were inactive before campaign
            // 3. Placed order after campaign

            $reengagedCustomers = DB::table('wa_campaign_recipients as wcr')
                ->join('users', 'wcr.user_id', '=', 'users.id')
                ->join('orders as o1', 'users.id', '=', 'o1.user_id')
                ->where('wcr.campaign_id', $campaignId)
                ->whereIn('wcr.status', ['sent', 'delivered', 'read'])
                ->whereNotNull('wcr.user_id')
                ->where('o1.created_at', '>=', $sentAt)
                ->whereIn('o1.order_status', ['delivered', 'refunded'])
                ->whereNotExists(function ($query) use ($sentAt) {
                    $query->select(DB::raw(1))
                        ->from('orders as o2')
                        ->whereColumn('o2.user_id', 'users.id')
                        ->where('o2.created_at', '>=', $sentAt->copy()->subDays(30))
                        ->where('o2.created_at', '<', $sentAt);
                })
                ->distinct()
                ->count('users.id');

            $totalSent = DB::table('wa_campaign_recipients')
                ->where('campaign_id', $campaignId)
                ->whereIn('status', ['sent', 'delivered', 'read'])
                ->whereNotNull('user_id')
                ->count();

            $reengagementRate = $totalSent > 0
                ? round(($reengagedCustomers / $totalSent) * 100, 2)
                : 0;

            return [
                'reengaged_count' => $reengagedCustomers,
                'total_sent' => $totalSent,
                'reengagement_rate' => $reengagementRate,
            ];

        } catch (\Exception $e) {
            Log::error('Failed to track re-engagement', [
                'campaign_id' => $campaignId,
                'error' => $e->getMessage(),
            ]);

            return [
                'reengaged_count' => 0,
                'reengagement_rate' => 0,
            ];
        }
    }

    /**
     * Calculate ROI for campaign
     *
     * @param int $campaignId Campaign ID
     * @return array ROI data (cost, revenue, roi percentage)
     */
    public function calculateROI($campaignId)
    {
        try {
            $campaign = DB::table('wa_campaigns')->find($campaignId);

            if (!$campaign) {
                throw new \Exception('Campaign not found');
            }

            // Cost calculation
            $estimatedCost = $campaign->estimated_cost ?? 0;

            // Actual cost = messages actually sent * cost per message
            $actualSent = DB::table('wa_campaign_recipients')
                ->where('campaign_id', $campaignId)
                ->whereIn('status', ['sent', 'delivered', 'read'])
                ->count();

            $costPerMessage = 0.10; // ₹0.10 per message
            $actualCost = $actualSent * $costPerMessage;

            // Revenue calculation (30-day attribution window)
            $revenue = 0;
            if ($campaign->sent_at) {
                $sentAt = Carbon::parse($campaign->sent_at);
                $endDate = $sentAt->copy()->addDays(30);

                $revenue = DB::table('wa_campaign_recipients as wcr')
                    ->join('orders', 'wcr.user_id', '=', 'orders.user_id')
                    ->where('wcr.campaign_id', $campaignId)
                    ->whereIn('wcr.status', ['sent', 'delivered', 'read'])
                    ->whereNotNull('wcr.user_id')
                    ->where('orders.created_at', '>=', $sentAt)
                    ->where('orders.created_at', '<=', $endDate)
                    ->whereIn('orders.order_status', ['delivered', 'refunded'])
                    ->sum('orders.order_amount');
            }

            // ROI calculation
            $profit = $revenue - $actualCost;
            $roi = $actualCost > 0
                ? round((($revenue - $actualCost) / $actualCost) * 100, 2)
                : 0;

            return [
                'cost' => $estimatedCost,
                'actual_cost' => round($actualCost, 2),
                'revenue' => round($revenue, 2),
                'profit' => round($profit, 2),
                'roi' => $roi,
            ];

        } catch (\Exception $e) {
            Log::error('Failed to calculate ROI', [
                'campaign_id' => $campaignId,
                'error' => $e->getMessage(),
            ]);

            return [
                'cost' => 0,
                'actual_cost' => 0,
                'revenue' => 0,
                'profit' => 0,
                'roi' => 0,
            ];
        }
    }

    /**
     * Generate comprehensive campaign report
     *
     * @param int $campaignId Campaign ID
     * @return array Full campaign report
     */
    public function generateReport($campaignId)
    {
        try {
            $metrics = $this->calculateMetrics($campaignId);

            if (!$metrics['success']) {
                throw new \Exception($metrics['error']);
            }

            $campaign = DB::table('wa_campaigns')->find($campaignId);

            // Get hourly delivery stats
            $hourlyStats = DB::table('wa_campaign_recipients')
                ->where('campaign_id', $campaignId)
                ->whereNotNull('delivered_at')
                ->select(
                    DB::raw('HOUR(delivered_at) as hour'),
                    DB::raw('COUNT(*) as count')
                )
                ->groupBy('hour')
                ->orderBy('hour')
                ->get();

            // Get failure reasons breakdown
            $failureReasons = DB::table('wa_campaign_recipients')
                ->where('campaign_id', $campaignId)
                ->where('status', 'failed')
                ->select('failure_reason', DB::raw('COUNT(*) as count'))
                ->groupBy('failure_reason')
                ->pluck('count', 'failure_reason')
                ->toArray();

            $report = [
                'success' => true,
                'campaign' => [
                    'id' => $campaign->id,
                    'name' => $campaign->name,
                    'created_at' => $campaign->created_at,
                    'sent_at' => $campaign->sent_at,
                    'completed_at' => $campaign->completed_at,
                ],
                'metrics' => $metrics['metrics'],
                'hourly_delivery' => $hourlyStats,
                'failure_breakdown' => $failureReasons,
                'generated_at' => now()->toDateTimeString(),
            ];

            // Store report
            DB::table('wa_campaign_reports')->updateOrInsert(
                ['campaign_id' => $campaignId],
                [
                    'report_data' => json_encode($report),
                    'generated_at' => now(),
                    'updated_at' => now(),
                ]
            );

            Log::info('Campaign report generated', [
                'campaign_id' => $campaignId,
            ]);

            return $report;

        } catch (\Exception $e) {
            Log::error('Failed to generate campaign report', [
                'campaign_id' => $campaignId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Calculate conversion rates for different time windows
     *
     * @param int $campaignId Campaign ID
     * @return array Conversion metrics
     */
    protected function calculateConversionRates($campaignId)
    {
        $campaign = DB::table('wa_campaigns')->find($campaignId);

        if (!$campaign || !$campaign->sent_at) {
            return [
                'conversions_24h' => 0,
                'conversions_7d' => 0,
                'conversions_30d' => 0,
                'conversion_rate_24h' => 0,
                'conversion_rate_7d' => 0,
                'conversion_rate_30d' => 0,
                'revenue_24h' => 0,
                'revenue_7d' => 0,
                'revenue_30d' => 0,
            ];
        }

        $sentAt = Carbon::parse($campaign->sent_at);
        $totalDelivered = DB::table('wa_campaign_recipients')
            ->where('campaign_id', $campaignId)
            ->whereIn('status', ['delivered', 'read'])
            ->count();

        // Calculate for each time window
        $windows = [
            '24h' => 24,
            '7d' => 168,
            '30d' => 720,
        ];

        $results = [];

        foreach ($windows as $key => $hours) {
            $endDate = $sentAt->copy()->addHours($hours);

            $conversions = DB::table('wa_campaign_recipients as wcr')
                ->join('orders', 'wcr.user_id', '=', 'orders.user_id')
                ->where('wcr.campaign_id', $campaignId)
                ->whereIn('wcr.status', ['delivered', 'read'])
                ->whereNotNull('wcr.user_id')
                ->where('orders.created_at', '>=', $sentAt)
                ->where('orders.created_at', '<=', $endDate)
                ->whereIn('orders.order_status', ['delivered', 'refunded'])
                ->distinct()
                ->count('wcr.user_id');

            $revenue = DB::table('wa_campaign_recipients as wcr')
                ->join('orders', 'wcr.user_id', '=', 'orders.user_id')
                ->where('wcr.campaign_id', $campaignId)
                ->whereIn('wcr.status', ['delivered', 'read'])
                ->whereNotNull('wcr.user_id')
                ->where('orders.created_at', '>=', $sentAt)
                ->where('orders.created_at', '<=', $endDate)
                ->whereIn('orders.order_status', ['delivered', 'refunded'])
                ->sum('orders.order_amount');

            $conversionRate = $totalDelivered > 0
                ? round(($conversions / $totalDelivered) * 100, 2)
                : 0;

            $results['conversions_' . $key] = $conversions;
            $results['conversion_rate_' . $key] = $conversionRate;
            $results['revenue_' . $key] = round($revenue, 2);
        }

        return $results;
    }

    /**
     * Store calculated metrics in database
     *
     * @param int $campaignId Campaign ID
     * @param array $metrics Metrics data
     */
    protected function storeMetrics($campaignId, $metrics)
    {
        try {
            DB::table('wa_campaign_metrics')->updateOrInsert(
                ['campaign_id' => $campaignId],
                [
                    'metrics_data' => json_encode($metrics),
                    'calculated_at' => now(),
                    'updated_at' => now(),
                ]
            );
        } catch (\Exception $e) {
            Log::error('Failed to store campaign metrics', [
                'campaign_id' => $campaignId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
