<?php

namespace App\Http\Controllers\Admin\WhatsApp;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WaCampaign;
use App\Services\WhatsAppApiService;
use App\Services\CustomerSegmentationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    protected $whatsAppApiService;
    protected $segmentationService;

    public function __construct(
        WhatsAppApiService $whatsAppApiService = null,
        CustomerSegmentationService $segmentationService = null
    ) {
        $this->whatsAppApiService = $whatsAppApiService;
        $this->segmentationService = $segmentationService;
    }

    /**
     * Display WhatsApp dashboard
     */
    public function index()
    {
        try {
            // Total customers with valid phone numbers
            $totalCustomers = User::whereNotNull('phone')
                ->where('phone', '!=', '')
                ->count();

            // Campaign counts - check if table exists first
            $totalCampaigns = 0;
            $activeCampaigns = 0;
            $recentCampaigns = collect();
            $todaySent = 0;

            if (DB::getSchemaBuilder()->hasTable('wa_campaigns')) {
                $totalCampaigns = WaCampaign::count();
                $activeCampaigns = WaCampaign::whereIn('status', ['running', 'scheduled'])->count();

                // Recent campaigns (last 10) - load with analytics relationship
                $recentCampaigns = WaCampaign::with('analytics')
                    ->orderBy('started_at', 'DESC')
                    ->take(10)
                    ->get();

                // Today's sent count (using correct column name 'sent')
                $todaySent = WaCampaign::whereDate('started_at', today())
                    ->sum('sent') ?? 0;
            }

            // WhatsApp account status - use safe method
            $accountStatus = $this->getAccountStatusSafe();

            // Top 5 performing segments - check if table exists
            $topSegments = collect();
            if (DB::getSchemaBuilder()->hasTable('wa_customer_segments')) {
                $topSegments = DB::table('wa_customer_segments')
                    ->select('id', 'name', 'customer_count', 'type')
                    ->orderBy('customer_count', 'DESC')
                    ->take(5)
                    ->get()
                    ->map(function($segment) {
                        // Calculate engagement rate if we have analytics data
                        $engagementRate = 0;
                        if (DB::getSchemaBuilder()->hasTable('wa_campaign_analytics')) {
                            $analytics = DB::table('wa_campaign_analytics')
                                ->where('segment_id', $segment->id)
                                ->select(DB::raw('AVG((delivered_count / NULLIF(sent_count, 0)) * 100) as rate'))
                                ->first();
                            $engagementRate = $analytics->rate ?? 0;
                        }

                        return [
                            'name' => $segment->name,
                            'customer_count' => $segment->customer_count ?? 0,
                            'engagement_rate' => round($engagementRate, 1)
                        ];
                    });
            }

            // Prepare stats array for view
            $stats = [
                'total_customers' => $totalCustomers,
                'total_campaigns' => $totalCampaigns,
                'active_campaigns' => $activeCampaigns,
                'today_sent' => $todaySent,
            ];

            // Prepare account array for view
            $account = [
                'phone_number' => $accountStatus['phone_number'] ?? null,
                'quality_rating' => $accountStatus['quality_rating'] ?? 'unknown',
                'account_mode' => $accountStatus['account_mode'] ?? 'SANDBOX',
            ];

            // Prepare chart data - last 30 days
            $chart_data = $this->getChartData();

            // Top segments
            $top_segments = $topSegments;

            return view('admin-views.whatsapp.dashboard', compact(
                'stats',
                'account',
                'chart_data',
                'recent_campaigns',
                'top_segments'
            ));

        } catch (\Exception $e) {
            \Log::error('WhatsApp Dashboard Error: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());

            // Return view with safe defaults
            return view('admin-views.whatsapp.dashboard')->with([
                'stats' => [
                    'total_customers' => 0,
                    'total_campaigns' => 0,
                    'active_campaigns' => 0,
                    'today_sent' => 0
                ],
                'account' => [
                    'phone_number' => null,
                    'quality_rating' => 'unknown',
                    'account_mode' => 'SANDBOX'
                ],
                'chart_data' => [
                    'labels' => [],
                    'sent' => [],
                    'delivered' => [],
                    'read' => []
                ],
                'recent_campaigns' => collect(),
                'top_segments' => collect()
            ]);
        }
    }

    /**
     * Get account status safely without throwing errors
     */
    private function getAccountStatusSafe()
    {
        try {
            if (isset($this->whatsAppApiService)) {
                return $this->whatsAppApiService->getAccountStatus();
            }
        } catch (\Exception $e) {
            \Log::warning('WhatsApp API Service not available: ' . $e->getMessage());
        }

        return [
            'phone_number' => config('whatsapp.phone_number_id'),
            'quality_rating' => 'unknown',
            'account_mode' => 'SANDBOX'
        ];
    }

    /**
     * Get chart data for last 30 days
     */
    private function getChartData()
    {
        $labels = [];
        $sent = [];
        $delivered = [];
        $read = [];

        try {
            if (DB::getSchemaBuilder()->hasTable('wa_campaigns')) {
                // Get last 30 days data
                for ($i = 29; $i >= 0; $i--) {
                    $date = now()->subDays($i);
                    $labels[] = $date->format('M d');

                    // Use correct column names from wa_campaigns table
                    $dayCampaigns = WaCampaign::whereDate('started_at', $date)->get();

                    $sent[] = $dayCampaigns->sum('sent') ?? 0;

                    // Get delivered/read from analytics if available
                    $delivered[] = $dayCampaigns->sum(function($campaign) {
                        return $campaign->analytics->delivered_count ?? 0;
                    });

                    $read[] = $dayCampaigns->sum(function($campaign) {
                        return $campaign->analytics->read_count ?? 0;
                    });
                }
            }
        } catch (\Exception $e) {
            \Log::warning('Error generating chart data: ' . $e->getMessage());
        }

        return [
            'labels' => $labels,
            'sent' => $sent,
            'delivered' => $delivered,
            'read' => $read
        ];
    }
}
