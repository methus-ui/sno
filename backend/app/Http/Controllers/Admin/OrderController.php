<?php

namespace App\Http\Controllers\Admin;

use App\Mail\OrderVerificationMail;
use App\Mail\PlaceOrder;
use App\Mail\UserOfflinePaymentMail;
use App\Models\Item;
use App\Models\Zone;
use App\Models\Order;
use App\Models\Store;
use App\Models\Coupon;
use App\Models\Refund;
use App\Models\Category;
use App\Scopes\ZoneScope;
use App\Scopes\StoreScope;
use App\Models\DeliveryMan;
use App\Models\OrderDetail;
use App\Models\Translation;
use App\Exports\OrderExport;
use App\Mail\RefundRejected;
use App\Models\ItemCampaign;
use App\Models\RefundReason;
use Illuminate\Http\Request;
use App\CentralLogics\Helpers;
use App\Models\BusinessSetting;
use App\CentralLogics\OrderLogic;
use App\CentralLogics\CouponLogic;
use Illuminate\Support\Facades\DB;
use App\CentralLogics\ProductLogic;
use App\CentralLogics\CustomerLogic;
use App\Http\Controllers\Controller;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;
use Rap2hpoutre\FastExcel\FastExcel;
use App\Exports\StoreOrderlistExport;
use App\Models\OrderPayment;
use App\Models\DeliveryTrackingStat;
use App\Models\OrderEditSession;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use MatanYadaev\EloquentSpatial\Objects\Point;

class OrderController extends Controller
{
    public function list($status, Request $request)
    {
        // dd($status);
        $key = explode(' ', $request['search']);
        if (session()->has('zone_filter') == false) {
            session()->put('zone_filter', 0);
        }
        $module_id = $request->query('module_id', null);

        // ✅ FIX: Merge session filters into Request instead of replacing it
        // ✅ ENHANCED: Added validation to prevent stdClass bug
        if (session()->has('order_filter')) {
            try {
                $sessionFilters = json_decode(session('order_filter'), true); // Decode as array
                if (is_array($sessionFilters)) {
                    $request->merge($sessionFilters); // Merge into existing Request object
                } else {
                    // Clear corrupted session filter if it's not an array
                    session()->forget('order_filter');
                    \Log::warning('Corrupted order_filter session data cleared', [
                        'type' => gettype($sessionFilters),
                        'data' => session('order_filter')
                    ]);
                }
            } catch (\Exception $e) {
                // If JSON decode fails, clear the corrupted session
                session()->forget('order_filter');
                \Log::error('Failed to decode order_filter session', [
                    'error' => $e->getMessage(),
                    'data' => session('order_filter')
                ]);
            }
        }

        // ✅ DEFENSIVE CHECK: Ensure $request is still a Request object (prevent stdClass bug)
        if (!($request instanceof \Illuminate\Http\Request)) {
            \Log::critical('Request object was replaced with ' . get_class($request) . ' - recreating from globals');
            $request = \Illuminate\Http\Request::createFromGlobals();
        }

        Order::where(['checked' => 0])->update(['checked' => 1]);

        $orders = Order::with(['customer', 'store', 'assignedAdmin', 'delivery_man.last_location'])
            ->when(isset($module_id), function ($query) use ($module_id) {
                return $query->module($module_id);
            })
            ->when(isset($request->zone), function ($query) use ($request) {
                return $query->whereHas('store', function ($q) use ($request) {
                    return $q->whereIn('zone_id', $request->zone);
                });
            })
            ->when($status == 'scheduled', function ($query) {
                return $query->whereRaw('created_at <> schedule_at');
            })
            ->when($status == 'searching_for_deliverymen', function ($query) {
                return $query->SearchingForDeliveryman();
            })
            ->when($status == 'pending', function ($query) {
                return $query->Pending();
            })
            ->when($status == 'accepted', function ($query) {
                return $query->AccepteByDeliveryman();
            })
            ->when($status == 'processing', function ($query) {
                return $query->Preparing();
            })
            ->when($status == 'item_on_the_way', function ($query) {
                return $query->ItemOnTheWay();
            })
            ->when($status == 'delivered', function ($query) {
                return $query->Delivered();
            })
            ->when($status == 'canceled', function ($query) {
                return $query->Canceled();
            })
            ->when($status == 'failed', function ($query) {
                return $query->failed();
            })
            ->when($status == 'refunded', function ($query) {
                return $query->Refunded();
            })
            ->when($status == 'requested', function ($query) {
                return $query->Refund_requested();
            })
            ->when($status == 'rejected', function ($query) {
                return $query->Refund_request_canceled();
            })
            ->when($status == 'scheduled', function ($query) {
                return $query->Scheduled();
            })
            ->when($status == 'on_going', function ($query) {
                return $query->Ongoing();
            })
            ->when(($status != 'all' && $status != 'scheduled' && $status != 'canceled' && $status != 'rejected' && $status != 'requested' && $status != 'refunded' && $status != 'delivered' && $status != 'failed'), function ($query) {
                return $query->OrderScheduledIn(30);
            })
            ->when(isset($request->vendor), function ($query) use ($request) {
                return $query->whereHas('store', function ($query) use ($request) {
                    return $query->whereIn('id', $request->vendor);
                });
            })
            ->when(isset($request->orderStatus) && $status == 'all', function ($query) use ($request) {
                return $query->whereIn('order_status', $request->orderStatus);
            })
            ->when(isset($request->order_type), function ($query) use ($request) {
                return $query->where('order_type', $request->order_type);
            })
            ->when(isset($request->from_date) && isset($request->to_date) && $request->from_date != null && $request->to_date != null, function ($query) use ($request) {
                return $query->whereBetween('created_at', [$request->from_date . " 00:00:00", $request->to_date . " 23:59:59"]);
            })
            ->when(isset($key), function ($query) use ($key) {
                return $query->where(function ($q) use ($key) {
                    foreach ($key as $value) {
                        $q->orWhere('id', 'like', "%{$value}%")
                            ->orWhere('order_status', 'like', "%{$value}%")
                            ->orWhere('transaction_reference', 'like', "%{$value}%");
                    }
                });
            })
            ->StoreOrder()
            ->NotDigitalOrder()
            ->module(Config::get('module.current_module_id'))
            ->orderBy('schedule_at', 'desc')
            ->paginate(config('default_pagination'));

        $orderstatus = isset($request->orderStatus) ? $request->orderStatus : [];
        $scheduled = isset($request->scheduled) ? $request->scheduled : 0;
        $vendor_ids = isset($request->vendor) ? $request->vendor : [];
        $zone_ids = isset($request->zone) ? $request->zone : [];
        $from_date = isset($request->from_date) ? $request->from_date : null;
        $to_date = isset($request->to_date) ? $request->to_date : null;
        $order_type = isset($request->order_type) ? $request->order_type : null;
        $total = $orders->total();

        // ✅ PERFORMANCE FIX: Get status counts efficiently from database
        // Instead of filtering paginated collection in memory, query database directly
        $base_query = Order::StoreOrder()
            ->NotDigitalOrder()
            ->module(Config::get('module.current_module_id'))
            ->when(isset($module_id), function ($query) use ($module_id) {
                return $query->module($module_id);
            })
            ->when(isset($request->zone), function ($query) use ($request) {
                return $query->whereHas('store', function ($q) use ($request) {
                    return $q->whereIn('zone_id', $request->zone);
                });
            })
            ->when(isset($request->vendor), function ($query) use ($request) {
                return $query->whereHas('store', function ($query) use ($request) {
                    return $query->whereIn('id', $request->vendor);
                });
            })
            ->when(isset($request->from_date) && isset($request->to_date) && $request->from_date != null && $request->to_date != null, function ($query) use ($request) {
                return $query->whereBetween('created_at', [$request->from_date . " 00:00:00", $request->to_date . " 23:59:59"]);
            });

        // Cache status counts for 30 seconds to reduce database load
        // ✅ DEFENSIVE: Use safe method to get request data (handles both Request and stdClass)
        $request_data = ($request instanceof \Illuminate\Http\Request) ? $request->all() : (array)$request;
        $cache_key = 'order_status_counts_' . md5(json_encode($request_data));
        $status_counts = \Cache::remember($cache_key, 30, function() use ($base_query) {
            return [
                'pending' => (clone $base_query)->Pending()->count(),
                'confirmed' => (clone $base_query)->where('order_status', 'confirmed')->count(),
                'processing' => (clone $base_query)->Preparing()->count(),
                'item_on_the_way' => (clone $base_query)->ItemOnTheWay()->count(),
                'delivered' => (clone $base_query)->Delivered()->count(),
                'canceled' => (clone $base_query)->Canceled()->count(),
                'refunded' => (clone $base_query)->Refunded()->count(),
                'failed' => (clone $base_query)->failed()->count(),
            ];
        });

        // Get all admins except super admin (role_id = 1)
        $admins = \App\Models\Admin::where('role_id', '!=', 1)->select('id', 'f_name', 'l_name')->get();

        // ============ START: Cart Feature (ADD THIS SECTION) ============
        try {
            // Get latest carts using raw DB query
            $latest_carts_raw = \DB::table('carts')
                ->whereNotNull('user_id')
                ->where('is_guest', 0)
                ->orderBy('created_at', 'DESC')
                ->limit(50)
                ->get();
            
            // Get unique user IDs (take first 15 users)
            $userIds = $latest_carts_raw->pluck('user_id')->unique()->take(15);
            
            // Load users
            $users = \App\Models\User::whereIn('id', $userIds)->get()->keyBy('id');
            
            // Get all item IDs for these users' carts
            $itemIds = $latest_carts_raw->whereIn('user_id', $userIds)->pluck('item_id')->unique();
            
            // Load items
            $items = \App\Models\Item::whereIn('id', $itemIds)->get()->keyBy('id');
            
            // Group carts by user and limit to 15 users
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

        return view('admin-views.order.list', compact('orders', 'status', 'orderstatus', 'scheduled', 'vendor_ids', 'zone_ids', 'from_date', 'to_date', 'total', 'order_type', 'admins', 'latest_carts', 'users', 'items', 'status_counts'));
    }

    // Add this new method for assigning orders
    public function assignOrder(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'admin_id' => 'nullable|exists:admins,id' // nullable for unassigning
        ]);

        $order = Order::findOrFail($request->order_id);
        $currentAdmin = auth('admin')->user();

        // If order is already assigned, only role IDs 1 and 2 can reassign
        if ($order->assigned_to && !in_array($currentAdmin->role_id, [1, 2])) {
            return response()->json([
                'success' => false,
                'message' => translate('You do not have permission to reassign orders')
            ], 403);
        }

        // If admin_id is empty => Unassign the order (only role IDs 1 and 2 allowed)
        if (empty($request->admin_id)) {
            if (!in_array($currentAdmin->role_id, [1, 2])) {
                return response()->json([
                    'success' => false,
                    'message' => translate('You do not have permission to unassign orders')
                ], 403);
            }

            $order->assigned_to = null;
            $order->save();

            return response()->json([
                'success' => true,
                'message' => translate('Order unassigned successfully'),
                'admin_name' => translate('messages.unassigned')
            ]);
        }

        // Assign the order
        $order->assigned_to = $request->admin_id;
        $order->save();

        $assignedAdmin = \App\Models\Admin::find($request->admin_id);

        return response()->json([
            'success' => true,
            'message' => translate('Order assigned successfully'),
            'admin_name' => $assignedAdmin->f_name . ' ' . $assignedAdmin->l_name
        ]);
    }

    public function dispatch_list($module,$status, Request $request)
    {
        $module_id = $request->query('module_id', null);
        $key = isset($request->search) ?explode(' ', $request->search): ($request['amp;search'] ? explode(' ', $request['amp;search']) : null) ;

        // ✅ FIX: Merge session filters into Request instead of replacing it
        // ✅ ENHANCED: Added validation to prevent stdClass bug
        if (session()->has('order_filter')) {
            try {
                $sessionFilters = json_decode(session('order_filter'), true); // Decode as array
                if (is_array($sessionFilters)) {
                    $request->merge($sessionFilters); // Merge into existing Request object
                } else {
                    session()->forget('order_filter');
                    \Log::warning('Corrupted order_filter session data cleared in dispatch_list');
                }
            } catch (\Exception $e) {
                session()->forget('order_filter');
                \Log::error('Failed to decode order_filter session in dispatch_list', ['error' => $e->getMessage()]);
            }
            $zone_ids = isset($request->zone) ? $request->zone : 0;
        }

        // ✅ DEFENSIVE CHECK: Ensure $request is still a Request object
        if (!($request instanceof \Illuminate\Http\Request)) {
            \Log::critical('Request object replaced in dispatch_list - recreating');
            $request = \Illuminate\Http\Request::createFromGlobals();
        }

        Order::where(['checked' => 0])->update(['checked' => 1]);

        $orders = Order::with(['customer', 'store', 'module'])
            // IMPROVEMENT: Allow viewing orders from all modules when module = 'all'
            ->when($module !== 'all', function($query) use($module){
                return $query->whereHas('module', function($q) use($module){
                    $q->where('id', $module);
                });
            })
            ->when(isset($key), function ($query) use ($key) {
                return $query->where(function ($q) use ($key) {
                    foreach ($key as $value) {
                        $q->orWhere('id', 'like', "%{$value}%")
                            ->orWhere('order_status', 'like', "%{$value}%")
                            ->orWhere('transaction_reference', 'like', "%{$value}%");
                    }
                });
            })
            ->when(isset($module_id), function ($query) use ($module_id) {
                return $query->module($module_id);
            })
            ->when(isset($request->zone), function ($query) use ($request) {
                return $query->whereHas('store', function ($query) use ($request) {
                    return $query->whereIn('zone_id', $request->zone);
                });
            })
            ->when($status == 'searching_for_deliverymen', function ($query) {
                return $query->SearchingForDeliveryman();
            })
            ->when($status == 'on_going', function ($query) {
                return $query->Ongoing();
            })
            ->when(isset($request->vendor), function ($query) use ($request) {
                return $query->whereHas('store', function ($query) use ($request) {
                    return $query->whereIn('id', $request->vendor);
                });
            })
            ->when(isset($request->from_date) && isset($request->to_date) && $request->from_date != null && $request->to_date != null, function ($query) use ($request) {
                return $query->whereBetween('created_at', [$request->from_date . " 00:00:00", $request->to_date . " 23:59:59"]);
            })
            ->StoreOrder()
            ->OrderScheduledIn(30)
            ->orderBy('schedule_at', 'desc')
            ->paginate(config('default_pagination'));

        $orderstatus = isset($request->orderStatus) ? $request->orderStatus : [];
        $scheduled = isset($request->scheduled) ? $request->scheduled : 0;
        $vendor_ids = isset($request->vendor) ? $request->vendor : [];
        $zone_ids = isset($request->zone) ? $request->zone : [];
        $from_date = isset($request->from_date) ? $request->from_date : null;
        $to_date = isset($request->to_date) ? $request->to_date : null;
        $total = $orders->total();

        return view('admin-views.order.distaptch_list', compact('orders','module', 'status', 'orderstatus', 'scheduled', 'vendor_ids', 'zone_ids', 'from_date', 'to_date', 'total'));
    }

   public function details(Request $request, $id)
    {
        $order = Order::with([
            'details',
            'offline_payments',
            'refund',
            'assignedAdmin.role', // Add this line for assigned admin with role
            'store' => function ($query) {
                return $query->withCount('orders');
            },
            'customer' => function ($query) {
                return $query->withCount('orders');
            },
            'delivery_man' => function ($query) {
                return $query->withCount('orders');
            },
            'delivery_man.last_location', // Eager load delivery man's last location
            'details.item' => function ($query) {
                return $query->withoutGlobalScope(StoreScope::class);
            },
            'details.campaign' => function ($query) {
                return $query->withoutGlobalScope(StoreScope::class);
            }
        ])->where(['id' => $id])->first();

        if (!$order) {
            Toastr::info(translate('messages.no_more_orders'));
            return back();
        }

        if ($order->order_type == 'parcel') {
            return to_route('admin.parcel.order.details', $id);
        }

        // Store coordinates for distance calculation
        $storeCoords = null;
        if (isset($order->store) && $order->store->latitude && $order->store->longitude) {
            $storeCoords = [
                'lat' => $order->store->latitude,
                'lng' => $order->store->longitude
            ];
        }

        // Delivery man logic - IN-ZONE deliverymen (priority list)
        $inZoneDeliveryMen = [];
        $outOfZoneDeliveryMen = [];

        $dmEagerLoads = ['rating', 'total_delivered_orders', 'total_ongoing_orders', 'wallet', 'vehicle', 'zone', 'last_location',
            'orders' => function ($q) {
                $q->whereIn('order_status', ['pending', 'accepted', 'confirmed', 'processing', 'handover', 'picked_up'])
                  ->with('store:id,name,latitude,longitude')
                  ->select('id', 'delivery_man_id', 'store_id', 'order_status', 'delivery_address');
            }
        ];

        if (isset($order->store) && $order->store) {
            $inZoneDeliveryMen = DeliveryMan::where('zone_id', $order->store->zone_id)
                ->where(function ($query) use ($order) {
                    $query->where('vehicle_id', $order->dm_vehicle_id)
                        ->orWhereNull('vehicle_id');
                })
                ->with($dmEagerLoads)
                ->available()
                ->active()
                ->get();

            // OUT-OF-ZONE deliverymen (secondary list, capped to avoid huge queries)
            $outOfZoneDeliveryMen = DeliveryMan::where('zone_id', '!=', $order->store->zone_id)
                ->with($dmEagerLoads)
                ->available()
                ->active()
                ->limit(30)
                ->get();
        } elseif ($order->zone_id) {
            // Parcel orders: no store but have a zone_id
            $inZoneDeliveryMen = DeliveryMan::where('zone_id', $order->zone_id)
                ->where(function ($query) use ($order) {
                    $query->where('vehicle_id', $order->dm_vehicle_id)
                        ->orWhereNull('vehicle_id');
                })
                ->with($dmEagerLoads)
                ->available()
                ->active()
                ->get();

            $outOfZoneDeliveryMen = DeliveryMan::where('zone_id', '!=', $order->zone_id)
                ->with($dmEagerLoads)
                ->available()
                ->active()
                ->limit(30)
                ->get();
        }

        // Get all active zones for zone change dropdown
        $zones = \App\Models\Zone::where('status', 1)->get(['id', 'name']);

        $category = $request->query('category_id', 0);
        $categories = Category::active()->get();
        $keyword = $request->query('keyword', false);
        $key = explode(' ', $keyword);

        $products = Item::withoutGlobalScope(StoreScope::class)
            ->where('store_id', $order->store_id)
            ->when($category, function ($query) use ($category) {
                $query->whereHas('category', function ($q) use ($category) {
                    return $q->whereId($category)
                            ->orWhere('parent_id', $category);
                });
            })
            ->when($keyword, function ($query) use ($key) {
                return $query->where(function ($q) use ($key) {
                    foreach ($key as $value) {
                        $q->orWhere('name', 'like', "%{$value}%");
                    }
                });
            })
            ->latest()
            ->paginate(10);

        $editing = false;
        if ($request->session()->has('order_cart')) {
            $cart = session()->get('order_cart');

            // Check if we have an editing session marker for this order (handles empty cart case)
            $editingOrderId = session()->get('editing_order_id');
            if ($editingOrderId !== null && (int)$editingOrderId === (int)$order->id) {
                $editing = true;
            } elseif ($cart instanceof \Illuminate\Support\Collection && $cart->isNotEmpty()) {
                $firstItem = $cart->first();
                $cartOrderId = null;
                if (is_object($firstItem)) {
                    $cartOrderId = $firstItem->order_id ?? ($firstItem['order_id'] ?? null);
                } elseif (is_array($firstItem)) {
                    $cartOrderId = $firstItem['order_id'] ?? null;
                }
                if ($cartOrderId !== null && (int)$cartOrderId === (int)$order->id) {
                    $editing = true;
                } else {
                    session()->forget('order_cart');
                    session()->forget('editing_order_id');
                }
            } elseif (is_array($cart) && !empty($cart)) {
                $firstItem = reset($cart);
                $cartOrderId = null;
                if (is_object($firstItem)) {
                    $cartOrderId = $firstItem->order_id ?? null;
                } elseif (is_array($firstItem)) {
                    $cartOrderId = $firstItem['order_id'] ?? null;
                }
                if ($cartOrderId !== null && (int)$cartOrderId === (int)$order->id) {
                    $editing = true;
                } else {
                    session()->forget('order_cart');
                    session()->forget('editing_order_id');
                }
            } else {
                // Empty cart but check if editing_order_id matches
                if ($editingOrderId === null || (int)$editingOrderId !== (int)$order->id) {
                    session()->forget('order_cart');
                    session()->forget('editing_order_id');
                } else {
                    $editing = true;
                }
            }
        }

        // Current order's customer coordinates for same-route detection
        $currentOrderCoords = null;
        if (!empty($order->delivery_address)) {
            $delAddr = is_string($order->delivery_address) ? json_decode($order->delivery_address, true) : $order->delivery_address;
            if (!empty($delAddr['latitude']) && !empty($delAddr['longitude'])) {
                $currentOrderCoords = [
                    'lat' => (float)$delAddr['latitude'],
                    'lng' => (float)$delAddr['longitude'],
                ];
            }
        }

        // Format with distance and sort by nearest first
        $inZoneDeliveryMen = Helpers::deliverymen_list_formatting($inZoneDeliveryMen, $storeCoords, $currentOrderCoords);
        $inZoneDeliveryMen = collect($inZoneDeliveryMen)->sortBy('distance')->values()->all();

        $outOfZoneDeliveryMen = Helpers::deliverymen_list_formatting($outOfZoneDeliveryMen, $storeCoords, $currentOrderCoords);
        $outOfZoneDeliveryMen = collect($outOfZoneDeliveryMen)->sortBy('distance')->values()->all();

        // Keep backward compatibility with $deliveryMen variable
        $deliveryMen = $inZoneDeliveryMen;

        // Other stores in the same zone for outside purchase dropdown
        $storesInZone = collect();
        if (isset($order->store) && $order->store) {
            $storesInZone = Store::where('zone_id', $order->store->zone_id)
                ->where('id', '!=', $order->store_id)
                ->active()
                ->get(['id', 'name']);
        }

        // ✅ Load transaction with edit history for audit display
        $transaction = \App\Models\OrderTransaction::where('order_id', $order->id)->first();
        $editHistory = [];
        if ($transaction && $transaction->is_edited && $transaction->edit_history) {
            $editHistory = json_decode($transaction->edit_history, true) ?? [];
        }

        return view('admin-views.order.order-view', compact(
            'order',
            'deliveryMen',
            'inZoneDeliveryMen',
            'outOfZoneDeliveryMen',
            'zones',
            'storeCoords',
            'storesInZone',
            'categories',
            'products',
            'category',
            'keyword',
            'editing',
            'transaction',
            'editHistory'
        ));
    }

    public function updateItemMrp(Request $request)
    {
        $request->validate([
            'item_id' => 'required|integer',
            'mrp' => 'required|numeric|min:0',
            'order_id' => 'required|integer'
        ]);

        try {
            $item = Item::find($request->item_id);
            if (!$item) {
                return response()->json(['success' => false, 'message' => translate('messages.item_not_found')]);
            }

            $order = Order::find($request->order_id);
            if (!$order) {
                return response()->json(['success' => false, 'message' => translate('messages.order_not_found')]);
            }

            // Update Item master price
            $item->price = $request->mrp;
            $item->save();

            // Update OrderDetail - use order_detail_id if provided for precision
            if ($request->has('order_detail_id')) {
                $orderDetail = OrderDetail::find($request->order_detail_id);
                \Log::info('MRP Update: Using order_detail_id', ['order_detail_id' => $request->order_detail_id, 'found' => $orderDetail ? 'yes' : 'no']);
            } else {
                $orderDetail = OrderDetail::where('order_id', $request->order_id)
                    ->where('item_id', $request->item_id)
                    ->first();
                \Log::info('MRP Update: Using item_id search', ['order_id' => $request->order_id, 'item_id' => $request->item_id, 'found' => $orderDetail ? 'yes' : 'no']);
            }

            if ($orderDetail) {
                $oldPrice = $orderDetail->price;
                $newPrice = $request->mrp;

                \Log::info('MRP Update: Before save', ['order_detail_id' => $orderDetail->id, 'old_price' => $oldPrice, 'new_price' => $newPrice]);

                $orderDetail->price = $newPrice;
                $result = $orderDetail->save();

                \Log::info('MRP Update: After save', ['save_result' => $result, 'price_in_db' => $orderDetail->fresh()->price]);

                // ✅ Adjust order amount by difference
                $diff = ($newPrice - $oldPrice) * $orderDetail->quantity;

                $order->order_amount = $order->order_amount + $diff;

                // 🔑 Ensure changes persist
                $order->save();

                // 🔥 CRITICAL FIX: Update session cart so Save doesn't overwrite with old price
                $cart = $request->session()->get('order_cart');
                if ($cart) {
                    $cart = collect($cart);
                    foreach ($cart as $key => $item) {
                        if (isset($item['id']) && $item['id'] == $orderDetail->id) {
                            $cart[$key]['price'] = $newPrice;
                            break;
                        }
                    }
                    $request->session()->put('order_cart', $cart);
                    \Log::info('MRP Update: Session cart updated', ['order_detail_id' => $orderDetail->id, 'new_price' => $newPrice]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => translate('messages.mrp_updated_successfully'),
                'new_total' => $order->order_amount
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => translate('messages.something_went_wrong')]);
        }
    }


    public function updateCampaignMrp(Request $request)
    {
        $request->validate([
            'campaign_id' => 'required|integer',
            'mrp' => 'required|numeric|min:0',
            'order_id' => 'required|integer'
        ]);

        try {
            $campaign = ItemCampaign::find($request->campaign_id);
            if (!$campaign) {
                return response()->json(['success' => false, 'message' => translate('messages.campaign_not_found')]);
            }

            $order = Order::find($request->order_id);
            if (!$order) {
                return response()->json(['success' => false, 'message' => translate('messages.order_not_found')]);
            }

            // Update Campaign master price
            $campaign->price = $request->mrp;
            $campaign->save();

            // Update OrderDetail - use order_detail_id if provided for precision
            if ($request->has('order_detail_id')) {
                $orderDetail = OrderDetail::find($request->order_detail_id);
            } else {
                $orderDetail = OrderDetail::where('order_id', $request->order_id)
                    ->where('item_campaign_id', $request->campaign_id)
                    ->first();
            }

            if ($orderDetail) {
                $oldPrice = $orderDetail->price;
                $newPrice = $request->mrp;

                $orderDetail->price = $newPrice;
                $orderDetail->save();

                // ✅ Adjust order total
                $diff = ($newPrice - $oldPrice) * $orderDetail->quantity;

                $order->order_amount = $order->order_amount + $diff;

                // 🔑 Save to DB
                $order->save();

                // 🔥 CRITICAL FIX: Update session cart so Save doesn't overwrite with old price
                $cart = $request->session()->get('order_cart');
                if ($cart) {
                    $cart = collect($cart);
                    foreach ($cart as $key => $item) {
                        if (isset($item['id']) && $item['id'] == $orderDetail->id) {
                            $cart[$key]['price'] = $newPrice;
                            break;
                        }
                    }
                    $request->session()->put('order_cart', $cart);
                }
            }

            return response()->json([
                'success' => true,
                'message' => translate('messages.campaign_mrp_updated_successfully'),
                'new_total' => $order->order_amount
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => translate('messages.something_went_wrong')]);
        }
    }

    public function approveMrpRequest(Request $request)
    {
        $request->validate([
            'order_detail_id' => 'required|integer',
            'action' => 'required|in:approved,rejected',
        ]);

        try {
            $orderDetail = OrderDetail::find($request->order_detail_id);
            if (!$orderDetail || $orderDetail->mrp_update_status !== 'pending') {
                return response()->json(['success' => false, 'message' => translate('messages.no_pending_mrp_request')]);
            }

            $order = Order::find($orderDetail->order_id);
            if (!$order) {
                return response()->json(['success' => false, 'message' => translate('messages.order_not_found')]);
            }

            if ($request->action === 'approved') {
                $oldPrice = $orderDetail->price;
                $newPrice = $orderDetail->requested_mrp;

                // Update order detail price
                $orderDetail->price = $newPrice;
                $orderDetail->mrp_update_status = 'approved';
                $orderDetail->save();

                // Update master item price
                if ($orderDetail->item_id) {
                    $item = Item::find($orderDetail->item_id);
                    if ($item) {
                        $item->price = $newPrice;
                        $item->save();
                    }
                } elseif ($orderDetail->item_campaign_id) {
                    $campaign = ItemCampaign::find($orderDetail->item_campaign_id);
                    if ($campaign) {
                        $campaign->price = $newPrice;
                        $campaign->save();
                    }
                }

                // Adjust order total
                $diff = ($newPrice - $oldPrice) * $orderDetail->quantity;
                $order->order_amount = $order->order_amount + $diff;
                $order->save();

                return response()->json([
                    'success' => true,
                    'message' => translate('messages.mrp_request_approved'),
                    'new_price' => $newPrice,
                    'new_total' => $order->order_amount,
                ]);
            } else {
                $orderDetail->mrp_update_status = 'rejected';
                $orderDetail->save();

                return response()->json([
                    'success' => true,
                    'message' => translate('messages.mrp_request_rejected'),
                ]);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => translate('messages.something_went_wrong')]);
        }
    }

    /**
     * Scan bill image using AI and verify against order
     */
    public function scanBill($id)
    {
        try {
            $order = Order::with(['details.item', 'details.campaign', 'store'])->find($id);

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => translate('messages.order_not_found')
                ]);
            }

            // Check if bill image exists
            $billData = is_array($order->bill_image)
                ? $order->bill_image
                : (is_string($order->bill_image) ? json_decode($order->bill_image, true) : []);

            if (empty($billData)) {
                return response()->json([
                    'success' => false,
                    'message' => translate('messages.no_bill_image_found')
                ]);
            }

            // Use BillScanService to scan and verify
            $billScanService = new \App\Services\BillScanService();
            $result = $billScanService->scanBill($order);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['error'] ?? translate('messages.bill_scan_failed')
                ]);
            }

            // Save scan result to order
            $order->bill_scan_result = $result;
            $order->save();

            return response()->json([
                'success' => true,
                'message' => translate('messages.bill_scanned_successfully'),
                'data' => $result
            ]);

        } catch (\Exception $e) {
            \Log::error('Bill scan error', ['order_id' => $id, 'error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => translate('messages.something_went_wrong') . ': ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Mark item as out of stock and remove from order
     */

    public function markItemOutOfStock(Request $request)
    {
        $request->validate([
            'item_id' => 'required|integer',
            'order_detail_id' => 'required|integer',
            'order_id' => 'required|integer',
            'key' => 'required|integer'
        ]);

        try {
            DB::beginTransaction();

            // Update item stock status
            $item = Item::find($request->item_id);
            if (!$item) {
                return response()->json([
                    'success' => false,
                    'message' => translate('messages.item_not_found')
                ]);
            }

            // Mark item as out of stock
            $item->stock = 0;
            $item->save();

            // Get order detail
            $orderDetail = OrderDetail::find($request->order_detail_id);
            if (!$orderDetail) {
                return response()->json([
                    'success' => false,
                    'message' => translate('messages.order_detail_not_found')
                ]);
            }

            // Calculate refund amount
            $refundAmount = $orderDetail->price * $orderDetail->quantity;
            
            // Update order total
            $order = Order::find($request->order_id);
            if ($order) {
                $order->order_amount = max(0, $order->order_amount - $refundAmount);
                
                // Also update tax and discount if needed
                
                $order->store_discount_amount = max(0, $order->store_discount_amount - ($orderDetail->discount_on_item * $orderDetail->quantity));
                
                $order->save();
            }

            // Soft delete the order detail first
            $orderDetail->delete();

            // 🔧 FIX: Clear session cart and reload from database to prevent key mismatch issues
            // Instead of trying to update session indexes (which causes bugs), just clear and reload
            if (session()->has('order_cart')) {
                session()->forget('order_cart');

                // Reload remaining order details into session
                $remainingDetails = OrderDetail::where('order_id', $request->order_id)
                    ->with(['item', 'campaign'])
                    ->get();

                if ($remainingDetails->count() > 0) {
                    $newCart = [];
                    foreach ($remainingDetails as $detail) {
                        if ($detail->item_id) {
                            $detail->item = $detail->item; // Ensure item is loaded
                        } elseif ($detail->campaign_id) {
                            $detail->campaign = $detail->campaign; // Ensure campaign is loaded
                        }
                        $detail->status = true;
                        $newCart[] = $detail;
                    }
                    session()->put('order_cart', $newCart);

                    // Also update database session
                    $adminId = auth('admin')->id();
                    OrderEditSession::updateOrCreate(
                        [
                            'order_id' => $request->order_id,
                            'admin_id' => $adminId
                        ],
                        [
                            'cart_data' => $newCart,
                            'updated_at' => now()
                        ]
                    );
                } else {
                    // No items left - clear everything but keep editing marker
                    session()->forget('order_cart');
                    session()->put('editing_order_id', $request->order_id);

                    // Clear database session
                    $adminId = auth('admin')->id();
                    OrderEditSession::where('order_id', $request->order_id)
                        ->where('admin_id', $adminId)
                        ->delete();
                }
            }

            DB::commit();

            // Check if order has any remaining items
            $hasRemainingItems = OrderDetail::where('order_id', $request->order_id)->exists();

            return response()->json([
                'success' => true,
                'message' => translate('messages.item_marked_out_of_stock_and_removed'),
                'refund_amount' => $refundAmount,
                'reload_required' => $hasRemainingItems, // Only reload if items remain
                'redirect_to_list' => !$hasRemainingItems, // Redirect to list if no items left
                'redirect_url' => !$hasRemainingItems ? route('admin.order.list', ['status' => 'all']) : null
            ]);

        } catch (Exception $e) {
            DB::rollback();
            \Log::error('Mark item out of stock error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => translate('messages.something_went_wrong'),
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Mark campaign as out of stock and remove from order
     */
    public function markCampaignOutOfStock(Request $request)
    {
        $request->validate([
            'campaign_id' => 'required|integer',
            'order_detail_id' => 'required|integer',
            'order_id' => 'required|integer',
            'key' => 'required|integer'
        ]);

        try {
            DB::beginTransaction();

            // Update campaign stock status
            $campaign = ItemCampaign::find($request->campaign_id);
            if (!$campaign) {
                return response()->json([
                    'success' => false,
                    'message' => translate('messages.campaign_not_found')
                ]);
            }

            // Mark campaign as out of stock
            $campaign->stock = 0;
            $campaign->save();

            // Get order detail
            $orderDetail = OrderDetail::find($request->order_detail_id);
            if (!$orderDetail) {
                return response()->json([
                    'success' => false,
                    'message' => translate('messages.order_detail_not_found')
                ]);
            }

            // Calculate refund amount
            $refundAmount = $orderDetail->price * $orderDetail->quantity;
            
            // Update order total
            $order = Order::find($request->order_id);
            if ($order) {
                $order->order_amount = max(0, $order->order_amount - $refundAmount);
                
                // Also update tax and discount if needed
                $order->tax_amount = max(0, $order->tax_amount - ($orderDetail->tax_amount * $orderDetail->quantity));
                $order->store_discount_amount = max(0, $order->store_discount_amount - ($orderDetail->discount_on_item * $orderDetail->quantity));
                
                $order->save();
            }

            // Soft delete the order detail first
            $orderDetail->delete();

            // 🔧 FIX: Clear session cart and reload from database to prevent key mismatch issues
            // Instead of trying to update session indexes (which causes bugs), just clear and reload
            if (session()->has('order_cart')) {
                session()->forget('order_cart');

                // Reload remaining order details into session
                $remainingDetails = OrderDetail::where('order_id', $request->order_id)
                    ->with(['item', 'campaign'])
                    ->get();

                if ($remainingDetails->count() > 0) {
                    $newCart = [];
                    foreach ($remainingDetails as $detail) {
                        if ($detail->item_id) {
                            $detail->item = $detail->item; // Ensure item is loaded
                        } elseif ($detail->campaign_id) {
                            $detail->campaign = $detail->campaign; // Ensure campaign is loaded
                        }
                        $detail->status = true;
                        $newCart[] = $detail;
                    }
                    session()->put('order_cart', $newCart);

                    // Also update database session
                    $adminId = auth('admin')->id();
                    OrderEditSession::updateOrCreate(
                        [
                            'order_id' => $request->order_id,
                            'admin_id' => $adminId
                        ],
                        [
                            'cart_data' => $newCart,
                            'updated_at' => now()
                        ]
                    );
                } else {
                    // No items left - clear everything but keep editing marker
                    session()->forget('order_cart');
                    session()->put('editing_order_id', $request->order_id);

                    // Clear database session
                    $adminId = auth('admin')->id();
                    OrderEditSession::where('order_id', $request->order_id)
                        ->where('admin_id', $adminId)
                        ->delete();
                }
            }

            DB::commit();

            // Check if order has any remaining items
            $hasRemainingItems = OrderDetail::where('order_id', $request->order_id)->exists();

            return response()->json([
                'success' => true,
                'message' => translate('messages.campaign_marked_out_of_stock_and_removed'),
                'refund_amount' => $refundAmount,
                'reload_required' => $hasRemainingItems, // Only reload if items remain
                'redirect_to_list' => !$hasRemainingItems, // Redirect to list if no items left
                'redirect_url' => !$hasRemainingItems ? route('admin.order.list', ['status' => 'all']) : null
            ]);

        } catch (Exception $e) {
            DB::rollback();
            \Log::error('Mark campaign out of stock error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => translate('messages.something_went_wrong'),
                'error' => $e->getMessage()
            ]);
        }
    }
    public function all_details(Request $request, $id)
    {
        $order = Order::with(['details','offline_payments' ,'refund', 'store' => function ($query) {
            return $query->withCount('orders');
        }, 'customer' => function ($query) {
            return $query->withCount('orders');
        }, 'delivery_man' => function ($query) {
            return $query->withCount('orders');
        }, 'details.item' => function ($query) {
            return $query->withoutGlobalScope(StoreScope::class);
        }, 'details.campaign' => function ($query) {
            return $query->withoutGlobalScope(StoreScope::class);
        }])->where(['id' => $id])->first();
        if (isset($order)) {
            if (isset($order->store)) {
                $deliveryMen = DeliveryMan::where('zone_id', $order->store->zone_id)
                    ->with(['rating', 'total_delivered_orders', 'total_ongoing_orders', 'wallet', 'vehicle'])
                    ->available()
                    ->active()
                    ->get();
            } else {
                $deliveryMen = isset($order->zone_id) ? DeliveryMan::where('zone_id', $order->zone_id)
                    ->with(['rating', 'total_delivered_orders', 'total_ongoing_orders', 'wallet', 'vehicle'])
                    ->zonewise()
                    ->available()
                    ->active()
                    ->get() : [];
            }
            $category = $request->query('category_id', 0);
            // $sub_category = $request->query('sub_category', 0);
            $categories = Category::active()->get();
            $keyword = $request->query('keyword', false);
            $key = explode(' ', $keyword);
            $products = Item::withoutGlobalScope(StoreScope::class)->where('store_id', $order->store_id)
                ->when($category, function ($query) use ($category) {
                    $query->whereHas('category', function ($q) use ($category) {
                        return $q->whereId($category)->orWhere('parent_id', $category);
                    });
                })
                ->when($keyword, function ($query) use ($key) {
                    return $query->where(function ($q) use ($key) {
                        foreach ($key as $value) {
                            $q->orWhere('name', 'like', "%{$value}%");
                        }
                    });
                })
                ->latest()->paginate(10);
            $editing = false;
            if ($request->session()->has('order_cart')) {
                $cart = session()->get('order_cart');
                $editingOrderId = session()->get('editing_order_id');
                if ($editingOrderId !== null && (int)$editingOrderId === (int)$order->id) {
                    $editing = true;
                } elseif ($cart && count($cart) > 0 && isset($cart[0]->order_id) && $cart[0]->order_id == $order->id) {
                    $editing = true;
                } else {
                    session()->forget('order_cart');
                    session()->forget('editing_order_id');
                }
            }

            $deliveryMen = Helpers::deliverymen_list_formatting($deliveryMen);
            return view('admin-views.order.order-view', compact('order', 'deliveryMen', 'categories', 'products', 'category', 'keyword', 'editing'));
        } else {
            Toastr::info(translate('messages.no_more_orders'));
            return back();
        }
    }

    public function search(Request $request)
    {
        $key = explode(' ', $request['search']);
        $parcel_order = $request->parcel_order ?? false;
        $module_section_type = $request->module_section_type ?? false;
        $orders = Order::where(function ($q) use ($key) {
            foreach ($key as $value) {
                $q->orWhere('id', 'like', "%{$value}%")
                    ->orWhere('order_status', 'like', "%{$value}%")
                    ->orWhere('transaction_reference', 'like', "%{$value}%");
            }
        })->module(Config::get('module.current_module_id'));
        if ($module_section_type) {
            $orders = $orders->module($module_section_type);
        }
        if ($parcel_order) {
            $orders = $orders->withOutGlobalScope(ZoneScope::class)->ParcelOrder();
        } else {
            $orders = $orders->StoreOrder();
        }
        $orders = $orders->limit(50)->get();

        return response()->json([
            'view' => view('admin-views.order.partials._table', compact('orders', 'parcel_order'))->render()
        ]);
    }

    public function status(Request $request)
    {
        $request->validate([
            'reason'=>'required_if:order_status,canceled'
        ]);

        $order = Order::with(['details', 'store' => function ($query) {
            return $query->withCount('orders');
        }, 'details.item' => function ($query) {
            return $query->withoutGlobalScope(StoreScope::class);
        }, 'details.campaign' => function ($query) {
            return $query->withoutGlobalScope(StoreScope::class);
        }])->withOutGlobalScope(ZoneScope::class)->find($request->id);

        if(!$order || (!$order->store && $order->order_type !='parcel') ){
            Toastr::warning(translate('messages.you_can_not_change_the_status_of_this_order'));
            return back();
        }

        if (in_array($order->order_status, ['refunded', 'failed'])) {
            Toastr::warning(translate('messages.you_can_not_change_the_status_of_a_completed_order'));
            return back();
        }
        if (in_array($order->order_status, ['refund_requested']) && BusinessSetting::where(['key' => 'refund_active_status'])->first()->value == false) {
            Toastr::warning(translate('Refund Option is not active. Please active it from Refund Settings'));
            return back();
        }

        if ($order['delivery_man_id'] == null && $request->order_status == 'out_for_delivery') {
            Toastr::warning(translate('messages.please_assign_deliveryman_first'));
            return back();
        }

        if ($request->order_status == 'delivered' && $order['transaction_reference'] == null && $order['payment_method'] != 'cash_on_delivery') {
            Toastr::warning(translate('messages.add_your_paymen_ref_first'));
            return back();
        }

        if ($request->order_status == 'delivered') {

            if ($order->transaction  == null) {
                $unpaid_payment = OrderPayment::where('payment_status','unpaid')->where('order_id',$order->id)->first()?->payment_method;
                $unpaid_pay_method = 'digital_payment';
                if($unpaid_payment){
                    $unpaid_pay_method = $unpaid_payment;
                }
                if ($order->payment_method == "cash_on_delivery" || $unpaid_pay_method == 'cash_on_delivery') {
                    if ($order->order_type == 'take_away') {
                        $ol = OrderLogic::create_transaction($order, 'store', null);
                    } else if ($order->delivery_man_id) {
                        $ol =  OrderLogic::create_transaction($order, 'deliveryman', null);
                    } else if ($order->user_id) {
                        $ol =  OrderLogic::create_transaction($order, false, null);
                    }
                } else {
                    $ol = OrderLogic::create_transaction($order, 'admin', null);
                }
                if (!$ol) {
                    Toastr::warning(translate('messages.faield_to_create_order_transaction'));
                    return back();
                }
            } else if ($order->delivery_man_id) {
                $order->transaction->update(['delivery_man_id' => $order->delivery_man_id]);
            }

            $order->payment_status = 'paid';
            if ($order->delivery_man) {
                $dm = $order->delivery_man;
                $dm->increment('order_count');
                $dm->current_orders = $dm->current_orders > 1 ? $dm->current_orders - 1 : 0;
                $dm->save();
            }
            $order->details->each(function ($item, $key) {
                if ($item->item) {
                    $item->item->increment('order_count');
                }
            });
            $order?->customer?->increment('order_count');
            if ($order->store) {
                $order->store->increment('order_count');
            }
            if ($order->parcel_category) {
                $order->parcel_category->increment('orders_count');
            }

            OrderLogic::update_unpaid_order_payment(order_id:$order->id, payment_method:$order->payment_method);

        } else if ($request->order_status == 'refunded' && BusinessSetting::where('key', 'refund_active_status')->first()->value == 1) {
            if ($order->payment_status == "unpaid") {
                Toastr::warning(translate('messages.you_can_not_refund_a_cod_order'));
                return back();
            }
            if (isset($order->delivered)) {
                $rt = OrderLogic::refund_order($order);
                if (!$rt) {
                    Toastr::warning(translate('messages.faield_to_create_order_transaction'));
                    return back();
                }
            }
            $refund_method = $request->refund_method  ?? 'manual';
            $wallet_status = BusinessSetting::where('key', 'wallet_status')->first()->value;
            $refund_to_wallet = BusinessSetting::where('key', 'wallet_add_refund')->first()->value;
            if ($order->payment_status == "paid" && $wallet_status == 1 && $refund_to_wallet == 1) {
                $refund_amount = round($order->order_amount - $order->delivery_charge - $order->dm_tips, config('round_up_to_digit'));
                CustomerLogic::create_wallet_transaction($order->user_id, $refund_amount, 'order_refund', $order->id);
                Toastr::info(translate('Refunded amount added to customer wallet'));
                $refund_method = 'wallet';
            } else {
                Toastr::warning(translate('Customer Wallet Refund is not active.Plase Manage the Refund Amount Manually'));
                $refund_method = $request->refund_method  ?? 'manual';
            }
            Refund::where('order_id', $order->id)->update([
                'order_status' => 'refunded',
                'admin_note' => $request->admin_note ?? null,
                'refund_status' => 'approved',
                'refund_method' => $refund_method,
            ]);
            $order?->store ?   Helpers::increment_order_count($order?->store) : '';

            if ($order->delivery_man) {
                $dm = $order->delivery_man;
                $dm->current_orders = $dm->current_orders > 1 ? $dm->current_orders - 1 : 0;
                $dm->save();
            }

            try {


                if(Helpers::getNotificationStatusData('customer','customer_refund_request_approval','push_notification_status') && $order?->customer?->cm_firebase_token){
                    $data = [
                        'title' => translate('messages.order_refunded'),
                        'description' => translate('messages.Your_refund_request_has_been_approved'),
                        'order_id' => $order->id,
                        'image' => '',
                        'type' => 'order_status',
                        'order_status' => $order->order_status,
                    ];
                    Helpers::send_push_notif_to_device($order?->customer?->cm_firebase_token, $data);
                    DB::table('user_notifications')->insert([
                        'data' => json_encode($data),
                        'user_id' => $order->user_id,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }



                if(config('mail.status') && $order?->customer?->email && Helpers::get_mail_status('refund_order_mail_status_user') == '1'  &&  Helpers::getNotificationStatusData('customer','customer_refund_request_approval','mail_status') ){
                    Mail::to($order->customer->email)->send(new \App\Mail\RefundedOrderMail($order->id));
                }
            } catch (\Throwable $th) {
                info($th->getMessage());
                Toastr::error(translate('messages.Failed_to_send_mail'));
            }
        } else if ($request->order_status == 'canceled') {
            if (in_array($order->order_status, ['delivered', 'canceled', 'refund_requested', 'refunded', 'failed'])) {
                Toastr::warning(translate('messages.you_can_not_cancel_a_completed_order'));
                return back();
            }
            $order->cancellation_reason = $request->reason;
            $order->canceled_by = 'admin';

            // Process DM compensation if DM is at location
            if ($order->delivery_man_id) {
                $compensation = \App\CentralLogics\OrderLogic::process_dm_cancellation_compensation($order);
                if ($compensation > 0) {
                    Toastr::info(translate('messages.dm_compensation_paid') . ': ' . \App\CentralLogics\Helpers::format_currency($compensation));
                }
            }

            $order?->store ?   Helpers::increment_order_count($order?->store) : '';

            if (config('module.' . $order->module->module_type)['stock']) {
                foreach ($order->details as $detail) {
                    $variant = json_decode($detail['variation'], true);
                    $item = $detail->item;
                    if ($detail->campaign) {
                        $item = $detail->campaign;
                    }
                    ProductLogic::update_stock($item, -$detail->quantity, count($variant) ? $variant[0]['type'] : null)->save();
                }
            }
            if ($order->delivery_man) {
                $dm = $order->delivery_man;
                $dm->current_orders = $dm->current_orders > 1 ? $dm->current_orders - 1 : 0;
                $dm->save();
            }
            if($order->is_guest == 0){

                OrderLogic::refund_before_delivered($order);
            }
        }
        $order->order_status = $request->order_status;
        if($request->order_status == 'processing') {
            $order->processing_time = ($request?->processing_time) ? $request->processing_time : explode('-', $order['store']['delivery_time'])[0];
        }
        $order[$request->order_status] = now();
        $order->save();

        if (!Helpers::send_order_notification($order)) {
            Toastr::warning(translate('messages.push_notification_faild'));
        }

        Toastr::success(translate('messages.order_status_updated'));
        return back();
    }

    public function add_delivery_man($order_id, $delivery_man_id)
    {
        if ($delivery_man_id == 0) {
            return response()->json(['message'=> translate('messages.deliveryman_not_found')  ], 400);
        }
        $order = Order::withOutGlobalScope(ZoneScope::class)->find($order_id);

        $deliveryman = DeliveryMan::where('id', $delivery_man_id)->available()->active()->first();
        if ($order->delivery_man_id == $delivery_man_id) {
            return response()->json(['message'=> translate('messages.order_already_assign_to_this_deliveryman')  ], 400);
        }
        if ($deliveryman) {
            if ($deliveryman->current_orders >= config('dm_maximum_orders')) {
                return response()->json(['message'=> translate('messages.dm_maximum_order_exceed_warning')  ], 400);
            }

            $payments = $order->payments()->where('payment_method','cash_on_delivery')->exists();
            $cash_in_hand = $deliveryman?->wallet?->collected_cash ?? 0;
            $dm_max_cash=BusinessSetting::where('key','dm_max_cash_in_hand')->first();
            $value=  $dm_max_cash?->value ?? 0;

            if(($order->payment_method == "cash_on_delivery" || $payments) && (($cash_in_hand+$order->order_amount) >= $value)){
                return response()->json(['message'=> \App\CentralLogics\Helpers::format_currency($value) ." ".translate('max_cash_in_hand_exceeds')  ], 400);
            }

            if ($order->delivery_man) {
                $dm = $order->delivery_man;
                $dm->current_orders = $dm->current_orders > 1 ? $dm->current_orders - 1 : 0;
                $dm->save();
                if (Helpers::getNotificationStatusData('deliveryman','deliveryman_order_assign_unassign','push_notification_status')) {
                    $data = [
                        'title' => translate('Order_Notification'),
                        'description' => translate('messages.you_are_unassigned_from_a_order'),
                        'order_id' => '',
                        'image' => '',
                        'type' => 'unassign'
                    ];
                    Helpers::send_push_notif_to_device($dm->fcm_token, $data);

                    DB::table('user_notifications')->insert([
                        'data' => json_encode($data),
                        'delivery_man_id' => $dm->id,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }

            }
            $order->delivery_man_id = $delivery_man_id;
            $order->order_status = in_array($order->order_status, ['pending', 'confirmed']) ? 'accepted' : $order->order_status;
            $order->accepted = now();
            $order->save();

            $deliveryman->current_orders = $deliveryman->current_orders + 1;
            $deliveryman->save();
            $deliveryman->increment('assigned_order_count');

            $fcm_token= $order->is_guest == 0 ? $order?->customer?->cm_firebase_token : $order?->guest?->fcm_token;
            $value = Helpers::order_status_update_message('accepted',$order->module->module_type,$order->customer?
            $order?->customer?->current_language_key:'en');
            $value = Helpers::text_variable_data_format(value:$value,store_name:$order->store?->name,order_id:$order->id,user_name:"{$order?->customer?->f_name} {$order?->customer?->l_name}",delivery_man_name:"{$order->delivery_man?->f_name} {$order->delivery_man?->l_name}");
            try {
                if ($value  && Helpers::getNotificationStatusData('customer','customer_order_notification','push_notification_status') && $fcm_token ) {
                    $data = [
                        'title' => translate('Order_Notification'),
                        'description' => $value,
                        'order_id' => $order['id'],
                        'image' => '',
                        'type' => 'order_status'
                    ];
                        Helpers::send_push_notif_to_device($fcm_token, $data);
                        DB::table('user_notifications')->insert([
                            'data' => json_encode($data),
                            'user_id' => $order?->customer?->id ,
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                }

                if(Helpers::getNotificationStatusData('deliveryman','deliveryman_order_assign_unassign','push_notification_status')){
                    $data = [
                        'title' => translate('Order_Notification'),
                        'description' => translate('messages.you_are_assigned_to_a_order'),
                        'order_id' => $order['id'],
                        'image' => '',
                        'type' => 'order_status'
                    ];
                    Helpers::send_push_notif_to_device($deliveryman->fcm_token, $data);
                    DB::table('user_notifications')->insert([
                        'data' => json_encode($data),
                        'delivery_man_id' => $deliveryman->id,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }

            } catch (\Exception $e) {
                info($e->getMessage());
                Toastr::warning(translate('messages.push_notification_faild'));
            }
            return response()->json([], 200);
        }
        return response()->json(['message' => 'Deliveryman not available!'], 400);
    }

    /**
     * Get deliverymen by zone for AJAX zone change functionality
     */
    public function getDeliverymenByZone(Request $request)
    {
        $order = Order::with('store')->find($request->order_id);
        if (!$order) {
            return response()->json(['message' => translate('messages.order_not_found')], 404);
        }

        $zoneId = $request->zone_id;

        // Store coordinates for distance calculation
        $storeCoords = null;
        if ($order->store && $order->store->latitude && $order->store->longitude) {
            $storeCoords = [
                'lat' => $order->store->latitude,
                'lng' => $order->store->longitude
            ];
        }

        // In-zone deliverymen for selected zone
        $inZone = DeliveryMan::where('zone_id', $zoneId)
            ->available()
            ->active()
            ->get();
        $inZone = Helpers::deliverymen_list_formatting($inZone, $storeCoords);
        $inZone = collect($inZone)->sortBy('distance')->values()->all();

        // Out-of-zone deliverymen
        $outZone = DeliveryMan::where('zone_id', '!=', $zoneId)
            ->available()
            ->active()
            ->get();
        $outZone = Helpers::deliverymen_list_formatting($outZone, $storeCoords);
        $outZone = collect($outZone)->sortBy('distance')->values()->all();

        // Get zone name for display
        $zone = \App\Models\Zone::find($zoneId);

        return response()->json([
            'in_zone_html' => view('admin-views.order.partials._dm-list', ['deliverymen' => $inZone, 'isInZone' => true])->render(),
            'out_zone_html' => view('admin-views.order.partials._dm-list', ['deliverymen' => $outZone, 'isInZone' => false])->render(),
            'in_zone_count' => count($inZone),
            'out_zone_count' => count($outZone),
            'zone_name' => $zone ? $zone->name : 'Unknown',
        ]);
    }

    public function update_shipping(Request $request, Order $order)
    {
        $request->validate([
            'contact_person_name' => 'required',
            'address_type' => 'required',
            'contact_person_number' => 'required',
        ]);
        if ($request->latitude && $request->longitude) {
            $zone = Zone::where('id', $order->store->zone_id)->whereContains('coordinates', new Point($request->latitude, $request->longitude, POINT_SRID))->first();
            if (!$zone) {
                Toastr::error(translate('messages.out_of_coverage'));
                return back();
            }
        }
        $address = [
            'contact_person_name' => $request->contact_person_name,
            'contact_person_number' => $request->contact_person_number,
            'address_type' => $request->address_type,
            'address' => $request->address,
            'floor' => $request->floor,
            'road' => $request->road,
            'house' => $request->house,
            'longitude' => $request->longitude,
            'latitude' => $request->latitude
        ];

        $order->delivery_address = json_encode($address);
        $order->save();
        Toastr::success(translate('messages.delivery_address_updated'));
        return back();
    }

    public function generate_invoice($id)
    {
        $order = Order::withOutGlobalScope(ZoneScope::class)->with(['details', 'store' => function ($query) {
            return $query->withCount('orders');
        }, 'details.item' => function ($query) {
            return $query->withoutGlobalScope(StoreScope::class);
        }, 'details.campaign' => function ($query) {
            return $query->withoutGlobalScope(StoreScope::class);
        }])->where('id', $id)->first();
        return view('admin-views.order.invoice', compact('order'));
    }

    public function print_invoice($id)
    {
        $order = Order::withOutGlobalScope(ZoneScope::class)->with(['details', 'store' => function ($query) {
            return $query->withCount('orders');
        }, 'details.item' => function ($query) {
            return $query->withoutGlobalScope(StoreScope::class);
        }, 'details.campaign' => function ($query) {
            return $query->withoutGlobalScope(StoreScope::class);
        }])->where('id', $id)->first();
        return view('admin-views.order.invoice-print', compact('order'))->render();
    }
/**
 * Find replacement items for out-of-stock product
 */
public function findReplacements(Request $request)
{
    $request->validate([
        'item_id' => 'required|integer',
        'order_id' => 'required|integer'
    ]);

    try {
        $order = Order::findOrFail($request->order_id);
        $outOfStockItem = Item::with('unit:id,unit')->findOrFail($request->item_id);
        
        // Parse category_ids JSON for broader category matching
        $sourceCategoryIds = [];
        if ($outOfStockItem->category_ids) {
            $decoded = is_string($outOfStockItem->category_ids) ? json_decode($outOfStockItem->category_ids, true) : $outOfStockItem->category_ids;
            if (is_array($decoded)) {
                $sourceCategoryIds = collect($decoded)->pluck('id')->toArray();
            }
        }
        if ($outOfStockItem->category_id && !in_array($outOfStockItem->category_id, $sourceCategoryIds)) {
            $sourceCategoryIds[] = $outOfStockItem->category_id;
        }

        // Extract keywords from source item name for word-level matching
        $sourceWords = array_filter(
            explode(' ', strtolower(preg_replace('/[^a-zA-Z0-9\s]/', '', $outOfStockItem->name))),
            fn($w) => strlen($w) > 2
        );

        // Search same store + also other stores in same zone for broader results
        $sameStoreItems = Item::where('store_id', $order->store_id)
            ->where('id', '!=', $request->item_id)
            ->where('status', 1)
            ->where(function($q) { $q->where('stock', '>', 0)->orWhereNull('stock'); })
            ->select('id', 'name', 'price', 'stock', 'image', 'category_id', 'category_ids', 'store_id', 'unit_id', 'veg', 'avg_rating', 'order_count', 'discount', 'discount_type')
            ->with('unit:id,unit')
            ->limit(100)
            ->get();

        // Also search other stores in the same zone for the same category
        $otherStoreItems = collect();
        if (count($sourceCategoryIds) > 0 && $order->zone_id) {
            $otherStoreItems = Item::where('status', 1)
                ->where('id', '!=', $request->item_id)
                ->where('store_id', '!=', $order->store_id)
                ->where(function($q) use ($sourceCategoryIds) {
                    $q->whereIn('category_id', $sourceCategoryIds);
                })
                ->whereHas('store', function($q) use ($order) {
                    $q->where('zone_id', $order->zone_id)->where('status', 1)->where('active', 1);
                })
                ->where(function($q) { $q->where('stock', '>', 0)->orWhereNull('stock'); })
                ->select('id', 'name', 'price', 'stock', 'image', 'category_id', 'category_ids', 'store_id', 'unit_id', 'veg', 'avg_rating', 'order_count', 'discount', 'discount_type')
                ->with(['unit:id,unit', 'store:id,name'])
                ->limit(50)
                ->get();
        }

        $allItems = $sameStoreItems->concat($otherStoreItems);

        $replacements = $allItems->map(function($item) use ($outOfStockItem, $sourceCategoryIds, $sourceWords, $order) {
                $score = 0;
                $categoryMatch = false;
                $sameStore = $item->store_id == $order->store_id;

                // Category match (35 points)
                if ($item->category_id == $outOfStockItem->category_id) {
                    $score += 35;
                    $categoryMatch = true;
                } elseif (count($sourceCategoryIds) > 0) {
                    $itemCatIds = [];
                    if ($item->category_ids) {
                        $decoded = is_string($item->category_ids) ? json_decode($item->category_ids, true) : $item->category_ids;
                        if (is_array($decoded)) $itemCatIds = collect($decoded)->pluck('id')->toArray();
                    }
                    if (count(array_intersect($sourceCategoryIds, $itemCatIds)) > 0) {
                        $score += 20;
                        $categoryMatch = true;
                    }
                }

                // Price similarity (20 points max)
                $priceDiff = abs($item->price - $outOfStockItem->price);
                $pricePercent = $outOfStockItem->price > 0 ? ($priceDiff / $outOfStockItem->price) * 100 : 100;
                if ($pricePercent <= 5) $score += 20;
                elseif ($pricePercent <= 15) $score += 15;
                elseif ($pricePercent <= 25) $score += 10;
                elseif ($pricePercent <= 40) $score += 5;

                // Name similarity - combined approach (25 points max)
                $nameLower = strtolower($item->name);
                similar_text(strtolower($outOfStockItem->name), $nameLower, $percent);
                $score += ($percent / 100) * 15;

                // Word-level matching bonus (10 points)
                if (count($sourceWords) > 0) {
                    $matchedWords = 0;
                    foreach ($sourceWords as $word) {
                        if (str_contains($nameLower, $word)) $matchedWords++;
                    }
                    $wordScore = (count($sourceWords) > 0) ? ($matchedWords / count($sourceWords)) * 10 : 0;
                    $score += $wordScore;
                }

                // Same store bonus (10 points)
                if ($sameStore) $score += 10;

                // Veg/non-veg match (5 points)
                if ($item->veg == $outOfStockItem->veg) $score += 5;

                // Popularity bonus (5 points max)
                if ($item->order_count > 10) $score += min(5, $item->order_count / 20);

                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'price' => $item->price,
                    'stock' => $item->stock,
                    'image_full_url' => $item->image_full_url,
                    'unit' => $item->unit,
                    'category_match' => $categoryMatch,
                    'same_store' => $sameStore,
                    'store_name' => $sameStore ? null : ($item->store->name ?? null),
                    'similarity_score' => round(min($score, 100), 0),
                    'price_difference' => round($item->price - $outOfStockItem->price, 2),
                    'avg_rating' => $item->avg_rating,
                    'discount' => $item->discount,
                    'discount_type' => $item->discount_type,
                ];
            })
            ->filter(fn($item) => $item['similarity_score'] >= 10)
            ->sortByDesc('similarity_score')
            ->take(8)
            ->values();

        return response()->json([
            'success' => true,
            'out_of_stock_item' => [
                'id' => $outOfStockItem->id,
                'name' => $outOfStockItem->name,
                'price' => $outOfStockItem->price,
                'image_full_url' => $outOfStockItem->image_full_url,
                'unit' => $outOfStockItem->unit,
            ],
            'replacements' => $replacements,
            'total_found' => $replacements->count()
        ]);
        
    } catch (\Exception $e) {
        \Log::error('Find replacements error: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => translate('messages.something_went_wrong'),
            'error' => $e->getMessage()
        ], 500);
    }
}

/**
 * Replace an out-of-stock item with a new item
 */
public function replaceItem(Request $request)
{
    $request->validate([
        'order_id' => 'required|exists:orders,id',
        'old_item_id' => 'required|exists:items,id',
        'new_item_id' => 'required|exists:items,id',
        'quantity' => 'required|integer|min:1'
    ]);

    try {
        DB::beginTransaction();

        $order = Order::findOrFail($request->order_id);
        $newItem = Item::findOrFail($request->new_item_id);
        
        // Check stock
        if (config('module.' . $order->store->module->module_type)['stock']) {
            if ($newItem->stock < $request->quantity) {
                return response()->json([
                    'success' => false,
                    'message' => translate('messages.insufficient_stock')
                ], 400);
            }
        }

        // Get formatted product
        $product = Helpers::product_data_formatting($newItem, false, false, app()->getLocale());
        
        // Calculate prices
        $price = $product['price'];
        $discount = Helpers::product_discount_calculate($product, $price, $order->store);
        $discount_amount = $discount['discount_amount'];
        $price_after_discount = $price - $discount_amount;
        
        // Calculate tax
        $tax_amount = Helpers::tax_calculate($product, $price_after_discount);

        // Create new order detail
        $orderDetail = new OrderDetail();
        $orderDetail->order_id = $order->id;
        $orderDetail->item_id = $newItem->id;
        $orderDetail->item_campaign_id = null;
        $orderDetail->quantity = $request->quantity;
        $orderDetail->price = $price;
        $orderDetail->discount_on_item = $discount_amount;
        $orderDetail->tax_amount = $tax_amount;
        $orderDetail->variant = json_encode([]);
        $orderDetail->variation = json_encode([]);
        $orderDetail->add_ons = json_encode([]);
        $orderDetail->total_add_on_price = 0;
        $orderDetail->item_details = json_encode($product);
        $orderDetail->save();

        // Calculate totals
        $item_total = ($price - $discount_amount) * $request->quantity;
        $item_tax = $tax_amount * $request->quantity;
        $additional_amount = $item_total + $item_tax;

        // Update order amounts
        $order->order_amount += $additional_amount;
        $order->cod_collection_amount = ($order->cod_collection_amount ?? 0) + $additional_amount;
        $order->edited = true;
        $order->save();

        // Deduct stock
        if (config('module.' . $order->store->module->module_type)['stock']) {
            DB::table('items')
                ->where('id', $newItem->id)
                ->decrement('stock', $request->quantity);
        }

        // Update session cart if editing
        if (session()->has('order_cart')) {
            $cart = session('order_cart');
            $newOrderDetail = OrderDetail::with('item')->find($orderDetail->id);
            $newOrderDetail->item = $product;
            $newOrderDetail->status = true;
            $cart[] = $newOrderDetail;
            session()->put('order_cart', $cart);
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => translate('messages.replacement_added_successfully'),
            'new_order_amount' => Helpers::format_currency($order->order_amount),
            'additional_amount' => Helpers::format_currency($additional_amount)
        ]);

    } catch (\Exception $e) {
        DB::rollBack();
        \Log::error('Replace item error: ' . $e->getMessage());
        
        return response()->json([
            'success' => false,
            'message' => translate('messages.something_went_wrong'),
            'error' => $e->getMessage()
        ], 500);
    }
}
    public function add_payment_ref_code(Request $request, $id)
    {
        $request->validate([
            'transaction_reference' => 'max:30'
        ]);
        Order::where(['id' => $id])->update([
            'transaction_reference' => $request['transaction_reference']
        ]);

        Toastr::success(translate('messages.payment_reference_code_is_added'));
        return back();
    }

    public function update_payment_info(Request $request, $id)
    {
        $request->validate([
            'payment_method'        => 'required|string|max:50',
            'payment_status'        => 'required|in:paid,unpaid',
            'transaction_reference' => 'nullable|max:100',
        ]);

        $order = Order::findOrFail($id);

        $oldPaymentMethod = $order->payment_method;
        $oldPaymentStatus = $order->payment_status;

        $order->payment_method        = $request->payment_method;
        $order->payment_status        = $request->payment_status;
        $order->transaction_reference = $request->transaction_reference;
        $order->save();

        // If marking as paid and there are unpaid order_payments records, sync them
        if ($request->payment_status === 'paid' && $oldPaymentStatus !== 'paid') {
            \App\Models\OrderPayment::where('order_id', $id)
                ->where('payment_status', 'unpaid')
                ->update([
                    'payment_status' => 'paid',
                    'payment_method' => $request->payment_method,
                ]);
        }

        \Log::info('Payment info updated by admin', [
            'order_id'      => $id,
            'admin_id'      => auth()->id(),
            'old_method'    => $oldPaymentMethod,
            'new_method'    => $request->payment_method,
            'old_status'    => $oldPaymentStatus,
            'new_status'    => $request->payment_status,
        ]);

        Toastr::success(translate('messages.payment_info_updated_successfully'));
        return back();
    }

    public function update_order_payment(Request $request, $id)
    {
        $request->validate([
            'payment_method' => 'required|string|max:100',
            'payment_status' => 'required|in:paid,unpaid',
            'amount'         => 'required|numeric|min:0',
            'transaction_ref'=> 'nullable|string|max:100',
        ]);

        $payment = \App\Models\OrderPayment::findOrFail($id);

        $payment->payment_method = $request->payment_method;
        $payment->payment_status = $request->payment_status;
        $payment->amount         = $request->amount;
        $payment->transaction_ref= $request->transaction_ref;
        $payment->save();

        // Sync parent order payment_status based on all payments
        $order = $payment->order;
        if ($order) {
            $hasUnpaid = $order->payments()->where('payment_status', 'unpaid')->exists();
            $hasPaid   = $order->payments()->where('payment_status', 'paid')->exists();
            if ($hasPaid && $hasUnpaid) {
                $order->payment_status = 'partially_paid';
            } elseif ($hasPaid && !$hasUnpaid) {
                $order->payment_status = 'paid';
            } else {
                $order->payment_status = 'unpaid';
            }
            $order->save();
        }

        \Log::info('Order payment record updated by admin', [
            'order_payment_id' => $id,
            'admin_id'         => auth()->id(),
            'new_method'       => $request->payment_method,
            'new_status'       => $request->payment_status,
            'new_amount'       => $request->amount,
        ]);

        Toastr::success(translate('messages.payment_updated_successfully'));
        return back();
    }

    public function add_order_proof(Request $request, $id)
    {
        if($request->order_proof == null ){
            Toastr::error(translate('messages.Must_select_an_Image'));
            return back();
        }

        $order = Order::find($id);
        $img_names = $order->order_proof?json_decode($order->order_proof):[];
        $images = [];
        $total_file = count($request->order_proof) + count($img_names);
        if(!$img_names){
            $request->validate([
                'order_proof' => 'required|array|max:5',
            ]);
        }

        if ($total_file>5) {
            Toastr::error(translate('messages.order_proof_must_not_have_more_than_5_item'));
            return back();
        }

        if (!empty($request->file('order_proof'))) {
            foreach ($request->order_proof as $img) {
                $image_name = Helpers::upload('order/', 'png', $img);
                array_push($img_names, ['img'=>$image_name, 'storage'=> Helpers::getDisk()]);
            }
            $images = $img_names;
        }

        if(count($images)>0){
            $order->order_proof = json_encode($images);
        }
        $order->save();

        Toastr::success(translate('messages.order_proof_added'));
        return back();
    }
    public function remove_proof_image(Request $request)
    {
        $order = Order::find($request['id']);
        $array = [];
        $proof = isset($order->order_proof) ? json_decode($order->order_proof, true) : [];
        if (count($proof) < 2) {
            Toastr::warning(translate('all_image_delete_warning'));
            return back();
        }

        Helpers::check_and_delete('order/' , $request['name']);

        foreach ($proof as $image) {
            if ($image != $request['name']) {
                array_push($array, $image);
            }
        }
        Order::where('id', $request['id'])->update([
            'order_proof' => json_encode($array),
        ]);
        Toastr::success(translate('order_proof_image_removed_successfully'));
        return back();
    }

    public function restaurnt_filter($id)
    {
        session()->put('restaurnt_filter', $id);
        return back();
    }

    public function filter(Request $request)
    {
        $request->validate([
            'from_date' => 'required_if:to_date,true',
            'to_date' => 'required_if:from_date,true',
        ]);
        session()->put('order_filter', json_encode($request->all()));
        return back();
    }
    public function filter_reset(Request $request)
    {
        session()->forget('order_filter');
        return back();
    }

    public function add_to_cart(Request $request)
    {
        // 🔧 FIX: Ensure cart is loaded from database if session is lost
        $cart = $request->session()->get('order_cart');
        if ((!$cart || (is_array($cart) && empty($cart))) && $request->order_id) {
            $recovered = $this->recoverCartFromDatabase($request, $request->order_id);
            if ($recovered) {
                $cart = $recovered;
            }
        }

        if ($request->item_type == 'item') {
            $product = Item::find($request->id);
        } else {
            $product = ItemCampaign::find($request->id);
        }

        if (isset($product->module_id) && $product->module->module_type == 'food' && $product->food_variations) {
            $data = new OrderDetail();
            if ($request->order_details_id) {
                $data['id'] = $request->order_details_id;
            }

            $data['item_id'] = $request->item_type == 'item' ? $product->id : null;
            $data['item_campaign_id'] = $request->item_type == 'campaign' ? $product->id : null;
            $data['item'] = $request->item_type == 'item' ? $product : null;
            $data['item_campaign'] = $request->item_type == 'campaign' ? $product : null;
            $data['order_id'] = $request->order_id;
            $variations = [];
            $price = 0;
            $addon_price = 0;
            $variation_price = 0;

            $product_variations = json_decode($product->food_variations, true);
            if ($request->variations && count($product_variations)) {
                foreach ($request->variations  as $key => $value) {

                    if ($value['required'] == 'on' &&  isset($value['values']) == false) {
                        return response()->json([
                            'data' => 'variation_error',
                            'message' => translate('Please select items from') . ' ' . $value['name'],
                        ]);
                    }
                    if (isset($value['values'])  && $value['min'] != 0 && $value['min'] > count($value['values']['label'])) {
                        return response()->json([
                            'data' => 'variation_error',
                            'message' => translate('Please select minimum ') . $value['min'] . translate('For') . $value['name'] . '.',
                        ]);
                    }
                    if (isset($value['values']) && $value['max'] != 0 && $value['max'] < count($value['values']['label'])) {
                        return response()->json([
                            'data' => 'variation_error',
                            'message' => translate('Please select maximum ') . $value['max'] . translate('For') . $value['name'] . '.',
                        ]);
                    }
                }
                $variation_data = Helpers::get_varient($product_variations, $request->variations);
                $variation_price = $variation_data['price'];
                $variations = $variation_data['variations'];
            }
            $price = $product->price + $variation_price;
            $data['variation'] = json_encode($variations);
            $data['variant'] = '';
            // $data['variation_price'] = $variation_price;
            $data['quantity'] = $request['quantity'];
            $data['price'] = $price;
            $data['status'] = true;
            $data['discount_on_item'] = Helpers::product_discount_calculate($product, $price, $product->store)['discount_amount'];
            $data["discount_type"] = "discount_on_product";
            $data["tax_amount"] = Helpers::tax_calculate($product, $price);
            $add_ons = [];
            $add_on_qtys = [];

            if ($request['addon_id']) {
                foreach ($request['addon_id'] as $id) {
                    $addon_price += $request['addon-price' . $id] * $request['addon-quantity' . $id];
                    $add_on_qtys[] = $request['addon-quantity' . $id];
                }
                $add_ons = $request['addon_id'];
            }

            $addon_data = Helpers::calculate_addon_price(\App\Models\AddOn::withOutGlobalScope(StoreScope::class)->whereIn('id', $add_ons)->get(), $add_on_qtys);
            $data['add_ons'] = json_encode($addon_data['addons']);
            $data['total_add_on_price'] = $addon_data['total_add_on_price'];
            $cart = $request->session()->get('order_cart', collect([]));

            if (isset($request->cart_item_key)) {
                $cart[$request->cart_item_key] = $data;
                $request->session()->put('order_cart', $cart);

                // Auto-save to database if in edit mode
                $this->syncCartToDatabase($request, $cart);

                return response()->json([
                    'data' => 2
                ]);
            } else {
                $cart->push($data);
                $request->session()->put('order_cart', $cart);

                // Auto-save to database if in edit mode
                $this->syncCartToDatabase($request, $cart);
            }
        } else {

            $data = new OrderDetail();
            if ($request->order_details_id) {
                $data['id'] = $request->order_details_id;
            }

            $data['item_id'] = $request->item_type == 'item' ? $product->id : null;
            $data['item_campaign_id'] = $request->item_type == 'campaign' ? $product->id : null;
            $data['item'] = $request->item_type == 'item' ? $product : null;
            $data['item_campaign'] = $request->item_type == 'campaign' ? $product : null;
            $data['order_id'] = $request->order_id;
            $str = '';
            $price = 0;
            $addon_price = 0;

            //Gets all the choice values of customer choice option and generate a string like Black-S-Cotton
            foreach (json_decode($product->choice_options) as $key => $choice) {
                if ($str != null) {
                    $str .= '-' . str_replace(' ', '', $request[$choice->name]);
                } else {
                    $str .= str_replace(' ', '', $request[$choice->name]);
                }
            }
            $data['variant'] = json_encode([]);
            $data['variation'] = json_encode([]);
            if ($request->session()->has('order_cart') && !isset($request->cart_item_key)) {
                if (count($request->session()->get('order_cart')) > 0) {
                    foreach ($request->session()->get('order_cart') as $key => $cartItem) {
                        // dd($cartItem);
                        if ($cartItem && $cartItem['item_id'] == $request['id'] && $cartItem['status'] == true) {
                            if (count(json_decode($cartItem['variation'], true)) > 0) {
                                if (json_decode($cartItem['variation'], true)[0]['type'] == $str) {
                                    return response()->json([
                                        'data' => 1
                                    ]);
                                }
                            } else {
                                return response()->json([
                                    'data' => 1
                                ]);
                            }
                        }
                    }
                }
            }
            //Check the string and decreases quantity for the stock
            if ($str != null) {
                $count = count(json_decode($product->variations));
                for ($i = 0; $i < $count; $i++) {
                    if (json_decode($product->variations)[$i]->type == $str) {
                        $vr = json_decode($product->variations);
                        $price = $vr[$i]->price;
                        $stock = isset($vr[$i]->stock) ? $vr[$i]->stock : 0;
                    }
                }
                $data['variation'] = json_encode([["type" => $str, "price" => $price, "stock" => $stock]]);
            } else {
                $price = $product->price;
            }

            $data['quantity'] = $request['quantity'];
            $data['price'] = $price;
            $data['status'] = true;
            $data['discount_on_item'] = Helpers::product_discount_calculate($product, $price, $product->store)['discount_amount'];
            $data["discount_type"] = "discount_on_product";
            $data["tax_amount"] = Helpers::tax_calculate($product, $price);
            $add_ons = [];
            $add_on_qtys = [];

            if ($request['addon_id']) {
                foreach ($request['addon_id'] as $id) {
                    $addon_price += $request['addon-price' . $id] * $request['addon-quantity' . $id];
                    $add_on_qtys[] = $request['addon-quantity' . $id];
                }
                $add_ons = $request['addon_id'];
            }

            $addon_data = Helpers::calculate_addon_price(\App\Models\AddOn::withoutGlobalScope(StoreScope::class)->whereIn('id', $add_ons)->get(), $add_on_qtys);
            $data['add_ons'] = json_encode($addon_data['addons']);
            $data['total_add_on_price'] = $addon_data['total_add_on_price'];
            // dd($data);
            $cart = $request->session()->get('order_cart', collect([]));
            if (!($cart instanceof \Illuminate\Support\Collection)) {
                $cart = collect($cart);
            }
            if (isset($request->cart_item_key)) {
                $cart[$request->cart_item_key] = $data;
                $request->session()->put('order_cart', $cart);

                // Auto-save to database if in edit mode
                $this->syncCartToDatabase($request, $cart);

                return response()->json([
                    'data' => 2
                ]);
            } else {
                $cart->push($data);
                $request->session()->put('order_cart', $cart);

                // Auto-save to database if in edit mode
                $this->syncCartToDatabase($request, $cart);
            }
        }
        return response()->json([
            'data' => 0
        ]);
    }

    public function remove_from_cart(Request $request)
    {
        $cart = $request->session()->get('order_cart');

        // 🔧 FIX: Recover from database if session is lost
        if (!$cart || (is_array($cart) && empty($cart))) {
            $cart = $this->recoverCartFromDatabase($request);

            if (!$cart) {
                return response()->json([
                    'success' => 0,
                    'message' => translate('messages.cart_session_expired_please_refresh')
                ], 400);
            }
        }

        // Convert to collection if it's an array
        $cart = collect($cart);

        // Check if the key exists
        if (!isset($cart[$request->key])) {
            return response()->json([
                'success' => 0,
                'message' => translate('messages.cart_item_not_found')
            ], 404);
        }

        // Mark item as removed (soft delete)
        $cart[$request->key]->status = false;
        $request->session()->put('order_cart', $cart);

        // Auto-save to database if in edit mode
        $this->syncCartToDatabase($request, $cart);

        return response()->json(['success' => 1], 200);
    }

    public function edit(Request $request, Order $order)
    {
        $adminId = auth('admin')->id();

        $order = Order::with(['details', 'store' => function ($query) {
            return $query->withCount('orders');
        }, 'customer' => function ($query) {
            return $query->withCount('orders');
        }, 'delivery_man' => function ($query) {
            return $query->withCount('orders');
        }, 'details.item' => function ($query) {
            return $query->withoutGlobalScope(StoreScope::class);
        }, 'details.campaign' => function ($query) {
            return $query->withoutGlobalScope(StoreScope::class);
        }])->where(['id' => $order->id])->StoreOrder()->first();

        // Handle cancel - cleanup both session and database
        if ($request->cancle) {
            OrderEditSession::where('order_id', $order->id)
                ->where('admin_id', $adminId)
                ->delete();

            if ($request->session()->has(['order_cart'])) {
                session()->forget(['order_cart']);
            }
            session()->forget('editing_order_id');
            return back();
        }

        // Check if another admin is currently editing (within last 5 minutes)
        $activeEdit = OrderEditSession::where('order_id', $order->id)
            ->where('admin_id', '!=', $adminId)
            ->where('last_activity', '>', now()->subMinutes(5))
            ->first();

        if ($activeEdit) {
            Toastr::warning(translate('messages.order_being_edited_by_another_admin'));
            return back();
        }

        // Load order details into cart
        $cart = collect([]);
        foreach ($order->details as $details) {
            $details['status'] = true;
            $cart->push($details);
        }

        // Save to database AND session (database is source of truth)
        OrderEditSession::updateOrCreate(
            ['order_id' => $order->id, 'admin_id' => $adminId],
            [
                'cart_data' => $cart->toArray(),
                'last_activity' => now(),
                'is_locked' => true,
                'locked_at' => now(),
            ]
        );

        $request->session()->put('order_cart', $cart);
        $request->session()->put('editing_order_id', $order->id);

        return back();
    }

    public function editV2(Request $request, Order $order)
    {
        $adminId = auth('admin')->id();

        $order = Order::with(['details', 'store' => function ($query) {
            return $query->withCount('orders');
        }, 'customer' => function ($query) {
            return $query->withCount('orders');
        }, 'details.item' => function ($query) {
            return $query->withoutGlobalScope(StoreScope::class);
        }, 'details.campaign' => function ($query) {
            return $query->withoutGlobalScope(StoreScope::class);
        }])->where(['id' => $order->id])->StoreOrder()->first();

        // Handle cancel
        if ($request->cancle) {
            OrderEditSession::where('order_id', $order->id)
                ->where('admin_id', $adminId)
                ->delete();

            if ($request->session()->has(['order_cart'])) {
                session()->forget(['order_cart']);
            }
            session()->forget('editing_order_id');
            return redirect()->route('admin.order.details', $order->id);
        }

        // Check if another admin is currently editing
        $activeEdit = OrderEditSession::where('order_id', $order->id)
            ->where('admin_id', '!=', $adminId)
            ->where('last_activity', '>', now()->subMinutes(5))
            ->first();

        if ($activeEdit) {
            Toastr::warning(translate('messages.order_being_edited_by_another_admin'));
            return redirect()->route('admin.order.details', $order->id);
        }

        // Load order details into cart
        $cart = collect([]);
        foreach ($order->details as $details) {
            $details['status'] = true;
            $cart->push($details);
        }

        // Save to session and database
        OrderEditSession::updateOrCreate(
            ['order_id' => $order->id, 'admin_id' => $adminId],
            [
                'cart_data' => $cart->toArray(),
                'last_activity' => now(),
                'is_locked' => true,
                'locked_at' => now(),
            ]
        );

        $request->session()->put('order_cart', $cart);
        $request->session()->put('editing_order_id', $order->id);

        // Load categories for this store
        $categories = \App\Models\Category::whereHas('products', function ($q) use ($order) {
            $q->where('store_id', $order->store_id);
        })->get(['id', 'name']);

        $module_type = optional(optional($order->store)->module)->module_type ?? 'food';

        // Build initialCart as a plain array for the JS init (avoids Blade parsing closures in @json)
        $initialCart = [];
        foreach ($cart->values() as $i => $c) {
            $item     = isset($c['item'])     ? $c['item']     : null;
            $campaign = isset($c['campaign']) ? $c['campaign'] : null;
            $productObj = $item ?? $campaign;
            $hasVariations = false;
            if ($productObj) {
                $fv = json_decode($productObj->food_variations ?? '[]', true);
                $co = json_decode($productObj->choice_options  ?? '[]', true);
                $hasVariations = !empty($fv) || !empty($co);
            }
            $initialCart[] = [
                'cartKey'                  => $i,
                'order_detail_id'          => $c['id']                        ?? null,
                'item_id'                  => $c['item_id']                   ?? null,
                'campaign_item_id'         => $c['item_campaign_id']          ?? null,
                'item_type'                => ($c['item_campaign_id'] ?? null) ? 'campaign' : 'item',
                'name'                     => $productObj ? $productObj->name : ($c['item_details']['name'] ?? 'Unknown'),
                'image'                    => $productObj ? ($productObj->image_full_url ?? null) : null,
                'price'                    => $c['price']                     ?? 0,
                'quantity'                 => $c['quantity']                  ?? 1,
                'discount'                 => $c['discount_on_item']          ?? 0,
                'tax'                      => $c['tax_amount']                ?? 0,
                'addon_price'              => $c['add_ons_cost']              ?? 0,
                'status'                   => $c['status']                    ?? true,
                'has_variations'           => $hasVariations,
                // Status fields
                'is_unavailable'           => (bool)($c['is_unavailable']           ?? false),
                'is_picked_up'             => (bool)($c['is_picked_up']             ?? false),
                // Outside purchase
                'is_outside_purchase'      => (bool)($c['is_outside_purchase']      ?? false),
                'outside_purchase_cost'    => (float)($c['outside_purchase_cost']   ?? 0),
                'outside_purchase_status'  => $c['outside_purchase_status']         ?? null,
                // MRP request
                'requested_mrp'            => $c['requested_mrp']  ? (float)$c['requested_mrp'] : null,
                'mrp_update_status'        => $c['mrp_update_status']                ?? null,
            ];
        }

        return view('admin-views.order.edit-v2', compact('order', 'cart', 'categories', 'module_type', 'initialCart'));
    }

public function update(Request $request, Order $order)
{
    $order = Order::with(['details', 'store' => function ($query) {
        return $query->withCount('orders');
    }, 'customer' => function ($query) {
        return $query->withCount('orders');
    }, 'delivery_man' => function ($query) {
        return $query->withCount('orders');
    }, 'details.item' => function ($query) {
        return $query->withoutGlobalScope(StoreScope::class);
    }, 'details.campaign' => function ($query) {
        return $query->withoutGlobalScope(StoreScope::class);
    }])->where(['id' => $order->id])->StoreOrder()->first();

    $adminId = auth('admin')->id();

    // Try session first, then database recovery
    $cart = $request->session()->get('order_cart');

    if (!$cart || (is_array($cart) && empty($cart))) {
        // RECOVERY: Load from database
        $editSession = OrderEditSession::where('order_id', $order->id)
            ->where('admin_id', $adminId)
            ->first();

        if (!$editSession) {
            Toastr::error(translate('messages.order_data_not_found'));
            return back();
        }

        $cart = collect($editSession->cart_data);
        Toastr::info(translate('messages.edit_session_recovered_from_backup'));
    } else {
        $cart = collect($cart);
    }

    // 🔑 Store original order amount BEFORE any changes
    if (!$order->original_order_amount || $order->original_order_amount == 0) {
        $order->original_order_amount = $order->order_amount;
        $order->save();
    }
    $originalTotal = $order->original_order_amount;
    $store = $order->store;
    $coupon = null;
    $total_addon_price = 0;
    $product_price = 0;
    $store_discount_amount = 0;
    
    if ($order->coupon_code) {
        $coupon = Coupon::where(['code' => $order->coupon_code])->first();
    }
    
    foreach ($cart as $c) {
        try {
            if ($c['status'] == true) {
                unset($c['status']);
                if ($c['item_campaign_id'] != null) {
                    $product = ItemCampaign::find($c['item_campaign_id']);
                    if ($product) {
                        $price = $c['price'];
                        $product = Helpers::product_data_formatting($product);
                        $c->item_details = json_encode($product);
                        $c->updated_at = now();
                        
                        if (isset($c->id)) {
                            OrderDetail::where('id', $c->id)->update([
                                'item_id' => $c->item_id,
                                'item_campaign_id' => $c->item_campaign_id,
                                'item_details' => $c->item_details,
                                'quantity' => $c->quantity,
                                'price' => $c->price,
                                'tax_amount' => $c->tax_amount,
                                'discount_on_item' => $c->discount_on_item,
                                'discount_type' => $c->discount_type,
                                'variant' => $c->variant,
                                'variation' => $c->variation,
                                'add_ons' => $c->add_ons,
                                'total_add_on_price' => $c->total_add_on_price,
                                'updated_at' => $c->updated_at
                            ]);
                        } else {
                            $c->save();
                        }

                        $total_addon_price += $c['total_add_on_price'];
                        $product_price += $price * $c['quantity'];
                        $store_discount_amount += $c['discount_on_item'] * $c['quantity'];
                    } else {
                        Toastr::error(translate('messages.item_not_found'));
                        return back();
                    }
                } else {
                    unset($c['item']);
                    unset($c['item_campaign']);
                    $product = Item::find($c['item_id']);
                    if ($product) {
                        $price = $c['price'];
                        $product = Helpers::product_data_formatting($product);
                        $c->item_details = json_encode($product);
                        $c->updated_at = now();
                        
                        if (isset($c->id)) {
                            OrderDetail::where('id', $c->id)->update([
                                'item_id' => $c->item_id,
                                'item_campaign_id' => $c->item_campaign_id,
                                'item_details' => $c->item_details,
                                'quantity' => $c->quantity,
                                'price' => $c->price,
                                'tax_amount' => $c->tax_amount,
                                'discount_on_item' => $c->discount_on_item,
                                'discount_type' => $c->discount_type,
                                'variant' => $c->variant,
                                'variation' => $c->variation,
                                'add_ons' => $c->add_ons,
                                'total_add_on_price' => $c->total_add_on_price,
                                'updated_at' => $c->updated_at
                            ]);
                        } else {
                            $c->save();
                        }

                        $total_addon_price += $c['total_add_on_price'];
                        $product_price += $price * $c['quantity'];
                        $store_discount_amount += $c['discount_on_item'] * $c['quantity'];
                    } else {
                        Toastr::error(translate('messages.item_not_found'));
                        return back();
                    }
                }
            } else {
                $c->delete();
            }
        } catch (\Throwable $th) {
            info($th->getMessage());
        }
    }

    $store_discount = Helpers::get_store_discount($store);
    if (isset($store_discount)) {
        if ($product_price + $total_addon_price < $store_discount['min_purchase']) {
            $store_discount_amount = 0;
        }

        if ($store_discount_amount > $store_discount['max_discount'] && $store_discount_amount > $store_discount['max_discount']) {
            $store_discount_amount = $store_discount['max_discount'];
        }
    }
    
    $order->delivery_charge = $order->original_delivery_charge;
    if ($coupon) {
        if ($coupon->coupon_type == 'free_delivery') {
            $order->delivery_charge = 0;
            $coupon = null;
        }
    }

    if ($order->store->free_delivery || $order->order_type == 'take_away') {
        $order->delivery_charge = 0;
    }

    $coupon_discount_amount = $coupon ? CouponLogic::get_discount($coupon, $product_price + $total_addon_price - $store_discount_amount) : 0;
    $total_price = $product_price + $total_addon_price - $store_discount_amount - $coupon_discount_amount;

    $tax = $store->tax;
    $order->tax_status = 'excluded';

    $tax_included = BusinessSetting::where(['key' => 'tax_included'])->first() ?  BusinessSetting::where(['key' => 'tax_included'])->first()->value : 0;
    if ($tax_included ==  1) {
        $order->tax_status = 'included';
    }

    $total_tax_amount = Helpers::product_tax($total_price, $tax, $order->tax_status == 'included');
    $total_tax_amount = $order->tax_status == 'included' ? 0 : $total_tax_amount;

    if ($store->minimum_order > $product_price + $total_addon_price) {
        Toastr::error(translate('messages.you_need_to_order_at_least', ['amount' => $store->minimum_order . ' ' . Helpers::currency_code()]));
        return back();
    }

    $free_delivery_over = BusinessSetting::where('key', 'free_delivery_over')->first()->value;
    if (isset($free_delivery_over)) {
        if ($free_delivery_over <= $product_price + $total_addon_price - $coupon_discount_amount - $store_discount_amount) {
            $order->delivery_charge = 0;
        }
    }

    // Added service charge
    $additional_charge_status = BusinessSetting::where('key', 'additional_charge_status')->first()->value;
    $additional_charge = BusinessSetting::where('key', 'additional_charge')->first()->value;
    if ($additional_charge_status == 1) {
        $order->additional_charge = $additional_charge ?? 0;
    } else {
        $order->additional_charge = 0;
    }

    // Subtract referral bonus if present (it's a discount already applied to the order)
    $ref_bonus = $order->ref_bonus_amount ?? 0;
    $total_order_ammount = $total_price + $total_tax_amount + $order->delivery_charge + $order->additional_charge - $ref_bonus;

    // 🔑 CORRECT CALCULATION: New Total - Original Total
    $adjustment = $total_order_ammount - $originalTotal;

    $order->coupon_discount_amount = $coupon_discount_amount;
    $order->store_discount_amount = $store_discount_amount;
    $order->total_tax_amount = $total_tax_amount;
    $order->order_amount = $total_order_ammount;
    $order->adjustment_amount = $adjustment; // ✅ Fixed typo
    $order->edited = true;

    // Payment adjustment logic
    $isDigitalPayment = !in_array($order->payment_method, ['cash_on_delivery', 'offline_payment']);
    $isWalletPayment = in_array($order->payment_method, ['wallet', 'partial_payment']);

    if ($adjustment > 0) {
        // Additional amount needs to be collected

        if ($isWalletPayment && $order->user_id) {
            // For wallet/partial payment orders - VERIFY wallet balance
            $user = \App\Models\User::find($order->user_id);

            if (!$user) {
                Toastr::error(translate('messages.customer_not_found'));
                return back();
            }

            if ($user->wallet_balance >= $adjustment) {
                // Sufficient wallet balance - deduct from wallet
                CustomerLogic::create_wallet_transaction($order->user_id, $adjustment, 'order_place', $order->id);
                $order->cod_collection_amount = 0;
                Toastr::success(translate('messages.additional_amount') . ': ' . Helpers::format_currency($adjustment) . ' ' . translate('messages.deducted_from_customer_wallet'));
            } else {
                // Insufficient wallet balance - must collect as COD
                $order->cod_collection_amount = $adjustment;
                Toastr::warning(
                    translate('messages.insufficient_wallet_balance') . '. ' .
                    translate('messages.additional_amount') . ': ' . Helpers::format_currency($adjustment) . ' ' .
                    translate('messages.will_be_collected_on_delivery') . '. ' .
                    translate('messages.customer_wallet_balance') . ': ' . Helpers::format_currency($user->wallet_balance)
                );
            }
        } elseif ($isDigitalPayment && $order->user_id) {
            // For other digital payments - try wallet, fallback to COD
            $user = \App\Models\User::find($order->user_id);
            if ($user && $user->wallet_balance >= $adjustment) {
                CustomerLogic::create_wallet_transaction($order->user_id, $adjustment, 'order_place', $order->id);
                $order->cod_collection_amount = 0;
                Toastr::success(translate('messages.additional_amount') . ': ' . Helpers::format_currency($adjustment) . ' ' . translate('messages.deducted_from_customer_wallet'));
            } else {
                $order->cod_collection_amount = $adjustment;
                Toastr::info(translate('messages.additional_amount') . ': ' . Helpers::format_currency($adjustment) . ' ' . translate('messages.will_be_collected_on_delivery'));
            }
        } else {
            // COD order - collect additional amount on delivery
            $order->cod_collection_amount = $adjustment;
            Toastr::info(translate('messages.additional_amount') . ': ' . Helpers::format_currency($adjustment) . ' ' . translate('messages.will_be_collected_on_delivery'));
        }
    } elseif ($adjustment < 0) {
        $totalReductionAmount = abs($adjustment);
        Toastr::info(translate('messages.order_amount_reduced') . ': ' . Helpers::format_currency($totalReductionAmount));

        // For COD orders, clear any pending extra collection amount
        if ($order->payment_method == 'cash_on_delivery') {
            $order->cod_collection_amount = 0;
        }
    }

    // 🔧 FIX: Recalculate outside purchase amount if order has outside purchase items
    $outsidePurchaseAmount = 0;
    foreach ($order->details as $detail) {
        if ($detail->is_outside_purchase && $detail->outside_purchase_cost) {
            $outsidePurchaseAmount += $detail->outside_purchase_cost * $detail->quantity;
        }
    }
    $order->outside_purchase_amount = $outsidePurchaseAmount;

    $order->save();

    // 🔧 FIX: Update transaction to match edited order
    if (config('app.enable_transaction_sync', true)) {
        try {
            \App\Services\OrderTransactionService::updateFromOrderEdit(
                $order,
                'admin',
                auth('admin')->id(),
                [
                    'items_modified' => true,
                    'adjustment_amount' => $adjustment ?? 0,
                    'edited_via' => 'admin_panel_full_edit',
                    'original_amount' => $originalTotal,
                    'new_amount' => $total_order_ammount,
                ]
            );
        } catch (\Exception $e) {
            \Log::error("Transaction sync failed for order {$order->id}: " . $e->getMessage());
            // Continue - order is saved, transaction sync can be retried
        }
    }

    // Update OrderPayment records for partial_payment orders
    if ($order->payment_method == 'partial_payment' && $order->cod_collection_amount > 0) {
        // Find the COD payment record and update it
        $codPayment = \App\Models\OrderPayment::where('order_id', $order->id)
            ->where('payment_method', 'cash_on_delivery')
            ->where('payment_status', 'unpaid')
            ->first();

        if ($codPayment) {
            // Update the COD amount to collect
            $codPayment->amount += $adjustment;
            $codPayment->save();

            \Log::info('Updated COD payment amount for edited order', [
                'order_id' => $order->id,
                'adjustment' => $adjustment,
                'new_cod_amount' => $codPayment->amount,
            ]);
        }
    }

    // Clear both session AND database after successful save
    session()->forget('order_cart');
    session()->forget('editing_order_id');

    OrderEditSession::where('order_id', $order->id)
        ->where('admin_id', $adminId)
        ->delete();

    Toastr::success(translate('messages.order_updated_successfully'));
    if ($request->get('from') === 'v2') {
        return redirect()->route('admin.order.details', $order->id);
    }
    return back();
}

    /**
     * Auto-save edit progress to database
     */
    public function saveEditProgress(Request $request)
    {
        $adminId = auth('admin')->id();
        $orderId = $request->session()->get('editing_order_id');

        if (!$orderId) {
            return response()->json([
                'success' => false,
                'message' => translate('messages.no_active_edit_session')
            ]);
        }

        $cart = $request->session()->get('order_cart', collect([]));

        OrderEditSession::where('order_id', $orderId)
            ->where('admin_id', $adminId)
            ->update([
                'cart_data' => $cart,
                'last_activity' => now(),
            ]);

        return response()->json([
            'success' => true,
            'message' => translate('messages.progress_saved')
        ]);
    }

    /**
     * Recover edit session from database
     */
    public function recoverEditSession(Request $request)
    {
        $adminId = auth('admin')->id();
        $orderId = $request->session()->get('editing_order_id');

        if (!$orderId) {
            return response()->json([
                'recovered' => false,
                'message' => translate('messages.no_active_edit')
            ]);
        }

        $editSession = OrderEditSession::where('order_id', $orderId)
            ->where('admin_id', $adminId)
            ->first();

        if (!$editSession) {
            // Session truly lost - clear everything
            session()->forget(['order_cart', 'editing_order_id']);
            return response()->json([
                'recovered' => false,
                'message' => translate('messages.session_expired')
            ]);
        }

        // Restore cart to session
        $cart = collect($editSession->cart_data);
        $request->session()->put('order_cart', $cart);

        return response()->json([
            'recovered' => true,
            'message' => translate('messages.session_restored'),
            'items_count' => $cart->count()
        ]);
    }

    /**
     * Helper method to sync cart to database when in edit mode
     */
    private function syncCartToDatabase(Request $request, $cart)
    {
        $adminId = auth('admin')->id();
        $orderId = $request->session()->get('editing_order_id');

        if ($orderId && $adminId) {
            OrderEditSession::updateOrCreate(
                ['order_id' => $orderId, 'admin_id' => $adminId],
                [
                    'cart_data' => is_array($cart) ? $cart : $cart->toArray(),
                    'last_activity' => now(),
                ]
            );
        }
    }

    /**
     * 🔧 Helper method to recover cart from database when session is lost
     */
    private function recoverCartFromDatabase(Request $request, $orderId = null)
    {
        $adminId = auth('admin')->id();
        $orderId = $orderId ?? $request->session()->get('editing_order_id');

        if (!$orderId || !$adminId) {
            return null;
        }

        $editSession = OrderEditSession::where('order_id', $orderId)
            ->where('admin_id', $adminId)
            ->first();

        if ($editSession && $editSession->cart_data) {
            $cart = collect($editSession->cart_data);
            $request->session()->put('order_cart', $cart);
            $request->session()->put('editing_order_id', $orderId);
            return $cart;
        }

        return null;
    }

    /**
     * Phase 2 & 3: Inline edit - Save progress (AJAX auto-save)
     */
    public function inlineSaveProgress(Request $request)
    {
        try {
            $orderId = $request->input('order_id');
            $orderData = $request->input('order_data');

            // Store in session for quick recovery
            session([
                'inline_edit_order_id' => $orderId,
                'inline_edit_data' => $orderData,
                'inline_edit_last_save' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Progress saved',
                'saved_at' => now()->toDateTimeString(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to save progress',
            ], 500);
        }
    }

    /**
     * Phase 2 & 3: Inline edit - Update order (AJAX submit)
     */
    public function inlineUpdate(Request $request)
    {
        try {
            $orderId = $request->input('order_id');
            $orderData = $request->input('order_data');

            // Load the order
            $order = Order::with(['details', 'store'])->findOrFail($orderId);

            // Verify order can be edited
            if (!in_array($order->order_status, ['pending', 'confirmed', 'processing', 'accepted'])) {
                return response()->json([
                    'success' => false,
                    'message' => translate('messages.cannot_edit_this_order_status'),
                ], 422);
            }

            DB::beginTransaction();

            // Update order details
            $items = $orderData['items'] ?? [];
            foreach ($items as $itemData) {
                $detailId = $itemData['detail_id'] ?? null;
                if ($detailId) {
                    $detail = OrderDetail::find($detailId);
                    if ($detail && $detail->order_id == $orderId) {
                        $detail->quantity = $itemData['quantity'];
                        $detail->price = $itemData['price'];
                        $detail->discount_on_item = $itemData['discount'];
                        $detail->save();
                    }
                }
            }

            // Store original amount if not already stored
            if (!$order->original_order_amount || $order->original_order_amount == 0) {
                $order->original_order_amount = $order->order_amount;
            }

            // 🔧 FIX: Recalculate outside purchase amount if order has outside purchase items
            $outsidePurchaseAmount = 0;
            $order->load('details'); // Reload details with updated values
            foreach ($order->details as $detail) {
                if ($detail->is_outside_purchase && $detail->outside_purchase_cost) {
                    $outsidePurchaseAmount += $detail->outside_purchase_cost * $detail->quantity;
                }
            }
            $order->outside_purchase_amount = $outsidePurchaseAmount;

            // Update order totals
            $originalTotal = $order->original_order_amount;
            $newTotal = $orderData['total'] ?? $order->order_amount;
            $adjustment = $newTotal - $originalTotal;

            $order->order_amount = $newTotal;
            $order->total_tax_amount = $orderData['tax'] ?? $order->total_tax_amount;
            $order->adjustment_amount = $adjustment;
            $order->edited = true;

            $order->save();

            // Update transaction if sync is enabled
            if (config('app.enable_transaction_sync', true)) {
                try {
                    \App\Services\OrderTransactionService::updateFromOrderEdit(
                        $order,
                        'admin',
                        auth('admin')->id(),
                        [
                            'items_modified' => true,
                            'edited_via' => 'inline_edit',
                            'adjustment_amount' => $adjustment,
                        ]
                    );
                } catch (\Exception $e) {
                    \Log::error("Inline edit transaction sync failed for order {$order->id}: " . $e->getMessage());
                    // Continue - order is saved, transaction sync can be retried
                }
            }

            DB::commit();

            // Clear saved progress
            session()->forget(['inline_edit_order_id', 'inline_edit_data', 'inline_edit_last_save']);

            return response()->json([
                'success' => true,
                'message' => translate('messages.order_updated_successfully'),
                'order_id' => $order->id,
                'new_total' => $order->order_amount,
                'adjustment' => $adjustment,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Inline update failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => translate('messages.failed_to_update_order'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function quick_view(Request $request)
    {

        $product =  Item::findOrFail($request->product_id);
        $item_type = 'item';
        $order_id = $request->order_id;

        return response()->json([
            'success' => 1,
            'view' => view('admin-views.order.partials._quick-view', compact('product', 'order_id', 'item_type'))->render(),
        ]);
    }

    public function quick_view_cart_item(Request $request)
    {
        $cart = session('order_cart');

        // 🔧 FIX: Recover from database if session is lost
        if (!$cart || (is_array($cart) && empty($cart))) {
            $adminId = auth('admin')->id();
            $orderId = session('editing_order_id') ?? $request->order_id;

            $editSession = OrderEditSession::where('order_id', $orderId)
                ->where('admin_id', $adminId)
                ->first();

            if ($editSession && $editSession->cart_data) {
                $cart = $editSession->cart_data;
                session(['order_cart' => $cart]);
            } else {
                return response()->json([
                    'success' => 0,
                    'message' => translate('messages.cart_session_expired_please_refresh'),
                ], 400);
            }
        }

        // Check if the key exists in cart
        if (!isset($cart[$request->key])) {
            return response()->json([
                'success' => 0,
                'message' => translate('messages.cart_item_not_found'),
            ], 404);
        }

        $cart_item = $cart[$request->key];
        $order_id = $request->order_id;
        $item_key = $request->key;

        if (is_array($cart_item)) {
            $cart_item = (object) $cart_item;
        }
        $product = !empty($cart_item->item) ? $cart_item->item : ($cart_item->campaign ?? null);
        $item_type = !empty($cart_item->item) ? 'item' : 'campaign';

        return response()->json([
            'success' => 1,
            'view' => view('admin-views.order.partials._quick-view-cart-item', compact('order_id', 'product', 'cart_item', 'item_key', 'item_type'))->render(),
        ]);
    }

    /**
     * AJAX Search Items for Order Edit
     * Searches by name, barcode, and description
     */
    public function searchItemsForOrder(Request $request)
    {
        try {
            $order_id = $request->order_id;
            $keyword = $request->keyword;
            $category_id = $request->category_id;

            $order = Order::find($order_id);
            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => translate('messages.order_not_found')
                ], 404);
            }

            $store_id = $order->store_id;

            $products = Item::withoutGlobalScope(\App\Scopes\StoreScope::class)
                ->with(['category', 'storage'])
                ->where('store_id', $store_id)
                ->when($category_id, function ($query) use ($category_id) {
                    return $query->whereHas('category', function ($q) use ($category_id) {
                        $q->whereId($category_id)->orWhere('parent_id', $category_id);
                    });
                })
                ->when($keyword, function ($query) use ($keyword) {
                    return $query->where(function ($q) use ($keyword) {
                        // Search for exact phrase match first (highest priority)
                        $q->where('name', 'like', "%{$keyword}%")
                          ->orWhere('barcode', 'like', "%{$keyword}%");

                        // Also search individual words (lower priority)
                        $words = explode(' ', $keyword);
                        if (count($words) > 1) {
                            foreach ($words as $word) {
                                $word = trim($word);
                                if (!empty($word)) {
                                    $q->orWhere('name', 'like', "%{$word}%")
                                      ->orWhere('barcode', 'like', "%{$word}%");
                                }
                            }
                        }
                    });
                })
                ->when($keyword, function ($query) use ($keyword) {
                    // Order by relevance: exact matches first, then partial matches
                    return $query->orderByRaw("
                        CASE
                            WHEN name = ? THEN 1
                            WHEN barcode = ? THEN 2
                            WHEN name LIKE ? THEN 3
                            WHEN barcode LIKE ? THEN 4
                            ELSE 5
                        END
                    ", [$keyword, $keyword, $keyword . '%', $keyword . '%']);
                })
                ->when(!$keyword, function ($query) {
                    return $query->latest();
                })
                ->limit(20)
                ->get();

            $html = view('admin-views.order.partials._search_items_ajax', compact('products', 'order_id'))->render();

            return response()->json([
                'success' => true,
                'count' => $products->count(),
                'html' => $html
            ]);
        } catch (\Exception $e) {
            \Log::error('Search Items For Order Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * AJAX Search Items for Order Edit V2 (POS-style editor)
     * Same query as searchItemsForOrder but renders V2 grid partial and marks out-of-stock.
     */
    public function searchItemsForOrderV2(Request $request)
    {
        try {
            $order_id    = $request->order_id;
            $store_id    = $request->store_id;
            $keyword     = $request->keyword;
            $category_id = $request->category_id;

            // Resolve store_id from order if not provided directly
            if (!$store_id && $order_id) {
                $order = Order::find($order_id);
                if (!$order) {
                    return response()->json(['success' => false, 'message' => translate('messages.order_not_found')], 404);
                }
                $store_id = $order->store_id;
            }

            if (!$store_id) {
                return response()->json(['success' => false, 'message' => 'store_id is required'], 422);
            }

            $products = Item::withoutGlobalScope(\App\Scopes\StoreScope::class)
                ->with(['category', 'storage'])
                ->where('store_id', $store_id)
                ->where('status', 1)
                ->where('is_approved', 1)
                ->when($category_id, function ($query) use ($category_id) {
                    return $query->whereHas('category', function ($q) use ($category_id) {
                        $q->whereId($category_id)->orWhere('parent_id', $category_id);
                    });
                })
                ->when($keyword, function ($query) use ($keyword) {
                    return $query->where(function ($q) use ($keyword) {
                        $q->where('name', 'like', "%{$keyword}%")
                          ->orWhere('barcode', 'like', "%{$keyword}%");

                        $words = explode(' ', $keyword);
                        if (count($words) > 1) {
                            foreach ($words as $word) {
                                $word = trim($word);
                                if (!empty($word)) {
                                    $q->orWhere('name', 'like', "%{$word}%")
                                      ->orWhere('barcode', 'like', "%{$word}%");
                                }
                            }
                        }
                    });
                })
                ->when($keyword, function ($query) use ($keyword) {
                    return $query->orderByRaw("
                        CASE
                            WHEN name = ? THEN 1
                            WHEN barcode = ? THEN 2
                            WHEN name LIKE ? THEN 3
                            WHEN barcode LIKE ? THEN 4
                            ELSE 5
                        END
                    ", [$keyword, $keyword, $keyword . '%', $keyword . '%']);
                })
                ->when(!$keyword, function ($query) {
                    return $query->latest();
                })
                ->limit(24)
                ->get();

            // Mark out-of-stock: only when stock is explicitly tracked (storage relation loaded)
            // stock=NULL means untracked; stock<=0 means genuinely out of stock
            foreach ($products as $p) {
                $hasStorage = $p->storage && $p->storage->isNotEmpty();
                $p->is_out_of_stock = $hasStorage && ($p->stock !== null && $p->stock <= 0);
            }

            $html = view('admin-views.order.partials._v2-search-results', compact('products'))->render();

            return response()->json([
                'success' => true,
                'count'   => $products->count(),
                'html'    => $html,
            ]);
        } catch (\Exception $e) {
            \Log::error('SearchItemsForOrderV2 Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Return the current order-edit session cart as a simple JS-friendly array.
     * Used by the V2 POS editor to refresh the cart panel after add/remove actions.
     */
    public function getEditCartItems(Request $request)
    {
        $cart = session('order_cart', collect([]));
        if (!($cart instanceof \Illuminate\Support\Collection)) {
            $cart = collect($cart);
        }

        $items = [];
        foreach ($cart->values() as $i => $c) {
            if (isset($c['status']) && $c['status'] === false) continue;

            // 'campaign' is the relation key used in editV2 (details.campaign)
            $productObj = $c['item'] ?? $c['campaign'] ?? null;
            if ($productObj && is_array($productObj)) {
                // ArrayAccess object stored as array — wrap it
                $productObj = (object) $productObj;
            }

            $hasVariations = false;
            if ($productObj) {
                $fv = json_decode($productObj->food_variations ?? '[]', true);
                $co = json_decode($productObj->choice_options ?? '[]', true);
                $hasVariations = !empty($fv) || !empty($co);
            }

            $items[] = [
                'cartKey'                  => $i,
                'order_detail_id'          => $c['id']            ?? null,
                'item_id'                  => $c['item_id']        ?? null,
                'campaign_item_id'         => $c['item_campaign_id'] ?? null,
                'item_type'                => ($c['item_campaign_id'] ?? null) ? 'campaign' : 'item',
                'name'                     => $productObj ? $productObj->name : ($c['name'] ?? 'Item'),
                'image'                    => $productObj ? ($productObj->image_full_url ?? null) : null,
                'price'                    => (float)($c['price'] ?? 0),
                'quantity'                 => (int)($c['quantity'] ?? 1),
                'discount'                 => (float)($c['discount_on_item'] ?? 0),
                'tax'                      => (float)($c['tax_amount'] ?? 0),
                'addon_price'              => (float)($c['add_ons_cost'] ?? $c['total_add_on_price'] ?? 0),
                'status'                   => $c['status'] ?? true,
                'has_variations'           => $hasVariations,
                // Status placeholders — overwritten below with fresh DB values
                'is_unavailable'           => (bool)($c['is_unavailable']          ?? false),
                'is_picked_up'             => (bool)($c['is_picked_up']            ?? false),
                'is_outside_purchase'      => (bool)($c['is_outside_purchase']     ?? false),
                'outside_purchase_cost'    => (float)($c['outside_purchase_cost']  ?? 0),
                'outside_purchase_status'  => $c['outside_purchase_status']        ?? null,
                'requested_mrp'            => $c['requested_mrp'] ? (float)$c['requested_mrp'] : null,
                'mrp_update_status'        => $c['mrp_update_status']              ?? null,
            ];
        }

        // Merge fresh DB values so the cart always reflects latest AJAX-update state
        $detailIds = array_filter(array_column($items, 'order_detail_id'));
        if (!empty($detailIds)) {
            $fresh = \App\Models\OrderDetail::whereIn('id', $detailIds)
                ->get(['id', 'price', 'discount_on_item', 'is_unavailable', 'is_picked_up',
                        'is_outside_purchase', 'outside_purchase_cost', 'outside_purchase_status',
                        'requested_mrp', 'mrp_update_status'])
                ->keyBy('id');

            foreach ($items as &$item) {
                $d = $fresh[$item['order_detail_id']] ?? null;
                if ($d) {
                    $item['price']                   = (float)$d->price;
                    $item['discount']                = (float)$d->discount_on_item;
                    $item['is_unavailable']          = (bool)$d->is_unavailable;
                    $item['is_picked_up']            = (bool)$d->is_picked_up;
                    $item['is_outside_purchase']     = (bool)$d->is_outside_purchase;
                    $item['outside_purchase_cost']   = (float)$d->outside_purchase_cost;
                    $item['outside_purchase_status'] = $d->outside_purchase_status;
                    $item['requested_mrp']           = $d->requested_mrp ? (float)$d->requested_mrp : null;
                    $item['mrp_update_status']       = $d->mrp_update_status;
                }
            }
            unset($item);
        }

        // Outside purchase aggregate for totals display
        $orderId = session('editing_order_id');
        $outsidePurchaseAmount = 0;
        if ($orderId) {
            $outsidePurchaseAmount = \App\Models\OrderDetail::where('order_id', $orderId)
                ->where('is_outside_purchase', true)
                ->selectRaw('COALESCE(SUM(outside_purchase_cost * quantity), 0) as total')
                ->value('total') ?? 0;
        }

        return response()->json(['success' => true, 'cart' => $items, 'outside_purchase_amount' => (float)$outsidePurchaseAmount]);
    }

    public function export_orders($file_type, $status, $type, Request $request)
    {
        $key = explode(' ', $request['search']);

        if (session()->has('zone_filter') == false) {
            session()->put('zone_filter', 0);
        }

        $module_id = $request->query('module_id', null);

        // ✅ FIX: Merge session filters into Request instead of replacing it
        // ✅ ENHANCED: Added validation to prevent stdClass bug
        if (session()->has('order_filter')) {
            try {
                $sessionFilters = json_decode(session('order_filter'), true); // Decode as array
                if (is_array($sessionFilters)) {
                    $request->merge($sessionFilters); // Merge into existing Request object
                } else {
                    session()->forget('order_filter');
                    \Log::warning('Corrupted order_filter session data cleared in export_orders');
                }
            } catch (\Exception $e) {
                session()->forget('order_filter');
                \Log::error('Failed to decode order_filter session in export_orders', ['error' => $e->getMessage()]);
            }
        }

        // ✅ DEFENSIVE CHECK: Ensure $request is still a Request object
        if (!($request instanceof \Illuminate\Http\Request)) {
            \Log::critical('Request object replaced in export_orders - recreating');
            $request = \Illuminate\Http\Request::createFromGlobals();
        }

        Order::where(['checked' => 0])->update(['checked' => 1]);

        $orders = Order::with(['customer', 'store'])
            ->when(isset($module_id), function ($query) use ($module_id) {
                return $query->module($module_id);
            })
            ->when(isset($request->zone), function ($query) use ($request) {
                return $query->whereHas('store', function ($q) use ($request) {
                    return $q->whereIn('zone_id', $request->zone);
                });
            })
            ->when($status == 'scheduled', function ($query) {
                return $query->whereRaw('created_at <> schedule_at');
            })
            ->when($status == 'searching_for_deliverymen', function ($query) {
                return $query->SearchingForDeliveryman();
            })
            ->when($status == 'pending', function ($query) {
                return $query->Pending();
            })
            ->when($status == 'accepted', function ($query) {
                return $query->AccepteByDeliveryman();
            })
            ->when($status == 'processing', function ($query) {
                return $query->Preparing();
            })
            ->when($status == 'item_on_the_way', function ($query) {
                return $query->ItemOnTheWay();
            })
            ->when($status == 'delivered', function ($query) {
                return $query->Delivered();
            })
            ->when($status == 'canceled', function ($query) {
                return $query->Canceled();
            })
            ->when($status == 'failed', function ($query) {
                return $query->failed();
            })
            ->when($status == 'refunded', function ($query) {
                return $query->Refunded();
            })
            ->when($status == 'scheduled', function ($query) {
                return $query->Scheduled();
            })
            ->when($status == 'on_going', function ($query) {
                return $query->Ongoing();
            })
            ->when(($status != 'all' && $status != 'scheduled' && $status != 'canceled' && $status != 'refund_requested' && $status != 'refunded' && $status != 'delivered' && $status != 'failed'), function ($query) {
                return $query->OrderScheduledIn(30);
            })
            ->when(isset($request->vendor), function ($query) use ($request) {
                return $query->whereHas('store', function ($query) use ($request) {
                    return $query->whereIn('id', $request->vendor);
                });
            })
            ->when(isset($request->orderStatus) && $status == 'all', function ($query) use ($request) {
                return $query->whereIn('order_status', $request->orderStatus);
            })
            ->when(isset($request->scheduled) && $status == 'all', function ($query) {
                return $query->scheduled();
            })
            ->when(isset($request->order_type) && $type == 'order', function ($query) use ($request) {
                return $query->where('order_type', $request->order_type);
            })
            ->when(isset($request->from_date) && isset($request->to_date) && $request->from_date != null && $request->to_date != null, function ($query) use ($request) {
                return $query->whereBetween('created_at', [$request->from_date . " 00:00:00", $request->to_date . " 23:59:59"]);
            })
            ->when($type == 'order', function ($query) {
                $query->StoreOrder();
            })
            ->when($type == 'parcel', function ($query) {
                $query->ParcelOrder();
            })
            ->when(isset($key), function ($query) use ($key) {
                return $query->where(function ($q) use ($key) {
                    foreach ($key as $value) {
                        $q->orWhere('id', 'like', "%{$value}%")
                            ->orWhere('order_status', 'like', "%{$value}%")
                            ->orWhere('transaction_reference', 'like', "%{$value}%");
                    }
                });
            })
            ->module(Config::get('module.current_module_id'))
            ->orderBy('schedule_at', 'desc')
            ->get();

            $data = [
                'orders'=>$orders,
                'type'=>$type,
                'status'=>$status,
                'order_status'=>isset($request->orderStatus)?implode(', ', $request->orderStatus):null,
                'search'=>$request->search??null,
                'from'=>$request->from_date??null,
                'to'=>$request->to_date??null,
                'zones'=>isset($request->zone)?Helpers::get_zones_name($request->zone):null,
                'stores'=>isset($request->vendor)?Helpers::get_stores_name($request->vendor):null,
            ];

        if ($file_type == 'excel') {
            return Excel::download(new OrderExport($data), 'Orders.xlsx');
        } else if ($file_type == 'csv') {
            return Excel::download(new OrderExport($data), 'Orders.csv');
        }
        // if ($file_type == 'excel') {
        //     return (new FastExcel(OrderLogic::format_export_data($orders, $type)))->download('Orders.xlsx');
        // } else if ($file_type == 'csv') {
        //     return (new FastExcel(OrderLogic::format_export_data($orders, $type)))->download('Orders.csv');
        // }
        return (new FastExcel(OrderLogic::format_export_data($orders, $type)))->download('Orders.xlsx');
    }

    public function store_order_search(Request $request)
    {
        $key = explode(' ', $request['search']);
        $orders = Order::where(function ($q) use ($key) {
            foreach ($key as $value) {
                $q->orWhere('id', 'like', "%{$value}%");
            }
        })->limit(50)->get();

        return response()->json([
            'view' => view('admin-views.vendor.view.partials._order', compact('orders'))->render()
        ]);
    }
    public function store_order_export(Request $request)
    {
        $key = explode(' ', $request['search']);
        $orders = Order::where('store_id', $request->store_id)->Notpos()
            ->when(isset($key ), function ($q) use ($key){
                $q->where(function ($q) use ($key) {
                    foreach ($key as $value) {
                        $q->orWhere('id', 'like', "%{$value}%");
                    }
                });
            })
            ->get();
        $store= Store::where('id', $request->store_id)->select(['id','zone_id'])->first();
        $data = [
            'data'=>$orders,
            'search'=>request()->search ?? null,
            'zone'=>Helpers::get_zones_name($store->zone_id) ,
            'store'=>  Helpers::get_stores_name($store->id),
        ];

        if($request->type == 'csv'){
            return Excel::download(new StoreOrderlistExport($data), 'OrderList.csv');
        }
        return Excel::download(new StoreOrderlistExport($data), 'OrderList.xlsx');



        // if ($type == 'excel') {
        //     return (new FastExcel(OrderLogic::format_export_data($orders, $type)))->download('Orders.xlsx');
        // } else if ($type == 'csv') {
        //     return (new FastExcel(OrderLogic::format_export_data($orders, $type)))->download('Orders.csv');
        // }
        // return (new FastExcel(OrderLogic::format_export_data($orders, $type)))->download('Orders.xlsx');
    }


    public function refund_settings()
    {
        $refund_active_status = BusinessSetting::where(['key' => 'refund_active_status'])->first();
        $reasons = RefundReason::orderBy('id', 'desc')
            ->paginate(config('default_pagination'));
        return view('admin-views.refund.index', compact('refund_active_status', 'reasons'));
    }

    public function refund_reason(Request $request)
    {
        $request->validate([
            'reason' => 'required|max:191',
            'reason.0' => 'required',
        ],[
            'reason.0.required'=>translate('default_reason_is_required'),
        ]);

        $reason = new RefundReason();
        $reason->reason = $request->reason[array_search('default', $request->lang)];
        $reason->save();
        $data = [];
        $default_lang = str_replace('_', '-', app()->getLocale());
        foreach ($request->lang as $index => $key) {
            if($default_lang == $key && !($request->reason[$index])){
                if ($key != 'default') {
                    array_push($data, array(
                        'translationable_type' => 'App\Models\RefundReason',
                        'translationable_id' => $reason->id,
                        'locale' => $key,
                        'key' => 'reason',
                        'value' => $reason->reason,
                    ));
                }
            }else{
                if ($request->reason[$index] && $key != 'default') {
                    array_push($data, array(
                        'translationable_type' => 'App\Models\RefundReason',
                        'translationable_id' => $reason->id,
                        'locale' => $key,
                        'key' => 'reason',
                        'value' => $request->reason[$index],
                    ));
                }
            }
        }
        Translation::insert($data);
        Toastr::success(translate('Refund Reason Added Successfully'));
        return back();
    }
    public function reason_edit(Request $request)
    {
        $request->validate([
            'reason' => 'required|max:191',
            'reason.0' => 'required',
        ],[
            'reason.0.required'=>translate('default_reason_is_required'),
        ]);
        $refund_reason = RefundReason::findOrFail($request->reason_id);
        $refund_reason->reason = $request->reason[array_search('default', $request->lang1)];
        $refund_reason->save();

        $default_lang = str_replace('_', '-', app()->getLocale());
        foreach ($request->lang1 as $index => $key) {
            if($default_lang == $key && !($request->reason[$index])){
                if ($key != 'default') {
                    Translation::updateOrInsert(
                        [
                            'translationable_type' => 'App\Models\RefundReason',
                            'translationable_id' => $refund_reason->id,
                            'locale' => $key,
                            'key' => 'reason'
                        ],
                        ['value' => $refund_reason->reason]
                    );
                }
            }else{
                if ($request->reason[$index] && $key != 'default') {
                    Translation::updateOrInsert(
                        [
                            'translationable_type' => 'App\Models\RefundReason',
                            'translationable_id' => $refund_reason->id,
                            'locale' => $key,
                            'key' => 'reason'
                        ],
                        ['value' => $request->reason[$index]]
                    );
                }
            }
        }


        Toastr::success(translate('Refund Reason Updated Successfully'));
        return back();
    }
    public function reason_delete(Request $request)
    {
        $refund_reason = RefundReason::findOrFail($request->id);
        $refund_reason?->translations()?->delete();
        $refund_reason->delete();
        Toastr::success(translate('Refund Reason Deleted Successfully'));
        return back();
    }
    public function reason_status(Request $request)
    {
        $refund_reason = RefundReason::findOrFail($request->id);
        $refund_reason->status = $request->status;
        $refund_reason->save();
        Toastr::success(translate('messages.status_updated'));
        return back();
    }

    public function order_refund_rejection(Request $request)
    {
        $request->validate([
            'order_id' => 'required',
            'admin_note' => 'nullable|string|max:65535',
        ]);
        Refund::where('order_id', $request->order_id)->update([
            'order_status' => 'refund_request_canceled',
            'admin_note' => $request->admin_note ?? null,
            'refund_status' => 'rejected',
            'refund_method' => 'canceled',
        ]);

        $order = Order::Notpos()->find($request->order_id);
        $order->order_status = 'refund_request_canceled';
        $order->refund_request_canceled = now();
        $order->save();
        try {


            if(Helpers::getNotificationStatusData('customer','customer_refund_request_rejaction','push_notification_status')  && isset($order?->customer?->cm_firebase_token))
            {
                $data = [
                    'title' => translate('messages.Refund Canceled'),
                    'description' => translate('Your Refund request has been Rejected'),
                    'order_id' => $order->id,
                    'image' => '',
                    'type'=> 'order_status',
                    'order_status' => $order->order_status,
                ];
                Helpers::send_push_notif_to_device($order?->customer?->cm_firebase_token, $data);

                DB::table('user_notifications')->insert([
                    'data'=> json_encode($data),
                    'user_id'=>$order?->customer?->id,
                    'created_at'=>now(),
                    'updated_at'=>now()
                ]);
            }

            if(config('mail.status') && $order?->customer?->email && Helpers::get_mail_status('refund_request_deny_mail_status_user') == '1' &&  Helpers::getNotificationStatusData('customer','customer_refund_request_rejaction','mail_status')){
                Mail::to($order->customer->email)->send(new RefundRejected($order->id));
            }
        } catch (\Throwable $th) {
            info($th->getMessage());
            Toastr::error(translate('messages.Failed_to_send_mail'));
        }
        Toastr::success(translate('Refund Rejection Successfully'));
        Helpers::send_order_notification($order);
        return back();
    }


    public function refund_mode()
    {
        $refund_mode = BusinessSetting::where('key', 'refund_active_status')->first();
        if (isset($refund_mode) == false) {
            Helpers::businessInsert([
                'key' => 'refund_active_status',
                'value' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            Helpers::businessUpdateOrInsert(['key' => 'refund_active_status'], [
                'value' => $refund_mode->value == 1 ? 0 : 1
            ]);
        }

        if (isset($refund_mode) && $refund_mode->value) {
            return response()->json(['message' => 'Order Refund Request Mode is off.']);
        }
        return response()->json(['message' => 'Order Refund Request Mode is on.']);
    }

    public function offline_payment(Request $request){
            $order=  Order::findOrFail($request->id);
            if($request->verify == 'yes'){

                $order->payment_status = 'paid';
                $order->confirmed = now();
                $order->order_status = 'confirmed';
                $order->save();
                Helpers::send_order_notification($order);
                $order->offline_payments()->update([
                    'status'=> 'verified'
                ]);

                if( $order?->store?->is_valid_subscription == 1 && $order?->store?->store_sub?->max_order != "unlimited" && $order?->store?->store_sub?->max_order > 0){
                    $order?->store?->store_sub?->decrement('max_order' , 1);
                }

                $payment_method_name = json_decode($order->offline_payments->payment_info, true)['method_name'];
                if($order->payment_method == 'partial_payment'){
                    $order->payments()->where('payment_status','unpaid')->update([
                        'payment_method'=>  $payment_method_name,
                        'payment_status'=> 'paid',
                    ]);
                }
                $value = Helpers::text_variable_data_format(value:Helpers::order_status_update_message('offline_verified',$order->module->module_type),store_name:$order->store?->name,order_id:$order->id,user_name:"{$order?->customer?->f_name} {$order?->customer?->l_name}",delivery_man_name:"{$order?->delivery_man?->f_name} {$order?->delivery_man?->l_name}");
                $data = [
                    'title' => translate('messages.Your_Offline_payment_is_approved'),
                    'description' => $value == false  ||  $value == null ? ' ' :  $value ,
                    'order_id' => $order->id,
                    'image' => '',
                    'type' => 'order_status',
                ];

                $fcm= $order->is_guest == 0 ? $order?->customer?->cm_firebase_token : $order?->guest?->fcm_token;


                if($fcm  && Helpers::getNotificationStatusData('customer','customer_offline_payment_approve','push_notification_status') ){
                    Helpers::send_push_notif_to_device($fcm, $data);
                    DB::table('user_notifications')->insert([
                        'data' => json_encode($data),
                        'user_id' => $order->user_id,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }


                $order->payment_method = $payment_method_name;
                    if($order->is_guest == 0){
                        $this->sent_mail_on_offline_payment(status:'approved', name:$order?->customer?->f_name .' '.$order?->customer?->l_name, email:  $order?->customer?->email , otp: $order->otp);
                    }
                }

            elseif($request->verify == 'switched_to_cod'){

                $order->offline_payments()->delete();

                if($order->payment_method == 'partial_payment'){
                    $order->payments()->where('payment_status','unpaid')->update([
                        'payment_method'=> 'cash_on_delivery',
                    ]);
                }

                if( $order?->store?->is_valid_subscription == 1 && $order?->store?->store_sub?->max_order != "unlimited" && $order?->store?->store_sub?->max_order > 0){
                    $order?->store?->store_sub?->decrement('max_order' , 1);
                }

                Helpers::send_order_notification($order);
                $order->payment_method = 'cash_on_delivery';
                $order->save();

                if($order->is_guest == 0){
                    $this->sent_mail_on_offline_payment(status:'COD', name:$order?->customer?->f_name .' '.$order?->customer?->l_name, email:  $order?->customer?->email ,order_id: $order->id);
                }

            }

            else{
                $order->offline_payments()->update([
                    'status'=> 'denied',
                    'note'=> $request->note ?? null
                ]);


                $value = Helpers::text_variable_data_format(value:Helpers::order_status_update_message('offline_denied',$order->module->module_type),store_name:$order->store?->name,order_id:$order->id,user_name:"{$order?->customer?->f_name} {$order?->customer?->l_name}",delivery_man_name:"{$order?->delivery_man?->f_name} {$order?->delivery_man?->l_name}");

                    $data = [
                        'title' => translate('messages.Your_Offline_payment_was_rejected'),
                        'description' => $value ?? $request->note,
                        'order_id' => $order->id,
                        'image' => '',
                        'type' => 'order_status',
                    ];

                    $fcm= $order->is_guest == 0 ? $order?->customer?->cm_firebase_token : $order?->guest?->fcm_token ;
                    if($fcm && ( $value || $request->note) &&  Helpers::getNotificationStatusData('customer','customer_offline_payment_deny','push_notification_status')){
                        Helpers::send_push_notif_to_device($fcm, $data);
                        DB::table('user_notifications')->insert([
                            'data' => json_encode($data),
                            'user_id' => $order->user_id,
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                    }
                    if($order->is_guest == 0){
                        $this->sent_mail_on_offline_payment(status:'denied', name:$order?->customer?->f_name .' '.$order?->customer?->l_name, email:  $order?->customer?->email);
                    }
            }

            Toastr::success(translate('Payment_status_updated'));
            return back();
    }


    private function sent_mail_on_offline_payment($status, $name ,$email ,$otp=null ,$order_id = null){
        try
        {
            if($status == 'approved' && config('mail.status') ){

                if(Helpers::get_mail_status('offline_payment_approve_mail_status_user') == '1' &&  Helpers::getNotificationStatusData('customer','customer_offline_payment_approve','mail_status')){
                    Mail::to($email)->send(new UserOfflinePaymentMail($name, 'approved'));
                }

                if ( Helpers::get_mail_status('order_verification_mail_status_user') == '1'  && $otp  && Helpers::getNotificationStatusData('customer','customer_delivery_verification','mail_status') ) {
                    Mail::to($email)->send(new OrderVerificationMail($otp, $name));
                }
            }

            if($status == 'COD' && $order_id  && config('mail.status')  && Helpers::getNotificationStatusData('customer','customer_order_notification','mail_status'))
            {
                Mail::to($email)->send(new PlaceOrder($order_id));
            }
            if($status == 'denied' && config('mail.status') && Helpers::get_mail_status('offline_payment_deny_mail_status_user') == '1' &&  Helpers::getNotificationStatusData('customer','customer_offline_payment_deny','mail_status')){
                Mail::to($email)->send(new UserOfflinePaymentMail($name, 'denied'));
            }
        }
        catch(\Exception $e)
        {
            Toastr::error(translate('Failed_to_Send_Email'));
            info($e->getMessage());
            return true;
        }
        return true ;
    }

    public function offline_verification_list(Request $request, $status)
    {
        $key = explode(' ', $request['search']);
        $orders = Order::with(['customer', 'store'])->has('offline_payments')
            ->when(isset($key), function ($query) use ($key) {
                return $query->where(function ($q) use ($key) {
                    foreach ($key as $value) {
                        $q->orWhere('id', 'like', "%{$value}%")
                            ->orWhere('order_status', 'like', "%{$value}%")
                            ->orWhere('transaction_reference', 'like', "%{$value}%");
                    }
                });
            })
            ->when($status == 'pending' , function ($query) {
                return $query->whereHas('offline_payments', function ($query) {
                    return $query->where('status', 'pending');
                });
            })
            ->when($status == 'denied' , function ($query) {
                return $query->whereHas('offline_payments', function ($query) {
                    return $query->where('status', 'denied');
                });
            })
            ->when($status == 'verified' , function ($query) {
                return $query->whereHas('offline_payments', function ($query) {
                    return $query->where('status', 'verified');
                });
            })
            ->StoreOrder()
            ->module(Config::get('module.current_module_id'))
            ->orderBy('schedule_at', 'desc')
            ->paginate(config('default_pagination'));

        return view('admin-views.order.offline_verification_list', compact('orders', 'status'));
    }




/**
 * Get alternative items with smart ranking
 */
public function getAlternatives(Request $request)
{
    try {
        $itemId = $request->item_id;
        $storeId = $request->store_id;
        
        // Get the original item
        $originalItem = \App\Models\Item::find($itemId);
        
        if (!$originalItem) {
            return response()->json(['success' => true, 'alternatives' => []]);
        }
        
        $originalPrice = $originalItem->price;
        
        // Strategy 1: Exact category match (highest priority)
        $exactMatches = \App\Models\Item::where('store_id', $storeId)
            ->where('id', '!=', $itemId)
            ->where('category_id', $originalItem->category_id)
            ->where('status', 1)
            ->get();
        
        // Strategy 2: Similar price range from other categories
        $priceMatches = \App\Models\Item::where('store_id', $storeId)
            ->where('id', '!=', $itemId)
            ->where('category_id', '!=', $originalItem->category_id)
            ->whereBetween('price', [$originalPrice * 0.8, $originalPrice * 1.2])
            ->where('status', 1)
            ->limit(3)
            ->get();
        
        // Combine and rank alternatives
        $allAlternatives = $exactMatches->concat($priceMatches);
        
        // Rank by similarity score
        $rankedAlternatives = $allAlternatives->map(function($item) use ($originalItem, $originalPrice) {
            $score = 100; // Base score
            
            // Same category bonus
            if ($item->category_id == $originalItem->category_id) {
                $score += 50;
            }
            
            // Price similarity bonus
            $priceDiff = abs($item->price - $originalPrice);
            if ($priceDiff <= $originalPrice * 0.1) {
                $score += 30; // Very similar price
            } elseif ($priceDiff <= $originalPrice * 0.2) {
                $score += 20; // Similar price
            } elseif ($priceDiff <= $originalPrice * 0.5) {
                $score += 10; // Somewhat similar
            }
            
            // Name similarity bonus
            similar_text(strtolower($originalItem->name), strtolower($item->name), $percent);
            $score += $percent / 2;
            
            // Popularity bonus (if avg_rating exists)
            if (isset($item->avg_rating) && $item->avg_rating > 4) {
                $score += 15;
            }
            
            // Calculate price difference
            $priceDifference = $item->price - $originalPrice;
            $priceChangePercent = ($priceDifference / $originalPrice) * 100;
            
            return [
                'id' => $item->id,
                'name' => $item->name,
                'price' => \App\CentralLogics\Helpers::format_currency($item->price),
                'price_raw' => $item->price,
                'price_difference' => $priceDifference,
                'price_change_percent' => round($priceChangePercent, 1),
                'image' => $item->image_full_url ?? asset('public/assets/admin/img/100x100/2.png'),
                'category' => optional($item->category)->name ?? 'N/A',
                'same_category' => $item->category_id == $originalItem->category_id,
                'rating' => $item->avg_rating ?? 0,
                'in_stock' => true,
                'match_score' => round($score, 1)
            ];
        })
        ->sortByDesc('match_score')
        ->take(10) // Show top 10 alternatives
        ->values();
        
        return response()->json([
            'success' => true,
            'alternatives' => $rankedAlternatives,
            'original_item' => [
                'name' => $originalItem->name,
                'price' => \App\CentralLogics\Helpers::format_currency($originalPrice),
                'category' => optional($originalItem->category)->name ?? 'N/A'
            ]
        ]);
        
    } catch (\Exception $e) {
        \Log::error('Get alternatives error: ' . $e->getMessage());
        return response()->json(['success' => true, 'alternatives' => []]);
    }
}
/**
 * Mark item as unavailable (status = 0)
 */
public function markItemUnavailable(Request $request)
{
    try {
        $orderDetailId = $request->order_detail_id;
        
        $orderDetail = OrderDetail::find($orderDetailId);
        
        if (!$orderDetail) {
            return response()->json([
                'success' => false,
                'message' => 'Order detail not found'
            ]);
        }
        
        // Toggle unavailability (is_unavailable=1 marks unavailable, 0 marks available)
        $markUnavailable = (bool)($request->is_unavailable ?? 1);
        $orderDetail->is_unavailable = $markUnavailable;
        $orderDetail->save();

        // 🔥 CRITICAL FIX: Update session cart so Save doesn't overwrite
        $cart = $request->session()->get('order_cart');
        if ($cart) {
            $cart = collect($cart);
            foreach ($cart as $key => $item) {
                if (isset($item['id']) && $item['id'] == $orderDetail->id) {
                    $cart[$key]['is_unavailable'] = $markUnavailable;
                    break;
                }
            }
            $request->session()->put('order_cart', $cart);
        }

        return response()->json([
            'success' => true,
            'message' => $markUnavailable ? 'Item marked as unavailable.' : 'Item marked as available.',
            'is_unavailable' => $markUnavailable,
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}




/**
 * Get cart data via AJAX for refresh
 */
public function getCartData(Request $request)
{
    try {
        // Get latest carts
        $latest_carts_raw = \DB::table('carts')
            ->whereNotNull('user_id')
            ->where('is_guest', 0)
            ->orderBy('created_at', 'DESC')
            ->limit(50)
            ->get();
        
        // Get unique user IDs
        $userIds = $latest_carts_raw->pluck('user_id')->unique()->take(15);
        
        // Load users
        $users = \App\Models\User::whereIn('id', $userIds)->get()->keyBy('id');
        
        // Get all item IDs
        $itemIds = $latest_carts_raw->whereIn('user_id', $userIds)->pluck('item_id')->unique();
        
        // Load items
        $items = \App\Models\Item::whereIn('id', $itemIds)->get()->keyBy('id');
        
        // Group carts by user
        $latest_carts = $latest_carts_raw->whereIn('user_id', $userIds)->groupBy('user_id')->take(15);
        
        // Render HTML view
        $html = view('admin-views.partials._customer-carts-grid', compact('latest_carts', 'users', 'items'))->render();
        
        return response()->json([
            'success' => true,
            'html' => $html,
            'count' => $latest_carts->count(),
            'cartData' => $latest_carts,
            'usersData' => $users,
            'itemsData' => $items
        ]);
        
    } catch (\Exception $e) {
        \Log::error('Cart data error: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => $e->getMessage()
        ], 500);
    }
}

    /**
     * Get tracking stats for an order (AJAX endpoint)
     */
    public function getTrackingStats($orderId)
    {
        try {
            $stats = DeliveryTrackingStat::where('order_id', $orderId)->first();

            if (!$stats) {
                return response()->json([
                    'success' => true,
                    'stats' => null,
                    'message' => 'No tracking stats yet'
                ]);
            }

            return response()->json([
                'success' => true,
                'stats' => [
                    'is_at_store' => $stats->is_at_store,
                    'is_at_customer' => $stats->is_at_customer,
                    'is_idle' => $stats->is_idle,
                    'idle_start_time' => $stats->idle_start_time?->toIso8601String(),
                    'store_arrival_time' => $stats->store_arrival_time?->toIso8601String(),
                    'customer_arrival_time' => $stats->customer_arrival_time?->toIso8601String(),
                    'total_idle_seconds' => $stats->getCurrentIdleSeconds(),
                    'store_duration_seconds' => $stats->getCurrentStoreDurationSeconds(),
                    'customer_duration_seconds' => $stats->getCurrentCustomerDurationSeconds(),
                    'movement_state' => $stats->movement_state,
                    'last_speed' => $stats->last_speed,
                    'formatted_idle_time' => DeliveryTrackingStat::formatDuration($stats->getCurrentIdleSeconds()),
                    'formatted_store_time' => DeliveryTrackingStat::formatDuration($stats->getCurrentStoreDurationSeconds()),
                    'formatted_customer_time' => DeliveryTrackingStat::formatDuration($stats->getCurrentCustomerDurationSeconds()),
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Get tracking stats error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get delivery man idle time ranking
     */
    public function getDeliveryManIdleRanking(Request $request)
    {
        try {
            $dateFrom = $request->get('from', now()->startOfDay());
            $dateTo = $request->get('to', now()->endOfDay());

            $ranking = DeliveryTrackingStat::select('delivery_man_id')
                ->selectRaw('SUM(total_idle_seconds) as total_idle')
                ->selectRaw('SUM(store_duration_seconds) as total_store_time')
                ->selectRaw('SUM(customer_duration_seconds) as total_customer_time')
                ->selectRaw('COUNT(*) as order_count')
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->groupBy('delivery_man_id')
                ->orderBy('total_idle', 'desc')
                ->with('deliveryMan:id,f_name,l_name,phone,image')
                ->paginate($request->get('limit', 20));

            // Format the data
            $ranking->getCollection()->transform(function ($item) {
                return [
                    'delivery_man_id' => $item->delivery_man_id,
                    'delivery_man' => $item->deliveryMan,
                    'total_idle_seconds' => $item->total_idle,
                    'total_store_seconds' => $item->total_store_time,
                    'total_customer_seconds' => $item->total_customer_time,
                    'order_count' => $item->order_count,
                    'formatted_idle_time' => DeliveryTrackingStat::formatDuration($item->total_idle),
                    'formatted_store_time' => DeliveryTrackingStat::formatDuration($item->total_store_time),
                    'formatted_customer_time' => DeliveryTrackingStat::formatDuration($item->total_customer_time),
                    'avg_idle_per_order' => $item->order_count > 0
                        ? DeliveryTrackingStat::formatDuration(round($item->total_idle / $item->order_count))
                        : '0s',
                ];
            });

            return response()->json([
                'success' => true,
                'ranking' => $ranking
            ]);
        } catch (\Exception $e) {
            \Log::error('Get idle ranking error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function mark_outside_purchase(Request $request)
    {
        $request->validate([
            'order_detail_id' => 'required|integer',
            'outside_purchase_cost' => 'required|numeric|min:0',
            'outside_purchase_store_id' => 'nullable|integer|exists:stores,id',
        ]);

        $detail = OrderDetail::findOrFail($request->order_detail_id);

        // Admin directly marking as outside purchase (auto-approved)
        $detail->is_outside_purchase = true;
        $detail->outside_purchase_cost = $request->outside_purchase_cost;
        $detail->outside_purchase_store_id = $request->outside_purchase_store_id;
        $detail->outside_purchase_status = 'approved';
        $detail->outside_purchase_requested_by = 'admin';
        $detail->outside_purchase_approved_by = auth('admin')->id();
        $detail->outside_purchase_approved_at = now();
        $detail->save();

        // Recalculate order's total outside purchase amount
        $order = $detail->order;
        $order->outside_purchase_amount = OrderDetail::where('order_id', $order->id)
            ->where('is_outside_purchase', true)
            ->selectRaw('SUM(outside_purchase_cost * quantity) as total')
            ->value('total') ?? 0;
        $order->save();

        // 🔥 CRITICAL FIX: Update session cart so Save doesn't overwrite
        $cart = $request->session()->get('order_cart');
        if ($cart) {
            $cart = collect($cart);
            foreach ($cart as $key => $item) {
                if (isset($item['id']) && $item['id'] == $detail->id) {
                    $cart[$key]['is_outside_purchase'] = true;
                    $cart[$key]['outside_purchase_cost'] = $request->outside_purchase_cost;
                    $cart[$key]['outside_purchase_store_id'] = $request->outside_purchase_store_id;
                    $cart[$key]['outside_purchase_status'] = 'approved';
                    break;
                }
            }
            $request->session()->put('order_cart', $cart);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => translate('messages.outside_purchase_marked_successfully'),
                'outside_purchase_amount' => $order->outside_purchase_amount,
            ]);
        }
        Toastr::success(translate('messages.outside_purchase_marked_successfully'));
        return back();
    }

    public function approve_outside_purchase(Request $request)
    {
        $request->validate([
            'order_detail_id' => 'required|integer',
        ]);

        $detail = OrderDetail::findOrFail($request->order_detail_id);

        // Validate that the request is pending
        if ($detail->outside_purchase_status !== 'pending') {
            Toastr::error(translate('messages.outside_purchase_not_pending'));
            return back();
        }

        $detail->is_outside_purchase = true;
        $detail->outside_purchase_status = 'approved';
        $detail->outside_purchase_approved_by = auth('admin')->id();
        $detail->outside_purchase_approved_at = now();
        $detail->save();

        // Recalculate order's total outside purchase amount
        $order = $detail->order;
        $order->outside_purchase_amount = OrderDetail::where('order_id', $order->id)
            ->where('is_outside_purchase', true)
            ->selectRaw('SUM(outside_purchase_cost * quantity) as total')
            ->value('total') ?? 0;
        $order->save();

        // 🔥 CRITICAL FIX: Update session cart so Save doesn't overwrite
        $cart = $request->session()->get('order_cart');
        if ($cart) {
            $cart = collect($cart);
            foreach ($cart as $key => $item) {
                if (isset($item['id']) && $item['id'] == $detail->id) {
                    $cart[$key]['is_outside_purchase'] = true;
                    $cart[$key]['outside_purchase_status'] = 'approved';
                    break;
                }
            }
            $request->session()->put('order_cart', $cart);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => translate('messages.outside_purchase_approved_successfully'),
                'outside_purchase_amount' => $order->outside_purchase_amount,
            ]);
        }
        Toastr::success(translate('messages.outside_purchase_approved_successfully'));
        return back();
    }

    public function reject_outside_purchase(Request $request)
    {
        $request->validate([
            'order_detail_id' => 'required|integer',
            'rejection_reason' => 'nullable|string|max:500',
        ]);

        $detail = OrderDetail::findOrFail($request->order_detail_id);

        // Validate that the request is pending
        if ($detail->outside_purchase_status !== 'pending') {
            Toastr::error(translate('messages.outside_purchase_not_pending'));
            return back();
        }

        $detail->is_outside_purchase = false;
        $detail->outside_purchase_status = 'rejected';
        $detail->outside_purchase_approved_by = auth('admin')->id();
        $detail->outside_purchase_approved_at = now();
        $detail->outside_purchase_rejection_reason = $request->rejection_reason;
        $detail->save();

        // 🔥 CRITICAL FIX: Update session cart so Save doesn't overwrite
        $cart = $request->session()->get('order_cart');
        if ($cart) {
            $cart = collect($cart);
            foreach ($cart as $key => $item) {
                if (isset($item['id']) && $item['id'] == $detail->id) {
                    $cart[$key]['is_outside_purchase'] = false;
                    $cart[$key]['outside_purchase_status'] = 'rejected';
                    $cart[$key]['outside_purchase_rejection_reason'] = $request->rejection_reason;
                    break;
                }
            }
            $request->session()->put('order_cart', $cart);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => translate('messages.outside_purchase_rejected_successfully'),
            ]);
        }
        Toastr::success(translate('messages.outside_purchase_rejected_successfully'));
        return back();
    }

    public function get_stores_for_outside_purchase(Request $request)
    {
        $search = $request->get('search', '');
        $limit = $request->get('limit', 100);
        $moduleId = $request->get('module_id');

        $stores = \App\Models\Store::where('status', 1)
            ->when($moduleId, function($query) use ($moduleId) {
                $query->where('module_id', $moduleId);
            })
            ->when($search, function($query) use ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%')
                      ->orWhere('id', $search);
                });
            })
            ->select('id', 'name', 'address', 'zone_id', 'module_id')
            ->with(['zone:id,name', 'module:id,module_name'])
            ->orderBy('name')
            ->limit($limit)
            ->get()
            ->map(function($store) {
                return [
                    'id' => $store->id,
                    'text' => $store->name . ' - ' . ($store->zone ? $store->zone->name : 'N/A'),
                    'name' => $store->name,
                    'address' => $store->address ?? '',
                    'zone' => $store->zone ? $store->zone->name : 'N/A',
                    'module' => $store->module ? $store->module->module_name : 'N/A',
                ];
            });

        return response()->json($stores);
    }

    public function mark_full_order_outside_purchase(Request $request)
    {
        $request->validate([
            'order_id' => 'required|integer',
            'outside_purchase_cost' => 'required|numeric|min:0',
            'outside_purchase_store_id' => 'nullable|integer|exists:stores,id',
        ]);

        $order = Order::with('details')->findOrFail($request->order_id);
        $details = $order->details;

        if ($details->isEmpty()) {
            Toastr::error(translate('messages.no_items_in_order'));
            return back();
        }

        // Distribute total cost proportionally across items based on their price * quantity
        $itemTotal = $details->sum(fn($d) => $d->price * $d->quantity);

        foreach ($details as $detail) {
            $detail->is_outside_purchase = true;
            $detail->outside_purchase_store_id = $request->outside_purchase_store_id;

            if ($itemTotal > 0) {
                $proportion = ($detail->price * $detail->quantity) / $itemTotal;
                $detail->outside_purchase_cost = round(($request->outside_purchase_cost * $proportion) / $detail->quantity, 2);
            } else {
                $detail->outside_purchase_cost = 0;
            }

            $detail->save();
        }

        $order->outside_purchase_amount = $request->outside_purchase_cost;
        $order->save();

        if ($request->wantsJson()) {
            return response()->json(['message' => translate('messages.full_order_outside_purchase_marked_successfully')]);
        }

        Toastr::success(translate('messages.full_order_outside_purchase_marked_successfully'));
        return back();
    }

    /**
     * Order View V2 - Modern view with bargaining integration
     * NON-BREAKING: This is a completely separate view from the v1 details() method
     */
    public function viewV2(Request $request, Order $order)
    {
        // Eager load ALL relationships including bargaining
        $order->load([
            'details.item', 'details.campaign',
            'store', 'customer', 'delivery_man.last_location',
            'offline_payments', 'refund', 'assignedAdmin.role',

            // Bargaining relationships
            'bargainingRequest.zone', 'bargainingRequest.module',
            'bargainingRequest.awardedStore',
            'bargainingAcceptedOffer.store',
            'bargainingAcceptedOffer.offerItems.bargainingCartItem',
            'bargainingAcceptedOffer.offerItems.matchedItem',

            // Top 5 competing offers for comparison
            'bargainingRequest.storeOffers' => function($q) {
                $q->where('status', 'submitted')
                  ->orderBy('rank', 'asc')
                  ->limit(5);
            },
        ]);

        // Redirect parcel orders
        if ($order->order_type == 'parcel') {
            return to_route('admin.parcel.order.details', $order->id);
        }

        // Calculate bargaining metrics if applicable
        $bargainingData = null;
        if ($order->is_bargaining_order && $order->bargainingRequest) {
            $bargainingData = [
                'request_code' => $order->bargainingRequest->request_code,
                'mode' => $order->bargainingRequest->mode,
                'original_cart_value' => $order->bargainingRequest->original_cart_value,
                'final_price' => $order->bargainingRequest->final_price,
                'total_savings' => $order->bargainingRequest->total_savings,
                'savings_percentage' => round(($order->bargainingRequest->total_savings / $order->bargainingRequest->original_cart_value) * 100, 1),
                'total_offers_received' => $order->bargainingRequest->total_offers_received,
                'winning_store' => $order->bargainingAcceptedOffer?->store->name ?? null,
                'fulfillment_percentage' => $order->bargainingAcceptedOffer?->fulfillment_percentage ?? 0,
                'items_missing' => $order->bargainingAcceptedOffer?->items_missing ?? 0,
                'missing_items_detail' => $order->bargainingAcceptedOffer?->missing_items_detail ?? [],
                'vendor_notes' => $order->bargainingAcceptedOffer?->vendor_notes ?? null,
                'competing_offers' => $order->bargainingRequest->storeOffers ?? collect(),
                'time_to_accept' => $order->bargainingRequest->accepted_at
                    ? $order->bargainingRequest->created_at->diffInSeconds($order->bargainingRequest->accepted_at)
                    : null,
            ];
        }

        // Load transaction edit history
        $transaction = \App\Models\OrderTransaction::where('order_id', $order->id)->first();
        $editHistory = $transaction && $transaction->edit_history
            ? json_decode($transaction->edit_history, true)
            : [];

        return view('admin-views.order.order-view-v2', compact(
            'order', 'bargainingData', 'transaction', 'editHistory'
        ));
    }

}
