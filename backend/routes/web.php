<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaytmController;
use App\Http\Controllers\LiqPayController;
use App\Http\Controllers\PaymobController;
use App\Http\Controllers\PaytabsController;
use App\Http\Controllers\FirebaseController;
use App\Http\Controllers\PaystackController;
use App\Http\Controllers\RazorPayController;
use App\Http\Controllers\SenangPayController;
use App\Http\Controllers\MercadoPagoController;
use App\Http\Controllers\BkashPaymentController;
use App\Http\Controllers\FlutterwaveV3Controller;
use App\Http\Controllers\PaypalPaymentController;
use App\Http\Controllers\StripePaymentController;
use App\Http\Controllers\SslCommerzPaymentController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
Route::get('/download', function () {
    return view('download');
});

Route::post('/admin/conversations/customer-message', [App\Http\Controllers\Admin\ConversationController::class, 'storeCustomerMessage'])->name('admin.conversations.customer-message');
Route::post('search-by-barcode', [App\Http\Controllers\Admin\ItemController::class, 'searchByBarcode'])->name('admin.item.regular_items.search_by_barcode');
Route::post('/subscribeToTopic', [FirebaseController::class, 'subscribeToTopic']);
Route::get('/', 'HomeController@index')->name('home');

// Public Chatbot API
Route::post('/chatbot/response', [App\Http\Controllers\Admin\ChatbotController::class, 'getPublicResponse'])->name('chatbot.api.response');
Route::get('lang/{locale}', 'HomeController@lang')->name('lang');
Route::get('terms-and-conditions', 'HomeController@terms_and_conditions')->name('terms-and-conditions');
Route::get('about-us', 'HomeController@about_us')->name('about-us');
Route::get('contact-us', 'HomeController@contact_us')->name('contact-us');
Route::post('send-message', 'HomeController@send_message')->name('send-message');
Route::get('privacy-policy', 'HomeController@privacy_policy')->name('privacy-policy');
Route::get('cancelation', 'HomeController@cancelation')->name('cancelation');
Route::get('refund', 'HomeController@refund_policy')->name('refund');
Route::get('shipping-policy', 'HomeController@shipping_policy')->name('shipping-policy');
Route::post('newsletter/subscribe', 'NewsletterController@newsLetterSubscribe')->name('newsletter.subscribe');
Route::get('subscription-invoice/{id}', 'HomeController@subscription_invoice')->name('subscription_invoice');
Route::get('order-invoice/{id}', 'HomeController@order_invoice')->name('order_invoice');

// Public invoice view (signed URL, no admin auth required — used for QR code scanning on devices)
Route::get('order/public-invoice/{id}', function ($id) {
    $order = \App\Models\Order::withoutGlobalScope(\App\Scopes\ZoneScope::class)
        ->with([
            'details',
            'store' => fn($q) => $q->withCount('orders'),
            'details.item' => fn($q) => $q->withoutGlobalScope(\App\Scopes\StoreScope::class),
            'details.campaign' => fn($q) => $q->withoutGlobalScope(\App\Scopes\StoreScope::class),
        ])->find($id);
    if (!$order) abort(404);
    return view('order-invoice-public', compact('order'));
})->name('public.order.invoice')->middleware('signed');

// Account Deletion Routes (Public - for App Store/Play Store compliance)
Route::group(['prefix' => 'account-deletion'], function () {
    // Customer Account Deletion
    Route::get('customer', 'AccountDeletionController@customerDeletionPage')->name('account-deletion.customer');
    Route::post('customer/request-otp', 'AccountDeletionController@customerRequestOTP')->name('account-deletion.customer.request-otp');
    Route::post('customer/delete', 'AccountDeletionController@customerDeleteAccount')->name('account-deletion.customer.delete');

    // Store/Vendor Account Deletion
    Route::get('store', 'AccountDeletionController@storeDeletionPage')->name('account-deletion.store');
    Route::post('store/request-otp', 'AccountDeletionController@storeRequestOTP')->name('account-deletion.store.request-otp');
    Route::post('store/delete', 'AccountDeletionController@storeDeleteAccount')->name('account-deletion.store.delete');

    // Delivery Man Account Deletion
    Route::get('delivery-man', 'AccountDeletionController@deliveryManDeletionPage')->name('account-deletion.delivery-man');
    Route::post('delivery-man/request-otp', 'AccountDeletionController@deliveryManRequestOTP')->name('account-deletion.delivery-man.request-otp');
    Route::post('delivery-man/delete', 'AccountDeletionController@deliveryManDeleteAccount')->name('account-deletion.delivery-man.delete');
});

Route::get('login/{tab}', 'LoginController@login')->name('login');
Route::post('external-login-from-drivemond', 'LoginController@externalLoginFromDrivemond');
Route::post('login_submit', 'LoginController@submit')->name('login_post')->middleware('actch');
Route::get('logout', 'LoginController@logout')->name('logout');
Route::get('/reload-captcha', 'LoginController@reloadCaptcha')->name('reload-captcha');
Route::get('/reset-password', 'LoginController@reset_password_request')->name('reset-password');
Route::post('/vendor-reset-password', 'LoginController@vendor_reset_password_request')->name('vendor-reset-password');
Route::get('/password-reset', 'LoginController@reset_password')->name('change-password');
Route::post('verify-otp', 'LoginController@verify_token')->name('verify-otp');
Route::post('reset-password-submit', 'LoginController@reset_password_submit')->name('reset-password-submit');
Route::get('otp-resent', 'LoginController@otp_resent')->name('otp_resent');

Route::get('authentication-failed', function () {
    $errors = [];
    array_push($errors, ['code' => 'auth-001', 'message' => 'Unauthenticated.']);
    return response()->json([
        'errors' => $errors,
    ], 401);
})->name('authentication-failed');

Route::group(['prefix' => 'payment-mobile'], function () {
    Route::get('/', 'PaymentController@payment')->name('payment-mobile');
    Route::get('set-payment-method/{name}', 'PaymentController@set_payment_method')->name('set-payment-method');
});

Route::get('payment-success', 'PaymentController@success')->name('payment-success');
Route::get('payment-fail', 'PaymentController@fail')->name('payment-fail');
Route::get('payment-cancel', 'PaymentController@cancel')->name('payment-cancel');

$is_published = 0;
try {
$full_data = include('Modules/Gateways/Addon/info.php');
$is_published = $full_data['is_published'] == 1 ? 1 : 0;
} catch (\Exception $exception) {}

if (!$is_published) {
    Route::group(['prefix' => 'payment'], function () {

        //SSLCOMMERZ
        Route::group(['prefix' => 'sslcommerz', 'as' => 'sslcommerz.'], function () {
            Route::get('pay', [SslCommerzPaymentController::class, 'index'])->name('pay');
            Route::post('success', [SslCommerzPaymentController::class, 'success'])
                ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
            Route::post('failed', [SslCommerzPaymentController::class, 'failed'])
                ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
            Route::post('canceled', [SslCommerzPaymentController::class, 'canceled'])
                ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
        });

        //STRIPE
        Route::group(['prefix' => 'stripe', 'as' => 'stripe.'], function () {
            Route::get('pay', [StripePaymentController::class, 'index'])->name('pay');
            Route::get('token', [StripePaymentController::class, 'payment_process_3d'])->name('token');
            Route::get('success', [StripePaymentController::class, 'success'])->name('success');
            Route::get('canceled', [StripePaymentController::class, 'canceled'])
                ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
        });
//RAZOR-PAY
        Route::group(['prefix' => 'razor-pay', 'as' => 'razor-pay.'], function () {
            Route::get('pay', [RazorPayController::class, 'index'])->name('pay');
            Route::get('/', [RazorPayController::class, 'index'])->name('index');
            
            Route::post('payment', [RazorPayController::class, 'payment'])->name('payment')
                ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
            
            // Accept both GET and POST for callback - this fixes your error!
            Route::match(['get', 'post'], 'callback', [RazorPayController::class, 'callback'])->name('callback')
                ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
            
            // Add missing success and cancel routes
            Route::get('success', [RazorPayController::class, 'success'])->name('success');
            Route::get('cancel', [RazorPayController::class, 'cancel'])->name('cancel');
            
            // Webhook route for server-to-server notifications (reliable payment confirmation)
            Route::post('webhook', [RazorPayController::class, 'webhook'])->name('webhook')
                ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

            // Status check endpoint (for AJAX polling)
            Route::get('check-status', [RazorPayController::class, 'checkStatus'])->name('check-status');
        });
//        //RAZOR-PAY
//        Route::group(['prefix' => 'razor-pay', 'as' => 'razor-pay.'], function () {
//            Route::get('pay', [RazorPayController::class, 'index']);
//            Route::post('payment', [RazorPayController::class, 'payment'])->name('payment')
//                ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
//            Route::post('callback', [RazorPayController::class, 'callback'])->name('callback')
//                ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
//        });

        //PAYPAL
        Route::group(['prefix' => 'paypal', 'as' => 'paypal.'], function () {
            Route::get('pay', [PaypalPaymentController::class, 'payment']);
            Route::any('success', [PaypalPaymentController::class, 'success'])->name('success')
                ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);;
            Route::any('cancel', [PaypalPaymentController::class, 'cancel'])->name('cancel')
                ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);;
        });

        //SENANG-PAY
        Route::group(['prefix' => 'senang-pay', 'as' => 'senang-pay.'], function () {
            Route::get('pay', [SenangPayController::class, 'index']);
            Route::any('callback', [SenangPayController::class, 'return_senang_pay']);
        });

        //PAYTM
        Route::group(['prefix' => 'paytm', 'as' => 'paytm.'], function () {
            Route::get('pay', [PaytmController::class, 'payment']);
            Route::any('response', [PaytmController::class, 'callback'])->name('response')
            ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
        });

        //FLUTTERWAVE
        Route::group(['prefix' => 'flutterwave-v3', 'as' => 'flutterwave-v3.'], function () {
            Route::get('pay', [FlutterwaveV3Controller::class, 'initialize'])->name('pay');
            Route::get('callback', [FlutterwaveV3Controller::class, 'callback'])->name('callback');
        });

        //PAYSTACK
        Route::group(['prefix' => 'paystack', 'as' => 'paystack.'], function () {
            Route::get('pay', [PaystackController::class, 'index'])->name('pay');
            Route::post('payment', [PaystackController::class, 'redirectToGateway'])->name('payment');
            Route::get('callback', [PaystackController::class, 'handleGatewayCallback'])->name('callback');
        });

        //BKASH

        Route::group(['prefix' => 'bkash', 'as' => 'bkash.'], function () {
            // Payment Routes for bKash
            Route::get('make-payment', [BkashPaymentController::class, 'make_tokenize_payment'])->name('make-payment');
            Route::any('callback', [BkashPaymentController::class, 'callback'])->name('callback');

            // Refund Routes for bKash
            // Route::get('refund', 'BkashRefundController@index')->name('bkash-refund');
            // Route::post('refund', 'BkashRefundController@refund')->name('bkash-refund');
        });

        //Liqpay
        Route::group(['prefix' => 'liqpay', 'as' => 'liqpay.'], function () {
            Route::get('payment', [LiqPayController::class, 'payment'])->name('payment');
            Route::any('callback', [LiqPayController::class, 'callback'])->name('callback');
        });

        //MERCADOPAGO

        Route::group(['prefix' => 'mercadopago', 'as' => 'mercadopago.'], function () {
            Route::get('pay', [MercadoPagoController::class, 'index'])->name('index');
            Route::any('make-payment', [MercadoPagoController::class, 'make_payment'])->name('make_payment')->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
            Route::get('success', [MercadoPagoController::class, 'success'])->name('success');
            Route::get('failed', [MercadoPagoController::class, 'failed'])->name('failed');
        });

        //PAYMOB
        Route::group(['prefix' => 'paymob', 'as' => 'paymob.'], function () {
            Route::any('pay', [PaymobController::class, 'credit'])->name('pay');
            Route::any('callback', [PaymobController::class, 'callback'])->name('callback');
        });

        //PAYTABS
        Route::group(['prefix' => 'paytabs', 'as' => 'paytabs.'], function () {
            Route::any('pay', [PaytabsController::class, 'payment'])->name('pay');
            Route::any('callback', [PaytabsController::class, 'callback'])->name('callback');
            Route::any('response', [PaytabsController::class, 'response'])->name('response');
        });
    });
}


Route::get('/test', function () {
    dd('Hello tester');
});

Route::get('module-test', function () {
});

//Restaurant Registration
Route::group(['prefix' => 'store', 'as' => 'restaurant.'], function () {
    Route::get('apply', 'VendorController@create')->name('create');
    Route::post('apply', 'VendorController@store')->name('store');
    Route::get('get-all-modules', 'VendorController@get_all_modules')->name('get-all-modules');
    Route::get('get-module-type', 'VendorController@get_modules_type')->name('get-module-type');
    Route::get('back', 'VendorController@back')->name('back');
    Route::post('business-plan', 'VendorController@business_plan')->name('business_plan');
    Route::post('payment', 'VendorController@payment')->name('payment');
    Route::get('final-step', 'VendorController@final_step')->name('final_step');

    // OTP verification routes for phone-based registration
    Route::post('send-otp', 'VendorController@sendOtp')->name('send-otp');
    Route::post('verify-otp', 'VendorController@verifyOtp')->name('verify-otp');
    Route::post('resend-otp', 'VendorController@resendOtp')->name('resend-otp');
});

//Deliveryman Registration
Route::group(['prefix' => 'deliveryman', 'as' => 'deliveryman.'], function () {
    Route::get('apply', 'DeliveryManController@create')->name('create');
    Route::post('apply', 'DeliveryManController@store')->name('store');
    Route::post('send-otp', 'DeliveryManController@sendOtp')->name('send-otp');
    Route::post('verify-otp', 'DeliveryManController@verifyOtp')->name('verify-otp');
    Route::post('resend-otp', 'DeliveryManController@resendOtp')->name('resend-otp');
});

// Employee Application Routes (Public - No Authentication Required)
Route::group(['prefix' => 'employee', 'as' => 'employee.'], function () {
    // Registration selection
    Route::get('register', [App\Http\Controllers\EmployeeApplicationController::class, 'selectType'])
        ->name('register');

    // Admin employee registration
    Route::get('register/admin', [App\Http\Controllers\EmployeeApplicationController::class, 'showAdminRegistration'])
        ->name('register.admin');
    Route::post('register/admin', [App\Http\Controllers\EmployeeApplicationController::class, 'submitAdminApplication'])
        ->name('register.admin.submit');

    // Vendor employee registration
    Route::get('register/vendor', [App\Http\Controllers\EmployeeApplicationController::class, 'showVendorRegistration'])
        ->name('register.vendor');
    Route::post('register/vendor', [App\Http\Controllers\EmployeeApplicationController::class, 'submitVendorApplication'])
        ->name('register.vendor.submit');

    // Application status checking
    Route::get('application/status', [App\Http\Controllers\EmployeeApplicationController::class, 'checkStatus'])
        ->name('application.check');
    Route::get('application/status/{id}', [App\Http\Controllers\EmployeeApplicationController::class, 'showStatus'])
        ->name('application.status');

    // Terms and Conditions page
    Route::get('terms-and-conditions', function () {
        return view('employee-application.terms-and-conditions');
    })->name('terms');
});

// Temporary cache clear - REMOVE AFTER USE
Route::get('/clear-cache-websocket-temp', function() {
    \Illuminate\Support\Facades\Cache::forget('business_settings_config_keys');
    \Illuminate\Support\Facades\Artisan::call('cache:clear');
    return 'Cache cleared! Now delete this route.';
});

// 🧪 Test Item Pickup Broadcasting - REMOVE AFTER TESTING
Route::get('/test-pickup-broadcast/{orderId}/{orderDetailId}', function($orderId, $orderDetailId) {
    try {
        event(new \App\Events\ItemPickupUpdated(
            $orderId,
            $orderDetailId,
            true,
            now()
        ));

        return response()->json([
            'success' => true,
            'message' => 'Pickup event broadcasted!',
            'order_id' => $orderId,
            'order_detail_id' => $orderDetailId,
            'instructions' => 'Check the order view page - badge should appear instantly!'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
});
