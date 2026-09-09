<?php

namespace App\Http\Controllers\Admin\WhatsApp;

use App\Http\Controllers\Controller;
use App\Models\WaCustomerSegment;
use App\Services\CustomerSegmentationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SegmentController extends Controller
{
    protected $segmentationService;

    public function __construct(CustomerSegmentationService $segmentationService)
    {
        $this->segmentationService = $segmentationService;
    }

    /**
     * Display segment manager
     */
    public function index()
    {
        try {
            // Get predefined segments
            $predefinedSegments = $this->segmentationService->getPredefinedSegments();

            // Get custom segments
            $customSegments = WaCustomerSegment::where('type', 'custom')
                ->orderBy('created_at', 'DESC')
                ->get();

            return view('admin-views.whatsapp.segments.index', compact(
                'predefinedSegments',
                'customSegments'
            ));

        } catch (\Exception $e) {
            \Log::error('WhatsApp Segment Manager Error: ' . $e->getMessage());

            return view('admin-views.whatsapp.segments.index')
                ->with('error', 'Failed to load segments: ' . $e->getMessage())
                ->with('predefinedSegments', collect())
                ->with('customSegments', collect());
        }
    }

    /**
     * Create custom segment
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:100',
                'filters' => 'required|array'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $segment = $this->segmentationService->createCustomSegment(
                $request->name,
                $request->filters,
                auth()->user()->id
            );

            return response()->json([
                'success' => true,
                'message' => 'Custom segment created successfully',
                'segment' => $segment
            ]);

        } catch (\Exception $e) {
            \Log::error('WhatsApp Create Segment Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to create segment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Preview segment customers
     */
    public function preview($id)
    {
        try {
            $customers = $this->segmentationService->getSegmentCustomers($id, 100);

            return response()->json([
                'success' => true,
                'customers' => $customers,
                'total' => $customers->count()
            ]);

        } catch (\Exception $e) {
            \Log::error('WhatsApp Preview Segment Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to preview segment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Recalculate segment
     */
    public function refresh($id)
    {
        try {
            $updatedCount = $this->segmentationService->refreshSegmentCache($id);

            return response()->json([
                'success' => true,
                'message' => 'Segment refreshed successfully',
                'customer_count' => $updatedCount
            ]);

        } catch (\Exception $e) {
            \Log::error('WhatsApp Refresh Segment Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to refresh segment: ' . $e->getMessage()
            ], 500);
        }
    }
}
