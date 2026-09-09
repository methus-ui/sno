<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BargainingRequest;
use App\Models\BargainingStoreOffer;
use App\Models\StoreBargainingSetting;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BargainingController extends Controller
{
    /**
     * Dashboard - Overview of bargaining system
     */
    public function dashboard(Request $request)
    {
        // Date range filter
        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));

        // Overall statistics
        $stats = [
            'total_requests' => BargainingRequest::whereBetween('created_at', [$startDate, $endDate])->count(),
            'active_requests' => BargainingRequest::whereIn('status', ['initiated', 'matching', 'offers_received'])->count(),
            'completed_requests' => BargainingRequest::where('status', 'accepted')->whereBetween('created_at', [$startDate, $endDate])->count(),
            'total_savings' => BargainingRequest::where('status', 'accepted')->whereBetween('created_at', [$startDate, $endDate])->sum('total_savings'),
            'avg_stores_matched' => BargainingRequest::whereBetween('created_at', [$startDate, $endDate])->avg('total_stores_matched'),
            'avg_offers_received' => BargainingRequest::whereBetween('created_at', [$startDate, $endDate])->avg('total_offers_received'),
        ];

        // Status breakdown
        $statusBreakdown = BargainingRequest::select('status', DB::raw('count(*) as count'))
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status');

        // Mode breakdown
        $modeBreakdown = BargainingRequest::select('mode', DB::raw('count(*) as count'))
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('mode')
            ->get()
            ->pluck('count', 'mode');

        // Recent requests
        $recentRequests = BargainingRequest::with(['awardedStore', 'acceptedOffer'])
            ->latest()
            ->limit(10)
            ->get();

        // Top performing stores
        $topStores = BargainingStoreOffer::select('store_id', DB::raw('count(*) as wins'))
            ->where('status', 'accepted')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('store_id')
            ->orderByDesc('wins')
            ->limit(10)
            ->with('store:id,name,logo')
            ->get();

        // Daily trends (last 30 days)
        $dailyTrends = BargainingRequest::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('count(*) as total'),
                DB::raw('sum(case when status = "accepted" then 1 else 0 end) as completed')
            )
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return view('admin-views.bargaining.dashboard', compact(
            'stats',
            'statusBreakdown',
            'modeBreakdown',
            'recentRequests',
            'topStores',
            'dailyTrends',
            'startDate',
            'endDate'
        ));
    }

    /**
     * List all bargaining requests
     */
    public function requests(Request $request)
    {
        $query = BargainingRequest::with(['awardedStore', 'acceptedOffer']);

        // Filters
        if ($request->has('status') && $request->status != 'all') {
            $query->where('status', $request->status);
        }

        if ($request->has('mode') && $request->mode != 'all') {
            $query->where('mode', $request->mode);
        }

        if ($request->has('search')) {
            $query->where('request_code', 'like', '%' . $request->search . '%');
        }

        // Pagination
        $requests = $query->latest()->paginate(20);

        return view('admin-views.bargaining.requests', compact('requests'));
    }

    /**
     * View single request details
     */
    public function requestDetails($id)
    {
        $request = BargainingRequest::with([
            'cartItems',
            'storeOffers' => function($q) {
                $q->orderBy('rank');
            },
            'storeOffers.store',
            'storeOffers.offerItems',
            'awardedStore',
            'acceptedOffer'
        ])->findOrFail($id);

        return view('admin-views.bargaining.request-details', compact('request'));
    }

    /**
     * Analytics page
     */
    public function analytics(Request $request)
    {
        $period = $request->input('period', '30days');

        $startDate = match($period) {
            '7days' => now()->subDays(7),
            '30days' => now()->subDays(30),
            '90days' => now()->subDays(90),
            'year' => now()->subYear(),
            default => now()->subDays(30),
        };

        // Conversion rates
        $totalRequests = BargainingRequest::where('created_at', '>=', $startDate)->count();
        $completedRequests = BargainingRequest::where('status', 'accepted')
            ->where('created_at', '>=', $startDate)
            ->count();
        $conversionRate = $totalRequests > 0 ? ($completedRequests / $totalRequests) * 100 : 0;

        // Average savings
        $avgSavings = BargainingRequest::where('status', 'accepted')
            ->where('created_at', '>=', $startDate)
            ->avg('total_savings');

        $avgSavingsPercentage = BargainingRequest::where('status', 'accepted')
            ->where('created_at', '>=', $startDate)
            ->whereRaw('original_cart_value > 0')
            ->selectRaw('AVG((total_savings / original_cart_value) * 100) as avg_pct')
            ->first()
            ->avg_pct ?? 0;

        // Store performance
        $storePerformance = BargainingStoreOffer::select(
                'store_id',
                DB::raw('count(*) as total_offers'),
                DB::raw('sum(case when status = "accepted" then 1 else 0 end) as wins'),
                DB::raw('avg(rank) as avg_rank'),
                DB::raw('sum(case when is_best_offer = 1 then 1 else 0 end) as times_best')
            )
            ->where('created_at', '>=', $startDate)
            ->groupBy('store_id')
            ->with('store:id,name,logo')
            ->get()
            ->map(function($item) {
                $item->win_rate = $item->total_offers > 0 ? ($item->wins / $item->total_offers) * 100 : 0;
                return $item;
            })
            ->sortByDesc('wins');

        // Popular items (most frequently in bargaining carts)
        $popularItems = DB::table('bargaining_cart_items')
            ->select('item_name', DB::raw('count(*) as frequency'))
            ->whereExists(function($query) use ($startDate) {
                $query->select(DB::raw(1))
                    ->from('bargaining_requests')
                    ->whereColumn('bargaining_requests.id', 'bargaining_cart_items.bargaining_request_id')
                    ->where('bargaining_requests.created_at', '>=', $startDate);
            })
            ->groupBy('item_name')
            ->orderByDesc('frequency')
            ->limit(20)
            ->get();

        // Fulfillment analysis
        $fulfillmentStats = BargainingStoreOffer::where('created_at', '>=', $startDate)
            ->selectRaw('
                AVG(fulfillment_percentage) as avg_fulfillment,
                COUNT(CASE WHEN fulfillment_percentage = 100 THEN 1 END) as full_fulfillment_count,
                COUNT(CASE WHEN fulfillment_percentage < 100 THEN 1 END) as partial_fulfillment_count
            ')
            ->first();

        // Time-based analysis
        $peakHours = BargainingRequest::select(
                DB::raw('HOUR(created_at) as hour'),
                DB::raw('count(*) as count')
            )
            ->where('created_at', '>=', $startDate)
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();

        return view('admin-views.bargaining.analytics', compact(
            'period',
            'totalRequests',
            'completedRequests',
            'conversionRate',
            'avgSavings',
            'avgSavingsPercentage',
            'storePerformance',
            'popularItems',
            'fulfillmentStats',
            'peakHours'
        ));
    }

    /**
     * Settings page
     */
    public function settings()
    {
        $config = config('bargaining');

        $storeSettings = StoreBargainingSetting::with('store:id,name')
            ->paginate(20);

        return view('admin-views.bargaining.settings', compact('config', 'storeSettings'));
    }

    /**
     * Update global settings
     */
    public function updateSettings(Request $request)
    {
        $request->validate([
            'enabled' => 'required|boolean',
            'instant_mode_enabled' => 'required|boolean',
            'wait_mode_enabled' => 'required|boolean',
            'wait_duration' => 'required|integer|min:30|max:300',
            'max_requests_per_user_per_day' => 'required|integer|min:1|max:100',
            'min_cart_value' => 'required|numeric|min:0',
            'max_cart_items' => 'required|integer|min:1|max:100',
            'fuzzy_similarity_threshold' => 'required|integer|min:50|max:100',
        ]);

        // Update .env file (simplified - in production use env manager)
        $envPath = base_path('.env');

        $envUpdates = [
            'BARGAINING_ENABLED' => $request->enabled ? 'true' : 'false',
            'BARGAINING_INSTANT_MODE' => $request->instant_mode_enabled ? 'true' : 'false',
            'BARGAINING_WAIT_MODE' => $request->wait_mode_enabled ? 'true' : 'false',
            'BARGAINING_WAIT_DURATION' => $request->wait_duration,
            'BARGAINING_MIN_CART_VALUE' => $request->min_cart_value,
        ];

        // TODO: Implement proper .env update mechanism
        // For now, just clear config cache
        \Artisan::call('config:clear');

        return redirect()->route('admin.bargaining.settings')
            ->with('success', 'Settings updated successfully. Please update .env file manually for persistence.');
    }

    /**
     * Update store-specific settings
     */
    public function updateStoreSettings(Request $request, $storeId)
    {
        $request->validate([
            'bargaining_enabled' => 'required|boolean',
            'auto_participate' => 'required|boolean',
            'manual_bidding_enabled' => 'required|boolean',
            'auto_discount_percentage' => 'nullable|numeric|min:0|max:100',
        ]);

        $setting = StoreBargainingSetting::where('store_id', $storeId)->firstOrFail();

        $setting->update([
            'bargaining_enabled' => $request->bargaining_enabled,
            'auto_participate' => $request->auto_participate,
            'manual_bidding_enabled' => $request->manual_bidding_enabled,
            'auto_discount_percentage' => $request->auto_discount_percentage ?? 0,
        ]);

        return response()->json([
            'message' => 'Store settings updated successfully',
        ]);
    }

    /**
     * Export analytics data
     */
    public function export(Request $request)
    {
        $period = $request->input('period', '30days');
        $format = $request->input('format', 'csv');

        $startDate = match($period) {
            '7days' => now()->subDays(7),
            '30days' => now()->subDays(30),
            '90days' => now()->subDays(90),
            default => now()->subDays(30),
        };

        $data = BargainingRequest::with(['awardedStore', 'acceptedOffer'])
            ->where('created_at', '>=', $startDate)
            ->get();

        if ($format === 'csv') {
            return $this->exportCsv($data);
        }

        return response()->json($data);
    }

    /**
     * Export to CSV
     */
    protected function exportCsv($data)
    {
        $filename = 'bargaining_export_' . now()->format('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($data) {
            $file = fopen('php://output', 'w');

            // Header row
            fputcsv($file, [
                'Request Code',
                'Status',
                'Mode',
                'Total Items',
                'Original Value',
                'Final Price',
                'Savings',
                'Stores Matched',
                'Offers Received',
                'Awarded Store',
                'Created At',
            ]);

            // Data rows
            foreach ($data as $request) {
                fputcsv($file, [
                    $request->request_code,
                    $request->status,
                    $request->mode,
                    $request->total_cart_items,
                    $request->original_cart_value,
                    $request->final_price ?? 0,
                    $request->total_savings ?? 0,
                    $request->total_stores_matched,
                    $request->total_offers_received,
                    $request->awardedStore->name ?? 'N/A',
                    $request->created_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Cancel/expire a request (admin action)
     */
    public function cancelRequest($id)
    {
        $request = BargainingRequest::findOrFail($id);

        if (!in_array($request->status, ['initiated', 'matching', 'offers_received'])) {
            return response()->json([
                'error' => 'Cannot cancel request in current status'
            ], 400);
        }

        $request->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        return response()->json([
            'message' => 'Request cancelled successfully'
        ]);
    }
}
