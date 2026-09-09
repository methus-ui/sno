<?php

namespace App\Http\Controllers;

use App\Models\Zone;
use App\Models\Admin;
use App\Models\Store;
use App\Models\Module;
use App\Models\Vendor;
use Illuminate\Http\Request;
use App\CentralLogics\Helpers;
use App\Mail\StoreRegistration;
use App\Models\BusinessSetting;
use App\CentralLogics\StoreLogic;
use App\CentralLogics\SMS_module;
use Illuminate\Http\JsonResponse;
use App\Models\SubscriptionPackage;
use Gregwar\Captcha\CaptchaBuilder;
use App\Mail\VendorSelfRegistration;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use MatanYadaev\EloquentSpatial\Objects\Point;
use Modules\Rental\Emails\ProviderRegistration;
use Modules\Rental\Emails\ProviderSelfRegistration;

class VendorController extends Controller
{
    public function create()
    {
        $status = BusinessSetting::where('key', 'toggle_store_registration')->first();
        if(!isset($status) || $status->value == '0')
        {
            Toastr::error(translate('messages.not_found'));
            return back();
        }
        $admin_commission= BusinessSetting::where('key','admin_commission')->first()?->value;
        $business_name= BusinessSetting::where('key','business_name')->first()?->value;
        $packages= SubscriptionPackage::where('status',1)->where('module_type', 'all')->latest()->get();
        $custome_recaptcha = new CaptchaBuilder;
        $custome_recaptcha->build();
        Session::put('six_captcha', $custome_recaptcha->getPhrase());

        return view('vendor-views.auth.general-info', compact('custome_recaptcha','admin_commission','business_name','packages' ));
    }

  public function store(Request $request)
{
    $status = BusinessSetting::where('key', 'toggle_store_registration')->first();
    if(!isset($status) || $status->value == '0')
    {
        Toastr::error(translate('messages.not_found'));
        return back();
    }

    // Removed captcha validation completely

    $validator = Validator::make($request->all(), [
        'f_name' => 'required',
        'l_name' => 'required',
        'name.default' => 'required', // Updated to handle array structure
        'address.default' => 'required', // Updated to handle array structure
        'latitude' => 'required',
        'longitude' => 'required',
        'email' => 'required|email|unique:vendors',
        'phone' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:10|unique:vendors',
        'minimum_delivery_time' => 'required|integer|min:1',
        'maximum_delivery_time' => 'required|integer|min:1|gt:minimum_delivery_time',
        'password' => 'required|min:8|confirmed',
        'zone_id' => 'required|exists:zones,id',
        'module_id' => 'required|exists:modules,id',
        'logo' => [
            'required',
            'image',
            'mimes:webp,jpg,jpeg,png',
            'max:2048',
        ],
        'cover_photo' => [
            'nullable',
            'image',
            'mimes:webp,jpg,jpeg,png',
            'max:2048',
        ],
        'tax' => 'required|numeric|min:0|max:100',
        'delivery_time_type' => 'required|in:min,hours,days',
        'business_plan' => 'required|in:commission-base,subscription-base',
        'package_id' => 'required_if:business_plan,subscription-base|exists:subscription_packages,id',
    ], [
        'f_name.required' => translate('First name is required'),
        'l_name.required' => translate('Last name is required'),
        'name.default.required' => translate('Store name is required'),
        'address.default.required' => translate('Store address is required'),
        'email.required' => translate('Email is required'),
        'email.email' => translate('Please provide a valid email address'),
        'email.unique' => translate('This email is already registered'),
        'phone.required' => translate('Phone number is required'),
        'phone.unique' => translate('This phone number is already registered'),
        'phone.regex' => translate('Please provide a valid phone number'),
        'password.required' => translate('Password is required'),
        'password.min' => translate('Password must be at least 8 characters long'),
        'password.confirmed' => translate('Passwords do not match'),
        'maximum_delivery_time.gt' => translate('Maximum delivery time must be greater than minimum delivery time'),
        'zone_id.required' => translate('Please select a zone'),
        'zone_id.exists' => translate('Selected zone is invalid'),
        'module_id.required' => translate('Please select a service type'),
        'module_id.exists' => translate('Selected service type is invalid'),
        'logo.required' => translate('Store logo is required'),
        'logo.image' => translate('Logo must be an image file'),
        'logo.mimes' => translate('Logo must be a WEBP, JPG, JPEG, or PNG file'),
        'logo.max' => translate('Logo size must not exceed 2MB'),
        'cover_photo.image' => translate('Cover photo must be an image file'),
        'cover_photo.mimes' => translate('Cover photo must be a WEBP, JPG, JPEG, or PNG file'),
        'cover_photo.max' => translate('Cover photo size must not exceed 2MB'),
        'tax.required' => translate('Tax rate is required'),
        'tax.numeric' => translate('Tax rate must be a number'),
        'tax.max' => translate('Tax rate cannot exceed 100%'),
        'delivery_time_type.required' => translate('Please select delivery time unit'),
        'delivery_time_type.in' => translate('Invalid delivery time unit'),
        'business_plan.required' => translate('Please select a business plan'),
        'business_plan.in' => translate('Invalid business plan selected'),
        'package_id.required_if' => translate('Please select a subscription package'),
        'package_id.exists' => translate('Selected package is invalid'),
    ]);

    if ($validator->fails()) {
        return back()
            ->withErrors($validator)
            ->withInput();
    }

    // Validate coordinates are within selected zone
    if($request->zone_id)
    {
        $zone = Zone::query()
            ->whereContains('coordinates', new Point($request->latitude, $request->longitude, POINT_SRID))
            ->where('id',$request->zone_id)
            ->first();
        if(!$zone){
            $validator->getMessageBag()->add('latitude', translate('messages.coordinates_out_of_zone'));
            return back()->withErrors($validator)
                    ->withInput();
        }
    }

    // Check rental module requirements
    $module = Module::find($request['module_id']);
    if ($module?->module_type == 'rental' && addon_published_status('Rental') && empty($request['pickup_zone_id'])){
        $validator->getMessageBag()->add('pickup_zone_id', translate('messages.You_must_select_a_pickup_zone'));
        return back()->withErrors($validator)
            ->withInput();
    }

    // Additional validation for subscription packages
    if ($request->business_plan == 'subscription-base' && $request->package_id == null ) {
        $validator->getMessageBag()->add('package_id', translate('messages.You_must_select_a_package'));
        return back()->withErrors($validator)
                ->withInput();
    }

    // Create vendor - Auto-approved immediately
    $vendor = new Vendor();
    $vendor->f_name = $request->f_name;
    $vendor->l_name = $request->l_name;
    $vendor->email = $request->email;
    $vendor->phone = $request->phone;
    $vendor->password = bcrypt($request->password);
    $vendor->status = 1; // Auto-approve vendor
    $vendor->save();

    // Create store - Auto-approved immediately
    $store = new Store;
    $store->name = $request->name['default'] ?? $request->name;
    $store->phone = $request->phone;
    $store->email = $request->email;
    $store->logo = Helpers::upload('store/', 'png', $request->file('logo'));
    $store->cover_photo = Helpers::upload('store/cover/', 'png', $request->file('cover_photo'));
    $store->address = $request->address['default'] ?? $request->address;
    $store->latitude = $request->latitude;
    $store->longitude = $request->longitude;
    $store->vendor_id = $vendor->id;
    $store->zone_id = $request->zone_id;
    $store->module_id = $request->module_id;
    $store->pickup_zone_id = json_encode($request['pickup_zone_id']?? []);
    $store->tax = $request->tax;
    $store->delivery_time = $request->minimum_delivery_time .'-'. $request->maximum_delivery_time.' '.$request->delivery_time_type;
    $store->status = 1; // Auto-approve store
    $store->store_business_model = 'none';
    $store->save();

    Helpers::add_or_update_translations(request: $request, key_data: 'name', name_field: 'name', model_name: 'Store', data_id: $store->id, data_value: $store->name);
    Helpers::add_or_update_translations(request: $request, key_data: 'address', name_field: 'address', model_name: 'Store', data_id: $store->id, data_value: $store->address);

    try{
        $admin= Admin::where('role_id', 1)->first();
        if($module?->module_type != 'rental' && config('mail.status') && Helpers::get_mail_status('registration_mail_status_store') == '1' &&  Helpers::getNotificationStatusData('store','store_registration','mail_status') ){
            Mail::to($request['email'])->send(new VendorSelfRegistration('pending', $vendor->f_name.' '.$vendor->l_name));
        }
        elseif($module?->module_type == 'rental' && addon_published_status('Rental')&& config('mail.status') && Helpers::get_mail_status('rental_registration_mail_status_provider') == '1' &&  Helpers::getRentalNotificationStatusData('provider','provider_registration','mail_status') ){
            Mail::to($request['email'])->send(new ProviderSelfRegistration('pending', $vendor->f_name.' '.$vendor->l_name));
        }

        if($module?->module_type != 'rental' && config('mail.status') && Helpers::get_mail_status('store_registration_mail_status_admin') == '1' &&  Helpers::getNotificationStatusData('admin','store_self_registration','mail_status') ){
            Mail::to($admin['email'])->send(new StoreRegistration('pending', $vendor->f_name.' '.$vendor->l_name));
        } elseif($module?->module_type == 'rental' && addon_published_status('Rental')&& config('mail.status') && Helpers::get_mail_status('rental_provider_registration_mail_status_admin') == '1' &&  Helpers::getRentalNotificationStatusData('admin','provider_self_registration','mail_status') ){
            Mail::to($admin['email'])->send(new ProviderRegistration('pending', $vendor->f_name.' '.$vendor->l_name));
        }

    }catch(\Exception $ex){
        info($ex->getMessage());
    }

    if(config('module.'.$store->module->module_type)['always_open'])
    {
        StoreLogic::insert_schedule($store->id);
    }

    if (Helpers::subscription_check()) {
        if ($request->business_plan == 'subscription-base' && $request->package_id != null ) {
            $key=['subscription_free_trial_days','subscription_free_trial_type','subscription_free_trial_status'];
            $free_trial_settings=BusinessSetting::whereIn('key', $key)->pluck('value','key');
            $store->package_id = $request->package_id;
            $store->save();

            return view('vendor-views.auth.register-subscription-payment',[
            'package_id'=> $request->package_id,
            'store_id' => $store->id,
            'free_trial_settings'=>$free_trial_settings,
            'payment_methods' => Helpers::getActivePaymentGateways(),
            ]);
        }
        elseif($request->business_plan == 'commission-base' ){
            $store->store_business_model = 'commission';
            $store->save();

            // Auto-login vendor and redirect to completion page
            Auth::guard('vendor')->login($vendor);

            return view('vendor-views.auth.register-complete',[
                'type'=>'commission',
                'store_id' => $store->id,
                'auto_approved' => true
            ]);
        }
        else{
            $admin_commission= BusinessSetting::where('key','admin_commission')->first();
            $business_name= BusinessSetting::where('key','business_name')->first();
            $packages= SubscriptionPackage::where('status',1)->where('module_type', 'all')->get();
            Toastr::error(translate('messages.please_follow_the_steps_properly.'));
            return view('vendor-views.auth.register-step-2',[
                'admin_commission'=> $admin_commission?->value,
                'business_name'=> $business_name?->value,
                'packages'=> $packages,
                'store_id' =>$store->id,
                'type'=>$request->type
                ]);
        }
    } else{
        $store->store_business_model = 'commission';
        $store->save();

        // Auto-login vendor and redirect to completion page
        Auth::guard('vendor')->login($vendor);

        Toastr::success(translate('messages.your_store_registration_is_successful'));
        return view('vendor-views.auth.register-complete',[
            'type'=>'commission',
            'store_id' => $store->id,
            'auto_approved' => true
        ]);
    }

    Toastr::success(translate('messages.application_placed_successfully'));
    return back();
}

    public function get_all_modules(Request $request){
        $module_data = Module::Active()->whereHas('zones', function($query)use ($request){
            $query->where('zone_id', $request->zone_id);
        })->notParcel()
        ->where('modules.module_name', 'like', '%'.$request->q.'%')
        ->limit(8)->get()->map(function($module) {
            return [
                'id' => $module->id,
                'text' => $module->module_name
            ];
        });
        return response()->json($module_data);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function get_modules_type(Request $request): JsonResponse
    {
        $module = Module::find($request->id);
        $packages=null;


        if ($module) {
            $packages= SubscriptionPackage::where('status',1)->where('module_type',$module?->module_type == 'rental' && addon_published_status('Rental') ? 'rental' : 'all')->latest()->get();

            $module = $module->module_type;
            return response()->json([
                'module_type' => $module,
                'view' => view('vendor-views.auth._package_data', compact('packages','module'))->render(),
            ]);
            // return response()->json(['module_type' => $module->module_type, '' => $packages ?? null]);
        }

        return response()->json(['module_type' => '']);
    }


    public function business_plan(Request $request){
        $store=Store::find($request->store_id);

        if ($request->business_plan == 'subscription-base' && $request->package_id != null ) {
            $key=['subscription_free_trial_days','subscription_free_trial_type','subscription_free_trial_status'];
            $free_trial_settings=BusinessSetting::whereIn('key', $key)->pluck('value','key');

            return view('vendor-views.auth.register-subscription-payment',[
            'package_id'=> $request->package_id,
            'store_id' => $request->store_id,
            'free_trial_settings'=>$free_trial_settings,
            'payment_methods' => Helpers::getActivePaymentGateways(),

            ]);
        }
        elseif($request->business_plan == 'commission-base' ){
            $store->store_business_model = 'commission';
            $store->status = 1; // Auto-approve store
            $store->save();

            // Also ensure vendor is approved
            if ($store->vendor) {
                $store->vendor->status = 1;
                $store->vendor->save();

                // Auto-login vendor
                Auth::guard('vendor')->login($store->vendor);
            }

            return view('vendor-views.auth.register-complete',[
                'type'=>'commission',
                'store_id' => $store->id,
                'auto_approved' => true
            ]);
        }
        else{
            $admin_commission= BusinessSetting::where('key','admin_commission')->first();
            $business_name= BusinessSetting::where('key','business_name')->first();
            $packages= SubscriptionPackage::where('status',1)->where('module_type', 'all')->get();
            Toastr::error(translate('messages.please_follow_the_steps_properly.'));
            return view('vendor-views.auth.register-step-2',[
                'admin_commission'=> $admin_commission?->value,
                'business_name'=> $business_name?->value,
                'packages'=> $packages,
                'store_id' => $request->store_id,
                'type'=>$request->type
                ]);
        }

    }

    public function payment(Request $request){
        $request->validate([
            'package_id' => 'required',
            'store_id' => 'required',
            'payment' => 'required'
        ]);

        $store= Store::Where('id',$request->store_id)->first(['id','vendor_id']);
        $package = SubscriptionPackage::withoutGlobalScope('translate')->find($request->package_id);

        if(!in_array($request->payment,['free_trial'])){
            $url= route('restaurant.final_step',['store_id' => $store->id?? null]);
            return redirect()->away(Helpers::subscriptionPayment(store_id:$store->id,package_id:$package->id,payment_gateway:$request->payment,payment_platform:'web',url:$url,type: 'new_join'));
        }
        if($request->payment == 'free_trial'){
            $plan_data=   Helpers::subscription_plan_chosen(store_id:$store->id,package_id:$package->id,payment_method:'free_trial',discount:0,reference:'free_trial',type: 'new_join');
        }
        $plan_data != false ?  Toastr::success( translate('Successfully_Subscribed.')) : Toastr::error( translate('Something_went_wrong!.'));
        return to_route('restaurant.final_step');
    }

public function back(Request $request){
    $admin_commission= BusinessSetting::where('key','admin_commission')->first();
    $business_name= BusinessSetting::where('key','business_name')->first();
    $store=Store::where('id',$request->store_id)->with('module')->first();
    $module=$store?->module?->module_type ?? 'all';
    $packages= SubscriptionPackage::where('status',1)->where('module_type',  $module == 'rental' ? 'rental' : 'all')->get();
    return view('vendor-views.auth.register-step-2',[
        'admin_commission'=> $admin_commission?->value,
        'business_name'=> $business_name?->value,
        'packages'=> $packages,
        'store_id' => $request->store_id,
        'module' => $module
        ]);
}


public function final_step(Request $request){
    $store_id = null;
    $payment_status = null;
    $auto_approved = false;

    if($request?->store_id && is_string($request?->store_id)){
        $data = explode('?', $request?->store_id);
        $store_id = $data[0];
        $payment_status = isset($data[1]) && $data[1] != 'flag=success' ? 'fail' : 'success';

        // If payment successful, auto-login vendor and set auto_approved flag
        if ($payment_status === 'success' && $store_id) {
            $store = Store::with('vendor')->find($store_id);
            if ($store && $store->vendor) {
                // Auto-login vendor
                Auth::guard('vendor')->login($store->vendor);
                $auto_approved = true;
            }
        }
    }

    return view('vendor-views.auth.register-complete', [
        'store_id' => $store_id,
        'payment_status' => $payment_status,
        'auto_approved' => $auto_approved
    ]);
}
        
        
        public function getCartDetails(Request $request): JsonResponse
          {
              try {
                  $vendor = auth('vendor')->user();
                  $store = Store::where('vendor_id', $vendor->id)->first();
                  
                  if (!$store) {
                      return response()->json([
                          'success' => false,
                          'message' => translate('messages.store_not_found')
                      ], 404);
                  }

                  $user = User::find($request->user_id);
                  
                  if (!$user) {
                      return response()->json([
                          'success' => false,
                          'message' => translate('messages.user_not_found')
                      ], 404);
                  }

                  $carts = Cart::with(['item' => function($query) {
                      $query->select('id', 'name', 'image', 'price', 'store_id');
                  }])
                  ->where('user_id', $request->user_id)
                  ->whereHas('item', function($query) use($store) {
                      $query->where('store_id', $store->id);
                  })
                  ->orderBy('created_at', 'desc')
                  ->get();

                  $html = view('vendor-views.order.partials._cart-details', compact('carts', 'user'))->render();
                  
                  return response()->json([
                      'success' => true,
                      'html' => $html,
                      'cart_count' => $carts->count(),
                      'total' => $carts->sum(function($cart) {
                          return $cart->price * $cart->quantity;
                      })
                  ]);

              } catch (\Exception $e) {
                  return response()->json([
                      'success' => false,
                      'message' => translate('messages.something_went_wrong'),
                      'error' => $e->getMessage()
                  ], 500);
              }
          }

          /**
           * Refresh carts data for AJAX updates
           *
           * @param Request $request
           * @return JsonResponse
           */
          public function refreshCarts(Request $request): JsonResponse
          {
              try {
                  $vendor = auth('vendor')->user();
                  $store = Store::where('vendor_id', $vendor->id)->first();
                  
                  if (!$store) {
                      return response()->json([
                          'success' => false,
                          'message' => translate('messages.store_not_found')
                      ], 404);
                  }

                  // Get latest carts from last 7 days
                  $latest_carts = Cart::with(['item'])
                      ->whereHas('item', function($query) use($store) {
                          $query->where('store_id', $store->id);
                      })
                      ->where('created_at', '>=', now()->subDays(7))
                      ->orderBy('updated_at', 'desc')
                      ->get()
                      ->groupBy('user_id');

                  // Get unique users
                  $userIds = $latest_carts->keys();
                  $users = User::whereIn('id', $userIds)->get()->keyBy('id');

                  // Get items
                  $itemIds = $latest_carts->flatten()->pluck('item_id')->unique();
                  $items = Item::whereIn('id', $itemIds)
                      ->select('id', 'name', 'image', 'price', 'store_id')
                      ->get()
                      ->keyBy('id');

                  $html = view('vendor-views.order.partials._carts-grid', compact('latest_carts', 'users', 'items'))->render();
                  
                  return response()->json([
                      'success' => true,
                      'html' => $html,
                      'total_carts' => $latest_carts->count(),
                      'total_value' => $latest_carts->flatten()->sum(function($cart) {
                          return $cart->price * $cart->quantity;
                      })
                  ]);

              } catch (\Exception $e) {
                  return response()->json([
                      'success' => false,
                      'message' => translate('messages.something_went_wrong'),
                      'error' => $e->getMessage()
                  ], 500);
              }
          }

          /**
           * Get real-time cart statistics
           *
           * @return JsonResponse
           */
          public function getCartStats(): JsonResponse
          {
              try {
                  $vendor = auth('vendor')->user();
                  $store = Store::where('vendor_id', $vendor->id)->first();
                  
                  if (!$store) {
                      return response()->json([
                          'success' => false,
                          'message' => translate('messages.store_not_found')
                      ], 404);
                  }

                  $carts = Cart::whereHas('item', function($query) use($store) {
                      $query->where('store_id', $store->id);
                  })
                  ->where('created_at', '>=', now()->subDays(7))
                  ->get();

                  $stats = [
                      'total_carts' => $carts->groupBy('user_id')->count(),
                      'total_items' => $carts->sum('quantity'),
                      'total_value' => $carts->sum(function($cart) {
                          return $cart->price * $cart->quantity;
                      }),
                      'recent_carts' => $carts->where('created_at', '>=', now()->subMinutes(30))
                          ->groupBy('user_id')
                          ->count(),
                      'hot_items' => $carts->groupBy('item_id')
                          ->map(function($group) {
                              return [
                                  'item_id' => $group->first()->item_id,
                                  'count' => $group->sum('quantity')
                              ];
                          })
                          ->sortByDesc('count')
                          ->take(5)
                          ->values()
                  ];

                  return response()->json([
                      'success' => true,
                      'stats' => $stats
                  ]);

              } catch (\Exception $e) {
                  return response()->json([
                      'success' => false,
                      'message' => translate('messages.something_went_wrong'),
                      'error' => $e->getMessage()
                  ], 500);
              }
          }

          /**
           * Clear abandoned carts (optional cleanup)
           *
           * @param Request $request
           * @return JsonResponse
           */
          public function clearAbandonedCarts(Request $request): JsonResponse
          {
              try {
                  $vendor = auth('vendor')->user();
                  $store = Store::where('vendor_id', $vendor->id)->first();
                  
                  if (!$store) {
                      return response()->json([
                          'success' => false,
                          'message' => translate('messages.store_not_found')
                      ], 404);
                  }

                  // Clear carts older than specified days (default 30)
                  $days = $request->days ?? 30;
                  
                  $deleted = Cart::whereHas('item', function($query) use($store) {
                      $query->where('store_id', $store->id);
                  })
                  ->where('created_at', '<', now()->subDays($days))
                  ->delete();

                  return response()->json([
                      'success' => true,
                      'message' => translate('messages.abandoned_carts_cleared'),
                      'deleted_count' => $deleted
                  ]);

              } catch (\Exception $e) {
                  return response()->json([
                      'success' => false,
                      'message' => translate('messages.something_went_wrong'),
                      'error' => $e->getMessage()
                  ], 500);
              }
          }

          /**
           * Export carts data to CSV
           *
           * @return \Illuminate\Http\Response
           */
          public function exportCarts(Request $request)
          {
              try {
                  $vendor = auth('vendor')->user();
                  $store = Store::where('vendor_id', $vendor->id)->first();
                  
                  if (!$store) {
                      Toastr::error(translate('messages.store_not_found'));
                      return back();
                  }

                  $carts = Cart::with(['user', 'item'])
                      ->whereHas('item', function($query) use($store) {
                          $query->where('store_id', $store->id);
                      })
                      ->where('created_at', '>=', now()->subDays(30))
                      ->orderBy('created_at', 'desc')
                      ->get();

                  $filename = 'carts_' . $store->name . '_' . date('Y-m-d') . '.csv';
                  
                  $headers = [
                      'Content-Type' => 'text/csv',
                      'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                  ];

                  $callback = function() use ($carts) {
                      $file = fopen('php://output', 'w');
                      
                      // Headers
                      fputcsv($file, [
                          'Customer Name',
                          'Phone',
                          'Email',
                          'Item',
                          'Quantity',
                          'Price',
                          'Total',
                          'Created At'
                      ]);

                      // Data
                      foreach ($carts as $cart) {
                          fputcsv($file, [
                              ($cart->user->f_name ?? '') . ' ' . ($cart->user->l_name ?? ''),
                              $cart->user->phone ?? 'N/A',
                              $cart->user->email ?? 'N/A',
                              $cart->item->name ?? 'Unknown',
                              $cart->quantity,
                              Helpers::format_currency($cart->price),
                              Helpers::format_currency($cart->price * $cart->quantity),
                              $cart->created_at->format('Y-m-d H:i:s')
                          ]);
                      }

                      fclose($file);
                  };

                  return response()->stream($callback, 200, $headers);

              } catch (\Exception $e) {
                  Toastr::error(translate('messages.something_went_wrong'));
                  return back();
              }
          }

    /**
     * Send OTP to phone number for vendor registration
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function sendOtp(Request $request): JsonResponse
    {
        // Log incoming request for debugging
        info('OTP Request received', ['phone' => $request->phone, 'all' => $request->all()]);

        $validator = Validator::make($request->all(), [
            'phone' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:10',
        ], [
            'phone.required' => 'Phone number is required',
            'phone.regex' => 'Please provide a valid phone number',
            'phone.min' => 'Phone number must be at least 10 digits',
        ]);

        if ($validator->fails()) {
            info('OTP Validation failed', ['errors' => $validator->errors()->toArray()]);
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        // Clean phone number - remove spaces, dashes, parentheses
        $phone = preg_replace('/[\s\-\(\)]/', '', $request->phone);

        // Check if phone already exists in vendors table
        $existingVendor = Vendor::where('phone', $phone)->first();
        if ($existingVendor) {
            return response()->json([
                'success' => false,
                'message' => 'This phone number is already registered'
            ], 422);
        }

        // Check for rate limiting (60 seconds between OTP requests)
        $existingOtp = DB::table('phone_verifications')->where('phone', $phone)->first();
        if ($existingOtp && $existingOtp->created_at) {
            $timeDiff = Carbon::parse($existingOtp->created_at)->diffInSeconds(now());
            if ($timeDiff < 60) {
                $waitTime = 60 - $timeDiff;
                return response()->json([
                    'success' => false,
                    'message' => 'Please wait ' . $waitTime . ' seconds before requesting a new OTP',
                    'wait_time' => $waitTime
                ], 429);
            }
        }

        // Generate 6-digit OTP
        $otp = rand(100000, 999999);

        // Test phone numbers for development - use fixed OTP 123456
        $testPhones = ['7006059519', '+917006059519', '917006059519'];
        $isTestPhone = in_array(preg_replace('/^\+/', '', $phone), $testPhones) ||
                       in_array($phone, $testPhones);

        if ($isTestPhone) {
            $otp = 123456; // Fixed OTP for test numbers
        }

        try {
            // Store OTP in phone_verifications table
            DB::table('phone_verifications')->updateOrInsert(
                ['phone' => $phone],
                [
                    'token' => $otp,
                    'otp_hit_count' => 0,
                    'is_temp_blocked' => 0,
                    'temp_block_time' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            // For test phones, skip actual SMS sending
            if ($isTestPhone) {
                info('Test OTP for phone ' . $phone . ': ' . $otp);
                return response()->json([
                    'success' => true,
                    'message' => 'OTP sent successfully (Test: ' . $otp . ')',
                    'phone' => $phone
                ]);
            }

            // Send OTP via SMS gateway (2Factor.in or configured gateway)
            $smsResponse = SMS_module::send($phone, $otp);

            // Log the SMS response for debugging
            info('SMS OTP Response for phone ' . $phone . ': ' . $smsResponse);

            if ($smsResponse === 'success') {
                return response()->json([
                    'success' => true,
                    'message' => 'OTP sent successfully',
                    'phone' => $phone
                ]);
            }

            // Provide more specific error messages
            $errorMessage = 'Failed to send OTP. Please try again.';
            if ($smsResponse === 'not_found') {
                $errorMessage = 'SMS gateway not configured. Please contact support.';
            } elseif ($smsResponse === 'error') {
                $errorMessage = 'SMS service error. Please try again later.';
            }

            return response()->json([
                'success' => false,
                'message' => $errorMessage,
                'debug' => config('app.debug') ? $smsResponse : null
            ], 500);

        } catch (\Exception $e) {
            info('OTP Send Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred. Please try again.',
                'debug' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Verify OTP for vendor registration
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function verifyOtp(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:10',
            'otp' => 'required|digits:6',
        ], [
            'phone.required' => 'Phone number is required',
            'otp.required' => 'OTP is required',
            'otp.digits' => 'OTP must be 6 digits',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        // Clean phone number - remove spaces, dashes, parentheses
        $phone = preg_replace('/[\s\-\(\)]/', '', $request->phone);
        $otp = $request->otp;

        \Log::info('Verify OTP request for phone: ' . $phone . ', OTP: ' . $otp);

        // Rate limiting - max 5 attempts
        $maxOtpHit = 5;
        $tempBlockTime = 600; // 10 minutes in seconds

        $verificationData = DB::table('phone_verifications')->where('phone', $phone)->first();

        if (!$verificationData) {
            \Log::info('No verification data found for phone: ' . $phone);
            return response()->json([
                'success' => false,
                'message' => 'Please request an OTP first'
            ], 404);
        }

        \Log::info('Verification data found - stored OTP: ' . $verificationData->token);

        // Check if temporarily blocked
        if ($verificationData->is_temp_blocked == 1) {
            if ($verificationData->temp_block_time && Carbon::parse($verificationData->temp_block_time)->diffInSeconds(now()) < $tempBlockTime) {
                $remainingTime = $tempBlockTime - Carbon::parse($verificationData->temp_block_time)->diffInSeconds(now());
                return response()->json([
                    'success' => false,
                    'message' => 'Too many failed attempts. Please try again after ' . ceil($remainingTime / 60) . ' minutes',
                    'blocked' => true,
                    'remaining_time' => $remainingTime
                ], 429);
            }

            // Reset block if time has passed
            DB::table('phone_verifications')->where('phone', $phone)->update([
                'otp_hit_count' => 0,
                'is_temp_blocked' => 0,
                'temp_block_time' => null,
                'updated_at' => now(),
            ]);
        }

        // Check OTP hit count
        if ($verificationData->otp_hit_count >= $maxOtpHit) {
            DB::table('phone_verifications')->where('phone', $phone)->update([
                'is_temp_blocked' => 1,
                'temp_block_time' => now(),
                'updated_at' => now(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Too many failed attempts. Please try again later.',
                'blocked' => true
            ], 429);
        }

        // Verify OTP
        if ($verificationData->token == $otp) {
            // OTP verified successfully - delete the verification record
            DB::table('phone_verifications')->where('phone', $phone)->delete();

            // Store verified phone in session for registration flow
            Session::put('verified_phone', $phone);
            Session::put('phone_verified_at', now());

            \Log::info('OTP verified successfully for phone: ' . $phone);

            return response()->json([
                'success' => true,
                'message' => 'Phone number verified successfully',
                'phone' => $phone
            ]);
        }

        // Increment hit count on failed attempt
        DB::table('phone_verifications')->where('phone', $phone)->update([
            'otp_hit_count' => $verificationData->otp_hit_count + 1,
            'updated_at' => now(),
        ]);

        $remainingAttempts = $maxOtpHit - ($verificationData->otp_hit_count + 1);

        \Log::info('OTP verification failed for phone: ' . $phone . ', remaining attempts: ' . $remainingAttempts);

        return response()->json([
            'success' => false,
            'message' => 'Invalid OTP. ' . $remainingAttempts . ' attempts remaining.',
            'remaining_attempts' => $remainingAttempts
        ], 400);
    }

    /**
     * Resend OTP for vendor registration
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function resendOtp(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:10',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        // Clean phone number - remove spaces, dashes, parentheses
        $phone = preg_replace('/[\s\-\(\)]/', '', $request->phone);

        \Log::info('Resend OTP request for phone: ' . $phone);

        // Check for rate limiting (60 seconds between OTP requests)
        $existingOtp = DB::table('phone_verifications')->where('phone', $phone)->first();
        if ($existingOtp && $existingOtp->updated_at) {
            $timeDiff = Carbon::parse($existingOtp->updated_at)->diffInSeconds(now());
            if ($timeDiff < 60) {
                $waitTime = 60 - $timeDiff;
                return response()->json([
                    'success' => false,
                    'message' => 'Please wait ' . $waitTime . ' seconds before requesting a new OTP',
                    'wait_time' => $waitTime
                ], 429);
            }
        }

        // Test phone numbers for development - use fixed OTP 123456
        $testPhones = ['7006059519', '+917006059519', '917006059519'];
        $cleanPhoneForTest = preg_replace('/^\+/', '', $phone);
        $isTestPhone = in_array($cleanPhoneForTest, $testPhones) || in_array($phone, $testPhones);

        // Generate new 6-digit OTP (or use fixed OTP for test phones)
        if ($isTestPhone) {
            $otp = 123456;
            \Log::info('Test phone detected, using fixed OTP: 123456');
        } else {
            $otp = rand(100000, 999999);
        }

        // Update OTP in phone_verifications table
        DB::table('phone_verifications')->updateOrInsert(
            ['phone' => $phone],
            [
                'token' => $otp,
                'otp_hit_count' => 0,
                'is_temp_blocked' => 0,
                'temp_block_time' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // For test phones, skip actual SMS sending
        if ($isTestPhone) {
            return response()->json([
                'success' => true,
                'message' => 'OTP resent successfully (Test mode: use 123456)',
                'phone' => $phone
            ]);
        }

        // Send OTP via SMS gateway
        try {
            $smsResponse = SMS_module::send($phone, $otp);
            \Log::info('Resend SMS response: ' . json_encode($smsResponse));

            if ($smsResponse === 'success') {
                return response()->json([
                    'success' => true,
                    'message' => 'OTP resent successfully',
                    'phone' => $phone
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to send OTP. Please try again.'
            ], 500);
        } catch (\Exception $e) {
            \Log::error('Resend OTP Exception: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'SMS service error: ' . $e->getMessage()
            ], 500);
        }
    }

}
