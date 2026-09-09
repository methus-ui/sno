<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V2\BargainingController;
use App\Http\Controllers\Api\V2\Vendor\BargainingController as VendorBargainingController;

/*
|--------------------------------------------------------------------------
| Bargaining Mode API Routes (V2)
|--------------------------------------------------------------------------
|
| Customer and vendor routes for bargaining mode feature.
| All routes are feature-flagged via middleware.
|
*/

// Customer Bargaining Routes
Route::middleware(['auth:api', 'bargaining_enabled'])->prefix('bargaining')->group(function () {

    // Initiate bargaining from current cart
    Route::post('initiate', [BargainingController::class, 'initiate'])->name('api.v2.bargaining.initiate');

    // Get bargaining status and offers
    Route::get('status/{requestCode}', [BargainingController::class, 'status'])->name('api.v2.bargaining.status');

    // Accept an offer
    Route::post('accept-offer', [BargainingController::class, 'acceptOffer'])->name('api.v2.bargaining.accept-offer');

    // Cancel bargaining
    Route::post('cancel/{requestCode}', [BargainingController::class, 'cancel'])->name('api.v2.bargaining.cancel');

    // Get customer's bargaining history
    Route::get('history', [BargainingController::class, 'history'])->name('api.v2.bargaining.history');

    // Get offer details
    Route::get('offer/{offerId}', [BargainingController::class, 'offerDetails'])->name('api.v2.bargaining.offer-details');
});

// Vendor Bargaining Routes
Route::middleware(['auth:api', 'vendor_employee', 'bargaining_enabled'])->prefix('vendor/bargaining')->group(function () {

    // Get available bargaining requests (for manual bidding)
    Route::get('available', [VendorBargainingController::class, 'availableRequests'])->name('api.v2.vendor.bargaining.available');

    // Submit counter-offer
    Route::post('counter-offer', [VendorBargainingController::class, 'submitCounterOffer'])->name('api.v2.vendor.bargaining.counter-offer');

    // Get store's bargaining settings
    Route::get('settings', [VendorBargainingController::class, 'getSettings'])->name('api.v2.vendor.bargaining.settings');

    // Update store's bargaining settings
    Route::put('settings', [VendorBargainingController::class, 'updateSettings'])->name('api.v2.vendor.bargaining.update-settings');

    // Get store's offer for a request
    Route::get('my-offer/{requestCode}', [VendorBargainingController::class, 'getMyOffer'])->name('api.v2.vendor.bargaining.my-offer');

    // Withdraw a counter-offer
    Route::post('withdraw-offer/{offerId}', [VendorBargainingController::class, 'withdrawOffer'])->name('api.v2.vendor.bargaining.withdraw-offer');

    // Get bargaining analytics
    Route::get('analytics', [VendorBargainingController::class, 'analytics'])->name('api.v2.vendor.bargaining.analytics');
});
