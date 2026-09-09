<?php

namespace App\Http\Controllers\Api\V2\Vendor;

use App\Http\Controllers\Controller;
use App\Models\BargainingRequest;
use App\Models\BargainingStoreOffer;
use App\Models\BargainingItemMatch;
use App\Models\StoreBargainingSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Events\NewBargainingOffer;

class BargainingController extends Controller
{
    /**
     * Get available bargaining requests for vendor to bid on
     *
     * GET /api/v2/vendor/bargaining/available
     */
    public function availableRequests(Request $request)
    {
        try {
            $vendor = $request->vendor;
            $storeId = $vendor->stores[0]->id ?? null;

            if (!$storeId) {
                return response()->json([
                    'errors' => [['code' => 'no_store', 'message' => 'No store found for vendor']]
                ], 400);
            }

            // Check if store has bargaining enabled and manual bidding allowed
            $setting = StoreBargainingSetting::where('store_id', $storeId)->first();
            if (!$setting || !$setting->manual_bidding_enabled) {
                return response()->json([
                    'message' => 'Manual bidding is not enabled for your store',
                    'requests' => [],
                ], 200);
            }

            // Get active bargaining requests in store's zone where vendor hasn't submitted manual offer
            $store = $vendor->stores[0];
            $requests = BargainingRequest::where('zone_id', $store->zone_id)
                ->where('module_id', $store->module_id)
                ->where('mode', 'wait') // Only wait mode allows manual bids
                ->whereIn('status', ['matching_completed', 'offers_received'])
                ->where('expires_at', '>', now())
                ->whereHas('cartItems.matches', function ($q) use ($storeId) {
                    $q->where('store_id', $storeId);
                })
                ->with(['storeOffers' => function ($q) use ($storeId) {
                    $q->where('store_id', $storeId);
                }])
                ->orderBy('created_at', 'desc')
                ->limit(50)
                ->get();

            $availableRequests = $requests->map(function ($req) use ($storeId) {
                // Get auto-calculated offer for this store
                $autoOffer = $req->storeOffers->where('offer_type', 'auto_calculated')->first();
                $manualOffer = $req->storeOffers->where('offer_type', 'vendor_submitted')->first();

                // Count items we have
                $itemsWeHave = BargainingItemMatch::whereHas('bargainingCartItem', function ($q) use ($req) {
                    $q->where('bargaining_request_id', $req->id);
                })
                    ->where('store_id', $storeId)
                    ->where('in_stock', true)
                    ->count();

                return [
                    'request_code' => $req->request_code,
                    'total_items' => $req->total_cart_items,
                    'items_we_have' => $itemsWeHave,
                    'fulfillment_percentage' => $req->total_cart_items > 0 ? round(($itemsWeHave / $req->total_cart_items) * 100, 2) : 0,
                    'estimated_value' => (float) $req->original_cart_value,
                    'auto_calculated_price' => $autoOffer ? (float) $autoOffer->total_amount : null,
                    'auto_offer_rank' => $autoOffer?->rank,
                    'current_best_price' => (float) optional($req->bestOffer)->total_amount,
                    'our_manual_offer' => $manualOffer ? [
                        'offer_id' => $manualOffer->id,
                        'total_amount' => (float) $manualOffer->total_amount,
                        'rank' => $manualOffer->rank,
                        'special_discount' => (float) ($manualOffer->special_discount ?? 0),
                    ] : null,
                    'time_remaining' => $req->time_remaining,
                    'expires_at' => $req->expires_at?->toIso8601String(),
                    'customer_budget' => $req->customer_budget ? (float) $req->customer_budget : null,
                    'delivery_distance' => null, // TODO: Calculate if lat/lng available
                ];
            });

            return response()->json([
                'requests' => $availableRequests,
                'total_count' => $availableRequests->count(),
                'can_bid' => $setting->isWithinBiddingHours(),
                'bidding_hours' => [
                    'start' => $setting->bidding_start_time,
                    'end' => $setting->bidding_end_time,
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error('Get available requests failed', [
                'vendor_id' => $request->vendor->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'errors' => [['code' => 'fetch_failed', 'message' => 'Failed to fetch available requests']]
            ], 500);
        }
    }

    /**
     * Submit counter-offer (manual bid)
     *
     * POST /api/v2/vendor/bargaining/counter-offer
     *
     * Body:
     *   - request_code: string (required)
     *   - special_discount: float (optional)
     *   - vendor_notes: string (optional)
     *   - estimated_delivery_time: int (optional, minutes)
     */
    public function submitCounterOffer(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'request_code' => 'required|string|exists:bargaining_requests,request_code',
            'special_discount' => 'nullable|numeric|min:0',
            'vendor_notes' => 'nullable|string|max:500',
            'estimated_delivery_time' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()->all()
            ], 400);
        }

        DB::beginTransaction();

        try {
            $vendor = $request->vendor;
            $storeId = $vendor->stores[0]->id ?? null;

            if (!$storeId) {
                return response()->json([
                    'errors' => [['code' => 'no_store', 'message' => 'No store found']]
                ], 400);
            }

            // Check settings
            $setting = StoreBargainingSetting::where('store_id', $storeId)->first();
            if (!$setting || !$setting->manual_bidding_enabled) {
                return response()->json([
                    'errors' => [['code' => 'bidding_disabled', 'message' => 'Manual bidding is not enabled']]
                ], 403);
            }

            if (!$setting->isWithinBiddingHours()) {
                return response()->json([
                    'errors' => [['code' => 'outside_hours', 'message' => 'Bidding is only allowed during business hours']]
                ], 403);
            }

            // Get bargaining request
            $bargainingRequest = BargainingRequest::where('request_code', $request->input('request_code'))
                ->whereIn('status', ['matching_completed', 'offers_received'])
                ->firstOrFail();

            // Validate request hasn't expired
            if ($bargainingRequest->isExpired()) {
                return response()->json([
                    'errors' => [['code' => 'expired', 'message' => 'Bargaining request has expired']]
                ], 400);
            }

            // Get auto-calculated offer
            $autoOffer = BargainingStoreOffer::where('bargaining_request_id', $bargainingRequest->id)
                ->where('store_id', $storeId)
                ->where('offer_type', 'auto_calculated')
                ->firstOrFail();

            // Calculate new offer with special discount
            $specialDiscount = $request->input('special_discount', 0);
            $newTotalAmount = $autoOffer->total_amount - $specialDiscount;

            // Create or update vendor offer
            $vendorOffer = BargainingStoreOffer::updateOrCreate(
                [
                    'bargaining_request_id' => $bargainingRequest->id,
                    'store_id' => $storeId,
                    'offer_type' => 'vendor_submitted',
                ],
                [
                    'items_available' => $autoOffer->items_available,
                    'items_missing' => $autoOffer->items_missing,
                    'fulfillment_percentage' => $autoOffer->fulfillment_percentage,
                    'subtotal' => $autoOffer->subtotal,
                    'item_discount' => $autoOffer->item_discount,
                    'store_discount' => $autoOffer->store_discount,
                    'flash_sale_discount' => $autoOffer->flash_sale_discount,
                    'tax_amount' => $autoOffer->tax_amount,
                    'delivery_charge' => $autoOffer->delivery_charge,
                    'packaging_charge' => $autoOffer->packaging_charge,
                    'special_discount' => $specialDiscount,
                    'total_amount' => $newTotalAmount,
                    'vendor_notes' => $request->input('vendor_notes'),
                    'estimated_delivery_time' => $request->input('estimated_delivery_time'),
                    'submitted_by_employee_id' => $vendor->id,
                    'status' => 'submitted',
                    'submitted_at' => now(),
                    'missing_items_detail' => $autoOffer->missing_items_detail,
                ]
            );

            // Copy offer items from auto offer (only if new offer)
            if ($vendorOffer->wasRecentlyCreated) {
                foreach ($autoOffer->offerItems as $autoItem) {
                    $vendorOffer->offerItems()->create([
                        'bargaining_cart_item_id' => $autoItem->bargaining_cart_item_id,
                        'matched_item_id' => $autoItem->matched_item_id,
                        'matched_type' => $autoItem->matched_type,
                        'is_available' => $autoItem->is_available,
                        'quantity' => $autoItem->quantity,
                        'unit_price' => $autoItem->unit_price,
                        'discount_per_unit' => $autoItem->discount_per_unit,
                        'final_price_per_unit' => $autoItem->final_price_per_unit,
                        'line_total' => $autoItem->line_total,
                        'flash_sale_applied' => $autoItem->flash_sale_applied,
                    ]);
                }
            }

            // Re-rank all offers
            $this->rankOffers($bargainingRequest);

            // Reload with rank
            $vendorOffer->refresh();

            DB::commit();

            // Broadcast new counter-offer event
            $isNewBestOffer = $vendorOffer->is_best_offer;
            event(new NewBargainingOffer($bargainingRequest->fresh(), $vendorOffer, $isNewBestOffer));

            Log::info('Counter-offer submitted', [
                'request_code' => $bargainingRequest->request_code,
                'store_id' => $storeId,
                'offer_id' => $vendorOffer->id,
                'special_discount' => $specialDiscount,
                'is_new_best_offer' => $isNewBestOffer,
            ]);

            return response()->json([
                'message' => 'Counter-offer submitted successfully',
                'offer_id' => $vendorOffer->id,
                'total_amount' => (float) $vendorOffer->total_amount,
                'special_discount' => (float) $specialDiscount,
                'rank' => $vendorOffer->rank,
                'is_best_offer' => $vendorOffer->is_best_offer,
                'current_best_price' => (float) optional($bargainingRequest->fresh()->bestOffer)->total_amount,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Submit counter-offer failed', [
                'vendor_id' => $request->vendor->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'errors' => [['code' => 'submit_failed', 'message' => $e->getMessage()]]
            ], 400);
        }
    }

    /**
     * Re-rank all offers for a request
     */
    protected function rankOffers($request)
    {
        $offers = BargainingStoreOffer::where('bargaining_request_id', $request->id)
            ->where('status', 'submitted')
            ->with('store:id,name,rating')
            ->get();

        $offers = $offers->sortByDesc(function ($offer) {
            $fulfillmentScore = $offer->fulfillment_percentage * 1000;
            $priceScore = -($offer->total_amount);
            $ratingScore = ($offer->store->rating ?? 0) * 10;
            $deliveryScore = -($offer->delivery_charge);

            return $fulfillmentScore + ($priceScore / 10) + $ratingScore + ($deliveryScore / 10);
        })->values();

        $rank = 1;
        foreach ($offers as $offer) {
            $offer->update([
                'rank' => $rank,
                'is_best_offer' => $rank === 1,
            ]);
            $rank++;
        }
    }

    /**
     * Get store's bargaining settings
     *
     * GET /api/v2/vendor/bargaining/settings
     */
    public function getSettings(Request $request)
    {
        try {
            $vendor = $request->vendor;
            $storeId = $vendor->stores[0]->id ?? null;

            if (!$storeId) {
                return response()->json([
                    'errors' => [['code' => 'no_store', 'message' => 'No store found']]
                ], 400);
            }

            $setting = StoreBargainingSetting::firstOrCreate(
                ['store_id' => $storeId],
                [
                    'bargaining_enabled' => true,
                    'auto_participate' => true,
                    'manual_bidding_enabled' => false,
                ]
            );

            return response()->json([
                'bargaining_enabled' => $setting->bargaining_enabled,
                'auto_participate' => $setting->auto_participate,
                'manual_bidding_enabled' => $setting->manual_bidding_enabled,
                'notify_on_new_request' => $setting->notify_on_new_request,
                'notify_on_award' => $setting->notify_on_award,
                'notification_channel' => $setting->notification_channel,
                'auto_discount_percentage' => (float) $setting->auto_discount_percentage,
                'min_order_value_for_discount' => (float) $setting->min_order_value_for_discount,
                'max_discount_amount' => $setting->max_discount_amount ? (float) $setting->max_discount_amount : null,
                'always_match_lowest_price' => $setting->always_match_lowest_price,
                'price_match_buffer' => (float) $setting->price_match_buffer,
                'max_concurrent_bargains' => $setting->max_concurrent_bargains,
                'min_cart_value' => $setting->min_cart_value,
                'max_cart_items' => $setting->max_cart_items,
                'bidding_start_time' => $setting->bidding_start_time,
                'bidding_end_time' => $setting->bidding_end_time,
            ], 200);

        } catch (\Exception $e) {
            Log::error('Get settings failed', [
                'vendor_id' => $request->vendor->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'errors' => [['code' => 'fetch_failed', 'message' => 'Failed to fetch settings']]
            ], 500);
        }
    }

    /**
     * Update store's bargaining settings
     *
     * PUT /api/v2/vendor/bargaining/settings
     */
    public function updateSettings(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'bargaining_enabled' => 'nullable|boolean',
            'auto_participate' => 'nullable|boolean',
            'manual_bidding_enabled' => 'nullable|boolean',
            'notify_on_new_request' => 'nullable|boolean',
            'notify_on_award' => 'nullable|boolean',
            'notification_channel' => 'nullable|in:push,sms,email',
            'auto_discount_percentage' => 'nullable|numeric|min:0|max:100',
            'min_order_value_for_discount' => 'nullable|numeric|min:0',
            'max_discount_amount' => 'nullable|numeric|min:0',
            'always_match_lowest_price' => 'nullable|boolean',
            'price_match_buffer' => 'nullable|numeric|min:0|max:100',
            'bidding_start_time' => 'nullable|date_format:H:i',
            'bidding_end_time' => 'nullable|date_format:H:i',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()->all()
            ], 400);
        }

        try {
            $vendor = $request->vendor;
            $storeId = $vendor->stores[0]->id ?? null;

            if (!$storeId) {
                return response()->json([
                    'errors' => [['code' => 'no_store', 'message' => 'No store found']]
                ], 400);
            }

            $setting = StoreBargainingSetting::where('store_id', $storeId)->firstOrFail();
            $setting->update($request->only([
                'bargaining_enabled',
                'auto_participate',
                'manual_bidding_enabled',
                'notify_on_new_request',
                'notify_on_award',
                'notification_channel',
                'auto_discount_percentage',
                'min_order_value_for_discount',
                'max_discount_amount',
                'always_match_lowest_price',
                'price_match_buffer',
                'bidding_start_time',
                'bidding_end_time',
            ]));

            return response()->json([
                'message' => 'Settings updated successfully',
                'settings' => $setting,
            ], 200);

        } catch (\Exception $e) {
            Log::error('Update settings failed', [
                'vendor_id' => $request->vendor->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'errors' => [['code' => 'update_failed', 'message' => 'Failed to update settings']]
            ], 500);
        }
    }

    /**
     * Get store's offer for a specific request
     *
     * GET /api/v2/vendor/bargaining/my-offer/{requestCode}
     */
    public function getMyOffer(Request $request, $requestCode)
    {
        try {
            $vendor = $request->vendor;
            $storeId = $vendor->stores[0]->id ?? null;

            if (!$storeId) {
                return response()->json([
                    'errors' => [['code' => 'no_store', 'message' => 'No store found']]
                ], 400);
            }

            $bargainingRequest = BargainingRequest::where('request_code', $requestCode)->firstOrFail();

            $offers = BargainingStoreOffer::where('bargaining_request_id', $bargainingRequest->id)
                ->where('store_id', $storeId)
                ->with('offerItems.bargainingCartItem')
                ->get();

            $autoOffer = $offers->where('offer_type', 'auto_calculated')->first();
            $manualOffer = $offers->where('offer_type', 'vendor_submitted')->first();

            return response()->json([
                'request_code' => $requestCode,
                'auto_calculated_offer' => $autoOffer ? [
                    'offer_id' => $autoOffer->id,
                    'total_amount' => (float) $autoOffer->total_amount,
                    'rank' => $autoOffer->rank,
                    'is_best_offer' => $autoOffer->is_best_offer,
                    'items_available' => $autoOffer->items_available,
                    'items_missing' => $autoOffer->items_missing,
                    'fulfillment_percentage' => (float) $autoOffer->fulfillment_percentage,
                ] : null,
                'manual_offer' => $manualOffer ? [
                    'offer_id' => $manualOffer->id,
                    'total_amount' => (float) $manualOffer->total_amount,
                    'special_discount' => (float) ($manualOffer->special_discount ?? 0),
                    'vendor_notes' => $manualOffer->vendor_notes,
                    'rank' => $manualOffer->rank,
                    'is_best_offer' => $manualOffer->is_best_offer,
                    'submitted_at' => $manualOffer->submitted_at?->toIso8601String(),
                ] : null,
                'current_best_price' => (float) optional($bargainingRequest->bestOffer)->total_amount,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'errors' => [['code' => 'not_found', 'message' => 'Request not found']]
            ], 404);
        }
    }

    /**
     * Withdraw a counter-offer
     *
     * POST /api/v2/vendor/bargaining/withdraw-offer/{offerId}
     */
    public function withdrawOffer(Request $request, $offerId)
    {
        try {
            $vendor = $request->vendor;
            $storeId = $vendor->stores[0]->id ?? null;

            $offer = BargainingStoreOffer::where('id', $offerId)
                ->where('store_id', $storeId)
                ->where('offer_type', 'vendor_submitted')
                ->whereIn('status', ['submitted'])
                ->firstOrFail();

            $offer->update(['status' => 'withdrawn']);

            // Re-rank remaining offers
            $this->rankOffers($offer->bargainingRequest);

            return response()->json([
                'message' => 'Offer withdrawn successfully',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'errors' => [['code' => 'withdraw_failed', 'message' => 'Failed to withdraw offer']]
            ], 400);
        }
    }

    /**
     * Get bargaining analytics for vendor
     *
     * GET /api/v2/vendor/bargaining/analytics?period=7days
     */
    public function analytics(Request $request)
    {
        try {
            $vendor = $request->vendor;
            $storeId = $vendor->stores[0]->id ?? null;

            if (!$storeId) {
                return response()->json([
                    'errors' => [['code' => 'no_store', 'message' => 'No store found']]
                ], 400);
            }

            $period = $request->input('period', '7days');
            $daysAgo = match ($period) {
                '24hours' => 1,
                '7days' => 7,
                '30days' => 30,
                default => 7,
            };

            $startDate = now()->subDays($daysAgo);

            // Get all offers for this store
            $offers = BargainingStoreOffer::where('store_id', $storeId)
                ->where('created_at', '>=', $startDate)
                ->with('bargainingRequest')
                ->get();

            $totalRequests = $offers->pluck('bargaining_request_id')->unique()->count();
            $totalOffers = $offers->count();
            $offersWon = $offers->where('status', 'accepted')->count();
            $winRate = $totalOffers > 0 ? round(($offersWon / $totalOffers) * 100, 2) : 0;

            $autoOffers = $offers->where('offer_type', 'auto_calculated');
            $manualOffers = $offers->where('offer_type', 'vendor_submitted');

            $timesRankedFirst = $offers->where('is_best_offer', true)->count();
            $averageRank = $offers->where('rank', '>', 0)->avg('rank');

            return response()->json([
                'period' => $period,
                'total_bargaining_requests' => $totalRequests,
                'total_offers_submitted' => $totalOffers,
                'auto_offers' => $autoOffers->count(),
                'manual_offers' => $manualOffers->count(),
                'offers_won' => $offersWon,
                'win_rate_percentage' => $winRate,
                'times_ranked_first' => $timesRankedFirst,
                'average_rank' => $averageRank ? round($averageRank, 2) : null,
                'total_revenue_from_bargaining' => (float) $offers->where('status', 'accepted')->sum('total_amount'),
            ], 200);

        } catch (\Exception $e) {
            Log::error('Get analytics failed', [
                'vendor_id' => $request->vendor->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'errors' => [['code' => 'analytics_failed', 'message' => 'Failed to fetch analytics']]
            ], 500);
        }
    }
}
