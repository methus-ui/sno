<?php

namespace App\Http\Controllers\Admin\WhatsApp;

use App\Http\Controllers\Controller;
use App\Models\WaCampaign;
use App\Models\WaCustomerSegment;
use App\Services\CampaignBuilderService;
use App\Services\CampaignAnalyticsService;
use App\Services\CustomerSegmentationService;
use App\Jobs\SendWhatsAppCampaignJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CampaignController extends Controller
{
    protected $campaignBuilderService;
    protected $analyticsService;
    protected $segmentationService;

    public function __construct(
        CampaignBuilderService $campaignBuilderService,
        CampaignAnalyticsService $analyticsService,
        CustomerSegmentationService $segmentationService
    ) {
        $this->campaignBuilderService = $campaignBuilderService;
        $this->analyticsService = $analyticsService;
        $this->segmentationService = $segmentationService;
    }

    /**
     * Display campaign list
     */
    public function index(Request $request)
    {
        try {
            $query = WaCampaign::query();

            // Status filter
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            // Date range filter
            if ($request->filled('date_from')) {
                $query->whereDate('started_at', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $query->whereDate('started_at', '<=', $request->date_to);
            }

            // Order by started_at DESC
            $campaigns = $query->orderBy('started_at', 'DESC')
                ->paginate(20);

            return view('admin-views.whatsapp.campaigns.index', compact('campaigns'));

        } catch (\Exception $e) {
            \Log::error('WhatsApp Campaign List Error: ' . $e->getMessage());

            return view('admin-views.whatsapp.campaigns.index')
                ->with('error', 'Failed to load campaigns: ' . $e->getMessage())
                ->with('campaigns', collect()->paginate(20));
        }
    }

    /**
     * Show campaign builder form
     */
    public function create()
    {
        try {
            // Get segment descriptions from service
            $segmentDefinitions = $this->segmentationService->getPredefinedSegments();

            // Get predefined segments from database with descriptions
            $predefined_segments = WaCustomerSegment::where('type', 'predefined')
                ->orderBy('name')
                ->get()
                ->map(function($segment) use ($segmentDefinitions) {
                    $description = 'Customer segment';

                    // Find matching description from service
                    foreach ($segmentDefinitions as $key => $def) {
                        if ($key === $segment->slug) {
                            $description = $def['description'] ?? $description;
                            break;
                        }
                    }

                    return [
                        'key' => $segment->id, // Use ID instead of slug
                        'id' => $segment->id,
                        'name' => $segment->name,
                        'description' => $description,
                        'count' => $segment->customer_count ?? 0
                    ];
                })
                ->toArray();

            // Get custom segments
            $custom_segments = WaCustomerSegment::where('type', 'custom')
                ->orderBy('name')
                ->get();

            return view('admin-views.whatsapp.campaigns.create', compact('predefined_segments', 'custom_segments'));

        } catch (\Exception $e) {
            \Log::error('WhatsApp Campaign Create Form Error: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());

            return redirect()->route('admin.whatsapp.campaigns.index')
                ->with('error', 'Failed to load campaign builder: ' . $e->getMessage());
        }
    }

    /**
     * Create new campaign
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'media_id' => 'required|string',
                'caption' => 'nullable|string|max:1000',
                'segment_ids' => 'required|array|min:1',
                'segment_ids.*' => 'required|integer'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            // Create campaign
            $campaign = $this->campaignBuilderService->createCampaign([
                'name' => $request->name,
                'media_id' => $request->media_id,
                'caption' => $request->caption,
                'segment_ids' => $request->segment_ids,
                'created_by' => auth()->user()->id
            ]);

            // Dispatch job to send campaign
            SendWhatsAppCampaignJob::dispatch($campaign->id);

            return response()->json([
                'success' => true,
                'message' => 'Campaign created and queued for sending',
                'campaign_id' => $campaign->id
            ]);

        } catch (\Exception $e) {
            \Log::error('WhatsApp Campaign Store Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to create campaign: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display campaign analytics
     */
    public function show($id)
    {
        try {
            // Load campaign with analytics
            $campaign = WaCampaign::with('analytics')->findOrFail($id);

            // Generate detailed analytics report
            $analyticsReport = $this->analyticsService->generateReport($id);

            // Prepare Chart.js data for visualization
            $chartData = [
                'delivery_chart' => [
                    'labels' => ['Sent', 'Delivered', 'Read', 'Failed'],
                    'data' => [
                        $campaign->total_recipients,
                        $campaign->delivered_count,
                        $campaign->read_count,
                        $campaign->failed_count
                    ]
                ],
                'timeline_chart' => $analyticsReport['timeline_data'] ?? [],
                'conversion_chart' => $analyticsReport['conversion_data'] ?? []
            ];

            return view('admin-views.whatsapp.campaigns.show', compact(
                'campaign',
                'analyticsReport',
                'chartData'
            ));

        } catch (\Exception $e) {
            \Log::error('WhatsApp Campaign Show Error: ' . $e->getMessage());

            return redirect()->route('admin.whatsapp.campaigns.index')
                ->with('error', 'Campaign not found or error loading analytics.');
        }
    }

    /**
     * Cancel running campaign
     */
    public function cancel($id)
    {
        try {
            $result = $this->campaignBuilderService->cancelCampaign($id);

            if ($result) {
                return response()->json([
                    'success' => true,
                    'message' => 'Campaign cancelled successfully'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Campaign cannot be cancelled (may already be completed or failed)'
            ], 400);

        } catch (\Exception $e) {
            \Log::error('WhatsApp Campaign Cancel Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel campaign: ' . $e->getMessage()
            ], 500);
        }
    }
}
