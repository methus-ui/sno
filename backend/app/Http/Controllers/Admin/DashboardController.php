<?php

namespace App\Http\Controllers\Admin;

use Carbon\Carbon;
use App\Models\Item;
use App\Models\User;
use App\Models\Admin;
use App\Models\Order;
use App\Models\Store;
use App\Models\Module;
use App\Models\Review;
use App\Models\Wishlist;
use App\Models\AdminRole;
use App\Scopes\ZoneScope;
use App\Models\DeliveryMan;
use App\Models\DeliveryTrackingStat;
use Illuminate\Http\Request;
use App\CentralLogics\Helpers;
use App\Models\OrderTransaction;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Config;
use App\Models\Attendance;
use App\Models\ShiftRoster;
use App\Models\EmployeeBreak;

class DashboardController extends Controller
{

    public function __construct()
    {
        // Clear OPcache once (temporary fix)
        if (function_exists('opcache_reset') && !session()->has('opcache_cleared_v2')) {
            opcache_reset();
            session()->put('opcache_cleared_v2', true);
        }
        DB::statement("SET sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''));");
    }
    public function user_dashboard(Request $request)
    {
        $params = [
            'zone_id' => $request['zone_id'] ?? 'all',
            'module_id' => Config::get('module.current_module_id'),
            'statistics_type' => $request['statistics_type'] ?? 'overall',
            'user_overview' => $request['user_overview'] ?? 'overall',
            'commission_overview' => $request['commission_overview'] ?? 'this_year',
            'business_overview' => $request['business_overview'] ?? 'overall',
        ];

        session()->put('dash_params', $params);
        $data = self::dashboard_data($request);
        $total_sell = $data['total_sell'];
        $commission = $data['commission'];
        $delivery_commission = $data['delivery_commission'];
        $customers = User::zone($params['zone_id'])->take(2)->get();

        $delivery_man = DeliveryMan::with('last_location')->when(is_numeric($params['zone_id']), function ($q) use ($params) {
            return $q->where('zone_id', $params['zone_id']);
        })
        ->Zonewise()
        ->limit(2)->get('image');

        $active_deliveryman = DeliveryMan::when(is_numeric($params['zone_id']), function ($q) use ($params) {
            return $q->where('zone_id', $params['zone_id']);
        })
        ->Zonewise()->Active()->count();

        $inactive_deliveryman = DeliveryMan::when(is_numeric($params['zone_id']), function ($q) use ($params) {
            return $q->where('zone_id', $params['zone_id']);
        })
        ->Zonewise()->where('application_status','approved')->where('active',0)->count();

        $blocked_deliveryman = DeliveryMan::when(is_numeric($params['zone_id']), function ($q) use ($params) {
            return $q->where('zone_id', $params['zone_id']);
        })
        ->Zonewise()->where('application_status','approved')->where('status',0)->count();

        $newly_joined_deliveryman = DeliveryMan::when(is_numeric($params['zone_id']), function ($q) use ($params) {
            return $q->where('zone_id', $params['zone_id']);
        })
        ->Zonewise()->whereDate('created_at', '>=', now()->subDays(30)->format('Y-m-d'))->count();

        $reviews = Review::when(is_numeric($params['zone_id']), function ($q) use ($params) {
            return $q->whereHas('item.store', function ($query) use ($params){
                return $query->where('zone_id', $params['zone_id']);
            });
        })->count();

        $positive_reviews = Review::when(is_numeric($params['zone_id']), function ($q) use ($params) {
            return $q->whereHas('item.store', function ($query) use ($params){
                return $query->where('zone_id', $params['zone_id']);
            });
        })->whereIn('rating', [4,5])->get()->count();
        $good_reviews = Review::when(is_numeric($params['zone_id']), function ($q) use ($params) {
            return $q->whereHas('item.store', function ($query) use ($params){
                return $query->where('zone_id', $params['zone_id']);
            });
        })->where('rating', 3)->count();
        $neutral_reviews = Review::when(is_numeric($params['zone_id']), function ($q) use ($params) {
            return $q->whereHas('item.store', function ($query) use ($params){
                return $query->where('zone_id', $params['zone_id']);
            });
        })->where('rating', 2)->count();
        $negative_reviews = Review::when(is_numeric($params['zone_id']), function ($q) use ($params) {
            return $q->whereHas('item.store', function ($query) use ($params){
                return $query->where('zone_id', $params['zone_id']);
            });
        })->where('rating', 1)->count();

        $from = now()->startOfMonth(); // first date of the current month
        $to = now();
        $this_month = User::zone($params['zone_id'])->whereMonth('created_at', now()->format('m'))->whereYear('created_at', now()->format('Y'))->count();
        $number = 12;
        $from = Carbon::now()->startOfYear()->format('Y-m-d');
        $to = Carbon::now()->endOfYear()->format('Y-m-d');

        $last_year_users = User::zone($params['zone_id'])
            ->whereMonth('created_at', 12)
            ->whereYear('created_at', now()->format('Y')-1)
            ->count();

        $users = User::zone($params['zone_id'])
            ->select(
                DB::raw('(count(id)) as total'),
                DB::raw('YEAR(created_at) year, MONTH(created_at) month')
            )
            ->whereBetween('created_at', [Carbon::parse(now())->startOfYear(), Carbon::parse(now())->endOfYear()])
            ->groupBy('year', 'month')->get()->toArray();

        for ($inc = 1; $inc <= $number; $inc++) {
            $user_data[$inc] = 0;
            foreach ($users as $match) {
                if ($match['month'] == $inc) {
                    $user_data[$inc] = $match['total'];
                }
            }
        }

        $active_customers = User::zone($params['zone_id'])->where('status',1)->count();
        $blocked_customers = User::zone($params['zone_id'])->where('status',0)->count();
        $newly_joined = User::zone($params['zone_id'])->whereDate('created_at', '>=', now()->subDays(30)->format('Y-m-d'))->count();

        $employees = Admin::zone()->with(['role'])->where('role_id', '!=','1')
        ->when(is_numeric($params['zone_id']), function ($q) use ($params) {
            return $q->where('zone_id', $params['zone_id']);
        })
        ->get();

        $deliveryMen = DeliveryMan::with('last_location')->when(is_numeric($params['zone_id']), function ($q) use ($params) {
            return $q->where('zone_id', $params['zone_id']);
        })->zonewise()->available()->active()->get();

        $deliveryMen = Helpers::deliverymen_list_formatting($deliveryMen);

        // Employee Performance data
        $period = $request->input('performance_period', 'today');
        $employee_performance = $this->employee_performance_data($period, $params['zone_id']);

        $module_type = Config::get('module.current_module_type');
        return view("admin-views.dashboard-{$module_type}", compact('data','reviews','this_month','user_data','neutral_reviews','good_reviews','negative_reviews','positive_reviews','employees','active_deliveryman','deliveryMen','inactive_deliveryman','newly_joined_deliveryman','delivery_man', 'total_sell', 'commission', 'delivery_commission', 'params','module_type', 'customers','active_customers','blocked_customers', 'newly_joined','last_year_users', 'blocked_deliveryman','employee_performance'));
    }

    public function transaction_dashboard(Request $request)
    {
        $module_type = Config::get('module.current_module_type');
        return view("admin-views.dashboard-{$module_type}");
    }

    public function dispatch_dashboard(Request $request)
    {
        $params = [
            'zone_id' => $request['zone_id'] ?? 'all',
            'module_id' => Config::get('module.current_module_id'),
            'statistics_type' => $request['statistics_type'] ?? 'overall',
            'user_overview' => $request['user_overview'] ?? 'overall',
            'commission_overview' => $request['commission_overview'] ?? 'this_year',
            'business_overview' => $request['business_overview'] ?? 'overall',
        ];

        session()->put('dash_params', $params);

        // PERFORMANCE OPTIMIZATION: Cache dashboard data for 3 minutes
        $cacheKey = 'dispatch_dashboard_' . md5(json_encode($params));
        $cachedData = Cache::remember($cacheKey, 180, function () use ($request, $params) {
            $data = self::dashboard_data($request);

            // Optimize: Get all delivery man stats in a single query
            $dm_stats_query = DeliveryMan::selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN active = 1 THEN 1 ELSE 0 END) as active_count,
                SUM(CASE WHEN application_status = "approved" AND active = 0 THEN 1 ELSE 0 END) as inactive_count,
                SUM(CASE WHEN application_status = "approved" AND status = 0 THEN 1 ELSE 0 END) as suspended_count,
                SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as newly_joined_count
            ')
            ->when(is_numeric($params['zone_id']), function ($q) use ($params) {
                return $q->where('zone_id', $params['zone_id']);
            })
            ->Zonewise()
            ->first();

            // Get available/unavailable counts (these need scopes, so keep separate but optimized)
            $availability_stats = DeliveryMan::selectRaw('
                SUM(CASE WHEN current_orders < 1 THEN 1 ELSE 0 END) as available_count,
                SUM(CASE WHEN current_orders >= 1 THEN 1 ELSE 0 END) as unavailable_count
            ')
            ->when(is_numeric($params['zone_id']), function ($q) use ($params) {
                return $q->where('zone_id', $params['zone_id']);
            })
            ->Zonewise()
            ->where('active', 1)
            ->first();

            return [
                'data' => $data,
                'total_sell' => $data['total_sell'],
                'commission' => $data['commission'],
                'delivery_commission' => $data['delivery_commission'],
                'label' => $data['label'],
                'active_deliveryman' => $dm_stats_query->active_count ?? 0,
                'inactive_deliveryman' => $dm_stats_query->inactive_count ?? 0,
                'suspend_deliveryman' => $dm_stats_query->suspended_count ?? 0,
                'newly_joined_deliveryman' => $dm_stats_query->newly_joined_count ?? 0,
                'available_deliveryman' => $availability_stats->available_count ?? 0,
                'unavailable_deliveryman' => $availability_stats->unavailable_count ?? 0,
            ];
        });

        // Extract cached data
        $data = $cachedData['data'];
        $total_sell = $cachedData['total_sell'];
        $commission = $cachedData['commission'];
        $delivery_commission = $cachedData['delivery_commission'];
        $label = $cachedData['label'];
        $active_deliveryman = $cachedData['active_deliveryman'];
        $inactive_deliveryman = $cachedData['inactive_deliveryman'];
        $suspend_deliveryman = $cachedData['suspend_deliveryman'];
        $newly_joined_deliveryman = $cachedData['newly_joined_deliveryman'];
        $available_deliveryman = $cachedData['available_deliveryman'];
        $unavailable_deliveryman = $cachedData['unavailable_deliveryman'];

        // Keep these fresh (not cached) for real-time tracking
        $customers = User::zone($params['zone_id'])->take(2)->get();
        $delivery_man = DeliveryMan::with('last_location')
            ->when(is_numeric($params['zone_id']), function ($q) use ($params) {
                return $q->where('zone_id', $params['zone_id']);
            })
            ->Zonewise()
            ->limit(2)
            ->get(['image', 'id']);

        $deliveryMen = DeliveryMan::when(is_numeric($params['zone_id']), function ($q) use ($params) {
            return $q->where('zone_id', $params['zone_id']);
        })->zonewise()->available()->active()->get();
        $deliveryMen = Helpers::deliverymen_list_formatting($deliveryMen);

        $module_type = Config::get('module.current_module_type');
        return view("admin-views.dashboard-{$module_type}", compact('data','active_deliveryman','deliveryMen','unavailable_deliveryman','available_deliveryman','inactive_deliveryman','newly_joined_deliveryman','delivery_man', 'total_sell', 'commission', 'delivery_commission','label', 'params','module_type','suspend_deliveryman'));
    }

    public function dashboard(Request $request)
    {
        $params = [
            'zone_id' => $request['zone_id'] ?? 'all',
            'module_id' => Config::get('module.current_module_id'),
            'statistics_type' => $request['statistics_type'] ?? 'overall',
            'user_overview' => $request['user_overview'] ?? 'overall',
            'commission_overview' => $request['commission_overview'] ?? 'this_year',
            'business_overview' => $request['business_overview'] ?? 'overall',
        ];
        session()->put('dash_params', $params);
        $data = self::dashboard_data($request);
        $total_sell = $data['total_sell'];
        $commission = $data['commission'];
        $delivery_commission = $data['delivery_commission'];
        $label = $data['label'];
        $module_type = Config::get('module.current_module_type');
        if($module_type == 'settings'){
            return redirect()->route('admin.business-settings.business-setup');
        }
        if($module_type == 'rental' && addon_published_status('Rental') == 1){
            return redirect()->route('admin.rental.dashboard');
        }
        if($module_type == 'rental' && addon_published_status('Rental') == 0){
            return view('errors.404');
        }
        
        // ============ START: Cart Feature (Working Version) ============
        try {
            // Get latest 15 carts - NO relationships, just raw data
            $latest_carts_raw = \DB::table('carts')
                ->whereNotNull('user_id')
                ->where('is_guest', 0)
                ->orderBy('created_at', 'DESC')
                ->limit(50) // Get 50 to ensure we have enough after filtering
                ->get();
            
            // Get unique user IDs
            $userIds = $latest_carts_raw->pluck('user_id')->unique()->take(15);
            
            // Load users
            $users = \App\Models\User::whereIn('id', $userIds)->get()->keyBy('id');
            
            // Get item IDs
            $itemIds = $latest_carts_raw->whereIn('user_id', $userIds)->pluck('item_id')->unique();
            
            // Load items
            $items = \App\Models\Item::whereIn('id', $itemIds)->get()->keyBy('id');
            
            // Group carts by user
            $latest_carts = $latest_carts_raw
                ->whereIn('user_id', $userIds)
                ->groupBy('user_id')
                ->take(15);
            
        } catch (\Exception $e) {
            \Log::error('Cart query error: ' . $e->getMessage());
            $latest_carts = collect();
            $users = collect();
            $items = collect();
        }
        // ============ END: Cart Feature ============




        
        
        // Employee dashboard data
        $empData = [];
        $admin = auth('admin')->user();
        if ($admin->role_id != 1) {
            $today = Carbon::today()->format('Y-m-d');
            $now = Carbon::now();
            $weekStart = $now->copy()->startOfWeek(Carbon::MONDAY)->format('Y-m-d');
            $monthStart = $now->copy()->startOfMonth()->format('Y-m-d');
            $monthEnd = $now->copy()->endOfMonth()->format('Y-m-d');

            // Today's attendance
            $empData['today_attendance'] = Attendance::where('admin_id', $admin->id)
                ->where('attendance_date', $today)->with('breaks')->first();

            // Active break
            $empData['active_break'] = null;
            if ($empData['today_attendance'] && !$empData['today_attendance']->punch_out) {
                $empData['active_break'] = EmployeeBreak::where('attendance_id', $empData['today_attendance']->id)
                    ->active()->first();
            }

            // This week's shift roster
            $empData['week_roster'] = ShiftRoster::where('admin_id', $admin->id)
                ->forWeek($weekStart)->with('template', 'rosterRole')->get()->keyBy('day_of_week');

            // Today's shift
            $todayDow = $now->dayOfWeekIso - 1; // 0=Mon, 6=Sun
            $empData['today_shift'] = $empData['week_roster'][$todayDow] ?? null;

            // Monthly attendance stats
            $monthlyAttendances = Attendance::where('admin_id', $admin->id)
                ->whereBetween('attendance_date', [$monthStart, $monthEnd])->get();
            $empData['month_present'] = $monthlyAttendances->where('status', 'present')->count();
            $empData['month_completed'] = $monthlyAttendances->where('shift_completed', true)->count();
            $empData['month_early'] = $monthlyAttendances->where('early_departure', true)->count();
            $empData['month_total_hours'] = $monthlyAttendances->sum('actual_work_hours');
            $empData['month_total_break'] = $monthlyAttendances->sum('total_break_minutes');
            $empData['month_extra_break'] = $monthlyAttendances->sum('extra_break_minutes');

            // Weekly attendance
            $weekEnd = $now->copy()->endOfWeek(Carbon::SUNDAY)->format('Y-m-d');
            $weekAttendances = Attendance::where('admin_id', $admin->id)
                ->whereBetween('attendance_date', [$weekStart, $weekEnd])->get();
            $empData['week_present'] = $weekAttendances->where('status', 'present')->count();
            $empData['week_hours'] = $weekAttendances->sum('actual_work_hours');

            // Recent attendance (last 7 records)
            $empData['recent_attendance'] = Attendance::where('admin_id', $admin->id)
                ->orderBy('attendance_date', 'desc')->limit(7)->get();

            // Can punch in/out
            $empData['can_punch_in'] = !$empData['today_attendance'] || ($empData['today_attendance'] && $empData['today_attendance']->punch_out);
            $empData['can_punch_out'] = $empData['today_attendance'] && $empData['today_attendance']->punch_in && !$empData['today_attendance']->punch_out;
        }

        // Traffic data from access logs
        $trafficToday = \App\Models\TrafficLog::whereDate('date', today())->first();
        $trafficYesterday = \App\Models\TrafficLog::whereDate('date', today()->subDay())->first();
        $trafficWeek = \App\Models\TrafficLog::where('date', '>=', today()->subDays(7))
            ->selectRaw('SUM(unique_visitors) as visitors, SUM(app_opens) as opens, SUM(total_requests) as requests, AVG(unique_visitors) as avg_visitors')
            ->first();
        $trafficData = [
            'today_visitors' => $trafficToday->unique_visitors ?? 0,
            'today_app_opens' => $trafficToday->app_opens ?? 0,
            'today_requests' => $trafficToday->total_requests ?? 0,
            'today_peak_hour' => $trafficToday->peak_hour ?? '-',
            'yesterday_visitors' => $trafficYesterday->unique_visitors ?? 0,
            'yesterday_app_opens' => $trafficYesterday->app_opens ?? 0,
            'week_visitors' => (int)($trafficWeek->visitors ?? 0),
            'week_opens' => (int)($trafficWeek->opens ?? 0),
            'week_avg_visitors' => round($trafficWeek->avg_visitors ?? 0),
            'hourly_distribution' => $trafficToday->hourly_distribution ?? [],
        ];

        return view('admin-views.dashboard-'.$module_type, compact('data', 'total_sell', 'commission', 'delivery_commission', 'label','params','module_type', 'latest_carts', 'empData', 'trafficData'));

    }

    public function delivery_stats(Request $request)
    {
        $params = [
            'zone_id' => $request['zone_id'] ?? 'all',
            'module_id' => Config::get('module.current_module_id'),
        ];

        // Cache key with zone and module for better cache segmentation
        $cacheKey = 'delivery_stats_' . $params['zone_id'] . '_' . $params['module_id'];

        // Cache for 30 seconds to reduce database load
        $data = Cache::remember($cacheKey, 30, function () use ($params, $request) {

            // Get dashboard data (stores, total orders, etc.)
            $dashData = self::dashboard_data($request);

            // Delivery personnel counts
            $data['total_stores'] = $dashData['total_stores'] ?? 0;
            $data['total_orders'] = $dashData['total_orders'] ?? 0;

            $data['delivery_personnel_count'] = DeliveryMan::when(is_numeric($params['zone_id']), function ($q) use ($params) {
                return $q->where('zone_id', $params['zone_id']);
            })->Zonewise()->count();

            $data['active_delivery_personnel'] = DeliveryMan::when(is_numeric($params['zone_id']), function ($q) use ($params) {
                return $q->where('zone_id', $params['zone_id']);
            })->Zonewise()->Active()->count();

            // OPTIMIZED: Single aggregated query instead of 10 separate queries
            $aggregated = Order::when(is_numeric($params['zone_id']), function ($q) use ($params) {
                    return $q->where('zone_id', $params['zone_id']);
                })
                ->selectRaw("
                    SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as today_orders,
                    SUM(CASE WHEN order_status IN ('processing', 'preparing') THEN 1 ELSE 0 END) as processing,
                    SUM(CASE WHEN order_status = 'picked_up' THEN 1 ELSE 0 END) as out_for_delivery,
                    SUM(CASE WHEN order_status = 'delivered' AND DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as delivered_today,
                    SUM(CASE WHEN order_status = 'canceled' AND DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as cancelled_today,
                    SUM(CASE WHEN order_status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
                    SUM(CASE WHEN order_status IN ('confirmed', 'handover') THEN 1 ELSE 0 END) as ready_for_pickup,
                    SUM(CASE WHEN order_status = 'failed' AND DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as failed_today,
                    SUM(CASE WHEN DATE(schedule_at) = CURDATE() AND schedule_at > NOW() THEN 1 ELSE 0 END) as scheduled_orders,
                    SUM(CASE WHEN order_status = 'refund_requested' AND DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as returned_today,
                    AVG(CASE WHEN DATE(created_at) = CURDATE() THEN order_amount ELSE NULL END) as avg_order_value,
                    AVG(CASE WHEN order_status = 'delivered' AND delivered IS NOT NULL AND picked_up IS NOT NULL
                        THEN TIMESTAMPDIFF(MINUTE, picked_up, delivered) ELSE NULL END) as avg_delivery_time,
                    AVG(CASE WHEN order_status IN ('delivered', 'picked_up') AND picked_up IS NOT NULL AND created_at IS NOT NULL
                        THEN TIMESTAMPDIFF(MINUTE, created_at, picked_up) ELSE NULL END) as avg_processing_time
                ")
                ->first();

            // Map aggregated results to data array
            $data['today_orders'] = $aggregated->today_orders ?? 0;
            $data['processing'] = $aggregated->processing ?? 0;
            $data['out_for_delivery'] = $aggregated->out_for_delivery ?? 0;
            $data['delivered_today'] = $aggregated->delivered_today ?? 0;
            $data['cancelled_today'] = $aggregated->cancelled_today ?? 0;
            $data['pending_orders'] = $aggregated->pending_orders ?? 0;
            $data['ready_for_pickup'] = $aggregated->ready_for_pickup ?? 0;
            $data['failed_today'] = $aggregated->failed_today ?? 0;
            $data['scheduled_orders'] = $aggregated->scheduled_orders ?? 0;
            $data['returned_today'] = $aggregated->returned_today ?? 0;
            $data['avg_order_value'] = round($aggregated->avg_order_value ?? 0, 2);
            $data['avg_delivery_time'] = round($aggregated->avg_delivery_time ?? 0, 1);
            $data['avg_processing_time'] = round($aggregated->avg_processing_time ?? 0, 1);

            // Peak hour calculation (separate query, but small and fast)
            $peakHour = Order::when(is_numeric($params['zone_id']), function ($q) use ($params) {
                    return $q->where('zone_id', $params['zone_id']);
                })
                ->whereDate('created_at', today())
                ->selectRaw('HOUR(created_at) as hour, COUNT(*) as count')
                ->groupBy('hour')
                ->orderByDesc('count')
                ->first();

            $data['peak_hour'] = $peakHour->hour ?? 0;
            $data['peak_hour_orders'] = $peakHour->count ?? 0;

            return $data;
        });

        // Get all zones for the dropdown
        $zones = \App\Models\Zone::orderBy('name')->get();

        // Generate chart data (also cached within service methods)
        $chartFilters = [
            'zone_id' => $params['zone_id'],
            'module_id' => $params['module_id'],
            'date_range' => $request->get('date_range', 'today'),
        ];

        $chartData = Cache::remember($cacheKey . '_charts', 30, function () use ($chartFilters) {
            $service = new \App\Services\DeliveryStatsService();
            return [
                'hourly' => $service->getHourlyDistribution($chartFilters),
                'status' => $service->getOrderStatusBreakdown($chartFilters),
                'trend' => $service->get90DayTrend($chartFilters),
                'deliveryTime' => $service->getDeliveryTimeDistribution($chartFilters),
            ];
        });

        // Return V2 view with chart data
        return view('admin-views.delivery-stats-v2', compact('data', 'zones', 'chartData'));
    }

    public function order(Request $request)
    {
        $params = session('dash_params');
        foreach ($params as $key => $value) {
            if ($key == 'statistics_type') {
                $params['statistics_type'] = $request['statistics_type'];
            }
        }
        if ($request['custom_date']) {
            $params['custom_date'] = $request['custom_date'];
        }
        session()->put('dash_params', $params);

        if ($params['zone_id'] != 'all') {
            $store_ids = Store::where(['module_id' => $params['module_id']])->where(['zone_id' => $params['zone_id']])->pluck('id')->toArray();
        } else {
            $store_ids = Store::where(['module_id' => $params['module_id']])->pluck('id')->toArray();
        }
        $data = self::order_stats_calc($params['zone_id'], $params['module_id']);
        $module_type = Config::get('module.current_module_type');
        if ($module_type == 'parcel') {
            return response()->json([
                'view' => view('admin-views.partials._dashboard-order-stats-parcel', compact('data'))->render()
            ], 200);
        }elseif($module_type == 'food'){
            return response()->json([
                'view' => view('admin-views.partials._dashboard-order-stats-food', compact('data'))->render()
            ], 200);
        }
        return response()->json([
            'view' => view('admin-views.partials._dashboard-order-stats', compact('data'))->render()
        ], 200);
    }

    public function zone(Request $request)
    {
        $params = session('dash_params');
        foreach ($params as $key => $value) {
            if ($key == 'zone_id') {
                $params['zone_id'] = $request['zone_id'];
            }
        }
        session()->put('dash_params', $params);

        $data = self::dashboard_data($request);
        $total_sell = $data['total_sell'];
        $commission = $data['commission'];
        $popular = $data['popular'];
        $top_deliveryman = $data['top_deliveryman'];
        $top_rated_foods = $data['top_rated_foods'];
        $top_restaurants = $data['top_restaurants'];
        $top_customers = $data['top_customers'];
        $top_sell = $data['top_sell'];
        $delivery_commission = $data['delivery_commission'];
        $module_type = Config::get('module.current_module_type');

        return response()->json([
            'popular_restaurants' => view('admin-views.partials._popular-restaurants', compact('popular'))->render(),
            'top_deliveryman' => view('admin-views.partials._top-deliveryman', compact('top_deliveryman'))->render(),
            'top_rated_foods' => view('admin-views.partials._top-rated-foods', compact('top_rated_foods'))->render(),
            'top_restaurants' => view('admin-views.partials._top-restaurants', compact('top_restaurants'))->render(),
            'top_customers' => view('admin-views.partials._top-customer', compact('top_customers'))->render(),
            'top_selling_foods' => view('admin-views.partials._top-selling-foods', compact('top_sell'))->render(),

            'order_stats' =>$module_type == 'parcel'? view('admin-views.partials._dashboard-order-stats-parcel', compact('data'))->render():

            ($module_type == 'food'? view('admin-views.partials._dashboard-order-stats-food', compact('data'))->render():
            view('admin-views.partials._dashboard-order-stats', compact('data'))->render()),


            'user_overview' => view('admin-views.partials._user-overview-chart', compact('data'))->render(),
            'monthly_graph' => view('admin-views.partials._monthly-earning-graph', compact('total_sell', 'commission', 'delivery_commission'))->render(),
            'stat_zone' => view('admin-views.partials._zone-change', compact('data'))->render(),
        ], 200);
    }

    public function user_overview(Request $request)
    {
        $params = session('dash_params');
        foreach ($params as $key => $value) {
            if ($key == 'user_overview') {
                $params['user_overview'] = $request['user_overview'];
            }
        }
        session()->put('dash_params', $params);

        $data = self::user_overview_calc($params['zone_id'], $params['module_id']);
        $module_type = Config::get('module.current_module_type');
        if ($module_type == 'parcel') {
            return response()->json([
                'view' => view('admin-views.partials._user-overview-chart-parcel', compact('data'))->render()
            ], 200);
        }

        return response()->json([
            'view' => view('admin-views.partials._user-overview-chart', compact('data'))->render()
        ], 200);
    }
    public function commission_overview(Request $request)
    {
        $params = session('dash_params');
        foreach ($params as $key => $value) {
            if ($key == 'commission_overview') {
                $params['commission_overview'] = $request['commission_overview'];
            }
        }
        session()->put('dash_params', $params);

        $data = self::dashboard_data($request);

        return response()->json([
            'view' => view('admin-views.partials._commission-overview-chart', compact('data'))->render(),
            'gross_sale' => view('admin-views.partials._gross_sale', compact('data'))->render()
        ], 200);
    }

    public function order_stats_calc($zone_id, $module_id)
    {
        $params = session('dash_params');
        $module_type = Config::get('module.current_module_type');

        if ($module_id && $params['statistics_type'] == 'custom_date' && !empty($params['custom_date'])) {
            $custom_date = $params['custom_date'];
            $searching_for_dm = Order::SearchingForDeliveryman()->where('module_id', $module_id)->whereDate('created_at', $custom_date);
            $accepted_by_dm = Order::AccepteByDeliveryman()->where('module_id', $module_id)->whereDate('accepted', $custom_date);
            $preparing_in_rs = Order::Preparing()->where('module_id', $module_id)->whereDate('processing', $custom_date);
            $picked_up = Order::ItemOnTheWay()->where('module_id', $module_id)->whereDate('picked_up', $custom_date);
            $delivered = Order::Delivered()->where('module_id', $module_id)->whereDate('created_at', $custom_date);
            $canceled = Order::where('module_id', $module_id)->where(['order_status' => 'canceled'])->whereDate('canceled', $custom_date);
            $refund_requested = Order::where('module_id', $module_id)->where(['order_status' => 'refund_requested'])->whereDate('refund_requested', $custom_date);
            $refunded = Order::where('module_id', $module_id)->where(['order_status' => 'refunded'])->whereDate('refunded', $custom_date);
            $new_orders = Order::where('module_id', $module_id)->whereDate('schedule_at', $custom_date);
            $new_items = Item::where('module_id', $module_id)->whereDate('created_at', $custom_date);
            $new_stores = Store::where('module_id', $module_id)->whereDate('created_at', $custom_date);
            $new_customers = User::whereDate('created_at', $custom_date);
            $total_orders = Order::where('module_id', $module_id);
            $total_items = Item::where('module_id', $module_id);
            $total_stores = Store::where('module_id', $module_id);
            $total_customers = User::all();
        } elseif ($module_id && $params['statistics_type'] == 'today') {
            $searching_for_dm = Order::SearchingForDeliveryman()->where('module_id', $module_id)->whereDate('created_at', Carbon::now());
            $accepted_by_dm = Order::AccepteByDeliveryman()->where('module_id', $module_id)->whereDate('accepted', Carbon::now());
            $preparing_in_rs = Order::Preparing()->where('module_id', $module_id)->whereDate('processing', Carbon::now());
            $picked_up = Order::ItemOnTheWay()->where('module_id', $module_id)->whereDate('picked_up', Carbon::now());
            $delivered = Order::Delivered()->where('module_id', $module_id)->whereDate('delivered', Carbon::now());
            $canceled = Order::where('module_id', $module_id)->where(['order_status' => 'canceled'])->whereDate('canceled', Carbon::now());
            $refund_requested = Order::where('module_id', $module_id)->where(['order_status' => 'refund_requested'])->whereDate('refund_requested', Carbon::now());
            $refunded = Order::where('module_id', $module_id)->where(['order_status' => 'refunded'])->whereDate('refunded', Carbon::now());
            $new_orders = Order::where('module_id', $module_id)->whereDate('schedule_at', Carbon::now());
            $new_items = Item::where('module_id', $module_id)->whereDate('created_at', Carbon::now());
            $new_stores = Store::where('module_id', $module_id)->whereDate('created_at', Carbon::now());
            $new_customers = User::whereDate('created_at', Carbon::now());
            if($module_type =='parcel'){
                $total_orders = Order::where('module_id', $module_id)->whereDate('created_at', Carbon::now());
            } else{
                $total_orders = Order::where('module_id', $module_id);
            }
            $total_items = Item::where('module_id', $module_id);
            $total_stores = Store::where('module_id', $module_id);
            $total_customers = User::all();
        } elseif($module_id && $params['statistics_type'] == 'this_year'){
            $searching_for_dm = Order::SearchingForDeliveryman()->where('module_id', $module_id)->whereYear('created_at', now()->format('Y'));
            $accepted_by_dm = Order::AccepteByDeliveryman()->where('module_id', $module_id)->whereYear('accepted', now()->format('Y'));
            $preparing_in_rs = Order::Preparing()->where('module_id', $module_id)->whereYear('processing', now()->format('Y'));
            $picked_up = Order::ItemOnTheWay()->where('module_id', $module_id)->whereYear('picked_up', now()->format('Y'));
            $delivered = Order::Delivered()->where('module_id', $module_id)->whereYear('delivered', now()->format('Y'));
            $canceled = Order::where('module_id', $module_id)->where(['order_status' => 'canceled'])->whereYear('canceled', now()->format('Y'));
            $refund_requested = Order::where('module_id', $module_id)->where(['order_status' => 'refund_requested'])->whereYear('refund_requested', now()->format('Y'));
            $refunded = Order::where('module_id', $module_id)->where(['order_status' => 'refunded'])->whereYear('refunded', now()->format('Y'));
            $new_orders = Order::where('module_id', $module_id)->whereYear('schedule_at', now()->format('Y'));
            $new_items = Item::where('module_id', $module_id)->whereYear('created_at', now()->format('Y'));
            $new_stores = Store::where('module_id', $module_id)->whereYear('created_at', now()->format('Y'));
            $new_customers = User::whereYear('created_at', now()->format('Y'));
            $total_orders = Order::where('module_id', $module_id);
            $total_items = Item::where('module_id', $module_id);
            $total_stores = Store::where('module_id', $module_id);
            $total_customers = User::all();
        } elseif($module_id && $params['statistics_type'] == 'this_month'){
            $searching_for_dm = Order::SearchingForDeliveryman()->where('module_id', $module_id)->whereMonth('created_at', now()->format('m'))->whereYear('created_at', now()->format('Y'));
            $accepted_by_dm = Order::AccepteByDeliveryman()->where('module_id', $module_id)->whereMonth('accepted', now()->format('m'))->whereYear('accepted', now()->format('Y'));
            $preparing_in_rs = Order::Preparing()->where('module_id', $module_id)->whereMonth('processing', now()->format('m'))->whereYear('processing', now()->format('Y'));
            $picked_up = Order::ItemOnTheWay()->where('module_id', $module_id)->whereMonth('picked_up', now()->format('m'))->whereYear('picked_up', now()->format('Y'));
            $delivered = Order::Delivered()->where('module_id', $module_id)->whereMonth('delivered', now()->format('m'))->whereYear('delivered', now()->format('Y'));
            $canceled = Order::where('module_id', $module_id)->where(['order_status' => 'canceled'])->whereMonth('canceled', now()->format('m'))->whereYear('canceled', now()->format('Y'));
            $refund_requested = Order::where('module_id', $module_id)->where(['order_status' => 'refund_requested'])->whereMonth('refund_requested', now()->format('m'))->whereYear('refund_requested', now()->format('Y'));
            $refunded = Order::where('module_id', $module_id)->where(['order_status' => 'refunded'])->whereMonth('refunded', now()->format('m'))->whereYear('refunded', now()->format('Y'));
            $new_orders = Order::where('module_id', $module_id)->whereMonth('schedule_at', now()->format('m'))->whereYear('schedule_at', now()->format('Y'));
            $new_items = Item::where('module_id', $module_id)->whereMonth('created_at', now()->format('m'))->whereYear('created_at', now()->format('Y'));
            $new_stores = Store::where('module_id', $module_id)->whereMonth('created_at', now()->format('m'))->whereYear('created_at', now()->format('Y'));
            $new_customers = User::whereMonth('created_at', now()->format('m'))->whereYear('created_at', now()->format('Y'));
            $total_orders = Order::where('module_id', $module_id);
            $total_items = Item::where('module_id', $module_id);
            $total_stores = Store::where('module_id', $module_id);
            $total_customers = User::all();
        } elseif($module_id && $params['statistics_type'] == 'this_week'){
            $searching_for_dm = Order::SearchingForDeliveryman()->where('module_id', $module_id)->whereBetween('created_at', [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')]);
            $accepted_by_dm = Order::AccepteByDeliveryman()->where('module_id', $module_id)->whereBetween('accepted', [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')]);
            $preparing_in_rs = Order::Preparing()->where('module_id', $module_id)->whereBetween('processing', [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')]);
            $picked_up = Order::ItemOnTheWay()->where('module_id', $module_id)->whereBetween('picked_up', [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')]);
            $delivered = Order::Delivered()->where('module_id', $module_id)->whereBetween('delivered', [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')]);
            $canceled = Order::where('module_id', $module_id)->where(['order_status' => 'canceled'])->whereBetween('canceled', [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')]);
            $refund_requested = Order::where('module_id', $module_id)->where(['order_status' => 'refund_requested'])->whereBetween('refund_requested', [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')]);
            $refunded = Order::where('module_id', $module_id)->where(['order_status' => 'refunded'])->whereBetween('refunded', [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')]);
            $new_orders = Order::where('module_id', $module_id)->whereBetween('schedule_at', [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')]);
            $new_items = Item::where('module_id', $module_id)->whereBetween('created_at', [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')]);
            $new_stores = Store::where('module_id', $module_id)->whereBetween('created_at', [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')]);
            $new_customers = User::whereBetween('created_at', [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')]);
            $total_orders = Order::where('module_id', $module_id);
            $total_items = Item::where('module_id', $module_id);
            $total_stores = Store::where('module_id', $module_id);
            $total_customers = User::all();
        } elseif($module_id) {
            $searching_for_dm = Order::SearchingForDeliveryman()->where('module_id', $module_id);
            $accepted_by_dm = Order::AccepteByDeliveryman()->where('module_id', $module_id);
            $preparing_in_rs = Order::Preparing()->where('module_id', $module_id);
            $picked_up = Order::ItemOnTheWay()->where('module_id', $module_id);
            $delivered = Order::Delivered()->where('module_id', $module_id);
            $canceled = Order::Canceled()->where('module_id', $module_id);
            $refund_requested = Order::failed()->where('module_id', $module_id);
            $refunded = Order::Refunded()->where('module_id', $module_id);
            $new_orders = Order::where('module_id', $module_id)->whereDate('schedule_at', '>=', now()->subDays(30)->format('Y-m-d'));
            $new_items = Item::where('module_id', $module_id)->whereDate('created_at', '>=', now()->subDays(30)->format('Y-m-d'));
            $new_stores = Store::where('module_id', $module_id)->whereDate('created_at', '>=', now()->subDays(30)->format('Y-m-d'));
            $new_customers = User::whereDate('created_at', '>=', now()->subDays(30)->format('Y-m-d'));
            $total_orders = Order::where('module_id', $module_id);
            $total_items = Item::where('module_id', $module_id);
            $total_stores = Store::where('module_id', $module_id);
            $total_customers = User::all();
        } else {
            $searching_for_dm = Order::SearchingForDeliveryman();
            $accepted_by_dm = Order::AccepteByDeliveryman();
            $preparing_in_rs = Order::Preparing();
            $picked_up = Order::ItemOnTheWay();
            $delivered = Order::Delivered();
            $canceled = Order::Canceled();
            $refund_requested = Order::failed();
            $refunded = Order::Refunded();
            $new_orders = Order::whereDate('schedule_at', '>=', now()->subDays(30)->format('Y-m-d'));
            $new_items = Item::whereDate('created_at', '>=', now()->subDays(30)->format('Y-m-d'));
            $new_stores = Store::whereDate('created_at', '>=', now()->subDays(30)->format('Y-m-d'));
            $new_customers = User::whereDate('created_at', '>=', now()->subDays(30)->format('Y-m-d'));
            $total_orders = Order::query();
            $total_items = Item::query();
            $total_stores = Store::query();
            $total_customers = User::query();
        }

        if (is_numeric($zone_id) && $module_id &&  !in_array($module_type ,['parcel']) ) {
            $searching_for_dm = $searching_for_dm->StoreOrder()->OrderScheduledIn(30)->where('zone_id', $zone_id)->count();
            $accepted_by_dm = $accepted_by_dm->StoreOrder()->where('zone_id', $zone_id)->count();
            $preparing_in_rs = $preparing_in_rs->StoreOrder()->where('zone_id', $zone_id)->count();
            $picked_up = $picked_up->StoreOrder()->where('zone_id', $zone_id)->count();
            $delivered = $delivered->StoreOrder()->where('zone_id', $zone_id)->count();
            $canceled = $canceled->StoreOrder()->where('zone_id', $zone_id)->count();
            $refund_requested = $refund_requested->StoreOrder()->where('zone_id', $zone_id)->count();
            $refunded = $refunded->StoreOrder()->where('zone_id', $zone_id)->count();
            $total_orders = $total_orders->StoreOrder()->where('zone_id', $zone_id)->count();
            $total_items = $total_items->count();
            $total_stores = $total_stores->where('zone_id', $zone_id)->count();
            $total_customers = $total_customers->count();
            $new_orders = $new_orders->StoreOrder()->where('zone_id', $zone_id)->count();
            $new_items = $new_items->count();
            $new_stores = $new_stores->where('zone_id', $zone_id)->count();
            $new_customers = $new_customers->count();
        } elseif($module_id && $module_type!='parcel') {
            $searching_for_dm = $searching_for_dm->StoreOrder()->OrderScheduledIn(30)->count();
            $accepted_by_dm = $accepted_by_dm->StoreOrder()->count();
            $preparing_in_rs = $preparing_in_rs->StoreOrder()->count();
            $picked_up = $picked_up->StoreOrder()->count();
            $delivered = $delivered->StoreOrder()->count();
            $canceled = $canceled->StoreOrder()->count();
            $refund_requested = $refund_requested->StoreOrder()->count();
            $refunded = $refunded->StoreOrder()->count();
            $total_orders = $total_orders->StoreOrder()->count();
            $total_items = $total_items->count();
            $total_stores = $total_stores->count();
            $total_customers = $total_customers->count();
            $new_orders = $new_orders->StoreOrder()->count();
            $new_items = $new_items->count();
            $new_stores = $new_stores->count();
            $new_customers = $new_customers->count();
        } elseif(is_numeric($zone_id) && $module_id && $module_type =='parcel') {
            $searching_for_dm = $searching_for_dm->ParcelOrder()->OrderScheduledIn(30)->where('zone_id', $zone_id)->count();
            $accepted_by_dm = $accepted_by_dm->ParcelOrder()->where('zone_id', $zone_id)->count();
            $preparing_in_rs = $preparing_in_rs->ParcelOrder()->where('zone_id', $zone_id)->count();
            $picked_up = $picked_up->ParcelOrder()->where('zone_id', $zone_id)->count();
            $delivered = $delivered->ParcelOrder()->where('zone_id', $zone_id)->count();
            $canceled = $canceled->ParcelOrder()->where('zone_id', $zone_id)->count();
            $refund_requested = $refund_requested->ParcelOrder()->where('zone_id', $zone_id)->count();
            $refunded = $refunded->ParcelOrder()->where('zone_id', $zone_id)->count();
            $total_orders = $total_orders->ParcelOrder()->where('zone_id', $zone_id)->count();
            $total_items = $total_items->count();
            $total_stores = $total_stores->where('zone_id', $zone_id)->count();
            $total_customers = $total_customers->where('zone_id', $zone_id)->count();
            $new_orders = $new_orders->ParcelOrder()->where('zone_id', $zone_id)->count();
            $new_items = $new_items->count();
            $new_stores = $new_stores->where('zone_id', $zone_id)->count();
            $new_customers = $new_customers->where('zone_id', $zone_id)->count();
        }
        elseif($module_id && $module_type =='parcel') {
            $searching_for_dm = $searching_for_dm->ParcelOrder()->OrderScheduledIn(30)->count();
            $accepted_by_dm = $accepted_by_dm->ParcelOrder()->count();
            $preparing_in_rs = $preparing_in_rs->ParcelOrder()->count();
            $picked_up = $picked_up->ParcelOrder()->count();
            $delivered = $delivered->ParcelOrder()->count();
            $canceled = $canceled->ParcelOrder()->count();
            $refund_requested = $refund_requested->ParcelOrder()->count();
            $refunded = $refunded->ParcelOrder()->count();
            $total_orders = $total_orders->ParcelOrder()->count();
            $total_items = $total_items->count();
            $total_stores = $total_stores->count();
            $total_customers = $total_customers->count();
            $new_orders = $new_orders->ParcelOrder()->count();
            $new_items = $new_items->count();
            $new_stores = $new_stores->count();
            $new_customers = $new_customers->count();
        }

        else{
            $searching_for_dm = $searching_for_dm->StoreOrder()->OrderScheduledIn(30)->count();
            $accepted_by_dm = $accepted_by_dm->StoreOrder()->count();
            $preparing_in_rs = $preparing_in_rs->StoreOrder()->count();
            $picked_up = $picked_up->StoreOrder()->count();
            $delivered = $delivered->StoreOrder()->count();
            $canceled = $canceled->StoreOrder()->count();
            $refund_requested = $refund_requested->StoreOrder()->count();
            $refunded = $refunded->StoreOrder()->count();
            $total_orders = $total_orders->count();
            $total_items = $total_items->count();
            $total_stores = $total_stores->count();
            $total_customers = $total_customers->count();
            $new_orders = $new_orders->count();
            $new_items = $new_items->count();
            $new_stores = $new_stores->count();
            $new_customers = $new_customers->count();
        }
        // Calculate uploaded (created) and edited items based on statistics_type
        $uploaded_items_query = Item::when($module_id, function ($q) use ($module_id) { return $q->where('module_id', $module_id); });
        $edited_items_query = Item::when($module_id, function ($q) use ($module_id) { return $q->where('module_id', $module_id); })
            ->whereColumn('updated_at', '!=', 'created_at');

        if ($params['statistics_type'] == 'custom_date' && !empty($params['custom_date'])) {
            $uploaded_items = $uploaded_items_query->whereDate('created_at', $params['custom_date'])->count();
            $edited_items = $edited_items_query->whereDate('updated_at', $params['custom_date'])->count();
        } elseif ($params['statistics_type'] == 'today') {
            $uploaded_items = $uploaded_items_query->whereDate('created_at', Carbon::now())->count();
            $edited_items = $edited_items_query->whereDate('updated_at', Carbon::now())->count();
        } elseif ($params['statistics_type'] == 'this_year') {
            $uploaded_items = $uploaded_items_query->whereYear('created_at', now()->format('Y'))->count();
            $edited_items = $edited_items_query->whereYear('updated_at', now()->format('Y'))->count();
        } elseif ($params['statistics_type'] == 'this_month') {
            $uploaded_items = $uploaded_items_query->whereMonth('created_at', now()->format('m'))->whereYear('created_at', now()->format('Y'))->count();
            $edited_items = $edited_items_query->whereMonth('updated_at', now()->format('m'))->whereYear('updated_at', now()->format('Y'))->count();
        } elseif ($params['statistics_type'] == 'this_week') {
            $uploaded_items = $uploaded_items_query->whereBetween('created_at', [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')])->count();
            $edited_items = $edited_items_query->whereBetween('updated_at', [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')])->count();
        } else {
            $uploaded_items = $uploaded_items_query->whereDate('created_at', '>=', now()->subDays(30)->format('Y-m-d'))->count();
            $edited_items = $edited_items_query->whereDate('updated_at', '>=', now()->subDays(30)->format('Y-m-d'))->count();
        }

        $data = [
            'searching_for_dm' => $searching_for_dm,
            'accepted_by_dm' => $accepted_by_dm,
            'preparing_in_rs' => $preparing_in_rs,
            'picked_up' => $picked_up,
            'delivered' => $delivered,
            'canceled' => $canceled,
            'refund_requested' => $refund_requested,
            'refunded' => $refunded,
            'total_orders' => $total_orders,
            'total_items' => $total_items,
            'total_stores' => $total_stores,
            'total_customers' => $total_customers,
            'new_orders' => $new_orders,
            'new_items' => $new_items,
            'new_stores' => $new_stores,
            'new_customers' => $new_customers,
            'uploaded_items' => $uploaded_items,
            'edited_items' => $edited_items,
        ];

        return $data;
    }

    public function user_overview_calc($zone_id, $module_id)
    {
        $params = session('dash_params');
        //zone
        if (is_numeric($zone_id)) {
            $customer = User::where('zone_id', $zone_id);
            $stores = Store::where('module_id', $module_id)->where(['zone_id' => $zone_id]);
            $delivery_man = DeliveryMan::where('application_status', 'approved')->where('zone_id', $zone_id)->Zonewise();
        } else {
            $customer = User::whereNotNull('id');
            $stores = Store::where('module_id', $module_id)->whereNotNull('id');
            $delivery_man = DeliveryMan::where('application_status', 'approved')->Zonewise();
        }
        //user overview
        if ($params['user_overview'] == 'overall') {
            $customer = $customer->count();
            $stores = $stores->count();
            $delivery_man = $delivery_man->count();
        } elseif($params['user_overview'] == 'this_month') {
            $customer = $customer->whereMonth('created_at', date('m'))
                ->whereYear('created_at', date('Y'))->count();
            $stores = $stores->whereMonth('created_at', date('m'))
                ->whereYear('created_at', date('Y'))->count();
            $delivery_man = $delivery_man->whereMonth('created_at', date('m'))
                ->whereYear('created_at', date('Y'))->count();
        } elseif($params['user_overview'] == 'this_year') {
            $customer = $customer
                ->whereYear('created_at', date('Y'))->count();
            $stores = $stores
                ->whereYear('created_at', date('Y'))->count();
            $delivery_man = $delivery_man
                ->whereYear('created_at', date('Y'))->count();
        } else {
            $customer = $customer->whereDate('created_at', [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')])->count();
            $stores = $stores->whereDate('created_at', [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')])->count();
            $delivery_man = $delivery_man->whereDate('created_at', [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')])->count();
        }
        $data = [
            'customer' => $customer,
            'stores' => $stores,
            'delivery_man' => $delivery_man
        ];
        return $data;
    }


    public function dashboard_data($request)
    {
        $params = session('dash_params');
        if (!url()->current() == $request->is('admin/users')) {
        $data_os = self::order_stats_calc($params['zone_id'], $params['module_id']);
        $data_uo = self::user_overview_calc($params['zone_id'], $params['module_id']);
        }
        $popular = Wishlist::with(['store'])
            ->whereHas('store')
            ->when(is_numeric($params['module_id']), function ($q) use ($params) {
                return $q->whereHas('store', function ($query) use ($params) {
                    return $query->where('module_id', $params['module_id']);
                });
            })
            ->when(is_numeric($params['zone_id']), function ($q) use ($params) {
                return $q->whereHas('store', function ($query) use ($params) {
                    return $query->where('zone_id', $params['zone_id']);
                });
            })
            ->select('store_id', DB::raw('COUNT(store_id) as count'))->groupBy('store_id')
            ->having("count" , '>', 0)
            ->orderBy('count', 'DESC')
            ->limit(6)->get();
        $top_sell = Item::withoutGlobalScope(ZoneScope::class)
            ->when(is_numeric($params['module_id']), function ($q) use ($params) {
                return $q->whereHas('store', function ($query) use ($params) {
                    return $query->where('module_id', $params['module_id']);
                });
            })
            ->when(is_numeric($params['zone_id']), function ($q) use ($params) {
                return $q->whereHas('store', function ($query) use ($params) {
                    return $query->where('module_id', $params['module_id'])->where('zone_id', $params['zone_id']);
                });
            })
            ->having("order_count" , '>', 0)
            ->orderBy("order_count", 'desc')
            ->take(6)
            ->get();
        $top_rated_foods = Item::withoutGlobalScope(ZoneScope::class)
            ->when(is_numeric($params['module_id']), function ($q) use ($params) {
                return $q->whereHas('store', function ($query) use ($params) {
                    return $query->where('module_id', $params['module_id']);
                });
            })
            ->when(is_numeric($params['zone_id']), function ($q) use ($params) {
                return $q->whereHas('store', function ($query) use ($params) {
                    return $query->where('zone_id', $params['zone_id']);
                });
            })
            ->having("rating_count" , '>', 0)
            ->orderBy('rating_count', 'desc')
            ->take(6)
            ->get();

        $top_deliveryman = DeliveryMan::withCount('orders')->when(is_numeric($params['zone_id']), function ($q) use ($params) {
                return $q->where('zone_id', $params['zone_id']);
            })
            ->Zonewise()
            ->having("orders_count" , '>', 0)
            ->orderBy("orders_count", 'desc')
            ->take(6)
            ->get();

        // Delivery Boy of the Month - Top performer this month
        $delivery_boy_of_month = DeliveryMan::withCount(['orders' => function($q) {
                $q->where('order_status', 'delivered')
                  ->whereMonth('delivered', now()->month)
                  ->whereYear('delivered', now()->year);
            }])
            ->when(is_numeric($params['zone_id']), function ($q) use ($params) {
                return $q->where('zone_id', $params['zone_id']);
            })
            ->Zonewise()
            ->having("orders_count", '>', 0)
            ->orderBy("orders_count", 'desc')
            ->first();

        // Delivery Boy of the Day - Least idle time today (best performer)
        $delivery_boy_of_day = null;
        $delivery_boy_of_day_stats = DeliveryTrackingStat::select('delivery_man_id')
            ->selectRaw('SUM(total_idle_seconds) as total_idle')
            ->selectRaw('SUM(store_duration_seconds) as total_store_time')
            ->selectRaw('SUM(customer_duration_seconds) as total_customer_time')
            ->selectRaw('COUNT(*) as order_count')
            ->whereDate('created_at', today())
            ->when(is_numeric($params['zone_id']), function ($q) use ($params) {
                return $q->whereHas('deliveryMan', function($dm) use ($params) {
                    $dm->where('zone_id', $params['zone_id']);
                });
            })
            ->groupBy('delivery_man_id')
            ->having('order_count', '>=', 1) // At least 1 order today
            ->orderBy('total_idle', 'asc') // Least idle time = best performer
            ->first();

        if ($delivery_boy_of_day_stats) {
            $delivery_boy_of_day = DeliveryMan::withCount(['orders' => function($q) {
                    $q->where('order_status', 'delivered')
                      ->whereDate('delivered', today());
                }])
                ->find($delivery_boy_of_day_stats->delivery_man_id);

            if ($delivery_boy_of_day) {
                $delivery_boy_of_day->today_idle_seconds = $delivery_boy_of_day_stats->total_idle;
                $delivery_boy_of_day->today_store_seconds = $delivery_boy_of_day_stats->total_store_time;
                $delivery_boy_of_day->today_customer_seconds = $delivery_boy_of_day_stats->total_customer_time;
                $delivery_boy_of_day->today_order_count = $delivery_boy_of_day_stats->order_count;
            }
        }

        // Bad Performers - Most idle time in last 7 days
        $bad_performers = DeliveryTrackingStat::select('delivery_man_id')
            ->selectRaw('SUM(total_idle_seconds) as total_idle')
            ->selectRaw('SUM(store_duration_seconds) as total_store_time')
            ->selectRaw('SUM(customer_duration_seconds) as total_customer_time')
            ->selectRaw('COUNT(*) as order_count')
            ->selectRaw('AVG(total_idle_seconds) as avg_idle_per_order')
            ->where('created_at', '>=', now()->subDays(7))
            ->when(is_numeric($params['zone_id']), function ($q) use ($params) {
                return $q->whereHas('deliveryMan', function($dm) use ($params) {
                    $dm->where('zone_id', $params['zone_id']);
                });
            })
            ->groupBy('delivery_man_id')
            ->having('order_count', '>=', 3) // At least 3 orders in 7 days to be considered
            ->orderBy('avg_idle_per_order', 'desc') // Highest average idle time = worst performer
            ->limit(5)
            ->get();

        // Enrich bad performers with delivery man details
        $bad_performers_list = [];
        foreach ($bad_performers as $bp) {
            $dm = DeliveryMan::find($bp->delivery_man_id);
            if ($dm) {
                $dm->total_idle_seconds = $bp->total_idle;
                $dm->avg_idle_seconds = round($bp->avg_idle_per_order);
                $dm->total_store_seconds = $bp->total_store_time;
                $dm->total_customer_seconds = $bp->total_customer_time;
                $dm->week_order_count = $bp->order_count;
                $bad_performers_list[] = $dm;
            }
        }

        // This month business stats
        $this_month_orders = Order::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->when(is_numeric($params['zone_id']), function ($q) use ($params) {
                return $q->where('zone_id', $params['zone_id']);
            })
            ->when(is_numeric($params['module_id']), function ($q) use ($params) {
                return $q->where('module_id', $params['module_id']);
            });

        $this_month_revenue = (clone $this_month_orders)->where('order_status', 'delivered')->sum('order_amount');
        $this_month_orders_count = (clone $this_month_orders)->count();
        $this_month_delivered = (clone $this_month_orders)->where('order_status', 'delivered')->count();
        $this_month_canceled = (clone $this_month_orders)->where('order_status', 'canceled')->count();

        // Last month comparison
        $last_month_revenue = Order::whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->where('order_status', 'delivered')
            ->when(is_numeric($params['zone_id']), function ($q) use ($params) {
                return $q->where('zone_id', $params['zone_id']);
            })
            ->when(is_numeric($params['module_id']), function ($q) use ($params) {
                return $q->where('module_id', $params['module_id']);
            })
            ->sum('order_amount');

        $revenue_growth = $last_month_revenue > 0 ? round((($this_month_revenue - $last_month_revenue) / $last_month_revenue) * 100, 1) : 0;

        // Today's stats
        $today_orders = Order::whereDate('created_at', today())
            ->when(is_numeric($params['zone_id']), function ($q) use ($params) {
                return $q->where('zone_id', $params['zone_id']);
            })
            ->when(is_numeric($params['module_id']), function ($q) use ($params) {
                return $q->where('module_id', $params['module_id']);
            })
            ->count();

        $today_revenue = Order::whereDate('created_at', today())
            ->where('order_status', 'delivered')
            ->when(is_numeric($params['zone_id']), function ($q) use ($params) {
                return $q->where('zone_id', $params['zone_id']);
            })
            ->when(is_numeric($params['module_id']), function ($q) use ($params) {
                return $q->where('module_id', $params['module_id']);
            })
            ->sum('order_amount');

        $top_customers = User::when(is_numeric($params['zone_id']), function ($q) use ($params) {
                return $q->where('zone_id', $params['zone_id']);
            })
            ->having("order_count" , '>', 0)
            ->orderBy("order_count", 'desc')
            ->take(6)
            ->get();

        $top_restaurants = Store::when(is_numeric($params['module_id']), function ($q) use ($params) {
                return $q->where('module_id', $params['module_id']);
            })
            ->when(is_numeric($params['zone_id']), function ($q) use ($params) {
                return $q->where('zone_id', $params['zone_id']);
            })
            ->having("order_count" , '>', 0)
            ->orderBy("order_count", 'desc')
            ->take(6)
            ->get();


        // custom filtering for bar chart
        $months = array(
            '"'.translate('Jan').'"',
            '"'.translate('Feb').'"',
            '"'.translate('Mar').'"',
            '"'.translate('Apr').'"',
            '"'.translate('May').'"',
            '"'.translate('Jun').'"',
            '"'.translate('Jul').'"',
            '"'.translate('Aug').'"',
            '"'.translate('Sep').'"',
            '"'.translate('Oct').'"',
            '"'.translate('Nov').'"',
            '"'.translate('Dec').'"'
        );
        $days = array(
            '"'.translate('Mon').'"',
            '"'.translate('Tue').'"',
            '"'.translate('Wed').'"',
            '"'.translate('Thu').'"',
            '"'.translate('Fri').'"',
            '"'.translate('Sat').'"',
            '"'.translate('Sun').'"',
        );
        $total_sell = [];
        $commission = [];
        $label = [];
        $query = OrderTransaction::NotRefunded()
        ->when(is_numeric($params['module_id']), function ($q) use ($params) {
            return $q->where('module_id', $params['module_id']);
        })
        ->when(is_numeric($params['zone_id']), function ($q) use ($params) {
            return $q->where('zone_id', $params['zone_id']);
        });
        switch ($params['commission_overview']) {
            case "this_year":
                // OPTIMIZED: Single query with GROUP BY instead of 36 separate queries
                $yearly_data = OrderTransaction::NotRefunded()
                    ->selectRaw('
                        MONTH(created_at) as month,
                        SUM(order_amount) as total_sell,
                        SUM(admin_commission + admin_expense - delivery_fee_comission) as commission_total,
                        SUM(delivery_fee_comission) as delivery_commission_total
                    ')
                    ->when(is_numeric($params['module_id']), function ($q) use ($params) {
                        return $q->where('module_id', $params['module_id']);
                    })
                    ->when(is_numeric($params['zone_id']), function ($q) use ($params) {
                        return $q->where('zone_id', $params['zone_id']);
                    })
                    ->whereYear('created_at', now()->format('Y'))
                    ->groupBy('month')
                    ->get()
                    ->keyBy('month');

                // Fill all 12 months with data (0 if no data)
                for ($i = 1; $i <= 12; $i++) {
                    $total_sell[$i] = $yearly_data->get($i)->total_sell ?? 0;
                    $commission[$i] = $yearly_data->get($i)->commission_total ?? 0;
                    $delivery_commission[$i] = $yearly_data->get($i)->delivery_commission_total ?? 0;
                }
                $label = $months;
                break;

            case "this_week":
                // OPTIMIZED: Single query with GROUP BY instead of 21 separate queries
                $weekStartDate = now()->startOfWeek();
                $weekEndDate = now()->endOfWeek();

                $weekly_data = OrderTransaction::NotRefunded()
                    ->selectRaw('
                        DATE(created_at) as date,
                        SUM(order_amount) as total_sell,
                        SUM(admin_commission + admin_expense - delivery_fee_comission) as commission_total,
                        SUM(delivery_fee_comission) as delivery_commission_total
                    ')
                    ->when(is_numeric($params['module_id']), function ($q) use ($params) {
                        return $q->where('module_id', $params['module_id']);
                    })
                    ->when(is_numeric($params['zone_id']), function ($q) use ($params) {
                        return $q->where('zone_id', $params['zone_id']);
                    })
                    ->whereBetween('created_at', [$weekStartDate->format('Y-m-d 00:00:00'), $weekEndDate->format('Y-m-d 23:59:59')])
                    ->groupBy('date')
                    ->get()
                    ->keyBy('date');

                // Fill all 7 days with data (0 if no data)
                for ($i = 0; $i < 7; $i++) {
                    $currentDate = $weekStartDate->copy()->addDays($i);
                    $dateKey = $currentDate->format('Y-m-d');
                    $total_sell[$i] = $weekly_data->get($dateKey)->total_sell ?? 0;
                    $commission[$i] = $weekly_data->get($dateKey)->commission_total ?? 0;
                    $delivery_commission[$i] = $weekly_data->get($dateKey)->delivery_commission_total ?? 0;
                }

                $label = $days;
                break;

            case "this_month":
                // OPTIMIZED: Single query with CASE statements to group by week ranges
                $total_days = now()->daysInMonth;
                $weeks = array(
                    '"Day 1-7"',
                    '"Day 8-14"',
                    '"Day 15-21"',
                    '"Day 22-' . $total_days . '"',
                );

                $monthly_data = OrderTransaction::NotRefunded()
                    ->selectRaw('
                        CASE
                            WHEN DAY(created_at) <= 7 THEN 1
                            WHEN DAY(created_at) <= 14 THEN 2
                            WHEN DAY(created_at) <= 21 THEN 3
                            ELSE 4
                        END as week_group,
                        SUM(order_amount) as total_sell,
                        SUM(admin_commission + admin_expense - delivery_fee_comission) as commission_total,
                        SUM(delivery_fee_comission) as delivery_commission_total
                    ')
                    ->when(is_numeric($params['module_id']), function ($q) use ($params) {
                        return $q->where('module_id', $params['module_id']);
                    })
                    ->when(is_numeric($params['zone_id']), function ($q) use ($params) {
                        return $q->where('zone_id', $params['zone_id']);
                    })
                    ->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->groupBy('week_group')
                    ->get()
                    ->keyBy('week_group');

                // Fill all 4 weeks with data (0 if no data)
                for ($i = 1; $i <= 4; $i++) {
                    $total_sell[$i] = $monthly_data->get($i)->total_sell ?? 0;
                    $commission[$i] = $monthly_data->get($i)->commission_total ?? 0;
                    $delivery_commission[$i] = $monthly_data->get($i)->delivery_commission_total ?? 0;
                }

                $label = $weeks;
                break;

            default:
                // OPTIMIZED: Single query with GROUP BY instead of 36 separate queries
                $default_data = OrderTransaction::NotRefunded()
                    ->selectRaw('
                        MONTH(created_at) as month,
                        SUM(order_amount) as total_sell,
                        SUM(admin_commission + admin_expense - delivery_fee_comission) as commission_total,
                        SUM(delivery_fee_comission) as delivery_commission_total
                    ')
                    ->when(is_numeric($params['module_id']), function ($q) use ($params) {
                        return $q->where('module_id', $params['module_id']);
                    })
                    ->when(is_numeric($params['zone_id']), function ($q) use ($params) {
                        return $q->where('zone_id', $params['zone_id']);
                    })
                    ->whereYear('created_at', now()->format('Y'))
                    ->groupBy('month')
                    ->get()
                    ->keyBy('month');

                // Fill all 12 months with data (0 if no data)
                for ($i = 1; $i <= 12; $i++) {
                    $total_sell[$i] = $default_data->get($i)->total_sell ?? 0;
                    $commission[$i] = $default_data->get($i)->commission_total ?? 0;
                    $delivery_commission[$i] = $default_data->get($i)->delivery_commission_total ?? 0;
                }
                $label = $months;
        }

        if (!url()->current() == $request->is('admin/users')) {
            $dash_data = array_merge($data_os, $data_uo);
        }

        $dash_data['popular'] = $popular;
        $dash_data['top_sell'] = $top_sell;
        $dash_data['top_rated_foods'] = $top_rated_foods;
        $dash_data['top_deliveryman'] = $top_deliveryman;
        $dash_data['top_restaurants'] = $top_restaurants;
        $dash_data['top_customers'] = $top_customers;
        $dash_data['total_sell'] = $total_sell;
        $dash_data['commission'] = $commission;
        $dash_data['delivery_commission'] = $delivery_commission;
        $dash_data['label'] = $label;
        // New business stats
        $dash_data['delivery_boy_of_month'] = $delivery_boy_of_month;
        $dash_data['delivery_boy_of_day'] = $delivery_boy_of_day;
        $dash_data['bad_performers'] = $bad_performers_list;
        $dash_data['this_month_revenue'] = $this_month_revenue;
        $dash_data['this_month_orders_count'] = $this_month_orders_count;
        $dash_data['this_month_delivered'] = $this_month_delivered;
        $dash_data['this_month_canceled'] = $this_month_canceled;
        $dash_data['revenue_growth'] = $revenue_growth;
        $dash_data['today_orders'] = $today_orders;
        $dash_data['today_revenue'] = $today_revenue;
        return $dash_data;
    }

    /**
     * Update admin's dashboard activity timestamp (heartbeat)
     */
    public function dashboardHeartbeat(Request $request)
    {
        if (auth('admin')->check()) {
            $admin = auth('admin')->user();
            $admin->update([
                'last_dashboard_activity' => now()
            ]);

            return response()->json([
                'success' => true,
                'timestamp' => now()->toIso8601String()
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Not authenticated'
        ], 401);
    }

    /**
     * Get dashboard presence data (who's currently viewing)
     */
    public function dashboardPresence(Request $request)
    {
        if (!auth('admin')->check() || auth('admin')->user()->role_id !== 1) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $twoMinutesAgo = now()->subMinutes(2);
        $activeAdmins = \App\Models\Admin::where('role_id', '!=', 1)
            ->where('status', 1)
            ->where('last_dashboard_activity', '>=', $twoMinutesAgo)
            ->whereNotNull('last_dashboard_activity')
            ->with('role')
            ->orderBy('f_name')
            ->get();

        $employees = $activeAdmins->map(function($admin) {
            return [
                'name' => $admin->f_name . ' ' . $admin->l_name,
                'role' => $admin->role?->name ?? 'Employee',
                'image' => $admin->image_full_url ?? asset('public/assets/admin/img/admin.png'),
            ];
        });

        return response()->json([
            'success' => true,
            'count' => $activeAdmins->count(),
            'employees' => $employees,
            'timestamp' => now()->toIso8601String()
        ]);
    }

    /**
     * Get employee performance data for dashboard widget
     *
     * @param string $period - today|week|month
     * @param int|null $zoneId - Zone filter
     * @return array
     */
    public function employee_performance_data($period = 'today', $zoneId = null)
    {
        $user = auth('admin')->user();
        $performanceService = new \App\Services\EmployeePerformanceService();

        // Convert period to date range
        $dateRange = $this->getPeriodDateRange($period);
        $startDate = $dateRange['start'];
        $endDate = $dateRange['end'];

        // Super admin sees top performers, regular employee sees only own data
        if ($user->role_id == 1) {
            // Super admin view
            $topPerformers = $performanceService->getTopPerformers(5, $startDate, $endDate, $zoneId);

            return [
                'is_super_admin' => true,
                'top_employees' => $topPerformers,
                'period' => $period
            ];
        } else {
            // Regular employee view - only their own performance
            $ownMetrics = $performanceService->calculateEmployeeMetrics($user->id, $startDate, $endDate, $zoneId);

            return [
                'is_super_admin' => false,
                'own_metrics' => $ownMetrics,
                'employee' => [
                    'id' => $user->id,
                    'name' => $user->f_name . ' ' . $user->l_name,
                    'role' => $user->role?->name ?? 'Employee',
                    'image' => $user->image_full_url ?? asset('public/assets/admin/img/admin.png'),
                ],
                'period' => $period
            ];
        }
    }

    /**
     * Convert period string to date range
     *
     * @param string $period
     * @return array
     */
    private function getPeriodDateRange($period)
    {
        switch ($period) {
            case 'week':
                return [
                    'start' => \Carbon\Carbon::now()->startOfWeek(),
                    'end' => \Carbon\Carbon::now()->endOfWeek()
                ];
            case 'month':
                return [
                    'start' => \Carbon\Carbon::now()->startOfMonth(),
                    'end' => \Carbon\Carbon::now()->endOfMonth()
                ];
            case 'today':
            default:
                return [
                    'start' => \Carbon\Carbon::today(),
                    'end' => \Carbon\Carbon::today()->endOfDay()
                ];
        }
    }

    /**
     * AJAX endpoint: Get detailed performance metrics for a specific employee
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function employee_performance_detail(Request $request)
    {
        $employeeId = $request->input('employee_id');
        $period = $request->input('period', 'today');
        $user = auth('admin')->user();

        // Security: Regular employees can only view their own data
        if ($user->role_id != 1 && $employeeId != $user->id) {
            return response()->json([
                'success' => false,
                'message' => translate('messages.you_do_not_have_permission_to_view_this_data')
            ], 403);
        }

        $employee = \App\Models\Admin::with('role')->find($employeeId);

        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => translate('messages.employee_not_found')
            ], 404);
        }

        $performanceService = new \App\Services\EmployeePerformanceService();
        $metrics = $performanceService->calculateEmployeeMetrics($employeeId, $period);

        return response()->json([
            'success' => true,
            'employee' => [
                'id' => $employee->id,
                'name' => $employee->f_name . ' ' . $employee->l_name,
                'role' => $employee->role?->name ?? 'Employee',
                'image' => $employee->image_full_url ?? asset('public/assets/admin/img/admin.png'),
            ],
            'metrics' => $metrics,
            'period' => $period
        ]);
    }
}
