<?php

namespace App\Http\Controllers\Vendor;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\Order;
use App\Models\Vendor;
use App\Models\OrderTransaction;
use App\Models\Cart;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function dashboard(Request $request)
    {
        $params = [
            'statistics_type' => $request['statistics_type'] ?? 'overall'
        ];
        session()->put('dash_params', $params);

        $data = self::dashboard_order_stats_data();
        $earning = [];
        $commission = [];
        $from = Carbon::now()->startOfYear()->format('Y-m-d');
        $to = Carbon::now()->endOfYear()->format('Y-m-d');
        $store_earnings = OrderTransaction::NotRefunded()->where(['vendor_id' => Helpers::get_vendor_id()])->select(
            DB::raw('IFNULL(sum(store_amount),0) as earning'),
            DB::raw('IFNULL(sum(admin_commission + admin_expense - delivery_fee_comission),0) as commission'),
            DB::raw('YEAR(created_at) year, MONTH(created_at) month')
        )->whereBetween('created_at', [$from, $to])->groupby('year', 'month')->get()->toArray();
        for ($inc = 1; $inc <= 12; $inc++) {
            $earning[$inc] = 0;
            $commission[$inc] = 0;
            foreach ($store_earnings as $match) {
                if ($match['month'] == $inc) {
                    $earning[$inc] = $match['earning'];
                    $commission[$inc] = $match['commission'];
                }
            }
        }

        $top_sell = Item::orderBy("order_count", 'desc')
            ->take(6)
            ->get();
        $most_rated_items = Item::where('avg_rating' ,'>' ,0)
        ->orderBy('avg_rating','desc')
        ->take(6)
        ->get();
        $data['top_sell'] = $top_sell;
        $data['most_rated_items'] = $most_rated_items;

        if( Helpers::get_store_data()?->storeConfig?->minimum_stock_for_warning > 0){
            $items=  Item::where('stock' ,'<=' , Helpers::get_store_data()->storeConfig->minimum_stock_for_warning );
        } else{
            $items=  Item::where('stock',0 );
        }

        $out_of_stock_count=  Helpers::get_store_data()->module->module_type != 'food' ?  $items->orderby('stock')->latest()->count() : null;

            $item = null;
            if($out_of_stock_count == 1 ){
                $item= $items->orderby('stock')->latest()->first();
            }

        // START: CART DATA SECTION
        $store_id = Helpers::get_store_id();
        $latest_carts = collect();
        $users = collect();
        $items = collect();  // Changed from $cart_items to $items
        
        if ($store_id) {
            // Get latest active customer carts (last 24 hours)
            $latest_carts = Cart::with(['item'])
                ->whereHas('item', function($query) use ($store_id) {
                    $query->where('store_id', $store_id);
                })
                ->where('created_at', '>=', now()->subHours(24))
                ->orderBy('created_at', 'desc')
                ->get()
                ->groupBy('user_id')
                ->take(50); // Limit to 50 most recent customers
            
            // Get user data for these carts
            $userIds = $latest_carts->keys();
            $users = User::whereIn('id', $userIds)->get()->keyBy('id');
            
            // Get item data
            $itemIds = $latest_carts->flatten()->pluck('item_id')->unique();
            $items = Item::whereIn('id', $itemIds)->get()->keyBy('id');  // Changed to $items
        }
        // END: CART DATA SECTION

        return view('vendor-views.dashboard', compact(
            'data',
            'earning',
            'commission',
            'params',
            'out_of_stock_count',
            'item',
            'latest_carts',
            'users',
            'items'  // Fixed: just the variable name, no key => value
        ));
    }

    public function store_data()
    {
        $new_pending_order_query = DB::table('orders')->where(['checked' => 0])->where('store_id', Helpers::get_store_id())->where('order_status','pending');
        if(config('order_confirmation_model') != 'store' && !Helpers::get_store_data()->sub_self_delivery)
        {
            $new_pending_order_query = $new_pending_order_query->where('order_type', 'take_away');
        }
        $new_pending_order = $new_pending_order_query->count();
        $new_confirmed_order = DB::table('orders')->where(['checked' => 0])->where('store_id', Helpers::get_store_id())->whereIn('order_status',['confirmed', 'accepted'])->whereNotNull('confirmed')->count();

        // Get recent order details
        $recent_orders = [];
        if ($new_pending_order > 0 || $new_confirmed_order > 0) {
            $orders = \App\Models\Order::with(['details.item', 'details.campaign'])
                ->where('store_id', Helpers::get_store_id())
                ->where('checked', 0)
                ->whereIn('order_status', ['pending', 'confirmed', 'accepted'])
                ->latest()
                ->limit(3)
                ->get();

            foreach ($orders as $order) {
                $items = [];
                foreach ($order->details as $detail) {
                    $items[] = [
                        'name' => $detail->item ? $detail->item->name : ($detail->campaign ? $detail->campaign->title : 'N/A'),
                        'quantity' => $detail->quantity,
                        'price' => $detail->price
                    ];
                }

                $recent_orders[] = [
                    'id' => $order->id,
                    'order_amount' => $order->order_amount,
                    'items' => $items,
                    'item_count' => count($items),
                    'order_status' => $order->order_status
                ];
            }
        }

        return response()->json([
            'success' => 1,
            'data' => [
                'new_pending_order' => $new_pending_order,
                'new_confirmed_order' => $new_confirmed_order,
                'recent_orders' => $recent_orders
            ]
        ]);
    }

    public function order_stats(Request $request)
    {
        $params = session('dash_params');
        foreach ($params as $key => $value) {
            if ($key == 'statistics_type') {
                $params['statistics_type'] = $request['statistics_type'];
            }
        }
        session()->put('dash_params', $params);

        $data = self::dashboard_order_stats_data();
        return response()->json([
            'view' => view('vendor-views.partials._dashboard-order-stats', compact('data'))->render()
        ], 200);
    }

    /**
     * Get cart data for AJAX refresh
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCartData(Request $request)
    {
        try {
            $store_id = Helpers::get_store_id();
            
            if (!$store_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Store not found'
                ]);
            }

            // Get latest carts (last 50 active carts from last 24 hours)
            $latest_carts = Cart::with(['item'])
                ->whereHas('item', function($query) use ($store_id) {
                    $query->where('store_id', $store_id);
                })
                ->where('created_at', '>=', now()->subHours(24))
                ->orderBy('created_at', 'desc')
                ->get()
                ->groupBy('user_id')
                ->take(50);

            // Get user data
            $userIds = $latest_carts->keys();
            $users = User::whereIn('id', $userIds)->get()->keyBy('id');

            // Get item data
            $itemIds = $latest_carts->flatten()->pluck('item_id')->unique();
            $items = Item::whereIn('id', $itemIds)->get()->keyBy('id');

            // Generate HTML view
            $html = view('vendor-views.partials._customer-carts-grid', [
                'latest_carts' => $latest_carts,
                'users' => $users,
                'items' => $items
            ])->render();

            return response()->json([
                'success' => true,
                'html' => $html,
                'cartData' => $latest_carts,
                'usersData' => $users,
                'itemsData' => $items,
                'count' => $latest_carts->count()
            ]);

        } catch (\Exception $e) {
            \Log::error('Cart data error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function dashboard_order_stats_data()
    {
        $params = session('dash_params');
        $today = $params['statistics_type'] == 'today' ? 1 : 0;
        $this_month = $params['statistics_type'] == 'this_month' ? 1 : 0;

        $confirmed = Order::when($today, function ($query) {
            return $query->whereDate('created_at', Carbon::today());
        })->when($this_month, function ($query) {
            return $query->whereMonth('created_at', Carbon::now());
        })->where(['store_id' => Helpers::get_store_id()])->whereIn('order_status',['confirmed', 'accepted'])->whereNotNull('confirmed')->StoreOrder()->NotDigitalOrder()->OrderScheduledIn(30)->count();

        $cooking = Order::when($today, function ($query) {
            return $query->whereDate('created_at', Carbon::today());
        })->when($this_month, function ($query) {
            return $query->whereMonth('created_at', Carbon::now());
        })->where(['order_status' => 'processing', 'store_id' => Helpers::get_store_id()])->StoreOrder()->NotDigitalOrder()->count();

        $ready_for_delivery = Order::when($today, function ($query) {
            return $query->whereDate('created_at', Carbon::today());
        })->when($this_month, function ($query) {
            return $query->whereMonth('created_at', Carbon::now());
        })->where(['order_status' => 'handover', 'store_id' => Helpers::get_store_id()])->StoreOrder()->NotDigitalOrder()->count();

        $item_on_the_way = Order::when($today, function ($query) {
            return $query->whereDate('created_at', Carbon::today());
        })->when($this_month, function ($query) {
            return $query->whereMonth('created_at', Carbon::now());
        })->ItemOnTheWay()->where(['store_id' => Helpers::get_store_id()])->StoreOrder()->NotDigitalOrder()->count();

        $delivered = Order::when($today, function ($query) {
            return $query->whereDate('created_at', Carbon::today());
        })->when($this_month, function ($query) {
            return $query->whereMonth('created_at', Carbon::now());
        })->where(['order_status' => 'delivered', 'store_id' => Helpers::get_store_id()])->StoreOrder()->NotDigitalOrder()->count();

        $refunded = Order::when($today, function ($query) {
            return $query->whereDate('created_at', Carbon::today());
        })->when($this_month, function ($query) {
            return $query->whereMonth('created_at', Carbon::now());
        })->where(['order_status' => 'refunded', 'store_id' => Helpers::get_store_id()])->StoreOrder()->NotDigitalOrder()->count();

        $scheduled = Order::when($today, function ($query) {
            return $query->whereDate('created_at', Carbon::today());
        })->when($this_month, function ($query) {
            return $query->whereMonth('created_at', Carbon::now());
        })->Scheduled()->where(['store_id' => Helpers::get_store_id()])->where(function($q){
            if(config('order_confirmation_model') == 'store')
            {
                $q->whereNotIn('order_status',['failed','canceled', 'refund_requested', 'refunded']);
            }
            else
            {
                $q->whereNotIn('order_status',['pending','failed','canceled', 'refund_requested', 'refunded'])->orWhere(function($query){
                    $query->where('order_status','pending')->where('order_type', 'take_away');
                });
            }

        })->StoreOrder()->NotDigitalOrder()->count();

        $all = Order::when($today, function ($query) {
            return $query->whereDate('created_at', Carbon::today());
        })->when($this_month, function ($query) {
            return $query->whereMonth('created_at', Carbon::now());
        })->where(['store_id' => Helpers::get_store_id()])
        ->where(function($query){
            return $query->whereNotIn('order_status',(config('order_confirmation_model') == 'store'|| \App\CentralLogics\Helpers::get_store_data()->sub_self_delivery)?['failed','canceled', 'refund_requested', 'refunded']:['pending','failed','canceled', 'refund_requested', 'refunded'])
            ->orWhere(function($query){
                return $query->where('order_status','pending')->where('order_type', 'take_away');
            });
        })
        ->StoreOrder()->NotDigitalOrder()->count();

        $data = [
            'confirmed' => $confirmed,
            'cooking' => $cooking,
            'ready_for_delivery' => $ready_for_delivery,
            'item_on_the_way' => $item_on_the_way,
            'delivered' => $delivered,
            'refunded' => $refunded,
            'scheduled' => $scheduled,
            'all' => $all,
        ];

        return $data;
    }

    public function updateDeviceToken(Request $request)
    {
        $vendor = Vendor::find(Helpers::get_vendor_id());
        $vendor->firebase_token =  $request->token;

        $vendor->save();

        return response()->json(['Token successfully stored.']);
    }
}
