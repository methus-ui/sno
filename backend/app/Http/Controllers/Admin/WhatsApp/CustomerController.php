<?php

namespace App\Http\Controllers\Admin\WhatsApp;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WaCampaignRecipient;
use App\Models\WaCustomerJourney;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    /**
     * Display customer list with filters
     */
    public function index(Request $request)
    {
        try {
            $query = User::query()
                ->whereNotNull('phone')
                ->where('phone', '!=', '');

            // Search filter
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('f_name', 'LIKE', "%{$search}%")
                      ->orWhere('l_name', 'LIKE', "%{$search}%")
                      ->orWhere('phone', 'LIKE', "%{$search}%")
                      ->orWhere('email', 'LIKE', "%{$search}%");
                });
            }

            // Segment filter
            if ($request->filled('segment_id')) {
                // This would require a join or subquery based on segment definition
                // Placeholder for now - implement based on segment structure
            }

            // Zone filter
            if ($request->filled('zone_id')) {
                $query->where('zone_id', $request->zone_id);
            }

            // Status filter
            if ($request->filled('status')) {
                $isActive = $request->status === 'active' ? 1 : 0;
                $query->where('is_active', $isActive);
            }

            // Order by
            $orderBy = $request->get('order_by', 'created_at');
            $orderDirection = $request->get('order_direction', 'DESC');

            if ($orderBy === 'last_order_at') {
                $query->leftJoin('orders', 'users.id', '=', 'orders.user_id')
                    ->select('users.*', DB::raw('MAX(orders.created_at) as last_order_at'))
                    ->groupBy('users.id')
                    ->orderBy('last_order_at', $orderDirection);
            } else {
                $query->orderBy($orderBy, $orderDirection);
            }

            // Eager load order stats
            $customers = $query->withCount('orders')
                ->withSum('orders as total_spent', 'order_amount')
                ->with(['orders' => function($q) {
                    $q->select('user_id', DB::raw('MAX(created_at) as last_order_date'))
                      ->groupBy('user_id');
                }])
                ->paginate(50);

            return view('admin-views.whatsapp.customers.index', compact('customers'));

        } catch (\Exception $e) {
            \Log::error('WhatsApp Customer List Error: ' . $e->getMessage());

            return view('admin-views.whatsapp.customers.index')
                ->with('error', 'Failed to load customers: ' . $e->getMessage())
                ->with('customers', collect()->paginate(50));
        }
    }

    /**
     * Display customer detail
     */
    public function show($id)
    {
        try {
            // Load customer with stats
            $customer = User::with(['orders' => function($q) {
                $q->latest()->take(20);
            }])
            ->withCount('orders')
            ->withSum('orders as total_spent', 'order_amount')
            ->findOrFail($id);

            // Get campaigns received
            $campaignsReceived = WaCampaignRecipient::where('user_id', $id)
                ->with('campaign')
                ->orderBy('created_at', 'DESC')
                ->get();

            // Get customer journey timeline
            $customerJourney = WaCustomerJourney::where('user_id', $id)
                ->orderBy('event_timestamp', 'DESC')
                ->get();

            // Additional stats
            $lastOrderDate = $customer->orders->first()?->created_at;
            $avgOrderValue = $customer->orders_count > 0
                ? $customer->total_spent / $customer->orders_count
                : 0;

            return view('admin-views.whatsapp.customers.show', compact(
                'customer',
                'campaignsReceived',
                'customerJourney',
                'lastOrderDate',
                'avgOrderValue'
            ));

        } catch (\Exception $e) {
            \Log::error('WhatsApp Customer Detail Error: ' . $e->getMessage());

            return redirect()->route('admin.whatsapp.customers.index')
                ->with('error', 'Customer not found or error loading data.');
        }
    }
}
