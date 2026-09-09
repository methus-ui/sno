<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\DeliveryQRPaymentController;
use App\Http\Controllers\Api\V1\RazorpayQRWebhookController;

/*
|--------------------------------------------------------------------------
| Delivery QR Payment API Routes
|--------------------------------------------------------------------------
|
| These routes handle Razorpay QR code generation and payment tracking
| for delivery confirmation payments.
|
| Base URL: /api/v1/delivery/qr-payment
|
*/

// Public webhook endpoint (no authentication required)
Route::post('/webhooks/razorpay-qr', [RazorpayQRWebhookController::class, 'handleWebhook'])
    ->name('webhooks.razorpay-qr');

// Delivery Man QR Payment Routes (requires authentication)
Route::group([
    'prefix' => 'api/v1/delivery-man/qr-payment',
    'middleware' => ['dm.api'] // Delivery man authentication middleware
], function () {

    // Generate QR code for delivery payment
    Route::post('/generate', [DeliveryQRPaymentController::class, 'generateQR'])
        ->name('delivery.qr.generate');

    // Check QR payment status
    Route::get('/status/{qr_payment_id}', [DeliveryQRPaymentController::class, 'checkStatus'])
        ->name('delivery.qr.status');

    // Get QR payment details
    Route::get('/{qr_payment_id}', [DeliveryQRPaymentController::class, 'getDetails'])
        ->name('delivery.qr.details');

    // Cancel QR payment
    Route::post('/cancel/{qr_payment_id}', [DeliveryQRPaymentController::class, 'cancelQR'])
        ->name('delivery.qr.cancel');

    // Get all QR payments for authenticated delivery man
    Route::get('/my-qr-payments', [DeliveryQRPaymentController::class, 'getMyQRPayments'])
        ->name('delivery.qr.my-payments');
});

/*
|--------------------------------------------------------------------------
| Installation Instructions
|--------------------------------------------------------------------------
|
| Add this line to your bootstrap/app.php or main routes file:
|
| require __DIR__.'/../routes/delivery_qr_api.php';
|
| Or if using Laravel 11+, add to bootstrap/app.php:
|
| ->withRouting(
|     web: __DIR__.'/../routes/web.php',
|     api: __DIR__.'/../routes/api.php',
|     commands: __DIR__.'/../routes/console.php',
|     health: '/up',
|     then: function () {
|         require __DIR__.'/../routes/delivery_qr_api.php';
|     }
| )
|
*/
