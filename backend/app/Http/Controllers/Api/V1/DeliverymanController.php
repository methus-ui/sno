<?php

namespace App\Http\Controllers\Api\V1;

ini_set('memory_limit', '-1');

use App\Models\Order;
use App\Library\Payer;
use App\Traits\Payment;
use App\Library\Receiver;
use App\Models\DeliveryMan;
use App\Models\Notification;
use App\Models\OrderPayment;
use Illuminate\Http\Request;
use App\CentralLogics\Helpers;
use App\Models\BusinessSetting;
use App\Models\DeliveryHistory;
use App\Models\DeliveryTrackingStat;
use App\Models\ProvideDMEarning;
use App\Models\UserNotification;
use App\Models\WithdrawalMethod;
use App\CentralLogics\OrderLogic;
use App\Models\DeliveryManWallet;
use App\Models\AccountTransaction;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\DisbursementDetails;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Config;
use App\Library\Payment as PaymentInfo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use App\Models\DisbursementWithdrawalMethod;
use App\Models\Store;
use App\Models\OrderDetail;
use App\Models\Item;
use App\Services\DeliverymanAttendanceService;
use App\Services\DmIncentiveService;
use App\Services\DmRankingService;
use App\Services\DmShiftService;
use App\Services\DmRushMonitorService;
use App\Services\DmZoneNotificationService;
use App\Services\DmHeatmapService;
use App\Services\DmReferralService;
use App\Services\DmWithdrawalService;
use App\Services\OrderBatchingService;
use App\Models\DmSosRequest;
use App\Events\ItemPickupUpdated;



class DeliverymanController extends Controller
{

    public function get_profile(Request $request)
    {
        $dm = DeliveryMan::with(['rating', 'wallet'])->where(['auth_token' => $request['token']])->first();
        if (!$dm) {
            return response()->json(['errors' => [['code' => 'not_found', 'message' => 'Delivery man not found']]], 404);
        }

        $min_amount_to_pay_dm = BusinessSetting::where('key', 'min_amount_to_pay_dm')->first()->value ?? 0;

        // ✅ Ratings & Stats
        $dm['avg_rating'] = (double) ($dm->rating[0]->average ?? 0);
        $dm['rating_count'] = (double) ($dm->rating[0]->rating_count ?? 0);
        $dm['order_count'] = (int) $dm->orders->count();
        $dm['todays_order_count'] = (int) $dm->todaysorders->count();
        $dm['this_week_order_count'] = (int) $dm->this_week_orders->count();
        $dm['member_since_days'] = (int) $dm->created_at->diffInDays();

        // ✅ Earnings (Delivery + Tips)
        $dm['todays_earning'] = (float) ($dm->todays_earning()->sum('original_delivery_charge') + $dm->todays_earning()->sum('dm_tips'));
        $dm['this_week_earning'] = (float) ($dm->this_week_earning()->sum('original_delivery_charge') + $dm->this_week_earning()->sum('dm_tips'));
        $dm['this_month_earning'] = (float) ($dm->this_month_earning()->sum('original_delivery_charge') + $dm->this_month_earning()->sum('dm_tips'));

        // ✅ Wallet Details
        $dm['cash_in_hands'] = (float) ($dm->wallet?->collected_cash ?? 0);
        $dm['total_earning'] = (float) ($dm->wallet?->total_earning ?? 0);
        $dm['total_withdrawn'] = (float) ($dm->wallet?->total_withdrawn ?? 0);
        $dm['pending_withdraw'] = (float) ($dm->wallet?->pending_withdraw ?? 0);
        $dm['incentive_earning'] = (float) ($dm->wallet?->incentive_earning ?? 0); // ✅ New Field

        $dm['balance'] = $dm['total_earning'] - ($dm['total_withdrawn'] + $dm['pending_withdraw']);
        $dm['withdraw_able_balance'] = max($dm['balance'] - $dm['cash_in_hands'], 0);
        $dm['Payable_Balance'] = (float) $dm['cash_in_hands'];

        // ✅ Overflow Warnings
        $over_flow_balance = $dm['balance'] - $dm['cash_in_hands'];
        $cash_in_hand_overflow = BusinessSetting::where('key', 'cash_in_hand_overflow_delivery_man')->first()?->value;
        $dm_max_cash = BusinessSetting::where('key', 'dm_max_cash_in_hand')->first()?->value;
        $threshold = $dm_max_cash - (($dm_max_cash * 10) / 100);

        $dm['over_flow_warning'] = $dm['cash_in_hands'] > $threshold && $cash_in_hand_overflow;
        $dm['over_flow_block_warning'] = $dm['cash_in_hands'] > $dm_max_cash && $cash_in_hand_overflow;

        // ✅ Incentive Milestones (for frontend display)
        $dm['incentives'] = [
            ['milestone' => 4,  'bonus' => 20],
            ['milestone' => 9,  'bonus' => 50],
            ['milestone' => 13, 'bonus' => 60],
            ['milestone' => 19, 'bonus' => 90],
        ];

        // ✅ Auto pay now button logic
        $digital_payment = Helpers::get_business_settings('digital_payment');
        $dm['show_pay_now_button'] = $min_amount_to_pay_dm <= $dm['cash_in_hands'] && ($digital_payment['status'] == 1);

        // ✅ Adjustable condition
        $wallet_earning = round($dm['balance'], 8);
        $dm['adjust_able'] = ($dm['cash_in_hands'] > 0 && $wallet_earning > 0 && $over_flow_balance != $dm['balance']);

        // ✅ Offline time tracking (for shift monitoring)
        $attendanceService = new DeliverymanAttendanceService();
        $offlineSummary = $attendanceService->getOfflineTimeSummary($dm->id);

        $dm['offline_time_today'] = $offlineSummary['total_offline_minutes'];
        $dm['offline_threshold'] = $offlineSummary['threshold_minutes'];
        $dm['incentive_eligible_today'] = $offlineSummary['incentive_eligible'];
        $dm['offline_warning'] = $offlineSummary['remaining_allowed_minutes'] <= 2 && $offlineSummary['remaining_allowed_minutes'] > 0
            ? "Only {$offlineSummary['remaining_allowed_minutes']} min offline time left!"
            : null;

        // ✅ Application status for mobile app (pending/approved/denied)
        // Mobile app needs this to show waiting screen or navigate to main screen
        $dm['application_status'] = $dm->application_status ?? 'pending';

        // ✅ Security deposit configuration
        $security_deposit_enabled = BusinessSetting::where('key', 'security_deposit_enabled')->first()?->value == '1';
        $security_deposit_amount = BusinessSetting::where('key', 'security_deposit_amount')->first()?->value ?? 0;

        $dm['security_deposit_enabled'] = (bool) $security_deposit_enabled;
        $dm['security_deposit_amount'] = (float) ($dm->security_deposit_amount ?: $security_deposit_amount);
        $dm['security_deposit_status'] = $dm->security_deposit_status ?? 'unpaid';

        // ✅ Clean up unnecessary relations
        unset($dm['orders'], $dm['rating'], $dm['todaysorders'], $dm['this_week_orders'], $dm['wallet']);

        return response()->json($dm, 200);
    }

    

    public function update_profile(Request $request)
    {
        $dm = DeliveryMan::with(['rating'])->where(['auth_token' => $request['token']])->first();
        $validator = Validator::make($request->all(), [
            'f_name' => 'required',
            'l_name' => 'required',
            'email' => 'required|unique:delivery_men,email,'.$dm->id,
            'password' => ['nullable', Password::min(8)->mixedCase()->letters()->numbers()->symbols()->uncompromised()],
        ], [
            'f_name.required' => 'First name is required!',
            'l_name.required' => 'Last name is required!',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $image = $request->file('image');

        if ($request->has('image')) {
            $imageName = Helpers::update('delivery-man/', $dm->image, 'png', $request->file('image'));
        } else {
            $imageName = $dm->image;
        }

        if ($request['password'] != null && strlen($request['password']) > 5) {
            $pass = bcrypt($request['password']);
        } else {
            $pass = $dm->password;
        }

        $dm->vehicle_id = $request->vehicle_id ??  $dm->vehicle_id ?? null;

        $dm->f_name = $request->f_name;
        $dm->l_name = $request->l_name;
        $dm->email = $request->email;
        $dm->image = $imageName;
        $dm->password = $pass;
        $dm->updated_at = now();
        $dm->save();

        if($dm->userinfo) {
            $userinfo = $dm->userinfo;
            $userinfo->f_name = $request->f_name;
            $userinfo->l_name = $request->l_name;
            $userinfo->email = $request->email;
            $userinfo->image = $imageName;
            $userinfo->save();
        }

        return response()->json(['message' => translate('successfully updated!')], 200);
    }

    public function activeStatus(Request $request)
    {

        $dm = DeliveryMan::with(['rating'])->where(['auth_token' => $request['token']])->first();

        if (!$dm) {
            return response()->json(['errors' => [['code' => 'auth', 'message' => 'Unauthenticated.']]], 401);
        }

        $previousStatus = $dm->active;
        $newStatus = $dm->active ? 0 : 1;

        // If trying to go ONLINE, check if near any store
        // TEMPORARILY DISABLED - Mobile app needs to be updated to send lat/long
        // TODO: Re-enable once mobile app sends location coordinates
        /*
        if ($newStatus == 1 && $previousStatus == 0) {
            // Get location from request (sent by mobile app)
            $latitude = $request->input('latitude');
            $longitude = $request->input('longitude');

            \Log::info("🔄 activeStatus: DM#{$dm->id} trying to go online", [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'dm_type' => $dm->type,
                'zone_id' => $dm->zone_id,
                'store_id' => $dm->store_id,
                'all_request' => $request->all()
            ]);

            $nearbyCheck = $this->isNearbyAnyStore($dm, $latitude, $longitude);

            \Log::info("🔄 activeStatus: nearbyCheck result", $nearbyCheck);

            if (!$nearbyCheck['is_nearby']) {
                return response()->json([
                    'errors' => [
                        ['code' => 'location', 'message' => $nearbyCheck['message']]
                    ]
                ], 403);
            }

            // Update DeliveryHistory with the provided location
            if ($latitude && $longitude) {
                DeliveryHistory::updateOrCreate(
                    ['delivery_man_id' => $dm->id],
                    [
                        'longitude' => $longitude,
                        'latitude' => $latitude,
                        'time' => now(),
                        'updated_at' => now()
                    ]
                );
            }
        }
        */

        $dm->active = $newStatus;
        $dm->save();

        // Handle attendance based on status change
        try {
            $attendanceService = new DeliverymanAttendanceService();

            if ($dm->active == 1 && $previousStatus == 0) {
                // Going ONLINE
                $attendance = $attendanceService->markPunchIn($dm->id, 'active_toggle');

                // Also mark as online (to close any offline session)
                $attendanceService->markOnline($dm->id);

                \Log::info("DM #{$dm->id} went ONLINE", [
                    'previous_status' => $previousStatus,
                    'new_status' => $newStatus
                ]);

            } elseif ($dm->active == 0 && $previousStatus == 1) {
                // Going OFFLINE
                $attendanceService->markOffline($dm->id);

                \Log::info("DM #{$dm->id} went OFFLINE", [
                    'previous_status' => $previousStatus,
                    'new_status' => $newStatus
                ]);
            }
        } catch (\Exception $e) {
            \Log::error("Attendance tracking failed for DM#{$dm->id}: " . $e->getMessage());
        }

        return response()->json(['message' => translate('messages.active_status_updated')], 200);
    }

    /**
     * Check if deliveryman is nearby any store (within 500 meters)
     *
     * @param DeliveryMan $dm
     * @param float|null $latitude Optional latitude from request
     * @param float|null $longitude Optional longitude from request
     * @return array ['is_nearby' => bool, 'message' => string]
     */
    private function isNearbyAnyStore(DeliveryMan $dm, $latitude = null, $longitude = null): array
    {
        $nearbyRadiusKm = 0.5; // 500 meters

        \Log::info("📍 isNearbyAnyStore: Input params", [
            'dm_id' => $dm->id,
            'input_latitude' => $latitude,
            'input_longitude' => $longitude,
            'latitude_type' => gettype($latitude),
            'longitude_type' => gettype($longitude),
        ]);

        // Use provided coordinates or fall back to DeliveryHistory
        if ($latitude && $longitude) {
            $dmLat = (float) $latitude;
            $dmLng = (float) $longitude;
            \Log::info("📍 isNearbyAnyStore: Using request coordinates", ['lat' => $dmLat, 'lng' => $dmLng]);
        } else {
            // Fallback: Get deliveryman's location from DeliveryHistory
            \Log::info("📍 isNearbyAnyStore: No coordinates provided, falling back to DeliveryHistory");
            $dmLocation = DeliveryHistory::where('delivery_man_id', $dm->id)
                ->latest('updated_at')
                ->first();

            \Log::info("📍 isNearbyAnyStore: DeliveryHistory result", [
                'found' => $dmLocation ? true : false,
                'latitude' => $dmLocation?->latitude,
                'longitude' => $dmLocation?->longitude,
            ]);

            if (!$dmLocation || !$dmLocation->latitude || !$dmLocation->longitude) {
                return [
                    'is_nearby' => false,
                    'message' => translate('messages.location_not_found_please_enable_location')
                ];
            }

            $dmLat = (float) $dmLocation->latitude;
            $dmLng = (float) $dmLocation->longitude;
        }

        // Get stores based on deliveryman type
        if ($dm->type == 'zone_wise') {
            // Zone-wise deliveryman - check all active stores in their zone
            $stores = Store::where('zone_id', $dm->zone_id)
                ->where('status', 1)
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->select('id', 'name', 'latitude', 'longitude')
                ->get();
        } else {
            // Store-specific deliveryman - check only their assigned store
            $stores = Store::where('id', $dm->store_id)
                ->where('status', 1)
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->select('id', 'name', 'latitude', 'longitude')
                ->get();
        }

        \Log::info("📍 isNearbyAnyStore: Stores found", [
            'dm_type' => $dm->type,
            'store_count' => $stores->count(),
            'stores' => $stores->map(fn($s) => ['id' => $s->id, 'name' => $s->name, 'lat' => $s->latitude, 'lng' => $s->longitude])
        ]);

        if ($stores->isEmpty()) {
            return [
                'is_nearby' => false,
                'message' => translate('messages.no_stores_found_in_your_zone')
            ];
        }

        // Check distance to each store
        foreach ($stores as $store) {
            $storeLat = (float) $store->latitude;
            $storeLng = (float) $store->longitude;

            $distance = $this->calculateDistance($dmLat, $dmLng, $storeLat, $storeLng);

            \Log::info("📍 isNearbyAnyStore: Distance check", [
                'store_id' => $store->id,
                'store_name' => $store->name,
                'dm_lat' => $dmLat,
                'dm_lng' => $dmLng,
                'store_lat' => $storeLat,
                'store_lng' => $storeLng,
                'distance_km' => $distance,
                'distance_m' => round($distance * 1000),
                'threshold_km' => $nearbyRadiusKm,
                'is_within' => $distance <= $nearbyRadiusKm
            ]);

            if ($distance <= $nearbyRadiusKm) {
                return [
                    'is_nearby' => true,
                    'message' => 'OK',
                    'store_name' => $store->name,
                    'distance' => round($distance * 1000) . 'm' // Convert to meters
                ];
            }
        }

        return [
            'is_nearby' => false,
            'message' => translate('messages.you_must_be_within_500m_of_a_store_to_go_online')
        ];
    }

    public function get_current_orders(Request $request)
    {
        $dm = DeliveryMan::where(['auth_token' => $request['token']])->first();
        $orders = Order::with(['customer', 'store','parcel_category', 'payments'])
        ->whereIn('order_status', ['accepted','confirmed','pending', 'processing', 'picked_up', 'handover'])
        ->where(['delivery_man_id' => $dm['id']])
        ->orderBy('accepted')
        ->orderBy('schedule_at', 'desc')
        ->dmOrder()
        ->get();

        // Fix payment status display issue for each order
        foreach ($orders as $order) {
            if ($order->payment_status == 'paid' && $order->payments()->where('payment_status', 'unpaid')->exists()) {
                $order->payments()->where('payment_status', 'unpaid')->update(['payment_status' => 'paid']);
            }
        }

        $orders= Helpers::order_data_formatting($orders, true);
        return response()->json($orders, 200);
    }

    public function get_latest_orders(Request $request)
    {
        $dm = DeliveryMan::where(['auth_token' => $request['token']])->first();

        $orders = Order::with(['customer', 'store','parcel_category', 'payments']);

        if($dm->type == 'zone_wise')
        {
            $orders = $orders->where('zone_id', $dm->zone_id)
            ->where(function($query){
                $query->whereNull('store_id')

                    ->orWhere(function($query){
                        $query->whereHas('store', function($q){
                            $q->where('store_business_model','subscription')->whereHas('store_sub', function($q1){
                                $q1->where('self_delivery', 0);
                            });
                        })
                        ->orWhereHas('store', function($qu) {
                            $qu->where('store_business_model','commission')->where('self_delivery_system', 0);
                        });
                    });
            });
        }
        else
        {
            $orders = $orders->where('store_id', $dm->store_id);
        }

        if(config('order_confirmation_model') == 'deliveryman' && $dm->type == 'zone_wise')
        {
            $orders = $orders->whereIn('order_status', ['pending', 'confirmed','processing','handover']);
        }
        else
        {
            $orders = $orders->where(function ($query) {
                return $query->whereIn('order_status', ['confirmed', 'processing', 'handover'])
                    ->orWhere(function ($subQuery) {
                        return  $subQuery->where('order_type', 'parcel')->whereIn('order_status', ['confirmed', 'processing', 'handover']);
                    });
            });
        }
        if(isset($dm->vehicle_id )){
            $orders = $orders->where('dm_vehicle_id',$dm->vehicle_id);
        }
        $orders = $orders->dmOrder()
        ->Notpos()
        ->NotDigitalOrder()
        ->OrderScheduledIn(30)
        ->whereNull('delivery_man_id')
        ->orderBy('schedule_at', 'desc')
        ->get();

        // Fix payment status display issue for each order
        foreach ($orders as $order) {
            if ($order->payment_status == 'paid' && $order->payments()->where('payment_status', 'unpaid')->exists()) {
                $order->payments()->where('payment_status', 'unpaid')->update(['payment_status' => 'paid']);
            }
        }

        $orders= Helpers::order_data_formatting($orders, true);
        return response()->json($orders, 200);
    }

    public function accept_order(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }
        $dm=DeliveryMan::where(['auth_token' => $request['token']])->first();
        $order = Order::where('id', $request['order_id'])
        // ->whereIn('order_status', ['pending', 'confirmed'])
        ->whereNull('delivery_man_id')
        ->dmOrder()
        ->first();
        if(!$order)
        {
            return response()->json([
                'errors' => [
                    ['code' => 'order', 'message' => translate('messages.can_not_accept')]
                ]
            ], 404);
        }
        if($dm->current_orders >= config('dm_maximum_orders'))
        {
            return response()->json([
                'errors'=>[
                    ['code' => 'dm_maximum_order_exceed', 'message'=> translate('messages.dm_maximum_order_exceed_warning')]
                ]
            ], 405);
        }

        $payments = $order->payments()->where('payment_method','cash_on_delivery')->exists();
        $cash_in_hand = $dm?->wallet?->collected_cash ?? 0;
        $dm_max_cash=BusinessSetting::where('key','dm_max_cash_in_hand')->first();
        $value=  $dm_max_cash?->value ?? 0;


        if(($order->payment_method == "cash_on_delivery" || $payments) && (($cash_in_hand+$order->order_amount) >= $value)){

            return response()->json([
                'errors'=>[
                    ['code' => 'dm_maximum_hand_in_cash', 'message'=> \App\CentralLogics\Helpers::format_currency($value) ." ".translate('max_cash_in_hand_exceeds') ]
                ]
            ], 405);
        }


        if($order->order_type == 'parcel' && $order->order_status=='confirmed')
        {
            $order->order_status = 'handover';
            $order->handover = now();
            $order->processing = now();
        }
        else{
            $order->order_status = in_array($order->order_status, ['pending', 'confirmed'])?'accepted':$order->order_status;
        }

        $order->delivery_man_id = $dm->id;
        $order->accepted = now();
        $order->save();

        $dm->current_orders = $dm->current_orders+1;
        $dm->total_orders_accepted = ($dm->total_orders_accepted ?? 0) + 1;
        $dm->save();

        $dm->increment('assigned_order_count');

        $fcm_token= $order->is_guest == 0 ? $order?->customer?->cm_firebase_token : $order?->guest?->fcm_token;


        $value = Helpers::order_status_update_message('accepted',$order->module->module_type);
        $value = Helpers::text_variable_data_format(value:$value,store_name:$order->store?->name,order_id:$order->id,user_name:"{$order?->customer?->f_name} {$order?->customer?->l_name}",delivery_man_name:"{$order->delivery_man?->f_name} {$order->delivery_man?->l_name}");
        try {
            if($value && $fcm_token && Helpers::getNotificationStatusData('customer','customer_order_notification','push_notification_status'))
            {
                $data = [
                    'title' =>translate('Order_Notification'),
                    'description' => $value,
                    'order_id' => $order['id'],
                    'image' => '',
                    'type'=> 'order_status'
                ];
                Helpers::send_push_notif_to_device($fcm_token, $data);
            }

        } catch (\Exception $e) {

        }

        return response()->json(['message' => 'Order accepted successfully'], 200);

    }

    public function record_location_data(Request $request)
    {
        // Cache DeliveryMan lookup by token for 60 seconds to reduce DB hits
        $cacheKey = 'dm_token_' . md5($request['token']);
        $dm = \Illuminate\Support\Facades\Cache::remember($cacheKey, 60, function () use ($request) {
            return DeliveryMan::where('auth_token', $request['token'])->first();
        });

        if (!$dm) {
            return response()->json(['message' => 'Invalid token'], 401);
        }


        // Dispatch location write + tracking stats to queue to avoid DB lock contention
        \App\Jobs\RecordDeliveryLocationJob::dispatch(
            $dm->id,
            (float) $request['latitude'],
            (float) $request['longitude'],
            $request['location'],
            (float) ($request['speed'] ?? 0),
        );

        // Check for nearby customer - only for active orders
        $activeOrder = Order::where('delivery_man_id', $dm->id)
            ->whereIn('order_status', ['picked_up', 'handover'])
            ->where('nearby_notification_sent', 0)
            ->first();

        if ($activeOrder) {
            // Get customer delivery location from JSON
            $deliveryAddress = json_decode($activeOrder->delivery_address, true);

            if (isset($deliveryAddress['latitude']) && isset($deliveryAddress['longitude'])) {
                $customerLat = (float) $deliveryAddress['latitude'];
                $customerLng = (float) $deliveryAddress['longitude'];
                $dmLat = (float) $request['latitude'];
                $dmLng = (float) $request['longitude'];

                // Calculate distance in meters using Haversine formula
                $distance = $this->calculateDistance($dmLat, $dmLng, $customerLat, $customerLng);

                // If within 100 meters (0.1 km), send notification
                if ($distance <= 0.1) {
                    $this->sendNearbyNotification($activeOrder, $dm);

                    // Mark notification as sent
                    $activeOrder->nearby_notification_sent = 1;
                    $activeOrder->save();
                }
            }
        }

        return response()->json(['message' => translate('location recorded')], 200);
    }

    /**
     * Check if delivery man is within proximity to complete delivery
     * Returns proximity status for current active order
     */
    public function check_delivery_proximity(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|integer',
            'token' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $dm = DeliveryMan::where('auth_token', $request['token'])->first();
        if (!$dm) {
            return response()->json(['message' => 'Invalid token'], 401);
        }

        $order = Order::where(['id' => $request['order_id'], 'delivery_man_id' => $dm->id])
            ->whereIn('order_status', ['picked_up', 'handover'])
            ->first();

        if (!$order) {
            return response()->json([
                'errors' => [['code' => 'order', 'message' => translate('messages.not_found')]]
            ], 404);
        }

        // Get delivery address
        $deliveryAddress = json_decode($order->delivery_address, true);

        if (!isset($deliveryAddress['latitude']) || !isset($deliveryAddress['longitude'])) {
            // No coordinates - allow delivery (fallback for old data)
            return response()->json([
                'within_proximity' => true,
                'distance' => null,
                'proximity_required' => 200,
                'message' => translate('messages.location_data_unavailable')
            ], 200);
        }

        // Get DM's last known location from tracking stats
        $trackingStat = DeliveryTrackingStat::where('order_id', $order->id)->first();

        if (!$trackingStat || !$trackingStat->last_latitude || !$trackingStat->last_longitude) {
            // No tracking data yet
            return response()->json([
                'within_proximity' => false,
                'distance' => null,
                'proximity_required' => 200,
                'message' => translate('messages.no_location_data_recorded_yet')
            ], 200);
        }

        $customerLat = (float) $deliveryAddress['latitude'];
        $customerLng = (float) $deliveryAddress['longitude'];
        $dmLat = $trackingStat->last_latitude;
        $dmLng = $trackingStat->last_longitude;

        // Calculate distance in kilometers
        $distance = $this->calculateDistance($dmLat, $dmLng, $customerLat, $customerLng);

        // Get proximity radius from settings (default 0.2 km = 200m)
        $proximityRadius = BusinessSetting::where('key', 'delivery_proximity_radius')->first();
        $requiredProximity = $proximityRadius ? (float)$proximityRadius->value : 0.2;

        // Check if within proximity
        $withinProximity = $distance <= $requiredProximity;

        return response()->json([
            'within_proximity' => $withinProximity,
            'distance' => round($distance * 1000, 2), // Convert to meters
            'proximity_required' => round($requiredProximity * 1000, 0), // Convert to meters
            'message' => $withinProximity
                ? translate('messages.within_delivery_proximity')
                : translate('messages.not_close_enough_to_customer_location')
        ], 200);
    }

    /**
     * Update tracking stats for delivery man's active orders
     */
    private function updateTrackingStats($dm, $dmLat, $dmLng, $speed)
    {
        try {
            $activeOrders = Order::where('delivery_man_id', $dm->id)
                ->whereIn('order_status', ['accepted', 'confirmed', 'processing', 'handover', 'picked_up'])
                ->with('store')
                ->get();

            foreach ($activeOrders as $order) {
                $storeLat = $order->store->latitude ?? null;
                $storeLng = $order->store->longitude ?? null;

                $deliveryAddress = json_decode($order->delivery_address, true);
                $customerLat = $deliveryAddress['latitude'] ?? null;
                $customerLng = $deliveryAddress['longitude'] ?? null;

                $stats = DeliveryTrackingStat::getOrCreateForOrder($order->id, $dm->id);
                $stats->updateLocationState($dmLat, $dmLng, $storeLat, $storeLng, $customerLat, $customerLng, $speed);
            }
        } catch (\Exception $e) {
            \Log::error("Failed to update tracking stats: " . $e->getMessage());
        }
    }

    /**
     * Calculate distance between two coordinates in kilometers
     */
    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371; // Earth radius in kilometers

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distance = $earthRadius * $c;

        return $distance; // Returns distance in kilometers
    }

    /**
     * Send nearby notification to customer
     */
    private function sendNearbyNotification($order, $deliveryMan)
    {
        try {
            $customer = \App\Models\User::find($order->user_id);

            if ($customer && $customer->cm_firebase_token) {
                $data = [
                    'title' => translate('Delivery Man Nearby!'),
                    'description' => translate('Your delivery person is at your doorstep. Please be ready to receive your order.'),
                    'order_id' => $order->id,
                    'image' => '',
                    'type' => 'order',
                    'order_status' => $order->order_status,
                ];

                // Send push notification
                Helpers::send_push_notif_to_device($customer->cm_firebase_token, $data);

                // Save notification to database
                DB::table('user_notifications')->insert([
                    'data' => json_encode($data),
                    'user_id' => $customer->id,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                \Log::info("Nearby notification sent to customer {$customer->id} for order {$order->id}");
            }
        } catch (\Exception $e) {
            \Log::error("Failed to send nearby notification: " . $e->getMessage());
        }
    }

    public function get_order_history(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }
        $dm = DeliveryMan::where(['auth_token' => $request['token']])->first();

        $history = DeliveryHistory::where(['order_id' => $request['order_id'], 'delivery_man_id' => $dm['id']])->get();
        return response()->json($history, 200);
    }

    public function send_order_otp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }
        $dm = DeliveryMan::where(['auth_token' => $request['token']])->first();
        $order = Order::where(['id' => $request['order_id'], 'delivery_man_id' => $dm['id']]);
        if(config('order_confirmation_model') == 'deliveryman' && $dm->type == 'zone_wise')
        {
            $order = $order->whereIn('order_status', ['pending', 'confirmed','processing','handover','picked_up']);
        }
        else
        {
            $order = $order->where(function($query){
                $query->whereIn('order_status', ['confirmed','processing','handover','picked_up'])->orWhere('order_type','parcel');
            });
        }
        $order = $order->dmOrder()->first();
        if(!$order)
        {
            return response()->json([
                'errors' => [
                    ['code' => 'order', 'message' => translate('messages.not_found')]
                ]
            ], 404);
        }
        $value = translate('your_order_is_ready_to_be_delivered,_plesae_share_your_otp_with_delivery_man.').' '.translate('otp:').$order->otp.', '.translate('order_id:').$order->id;
        try {

            $fcm_token= $order->is_guest == 0 ? $order?->customer?->cm_firebase_token : $order?->guest?->fcm_token;
            if ($value && $fcm_token && Helpers::getNotificationStatusData('customer','customer_delivery_verification' ,'push_notification_status')) {
                $data = [
                    'title' => translate('messages.order_ready_to_be_delivered'),
                    'description' => $value,
                    'order_id' => $order->id,
                    'image' => '',
                    'type' => 'otp',
                ];

                Helpers::send_push_notif_to_device($fcm_token , $data);
                DB::table('user_notifications')->insert([
                    'data' => json_encode($data),
                    'user_id' => $order->user_id,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        } catch (\Exception $e) {
            info($e->getMessage());
            return response()->json(['message' => translate('messages.push_notification_faild')], 403);
        }
        return response()->json([], 200);
    }

public function update_order_status(Request $request)
{
    $validator = Validator::make($request->all(), [
        'order_id' => 'required',
        'status' => 'required|in:confirmed,canceled,picked_up,delivered,handover',
        'reason' => 'required_if:status,canceled',
        'order_proof' => 'array|max:5',
    ]);

    $validator->sometimes('otp', 'required', function ($request) {
        return (Config::get('order_delivery_verification') == 1 && $request['status'] == 'delivered');
    });

    if ($validator->fails()) {
        return response()->json(['errors' => Helpers::error_processor($validator)], 403);
    }

    $dm = DeliveryMan::where(['auth_token' => $request['token']])->first();

    $order = Order::where(['id' => $request['order_id'], 'delivery_man_id' => $dm['id']])->dmOrder()->first();

    if (!$order || (!$order->store && $order->order_type != 'parcel')) {
        return response()->json([
            'errors' => [
                ['code' => 'not_found', 'message' => translate('messages.you_can_not_change_the_status_of_this_order')]
            ]
        ], 403);
    }

    // ===== Basic validations ===== //
    if ($request['status'] == "confirmed" && config('order_confirmation_model') == 'store') {
        return response()->json(['errors' => [['code' => 'order-confirmation-model', 'message' => translate('messages.order_confirmation_warning')]]], 403);
    }

    if ($request['status'] == 'canceled' && !config('canceled_by_deliveryman')) {
        return response()->json(['errors' => [['code' => 'status', 'message' => translate('messages.you_can_not_cancel_a_order')]]], 403);
    }

    if ($order->confirmed && $request['status'] == 'canceled') {
        return response()->json(['errors' => [['code' => 'delivery-man', 'message' => translate('messages.order_can_not_cancle_after_confirm')]]], 403);
    }

    // ===== OTP verification ===== //
    if (Config::get('order_delivery_verification') == 1
        && $order->payment_method == 'cash_on_delivery'
        && $request['status'] == 'picked_up'
        && $order->otp != $request['otp']) {
        return response()->json(['errors' => [['code' => 'otp', 'message' => translate('Not matched')]]], 406);
    }

    if (Config::get('order_delivery_verification') == 1
        && $order->payment_method == 'cash_on_delivery'
        && $request['status'] == 'delivered'
        && $order->otp != $request['otp']) {
        return response()->json(['errors' => [['code' => 'otp', 'message' => translate('Not matched')]]], 406);
    }

    // ===== Proximity Verification for Delivery ===== //
    if ($request->status == 'delivered') {
        // Check if proximity enforcement is enabled (business setting)
        $proximityEnforcement = BusinessSetting::where('key', 'delivery_proximity_enforcement')->first();
        $enforceProximity = $proximityEnforcement ? (int)$proximityEnforcement->value : 0;

        if ($enforceProximity) {
            $deliveryAddress = json_decode($order->delivery_address, true);

            if (isset($deliveryAddress['latitude']) && isset($deliveryAddress['longitude'])) {
                $trackingStat = DeliveryTrackingStat::where('order_id', $order->id)->first();

                if ($trackingStat && $trackingStat->last_latitude && $trackingStat->last_longitude) {
                    $customerLat = (float) $deliveryAddress['latitude'];
                    $customerLng = (float) $deliveryAddress['longitude'];
                    $dmLat = $trackingStat->last_latitude;
                    $dmLng = $trackingStat->last_longitude;

                    $distance = $this->calculateDistance($dmLat, $dmLng, $customerLat, $customerLng);

                    // Get proximity radius from settings (default 0.2km = 200m)
                    $proximityRadius = BusinessSetting::where('key', 'delivery_proximity_radius')->first();
                    $requiredProximity = $proximityRadius ? (float)$proximityRadius->value : 0.2;

                    if ($distance > $requiredProximity) {
                        return response()->json([
                            'errors' => [[
                                'code' => 'proximity',
                                'message' => translate('messages.you_must_be_within_proximity_to_complete_delivery'),
                                'current_distance' => round($distance * 1000, 2),
                                'required_distance' => round($requiredProximity * 1000, 0)
                            ]]
                        ], 403);
                    }
                }
            }
        }
    }

    // ===== When delivered ===== //
    if ($request->status == 'delivered') {

        // ✅ Handle payments
        if ($order->transaction == null) {
            // Check if the order is already paid via digital payment
            if ($order->payment_status == 'paid') {
                // Order already paid digitally, determine payment method
                $pay_method = 'digital_payment';
                $received_by = 'admin';
            } else {
                // Check for unpaid payment records
                $unpaid_payment = OrderPayment::where('payment_status', 'unpaid')->where('order_id', $order->id)->first();
                $pay_method = $unpaid_payment && $unpaid_payment->payment_method == 'cash_on_delivery'
                    ? 'cash_on_delivery' : 'digital_payment';
                $received_by = ($order->payment_method == 'cash_on_delivery' || $pay_method == 'cash_on_delivery')
                    ? ($dm->type != 'zone_wise' ? 'store' : 'deliveryman')
                    : 'admin';
            }

            if (OrderLogic::create_transaction($order, $received_by, null)) {
                $order->payment_status = 'paid';
            } else {
                return response()->json(['errors' => [['code' => 'error', 'message' => translate('messages.faield_to_create_order_transaction')]]], 406);
            }
        }

        if ($order->transaction) {
            $order->transaction->update(['delivery_man_id' => $dm->id]);
        }

        // ✅ Update stats
        $order->details->each(function ($item) {
            if ($item->food) {
                $item->food->increment('order_count');
            }
        });

        $order?->customer?->increment('order_count');
        $dm->current_orders = max($dm->current_orders - 1, 0);
        $dm->increment('order_count');

        // 🎁 NEW JOINER BONUS: Track completed deliveries for milestone unlock
        $dm->increment('total_completed_deliveries');

        $dm->save();

        // ===== Incentive Logic (Database-Driven) ===== //
        try {
            $incentiveService = new DmIncentiveService();
            $incentiveService->processAllIncentives($dm, $order);
        } catch (\Exception $e) {
            \Log::error("Incentive processing failed for DeliveryMan ID {$dm->id}: " . $e->getMessage());
        }

        // ===== Referral Bonus Check ===== //
        try {
            $referralService = new DmReferralService();
            $referralService->checkAndPayBonus($dm);
        } catch (\Exception $e) {
            \Log::error("Referral bonus check failed for DM {$dm->id}: " . $e->getMessage());
        }

        // ✅ Proof image handling
        if (!empty($request->file('order_proof'))) {
            $proofs = [];
            foreach ($request->order_proof as $img) {
                $image_name = Helpers::upload('order/', 'png', $img);
                $proofs[] = ['img' => $image_name, 'storage' => Helpers::getDisk()];
            }
            $order->order_proof = json_encode($proofs);
        }

        OrderLogic::update_unpaid_order_payment(order_id: $order->id, payment_method: $order->payment_method);
    }

    // Cancel / Handover handling
    else if ($request->status == 'canceled') {
        if ($order->delivery_man) {
            $dm = $order->delivery_man;
            $dm->current_orders = max($dm->current_orders - 1, 0);
            $dm->total_orders_rejected = ($dm->total_orders_rejected ?? 0) + 1;
            $dm->save();
        }
        $order->cancellation_reason = $request->reason;
        $order->canceled_by = 'deliveryman';
    }
    else if ($order->order_type == 'parcel' && $request->status == 'handover') {
        $order->confirmed = now();
        $order->processing = now();
    }

    // Save status
    $order->order_status = $request['status'];
    $order[$request['status']] = now();
    $order->save();

    Helpers::send_order_notification($order);

    return response()->json(['message' => translate('Status updated')], 200);
}


    public function get_order_details(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }
        $dm = DeliveryMan::where(['auth_token' => $request['token']])->first();

        $dm = DeliveryMan::where(['auth_token' => $request['token']])->first();
        $order = Order::with(['details'])->where('id',$request['order_id'])->where(function($query) use($dm){
            $query->WhereNull('delivery_man_id')
                ->orWhere('delivery_man_id', $dm['id']);
        })->Notpos()->first();
        if(!$order)
        {
            return response()->json([
                'errors' => [
                    ['code' => 'order', 'message' => translate('messages.not_found')]
                ]
            ], 404);
        }
        $details = isset($order->details)?$order->details:null;
        if ($details != null && $details->count() > 0) {
            $details[0]['vendor_id'] = $order?->store?->vendor_id;
            $details = $details = Helpers::order_details_data_formatting($details);
            $details[0]['is_guest'] = (int)$order->is_guest;
            return response()->json($details, 200);
        }
        else if ($order->order_type == 'parcel' ) {
            $order->delivery_address = $order->delivery_address?json_decode($order->delivery_address, true):[];
            return response()->json(($order), 200);
        }
        elseif($order->prescription_order == 1){
            return response()->json([], 200);
        }

        return response()->json([
            'errors' => [
                ['code' => 'order', 'message' => translate('messages.not_found')]
            ]
        ], 404);
    }

    public function get_order(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }
        $dm = DeliveryMan::where(['auth_token' => $request['token']])->first();

        $order = Order::with(['customer', 'store','details','parcel_category','payments'])->where(['delivery_man_id' => $dm['id'], 'id' => $request['order_id']])->Notpos()->first();
        if(!$order)
        {
            return response()->json([
                'errors' => [
                    ['code' => 'order', 'message' => translate('messages.not_found')]
                ]
            ], 204);
        }

        // Fix payment status display issue - sync order_payments with order payment_status
        if ($order->payment_status == 'paid' && $order->payments()->where('payment_status', 'unpaid')->exists()) {
            // Update any unpaid payment records to paid if the main order is paid
            $order->payments()->where('payment_status', 'unpaid')->update(['payment_status' => 'paid']);
        }

        return response()->json(Helpers::order_data_formatting($order), 200);
    }

    public function get_all_orders(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'required',
            'offset' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $dm = DeliveryMan::where(['auth_token' => $request['token']])->first();

        $paginator = Order::with(['customer', 'store','parcel_category', 'payments'])
        ->where(['delivery_man_id' => $dm['id']])
        ->whereIn('order_status', ['delivered','canceled','refund_requested','refunded','failed'])
        ->orderBy('schedule_at', 'desc')
        ->dmOrder()
        ->paginate($request['limit'], ['*'], 'page', $request['offset']);

        // Fix payment status display issue for each order
        foreach ($paginator->items() as $order) {
            if ($order->payment_status == 'paid' && $order->payments()->where('payment_status', 'unpaid')->exists()) {
                $order->payments()->where('payment_status', 'unpaid')->update(['payment_status' => 'paid']);
            }
        }

        $orders= Helpers::order_data_formatting($paginator->items(), true);
        $data = [
            'total_size' => $paginator->total(),
            'limit' => $request['limit'],
            'offset' => $request['offset'],
            'orders' => $orders
        ];
        return response()->json($data, 200);
    }

    public function get_last_location(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $last_data = DeliveryHistory::whereHas('delivery_man.orders', function($query) use($request){
            return $query->where('id',$request->order_id);
        })->latest()->first();
        return response()->json($last_data, 200);
    }

    public function order_payment_status_update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required',
            'status' => 'required|in:paid'
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $dm = DeliveryMan::where(['auth_token' => $request['token']])->first();

        if (Order::where(['delivery_man_id' => $dm['id'], 'id' => $request['order_id']])->dmOrder()->first()) {
            Order::where(['delivery_man_id' => $dm['id'], 'id' => $request['order_id']])->update([
                'payment_status' => $request['status']
            ]);
            return response()->json(['message' => translate('Payment status updated') ], 200);
        }
        return response()->json([
            'errors' => [
                ['code' => 'order', 'message' => translate('not found!')]
            ]
        ], 404);
    }

    public function update_fcm_token(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fcm_token' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }
        $dm = DeliveryMan::where(['auth_token' => $request['token']])->first();

        DeliveryMan::where(['id' => $dm['id']])->update([
            'fcm_token' => $request['fcm_token']
        ]);

        return response()->json(['message'=> translate('successfully updated!')], 200);
    }

    public function get_notifications(Request $request){

        $dm = DeliveryMan::where(['auth_token' => $request['token']])->first();

        $notifications = Notification::active()->where(function($q) use($dm){
                $q->whereNull('zone_id')->orWhere('zone_id', $dm->zone_id);
            })->where('tergat', 'deliveryman')->where('created_at', '>=', \Carbon\Carbon::today()->subDays(7))->get();

        $user_notifications = UserNotification::where('delivery_man_id', $dm->id)->where('created_at', '>=', \Carbon\Carbon::today()->subDays(7))->get();

        $notifications->append('data');

        $notifications =  $notifications->merge($user_notifications);
        try {
            return response()->json($notifications, 200);
        } catch (\Exception $e) {
            return response()->json([], 200);
        }
    }

    public function remove_account(Request $request)
    {
        $dm = DeliveryMan::where(['auth_token' => $request['token']])->first();

        if(Order::where('delivery_man_id', $dm->id)->whereIn('order_status', ['pending','accepted','confirmed','processing','handover','picked_up'])->count())
        {
            return response()->json(['errors'=>[['code'=>'on-going', 'message'=>translate('messages.Please_complete_your_ongoing_and_accepted_orders')]]],203);
        }

        if($dm->wallet && $dm->wallet->collected_cash > 0)
        {
            return response()->json(['errors'=>[['code'=>'on-going', 'message'=>translate('messages.You_have_cash_in_hand,_you_have_to_pay_the_due_to_delete_your_account.')]]],203);
        }


        Helpers::check_and_delete('delivery-man/' , $dm['image']);


        foreach (json_decode($dm['identity_image'], true) as $img) {
            Helpers::check_and_delete('delivery-man/' , $img);

        }
        if($dm->userinfo){

            $dm->userinfo->delete();
        }
        $dm->delete();
        return response()->json([]);
    }
    Public function make_payment(Request $request){
        $validator = Validator::make($request->all(), [
            'payment_gateway' => 'required',
            'amount' => 'required|numeric|min:.001',
            'callback' => 'required',
            'token' => 'required'
        ]);


        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }
        $dm = DeliveryMan::where(['auth_token' => $request['token']])->firstOrfail();

        $payer = new Payer(
            $dm->f_name ,
            $dm->email,
            $dm->phone,
            ''
        );

        $store_logo= BusinessSetting::where(['key' => 'logo'])->first();
        $additional_data = [
            'business_name' => BusinessSetting::where(['key'=>'business_name'])->first()?->value,
            'business_logo' => \App\CentralLogics\Helpers::get_full_url('business',$store_logo?->value,$store_logo?->storage[0]?->value ?? 'public' )
        ];
        $payment_info = new PaymentInfo(
            success_hook: 'collect_cash_success',
            failure_hook: 'collect_cash_fail',
            currency_code: Helpers::currency_code(),
            payment_method: $request->payment_gateway,
            payment_platform: 'app',
            payer_id: $dm->id,
            receiver_id: '100',
            additional_data:  $additional_data,
            payment_amount: $request->amount ,
            external_redirect_link: $request->has('callback')?$request['callback']:session('callback'),
            attribute: 'deliveryman_collect_cash_payments',
            attribute_id: $dm->id,
        );

        $receiver_info = new Receiver('Admin','example.png');
        $redirect_link = Payment::generate_link($payer, $payment_info, $receiver_info);

        $data = [
            'redirect_link' => $redirect_link,
        ];
        return response()->json($data, 200);

    }


    public function make_wallet_adjustment(Request $request){

        $dm = DeliveryMan::where(['auth_token' => $request['token']])->firstOrfail();

        // FIX #1: Wrap entire operation in DB transaction for data consistency
        DB::beginTransaction();
        try {
            // FIX #2: Add pessimistic lock to prevent race conditions
            $wallet = DeliveryManWallet::where('delivery_man_id', $dm->id)
                ->lockForUpdate()
                ->firstOrFail();

            $wallet_earning =  round($wallet->total_earning -($wallet->total_withdrawn + $wallet->pending_withdraw) ,8);
            $adj_amount =  $wallet->collected_cash - $wallet_earning;

            // FIX #3: Validate wallet state before processing
            if($wallet->collected_cash < 0 || $wallet_earning < 0) {
                DB::rollBack();
                \Log::error('Invalid wallet state during adjustment', [
                    'dm_id' => $dm->id,
                    'collected_cash' => $wallet->collected_cash,
                    'wallet_earning' => $wallet_earning
                ]);
                return response()->json(['errors' => [['code' => 'invalid_wallet', 'message' => translate('messages.invalid_wallet_state')]]], 400);
            }

            if($wallet->collected_cash == 0 || $wallet_earning == 0 ){
                DB::rollBack();
                return response()->json(['message' => translate('messages.Already_Adjusted')], 201);
            }

            if($adj_amount > 0 ){
                $wallet->total_withdrawn =  $wallet->total_withdrawn + $wallet_earning ;
                $wallet->collected_cash =   $wallet->collected_cash - $wallet_earning ;

                $data = [
                    'delivery_man_id' => $dm->id,
                    'amount' => $wallet_earning,
                    'ref' => "delivery_man_wallet_adjustment_partial",
                    'method' => "adjustment",
                    'status' => 'credited',
                    'credited_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now()
                ];

            } else{
                $data = [
                    'delivery_man_id' => $dm->id,
                    'amount' => $wallet->collected_cash ,
                    'ref' => "delivery_man_wallet_adjustment_full",
                    'method' => "adjustment",
                    'status' => 'credited',
                    'credited_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now()
                ];
                $wallet->total_withdrawn =  $wallet->total_withdrawn + $wallet->collected_cash ;
                $wallet->collected_cash =   0;

            }

            $wallet->save();
            DB::table('provide_d_m_earnings')->insert($data);

            // Commit transaction only if both operations succeeded
            DB::commit();

            return response()->json(['message' => translate('messages.Delivery_man_wallet_adjustment_successfull')], 200);

        } catch(\Exception $e) {
            // Rollback all changes if any operation fails
            DB::rollBack();
            \Log::error('Wallet adjustment failed', [
                'dm_id' => $dm->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['errors' => [['code' => 'adjustment_failed', 'message' => translate('messages.adjustment_failed')]]], 500);
        }
    }
    public function wallet_payment_list(Request $request)
    {
        $limit= $request['limit'] ?? 25;
        $offset = $request['offset'] ?? 1;
        $dm = DeliveryMan::where(['auth_token' => $request['token']])->firstOrFail();

        $key = isset($request['search']) ? explode(' ', $request['search']) : [];
        $paginator = AccountTransaction::
        when(isset($key), function ($query) use ($key) {
            return $query->where(function ($q) use ($key) {
                foreach ($key as $value) {
                    $q->orWhere('ref', 'like', "%{$value}%");
                }
            });
        })
            ->where('type', 'collected')
            ->where('created_by' , 'deliveryman')
            ->where('from_id',$dm->id)
            ->where('from_type', 'deliveryman')
            ->latest()

            ->paginate($limit, ['*'], 'page', $offset);

        $temp= [];

        foreach( $paginator->items() as $item)
        {
            $item['status'] = 'approved';
            $item['payment_time'] = \App\CentralLogics\Helpers::time_date_format($item->created_at);

            $temp[] = $item;
        }
        $data = [
            'total_size' => $paginator->total(),
            'limit' => $limit,
            'offset' => $offset,
            'transactions' => $temp,
        ];

        return response()->json($data, 200);
    }
    public function wallet_provided_earning_list(Request $request)
    {
        $limit= $request['limit'] ?? 25;
        $offset = $request['offset'] ?? 1;
        $dm = DeliveryMan::where(['auth_token' => $request['token']])->firstOrFail();

        $key = isset($request['search']) ? explode(' ', $request['search']) : [];
        $paginator = ProvideDMEarning::
        when(isset($key), function ($query) use ($key) {
            return $query->where(function ($q) use ($key) {
                foreach ($key as $value) {
                    $q->orWhere('ref', 'like', "%{$value}%");
                }
            });
        })
            ->where('delivery_man_id',$dm->id)
            ->where('method', 'adjustment')
            ->whereIn('ref', ['delivery_man_wallet_adjustment_partial' , 'delivery_man_wallet_adjustment_full' ])
            ->latest()
            ->paginate($limit, ['*'], 'page', $offset);

        $temp= [];

        foreach( $paginator->items() as $item)
        {
            $item['amount'] = (float) $item['amount'];
            $item['status'] = 'Approved';
            $item['payment_time'] = \App\CentralLogics\Helpers::time_date_format($item->created_at);

            $temp[] = $item;
        }
        $data = [
            'total_size' => $paginator->total(),
            'limit' => $limit,
            'offset' => $offset,
            'transactions' => $temp,
        ];

        return response()->json($data, 200);
    }

    public function get_disbursement_withdrawal_methods(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'required',
            'offset' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $dm = DeliveryMan::where(['auth_token' => $request['token']])->first();

        $key = explode(' ', $request['search']);
        $paginator = DisbursementWithdrawalMethod::where('delivery_man_id', $dm['id'])
            ->when( isset($key) , function($query) use($key){
                $query->where(function ($q) use ($key) {
                    foreach ($key as $value) {
                        $q->orWhere('method_name', 'like', "%{$value}%");
                    }
                });
            }
            )
            ->latest()
            ->paginate($request['limit'], ['*'], 'page', $request['offset']);

        $datas =[];
        foreach ($paginator->items() as $k => $v) {
            $userInputs=[];
            foreach(json_decode($v->method_fields,true)as $key => $value){
                $userInput = [
                    'user_input' => $key,
                    'user_data' => $value,
                ];
                $userInputs[] = $userInput;
            }
            $v['method_fields'] = $userInputs;
            $datas[] = $v;
        }

        $data = [
            'total_size' => $paginator->total(),
            'limit' => $request['limit'],
            'offset' => $request['offset'],
            'methods' => $datas
        ];
        return response()->json($data, 200);
    }

    public function withdraw_method_list(){
        $wi=WithdrawalMethod::where('is_active',1)->get();
        return response()->json($wi,200);
    }

    public function disbursement_withdrawal_method_store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'withdraw_method_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $dm = DeliveryMan::where(['auth_token' => $request['token']])->first();

        $method = WithdrawalMethod::find($request['withdraw_method_id']);
        $fields = array_column($method->method_fields, 'input_name');
        $values = $request->all();

        $method_data = [];
        foreach ($fields as $field) {
            if(key_exists($field, $values)) {
                $method_data[$field] = $values[$field];
            }
        }

        $data = [
            'delivery_man_id' => $dm['id'],
            'withdrawal_method_id' => $method['id'],
            'method_name' => $method['method_name'],
            'method_fields' => json_encode($method_data),
            'is_default' => 0,
            'created_at' => now(),
            'updated_at' => now()
        ];

        DB::table('disbursement_withdrawal_methods')->insert($data);

        return response()->json(['message'=>translate('successfully added!')], 200);
    }

    public function disbursement_withdrawal_method_default(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'is_default' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }
        $dm = DeliveryMan::where(['auth_token' => $request['token']])->first();
        $method = DisbursementWithdrawalMethod::find($request->id);
        $method->is_default = $request->is_default;
        $method->save();
        DisbursementWithdrawalMethod::whereNot('id', $request->id)->where('delivery_man_id',$dm['id'])->update(['is_default' => 0]);
        return response()->json(['message'=>translate('messages.method_updated_successfully')], 200);
    }

    public function disbursement_withdrawal_method_delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $method = DisbursementWithdrawalMethod::find($request->id);
        $method->delete();
        return response()->json(['message'=>translate('messages.method_deleted_successfully')], 200);
    }

    public function disbursement_report(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'required',
            'offset' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }
        $limit = $request['limit']??25;
        $offset = $request['offset']??1;

        $dm = DeliveryMan::where(['auth_token' => $request['token']])->first();

        $total_disbursements=DisbursementDetails::where('delivery_man_id',$dm['id'])->latest()->get();
        $paginator=DisbursementDetails::where('delivery_man_id',$dm['id'])->latest()->paginate($limit, ['*'], 'page', $offset);

        $paginator->each(function ($data) {
            $data->withdraw_method?->method_fields ?  $data->withdraw_method->method_fields = json_decode($data->withdraw_method?->method_fields, true) : '';
        });

        $data = [
            'total_size' => $paginator->total(),
            'limit' => $limit,
            'offset' => $offset,
            'pending' =>(float) $total_disbursements->where('status','pending')->sum('disbursement_amount'),
            'completed' =>(float) $total_disbursements->where('status','completed')->sum('disbursement_amount'),
            'canceled' =>(float) $total_disbursements->where('status','canceled')->sum('disbursement_amount'),
            'complete_day' =>(int) BusinessSetting::where(['key'=>'dm_disbursement_waiting_time'])->first()?->value,
            'disbursements' => $paginator->items()
        ];
        return response()->json($data,200);

    }

    public function mark_item_unavailable(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|integer',
            'order_detail_id' => 'required|integer',
            'is_unavailable' => 'required|boolean',
            'unavailable_note' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $dm = DeliveryMan::where(['auth_token' => $request['token']])->first();

        $order = Order::where(['id' => $request['order_id'], 'delivery_man_id' => $dm['id']])->first();
        if (!$order) {
            return response()->json([
                'errors' => [
                    ['code' => 'order', 'message' => translate('messages.not_found')]
                ]
            ], 404);
        }

        $orderDetail = OrderDetail::where(['id' => $request['order_detail_id'], 'order_id' => $request['order_id']])->first();
        if (!$orderDetail) {
            return response()->json([
                'errors' => [
                    ['code' => 'order_detail', 'message' => translate('messages.not_found')]
                ]
            ], 404);
        }

        $orderDetail->is_unavailable = $request['is_unavailable'];
        $orderDetail->unavailable_note = $request['unavailable_note'];
        $orderDetail->marked_unavailable_at = $request['is_unavailable'] ? now() : null;
        $orderDetail->save();

        return response()->json([
            'message' => translate('messages.item_unavailability_updated'),
            'order_detail_id' => $orderDetail->id,
            'is_unavailable' => (bool) $orderDetail->is_unavailable,
        ], 200);
    }

    public function update_item_pickup(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|integer',
            'order_detail_id' => 'required|integer',
            'is_picked_up' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $dm = DeliveryMan::where(['auth_token' => $request['token']])->first();

        $order = Order::where(['id' => $request['order_id'], 'delivery_man_id' => $dm['id']])->first();
        if (!$order) {
            return response()->json([
                'errors' => [
                    ['code' => 'order', 'message' => translate('messages.not_found')]
                ]
            ], 404);
        }

        $orderDetail = OrderDetail::where(['id' => $request['order_detail_id'], 'order_id' => $request['order_id']])->first();
        if (!$orderDetail) {
            return response()->json([
                'errors' => [
                    ['code' => 'order_detail', 'message' => translate('messages.not_found')]
                ]
            ], 404);
        }

        $orderDetail->is_picked_up = $request['is_picked_up'];
        $orderDetail->picked_up_at = $request['is_picked_up'] ? now() : null;
        $orderDetail->save();

        // Broadcast real-time update
        event(new ItemPickupUpdated(
            $order->id,
            $orderDetail->id,
            $orderDetail->is_picked_up,
            $orderDetail->picked_up_at
        ));

        return response()->json([
            'message' => translate('messages.item_pickup_status_updated'),
            'order_detail_id' => $orderDetail->id,
            'is_picked_up' => (bool) $orderDetail->is_picked_up,
            'picked_up_at' => $orderDetail->picked_up_at,
        ], 200);
    }

    public function upload_bill_image(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|integer',
            'bill_image' => 'required|array|max:3',
            'bill_image.*' => 'required|image|mimes:jpg,jpeg,png|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $dm = DeliveryMan::where(['auth_token' => $request['token']])->first();

        $order = Order::where(['id' => $request['order_id'], 'delivery_man_id' => $dm['id']])->first();
        if (!$order) {
            return response()->json([
                'errors' => [
                    ['code' => 'order', 'message' => translate('messages.not_found')]
                ]
            ], 404);
        }

        $img_names = [];
        foreach ($request->bill_image as $img) {
            $image_name = Helpers::upload('order/', 'png', $img);
            $img_names[] = ['img' => $image_name, 'storage' => Helpers::getDisk()];
        }

        $order->bill_image = json_encode($img_names);
        $order->is_billed = true;
        $order->billed_at = now();
        $order->save();

        // Notify admin about bill upload
        try {
            $storeName = $order->store ? $order->store->name : 'N/A';
            $notifData = [
                'title' => translate('messages.bill_received'),
                'description' => translate('messages.bill_uploaded_for_order') . ' #' . $order->id . ' (' . $storeName . ')',
                'order_id' => $order->id,
                'image' => '',
                'type' => 'bill_uploaded',
            ];
            Helpers::send_push_notif_to_topic($notifData, 'admin_message', 'bill_uploaded', url('/') . '/admin/order/details/' . $order->id);
            DB::table('user_notifications')->insert([
                'data' => json_encode($notifData),
                'user_id' => $order->user_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            \Log::warning('Bill upload notification failed: ' . $e->getMessage());
        }

        return response()->json([
            'message' => translate('messages.bill_image_uploaded_successfully'),
            'is_billed' => true,
            'billed_at' => $order->billed_at->format('Y-m-d H:i:s'),
            'bill_image_full_url' => $order->bill_image_full_url,
        ], 200);
    }

    public function request_mrp_update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|integer',
            'order_detail_id' => 'required|integer',
            'requested_mrp' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $dm = DeliveryMan::where(['auth_token' => $request['token']])->first();

        $order = Order::where(['id' => $request['order_id'], 'delivery_man_id' => $dm['id']])->first();
        if (!$order) {
            return response()->json([
                'errors' => [
                    ['code' => 'order', 'message' => translate('messages.not_found')]
                ]
            ], 404);
        }

        $orderDetail = OrderDetail::where(['id' => $request['order_detail_id'], 'order_id' => $request['order_id']])->first();
        if (!$orderDetail) {
            return response()->json([
                'errors' => [
                    ['code' => 'order_detail', 'message' => translate('messages.not_found')]
                ]
            ], 404);
        }

        $orderDetail->requested_mrp = $request['requested_mrp'];
        $orderDetail->mrp_update_status = 'pending';
        $orderDetail->save();

        return response()->json([
            'message' => translate('messages.mrp_update_request_submitted'),
            'order_detail_id' => $orderDetail->id,
            'requested_mrp' => (float) $orderDetail->requested_mrp,
            'mrp_update_status' => $orderDetail->mrp_update_status,
        ], 200);
    }

    public function mark_outside_purchase(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_detail_id' => 'required|integer',
            'outside_purchase_cost' => 'required|numeric|min:0',
            'outside_purchase_store_id' => 'nullable|integer|exists:stores,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $dm = DeliveryMan::where(['auth_token' => $request->header('token')])->first();
        $detail = OrderDetail::findOrFail($request->order_detail_id);
        $order = $detail->order;

        if ($order->delivery_man_id != $dm->id) {
            return response()->json(['errors' => [['code' => 'unauthorized', 'message' => translate('messages.unauthorized')]]], 403);
        }

        // Delivery man can only request - admin needs to approve
        $detail->is_outside_purchase = false; // Will be set to true after admin approval
        $detail->outside_purchase_cost = $request->outside_purchase_cost;
        $detail->outside_purchase_store_id = $request->outside_purchase_store_id;
        $detail->outside_purchase_status = 'pending';
        $detail->outside_purchase_requested_by = 'deliveryman';
        $detail->save();

        return response()->json([
            'message' => translate('messages.outside_purchase_request_submitted'),
            'status' => 'pending'
        ], 200);
    }

    public function get_stores_for_outside_purchase(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'search' => 'nullable|string|max:255',
            'limit' => 'nullable|integer|min:1|max:200',
            'order_id' => 'nullable|integer|exists:orders,id',
            'module_id' => 'nullable|integer|exists:modules,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $search = $request->get('search', '');
        $limit = $request->get('limit', 100);
        $moduleId = $request->get('module_id');
        $orderId = $request->get('order_id');

        // If order_id provided, get module from order
        if ($orderId && !$moduleId) {
            $order = \App\Models\Order::with('store:id,module_id')->find($orderId);
            if ($order && $order->store) {
                $moduleId = $order->store->module_id;
            }
        }

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
                    'name' => $store->name,
                    'address' => $store->address ?? '',
                    'zone' => $store->zone ? $store->zone->name : null,
                    'module' => $store->module ? $store->module->module_name : null,
                    'display_name' => $store->name . ($store->zone ? ' - ' . $store->zone->name : ''),
                ];
            });

        return response()->json([
            'stores' => $stores,
            'total' => $stores->count(),
            'module_filtered' => $moduleId ? true : false,
            'module_id' => $moduleId
        ], 200);
    }

    // ===== Performance & Ranking APIs ===== //

    public function getPerformanceStats(Request $request)
    {
        $dm = DeliveryMan::where(['auth_token' => $request['token']])->first();
        if (!$dm) return response()->json(['errors' => [['code' => 'not_found', 'message' => 'Delivery man not found']]], 404);

        $service = new DmRankingService();
        return response()->json($service->getDmPerformanceStats($dm), 200);
    }

    public function getLeaderboard(Request $request)
    {
        $dm = DeliveryMan::where(['auth_token' => $request['token']])->first();
        if (!$dm) return response()->json(['errors' => [['code' => 'not_found', 'message' => 'Delivery man not found']]], 404);

        $type = $request->get('type', 'daily');
        $zoneId = $request->get('zone_id', $dm->zone_id);

        $service = new DmRankingService();
        $leaderboard = $service->getLeaderboard($type, $zoneId);

        return response()->json(['leaderboard' => $leaderboard], 200);
    }

    public function getIncentiveBreakdown(Request $request)
    {
        $dm = DeliveryMan::where(['auth_token' => $request['token']])->first();
        if (!$dm) {
            return response()->json([
                'errors' => [['code' => 'not_found', 'message' => 'Delivery man not found']]
            ], 404);
        }

        $period = $request->get('period', 'today'); // today, week, month

        try {
            $service = new DmIncentiveService();
            $breakdown = $service->getIncentiveBreakdown($dm, $period);

            return response()->json($breakdown, 200);
        } catch (\Exception $e) {
            \Log::error('Incentive breakdown error: ' . $e->getMessage(), [
                'dm_id' => $dm->id,
                'period' => $period,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'errors' => [['code' => 'server_error', 'message' => 'Failed to fetch incentive breakdown']]
            ], 500);
        }
    }

    public function getRushStatus(Request $request)
    {
        $dm = DeliveryMan::where(['auth_token' => $request['token']])->first();
        if (!$dm) return response()->json(['errors' => [['code' => 'not_found', 'message' => 'Delivery man not found']]], 404);

        $service = new DmRushMonitorService();
        $status = $service->getRushStatusForZone($dm->zone_id);

        return response()->json(['rush_status' => $status], 200);
    }

    // ===== Shift Management APIs ===== //

    /**
     * @deprecated Use getShiftSlots instead - This method now redirects to getShiftSlots
     */
    public function getAvailableShifts(Request $request)
    {
        // Get response from new method
        $newResponse = $this->getShiftSlots($request);
        $newData = $newResponse->getData(true);

        // Transform to old format for backward compatibility
        $already_booked = false;
        $current_shift = null;
        $available_shifts = [];

        if (isset($newData['slots']) && count($newData['slots']) > 0) {
            $firstSlot = $newData['slots'][0];

            if (isset($firstSlot['is_booked']) && $firstSlot['is_booked']) {
                $already_booked = true;
                $current_shift = $firstSlot;
            } else {
                $available_shifts = $newData['slots'];
            }
        }

        return response()->json([
            'already_assigned' => $already_booked,
            'current_shift' => $current_shift,
            'available_shifts' => $available_shifts,
        ], 200);
    }

    /**
     * @deprecated Use bookShift instead
     */
    public function selfAssignShift(Request $request)
    {
        $dm = DeliveryMan::where(['auth_token' => $request['token']])->first();
        if (!$dm) return response()->json(['errors' => [['code' => 'not_found', 'message' => 'Delivery man not found']]], 404);

        $validator = Validator::make($request->all(), [
            'shift_template_id' => 'required|integer',
            'date' => 'required|date|after_or_equal:today',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        // DEPRECATED: Using unified DmShiftBookingService for consistency
        \Log::warning('DEPRECATED: selfAssignShift endpoint called. Consider migrating to bookShift.', [
            'dm_id' => $dm->id,
            'shift_template_id' => $request->shift_template_id,
            'date' => $request->date,
        ]);

        try {
            $service = new \App\Services\DmShiftBookingService();
            $booking = $service->bookShift($dm->id, $request->shift_template_id, $request->date, null);
            return response()->json(['message' => 'Shift assigned successfully', 'roster' => $booking], 200)
                ->header('Deprecated', 'true')
                ->header('Sunset', '2026-06-30')
                ->header('Warning', '299 - "This endpoint is deprecated. Use bookShift instead."');
        } catch (\Exception $e) {
            return response()->json(['errors' => [['code' => 'error', 'message' => $e->getMessage()]]], 400);
        }
    }

    /**
     * @deprecated Use getMyBookings instead - This method now redirects to getMyBookings
     */
    public function getMyShifts(Request $request)
    {
        // Silently redirect to new method
        return $this->getMyBookings($request);
    }

    /**
     * Get my upcoming shift bookings
     * GET /api/v1/delivery-man/my-bookings
     */
    public function getMyBookings(Request $request)
    {
        $dm = DeliveryMan::where(['auth_token' => $request['token']])->first();
        if (!$dm) return response()->json(['errors' => [['code' => 'not_found', 'message' => 'Delivery man not found']]], 404);

        $days = $request->get('days', 7);

        $service = new \App\Services\DmShiftBookingService();
        return response()->json(['shifts' => $service->getUpcomingShifts($dm->id, $days)], 200);
    }

    public function requestShiftSwap(Request $request)
    {
        $dm = DeliveryMan::where(['auth_token' => $request['token']])->first();
        if (!$dm) return response()->json(['errors' => [['code' => 'not_found', 'message' => 'Delivery man not found']]], 404);

        // DEPRECATED: Shift swap functionality has been migrated to the new booking system
        // This endpoint is temporarily disabled during migration
        \Log::warning('DEPRECATED: requestShiftSwap endpoint called. Temporarily disabled.', [
            'dm_id' => $dm->id,
        ]);

        return response()->json([
            'errors' => [[
                'code' => 'temporarily_unavailable',
                'message' => 'Shift swap functionality is currently being updated. Please contact support for assistance with shift changes.'
            ]]
        ], 503);
    }

    public function updateNotificationPreferences(Request $request)
    {
        $dm = DeliveryMan::where(['auth_token' => $request['token']])->first();
        if (!$dm) return response()->json(['errors' => [['code' => 'not_found', 'message' => 'Delivery man not found']]], 404);

        $dm->update([
            'notify_new_orders' => $request->get('notify_new_orders', $dm->notify_new_orders),
            'notify_rush_active' => $request->get('notify_rush_active', $dm->notify_rush_active),
            'notify_incentives' => $request->get('notify_incentives', $dm->notify_incentives),
        ]);

        return response()->json(['message' => 'Notification preferences updated'], 200);
    }

    // ========== Heatmap & Hotspots ==========

    public function getHeatmap(Request $request)
    {
        $dm = DeliveryMan::where(['auth_token' => $request['token']])->first();
        if (!$dm) return response()->json(['errors' => [['code' => 'not_found', 'message' => 'Delivery man not found']]], 404);

        $service = new DmHeatmapService();
        return response()->json($service->generateHeatmapData($dm->zone_id), 200);
    }

    public function getHotspots(Request $request)
    {
        $dm = DeliveryMan::where(['auth_token' => $request['token']])->first();
        if (!$dm) return response()->json(['errors' => [['code' => 'not_found', 'message' => 'Delivery man not found']]], 404);

        $service = new DmHeatmapService();
        return response()->json($service->getHotspots($dm->zone_id), 200);
    }

    public function getDemandForecast(Request $request)
    {
        $dm = DeliveryMan::where(['auth_token' => $request['token']])->first();
        if (!$dm) return response()->json(['errors' => [['code' => 'not_found', 'message' => 'Delivery man not found']]], 404);

        $service = new DmHeatmapService();
        return response()->json($service->getDemandForecast($dm->zone_id), 200);
    }

    public function getActiveCartHotspots(Request $request)
    {
        $dm = DeliveryMan::where(['auth_token' => $request['token']])->first();
        if (!$dm) return response()->json(['errors' => [['code' => 'not_found', 'message' => 'Delivery man not found']]], 404);

        $service = new DmHeatmapService();
        return response()->json($service->getActiveCartHotspots($dm->zone_id), 200);
    }

    // ========== Order Batch ==========

    public function getCurrentBatch(Request $request)
    {
        $dm = DeliveryMan::where(['auth_token' => $request['token']])->first();
        if (!$dm) return response()->json(['errors' => [['code' => 'not_found', 'message' => 'Delivery man not found']]], 404);

        $service = new OrderBatchingService();
        $batch = $service->getActiveBatchForDm($dm->id);

        if (!$batch) return response()->json(['message' => 'No active batch'], 200);

        return response()->json([
            'batch_code' => $batch->batch_code,
            'status' => $batch->status,
            'total_orders' => $batch->total_orders,
            'orders' => $batch->orders->map(function ($o) {
                return [
                    'id' => $o->id,
                    'order_status' => $o->order_status,
                    'store_name' => $o->store?->name,
                    'store_lat' => $o->store?->latitude,
                    'store_lng' => $o->store?->longitude,
                    'delivery_address' => $o->delivery_address,
                    'order_amount' => $o->order_amount,
                ];
            }),
        ], 200);
    }

    // ========== SOS ==========

    public function triggerSos(Request $request)
    {
        $dm = DeliveryMan::where(['auth_token' => $request['token']])->first();
        if (!$dm) return response()->json(['errors' => [['code' => 'not_found', 'message' => 'Delivery man not found']]], 404);

        $sos = DmSosRequest::create([
            'delivery_man_id' => $dm->id,
            'order_id' => $request->order_id,
            'type' => $request->type ?? 'other',
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'status' => 'active',
            'notes' => $request->notes,
        ]);

        // Notify all admins
        try {
            $admins = \App\Models\Admin::whereNotNull('firebase_token')->get();
            foreach ($admins as $admin) {
                Helpers::send_push_notif_to_device($admin->firebase_token, [
                    'title' => 'SOS ALERT!',
                    'description' => "Delivery man {$dm->f_name} {$dm->l_name} triggered SOS ({$sos->type})",
                    'type' => 'sos_alert',
                    'sos_id' => $sos->id,
                ]);
            }
        } catch (\Exception $e) {
            \Log::error("SOS admin notification failed: " . $e->getMessage());
        }

        return response()->json(['message' => 'SOS triggered successfully', 'sos_id' => $sos->id], 200);
    }

    public function getActiveSos(Request $request)
    {
        $dm = DeliveryMan::where(['auth_token' => $request['token']])->first();
        if (!$dm) return response()->json(['errors' => [['code' => 'not_found', 'message' => 'Delivery man not found']]], 404);

        $sos = DmSosRequest::where('delivery_man_id', $dm->id)->active()->latest()->first();

        return response()->json($sos, 200);
    }

    // ========== Referral ==========

    public function getReferralCode(Request $request)
    {
        $dm = DeliveryMan::where(['auth_token' => $request['token']])->first();
        if (!$dm) return response()->json(['errors' => [['code' => 'not_found', 'message' => 'Delivery man not found']]], 404);

        $service = new DmReferralService();
        $code = $service->generateRefCode($dm);

        return response()->json(['ref_code' => $code], 200);
    }

    public function getReferralStats(Request $request)
    {
        $dm = DeliveryMan::where(['auth_token' => $request['token']])->first();
        if (!$dm) return response()->json(['errors' => [['code' => 'not_found', 'message' => 'Delivery man not found']]], 404);

        $service = new DmReferralService();
        return response()->json($service->getReferralStats($dm), 200);
    }

    // ========== Instant Withdrawal ==========

    public function requestWithdrawal(Request $request)
    {
        $dm = DeliveryMan::where(['auth_token' => $request['token']])->first();
        if (!$dm) return response()->json(['errors' => [['code' => 'not_found', 'message' => 'Delivery man not found']]], 404);

        $service = new DmWithdrawalService();
        $result = $service->requestWithdrawal(
            $dm,
            (float) $request->amount,
            $request->method ?? 'bank',
            $request->account_details
        );

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    public function getWithdrawalHistory(Request $request)
    {
        $dm = DeliveryMan::where(['auth_token' => $request['token']])->first();
        if (!$dm) return response()->json(['errors' => [['code' => 'not_found', 'message' => 'Delivery man not found']]], 404);

        $service = new DmWithdrawalService();
        return response()->json($service->getHistory($dm), 200);
    }

    public function update_item_location(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'item_id' => 'required|integer|exists:items,id',
            'token' => 'required',
            'rack' => 'nullable|string|max:50',
            'row' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        // Authenticate delivery man
        $dm = DeliveryMan::where(['auth_token' => $request['token']])->first();
        if (!$dm) {
            return response()->json([
                'errors' => [['code' => 'not_found', 'message' => 'Delivery man not found']]
            ], 404);
        }

        // Update item location
        $item = Item::find($request->item_id);
        if (!$item) {
            return response()->json([
                'errors' => [['code' => 'not_found', 'message' => 'Item not found']]
            ], 404);
        }

        $item->rack = $request->rack;
        $item->row = $request->row;
        $item->save();

        return response()->json([
            'message' => 'Item location updated successfully',
            'item' => [
                'id' => $item->id,
                'name' => $item->name,
                'rack' => $item->rack,
                'row' => $item->row,
            ]
        ], 200);
    }

    /**
     * Get offline time summary for today
     *
     * This helps delivery boys track their offline time and incentive eligibility
     */
    public function getOfflineTimeSummary(Request $request)
    {
        $dm = DeliveryMan::where(['auth_token' => $request['token']])->first();

        if (!$dm) {
            return response()->json([
                'errors' => [['code' => 'not_found', 'message' => 'Delivery man not found']]
            ], 404);
        }

        $attendanceService = new DeliverymanAttendanceService();
        $summary = $attendanceService->getOfflineTimeSummary($dm->id);

        return response()->json([
            'offline_summary' => $summary,
            'warning' => $summary['remaining_allowed_minutes'] <= 2 && $summary['remaining_allowed_minutes'] > 0
                ? "Warning: Only {$summary['remaining_allowed_minutes']} minutes of offline time remaining before losing incentives!"
                : null,
            'message' => !$summary['incentive_eligible']
                ? 'You have exceeded the offline time limit. Incentives will not be credited for today.'
                : 'You are currently eligible for incentives.',
        ], 200);
    }

    /**
     * Get available shift slots for a specific date
     * GET /api/v1/delivery-man/shift-slots?token={token}&date={YYYY-MM-DD}
     */
    public function getShiftSlots(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        // Validate date is not too far in the past (allow yesterday for timezone flexibility)
        $requestDate = \Carbon\Carbon::parse($request->date);
        if ($requestDate->isBefore(now()->subDay())) {
            return response()->json([
                'errors' => [['code' => 'invalid_date', 'message' => 'Cannot view shifts for dates more than 1 day in the past']]
            ], 403);
        }

        $dm = DeliveryMan::where(['auth_token' => $request['token']])->first();
        if (!$dm) {
            return response()->json(['errors' => [['code' => 'not_found', 'message' => 'Delivery man not found']]], 404);
        }

        $date = $request->date;

        // Use unified service for consistent availability calculation
        $service = new \App\Services\DmShiftBookingService();
        $result = $service->getAvailableSlots($dm->id, $date);

        // Format all slots — each slot carries its own status ('available', 'booked', 'full', 'closed')
        // NOTE: current_booking was deprecated and is always null; use slot status instead.
        $slots = collect($result['available_slots'])->map(function ($slot) {
            return [
                'id'               => $slot['id'],
                'shift_template_id'=> $slot['id'],
                'name'             => $slot['name'],
                'date'             => $slot['date'],
                'start_time'       => $slot['start_time'],
                'end_time'         => $slot['end_time'],
                'zone'             => $slot['zone'],
                'spots_available'  => $slot['spots_available'],
                'spots_booked'     => $slot['spots_booked'],
                'total_spots'      => $slot['total_spots'],
                'incentive_amount' => $slot['incentive_amount'],
                'booking_deadline' => $slot['booking_deadline'],
                'status'           => $slot['status'],   // 'available'|'booked'|'full'|'closed'
                'is_booked'        => $slot['is_booked_by_dm'],  // true when THIS DM has booked it
                'can_book'         => $slot['can_book'],
            ];
        });

        return response()->json([
            'already_booked' => $result['already_booked'],
            'booked_count'   => $result['booked_count'],
            'slots'          => $slots->values(),
        ], 200);
    }

    /**
     * Book a shift slot
     * POST /api/v1/delivery-man/book-shift
     */
    public function bookShift(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'shift_template_id' => 'required|exists:shift_templates,id',
            'date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $dm = DeliveryMan::where(['auth_token' => $request['token']])->first();
        if (!$dm) {
            return response()->json(['errors' => [['code' => 'not_found', 'message' => 'Delivery man not found']]], 404);
        }

        $shiftTemplateId = $request->shift_template_id;
        $date = $request->date;

        try {
            // Use unified service for booking
            $service = new \App\Services\DmShiftBookingService();
            $booking = $service->bookShift($dm->id, $shiftTemplateId, $date, null);

            return response()->json([
                'success' => true,
                'message' => 'Shift booked successfully',
                'booking_id' => $booking->id,
            ], 200);

        } catch (\Exception $e) {
            // Business-logic errors (full slot, deadline passed, overlap, etc.) → 400 not 500
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Cancel a shift booking
     * POST /api/v1/delivery-man/cancel-shift-booking
     */
    public function cancelShiftBooking(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'booking_id' => 'required|exists:dm_shift_bookings,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $dm = DeliveryMan::where(['auth_token' => $request['token']])->first();
        if (!$dm) {
            return response()->json(['errors' => [['code' => 'not_found', 'message' => 'Delivery man not found']]], 404);
        }

        $bookingId = $request->booking_id;
        $reason = $request->input('reason');

        try {
            // Use unified service for cancellation
            $service = new \App\Services\DmShiftBookingService();
            $service->cancelBooking($bookingId, $dm->id, $reason);

            return response()->json([
                'success' => true,
                'message' => 'Booking cancelled successfully',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel booking: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get weekly earnings breakdown for delivery man
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function get_weekly_earnings(Request $request)
    {
        $dm = DeliveryMan::where(['auth_token' => $request['token']])->first();
        if (!$dm) {
            return response()->json(['errors' => [['code' => 'auth', 'message' => 'Unauthorized']]], 401);
        }

        // Parse week_start (Monday) or default to current week
        if ($request->has('week_start') && $request['week_start']) {
            try {
                $weekStart = Carbon::parse($request['week_start'])->startOfDay();
            } catch (\Exception $e) {
                $weekStart = Carbon::now()->startOfWeek();
            }
        } else {
            $weekStart = Carbon::now()->startOfWeek();
        }
        $weekEnd = $weekStart->copy()->addDays(6)->endOfDay();

        // Query order_transactions for delivery charges and tips
        $transactions = \App\Models\OrderTransaction::where('delivery_man_id', $dm->id)
            ->whereBetween('created_at', [$weekStart, $weekEnd])
            ->select([
                DB::raw('DAYOFWEEK(created_at) as dow'),  // 1=Sun..7=Sat in MySQL
                DB::raw('SUM(original_delivery_charge) as total_delivery'),
                DB::raw('SUM(COALESCE(dm_tips, 0)) as total_tips'),
                DB::raw('COUNT(*) as order_count'),
            ])
            ->groupBy('dow')
            ->get();

        // Query incentive earnings (from provide_d_m_earnings table)
        $incentives = \App\Models\ProvideDMEarning::where('delivery_man_id', $dm->id)
            ->where('method', 'incentive')
            ->where('status', 'credited') // Only credited incentives
            ->whereBetween('created_at', [$weekStart, $weekEnd])
            ->select([
                DB::raw('DAYOFWEEK(created_at) as dow'),
                DB::raw('SUM(amount) as total_incentive'),
            ])
            ->groupBy('dow')
            ->get();

        // MySQL DAYOFWEEK: 1=Sun, 2=Mon, ..., 7=Sat
        // App expects: 1=Mon, 2=Tue, ..., 7=Sun
        // Conversion: app_day = mysql_dow == 1 ? 7 : mysql_dow - 1
        $daily = [];
        $totalEarning = 0;
        $totalTips = 0;
        $totalIncentive = 0;

        // Initialize all 7 days
        for ($d = 1; $d <= 7; $d++) {
            $daily[$d] = [
                'day' => $d,
                'earning' => 0.0,
                'tips' => 0.0,
                'incentive' => 0.0,
                'orders' => 0
            ];
        }

        // Populate delivery charges and tips
        foreach ($transactions as $t) {
            $mysqlDow = (int)$t->dow;
            $appDay = $mysqlDow == 1 ? 7 : $mysqlDow - 1;

            $delivery = (float)$t->total_delivery;
            $tips = (float)$t->total_tips;
            $earning = $delivery + $tips;

            $daily[$appDay]['earning'] = round($earning, 2);
            $daily[$appDay]['tips'] = round($tips, 2);
            $daily[$appDay]['orders'] = (int)$t->order_count;

            $totalEarning += $earning;
            $totalTips += $tips;
        }

        // Populate incentives
        foreach ($incentives as $inc) {
            $mysqlDow = (int)$inc->dow;
            $appDay = $mysqlDow == 1 ? 7 : $mysqlDow - 1;

            $incentiveAmount = (float)$inc->total_incentive;
            $daily[$appDay]['incentive'] = round($incentiveAmount, 2);

            $totalIncentive += $incentiveAmount;
            $totalEarning += $incentiveAmount;
        }

        return response()->json([
            'total_earning' => round($totalEarning, 2),
            'total_tips' => round($totalTips, 2),
            'total_incentive' => round($totalIncentive, 2),
            'week_start' => $weekStart->format('Y-m-d'),
            'week_end' => $weekEnd->format('Y-m-d'),
            'daily' => array_values($daily),
        ], 200);
    }
}
