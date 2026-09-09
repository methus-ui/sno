<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Services\BargainingService;
use App\Models\BargainingRequest;
use App\Models\BargainingStoreOffer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class BargainingController extends Controller
{
    protected $bargainingService;

    public function __construct(BargainingService $bargainingService)
    {
        $this->bargainingService = $bargainingService;
    }

    /**
     * Initiate bargaining from customer's cart
     *
     * POST /api/v2/bargaining/initiate
     *
     * Headers:
     *   - zoneId: [1,2,3]
     *   - moduleId: 1
     *   - Authorization: Bearer {token}
     *
     * Body:
     *   - mode: instant|wait (required)
     *   - delivery_address: {} (optional)
     *   - latitude: float (optional)
     *   - longitude: float (optional)
     *   - customer_budget: float (optional)
     */
    public function initiate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mode' => 'required|in:instant,wait',
            'delivery_address' => 'nullable|array',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'customer_budget' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()->all()
            ], 400);
        }

        try {
            // Check rate limiting
            $userId = $request->user()->id;
            $todayRequestsCount = BargainingRequest::where('user_id', $userId)
                ->whereDate('created_at', today())
                ->count();

            $maxRequests = config('bargaining.max_requests_per_user_per_day', 10);
            if ($todayRequestsCount >= $maxRequests) {
                return response()->json([
                    'errors' => [['code' => 'rate_limit', 'message' => "You have reached the maximum of {$maxRequests} bargaining requests per day"]]
                ], 429);
            }

            // Validate mode is enabled
            $mode = $request->input('mode');
            if ($mode === 'instant' && !config('bargaining.instant_mode_enabled', true)) {
                return response()->json([
                    'errors' => [['code' => 'mode_disabled', 'message' => 'Instant mode is currently disabled']]
                ], 400);
            }
            if ($mode === 'wait' && !config('bargaining.wait_mode_enabled', true)) {
                return response()->json([
                    'errors' => [['code' => 'mode_disabled', 'message' => 'Wait mode is currently disabled']]
                ], 400);
            }

            // Initiate bargaining
            $bargainingRequest = $this->bargainingService->initiateBargaining(
                userId: $userId,
                guestId: null,
                mode: $mode,
                deliveryAddress: $request->input('delivery_address'),
                latitude: $request->input('latitude'),
                longitude: $request->input('longitude'),
                customerBudget: $request->input('customer_budget')
            );

            return response()->json([
                'message' => 'Bargaining initiated successfully',
                'request_code' => $bargainingRequest->request_code,
                'status' => $bargainingRequest->status,
                'mode' => $bargainingRequest->mode,
                'total_cart_items' => $bargainingRequest->total_cart_items,
                'original_cart_value' => (float) $bargainingRequest->original_cart_value,
                'wait_duration' => $mode === 'wait' ? config('bargaining.wait_duration', 60) : null,
                'expires_at' => $bargainingRequest->expires_at?->toIso8601String(),
                'time_remaining' => $bargainingRequest->time_remaining,
            ], 201);

        } catch (\Exception $e) {
            Log::error('Bargaining initiation failed', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'errors' => [['code' => 'initiation_failed', 'message' => $e->getMessage()]]
            ], 400);
        }
    }

    /**
     * Get bargaining status and offers
     *
     * GET /api/v2/bargaining/status/{requestCode}
     */
    public function status(Request $request, $requestCode)
    {
        try {
            $bargainingRequest = $this->bargainingService->getRequestStatus($requestCode);

            // Validate ownership
            $userId = $request->user()->id;
            if ($bargainingRequest->user_id != $userId) {
                return response()->json([
                    'errors' => [['code' => 'unauthorized', 'message' => 'Unauthorized access']]
                ], 403);
            }

            // Get best offer
            $bestOffer = $bargainingRequest->bestOffer;

            // Get all offers
            $allOffers = $bargainingRequest->storeOffers->map(function ($offer) {
                return [
                    'offer_id' => $offer->id,
                    'store_id' => $offer->store_id,
                    'store_name' => $offer->store->name,
                    'store_logo' => $offer->store->logo_full_url,
                    'store_rating' => (float) ($offer->store->rating ?? 0),
                    'offer_type' => $offer->offer_type,
                    'is_vendor_offer' => $offer->isVendorOffer(),
                    'items_available' => $offer->items_available,
                    'items_missing' => $offer->items_missing,
                    'fulfillment_percentage' => (float) $offer->fulfillment_percentage,
                    'missing_items' => $offer->missing_items_detail ?? [],
                    'subtotal' => (float) $offer->subtotal,
                    'total_discount' => (float) $offer->total_discount,
                    'item_discount' => (float) $offer->item_discount,
                    'store_discount' => (float) $offer->store_discount,
                    'flash_sale_discount' => (float) $offer->flash_sale_discount,
                    'special_discount' => (float) ($offer->special_discount ?? 0),
                    'tax_amount' => (float) $offer->tax_amount,
                    'delivery_charge' => (float) $offer->delivery_charge,
                    'packaging_charge' => (float) $offer->packaging_charge,
                    'total_amount' => (float) $offer->total_amount,
                    'savings' => (float) $offer->savings_vs_original,
                    'rank' => $offer->rank,
                    'is_best_offer' => $offer->is_best_offer,
                    'vendor_notes' => $offer->vendor_notes,
                    'estimated_delivery_time' => $offer->estimated_delivery_time ?? $offer->store->delivery_time,
                ];
            });

            return response()->json([
                'request_code' => $bargainingRequest->request_code,
                'status' => $bargainingRequest->status,
                'mode' => $bargainingRequest->mode,
                'total_cart_items' => $bargainingRequest->total_cart_items,
                'original_cart_value' => (float) $bargainingRequest->original_cart_value,
                'total_stores_matched' => $bargainingRequest->total_stores_matched,
                'total_offers_received' => $bargainingRequest->total_offers_received,
                'expires_at' => $bargainingRequest->expires_at?->toIso8601String(),
                'time_remaining' => $bargainingRequest->time_remaining,
                'can_accept_offers' => $bargainingRequest->canAcceptOffers(),
                'is_expired' => $bargainingRequest->isExpired(),
                'best_offer' => $bestOffer ? [
                    'offer_id' => $bestOffer->id,
                    'store_id' => $bestOffer->store_id,
                    'store_name' => $bestOffer->store->name,
                    'store_logo' => $bestOffer->store->logo_full_url,
                    'store_rating' => (float) ($bestOffer->store->rating ?? 0),
                    'total_amount' => (float) $bestOffer->total_amount,
                    'items_available' => $bestOffer->items_available,
                    'items_missing' => $bestOffer->items_missing,
                    'fulfillment_percentage' => (float) $bestOffer->fulfillment_percentage,
                    'savings' => (float) $bestOffer->savings_vs_original,
                    'is_vendor_offer' => $bestOffer->isVendorOffer(),
                    'vendor_notes' => $bestOffer->vendor_notes,
                    'delivery_charge' => (float) $bestOffer->delivery_charge,
                ] : null,
                'all_offers' => $allOffers,
                'awarded_store_id' => $bargainingRequest->awarded_store_id,
                'final_price' => $bargainingRequest->final_price ? (float) $bargainingRequest->final_price : null,
                'total_savings' => $bargainingRequest->total_savings ? (float) $bargainingRequest->total_savings : null,
            ], 200);

        } catch (\Exception $e) {
            Log::error('Get bargaining status failed', [
                'request_code' => $requestCode,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'errors' => [['code' => 'status_failed', 'message' => 'Bargaining request not found']]
            ], 404);
        }
    }

    /**
     * Accept an offer and prepare cart for checkout
     *
     * POST /api/v2/bargaining/accept-offer
     *
     * Body:
     *   - request_code: string (required)
     *   - offer_id: int (required)
     */
    public function acceptOffer(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'request_code' => 'required|string',
            'offer_id' => 'required|integer|exists:bargaining_store_offers,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()->all()
            ], 400);
        }

        try {
            $userId = $request->user()->id;

            $bargainingRequest = $this->bargainingService->acceptOffer(
                requestCode: $request->input('request_code'),
                offerId: $request->input('offer_id'),
                userId: $userId,
                guestId: null
            );

            $acceptedOffer = $bargainingRequest->acceptedOffer;

            return response()->json([
                'message' => 'Offer accepted successfully - cart updated',
                'request_code' => $bargainingRequest->request_code,
                'status' => $bargainingRequest->status,
                'store_id' => $acceptedOffer->store_id,
                'store_name' => $acceptedOffer->store->name,
                'cart_items' => $acceptedOffer->offerItems()->where('is_available', true)->count(),
                'items_unavailable' => $acceptedOffer->items_missing,
                'total_amount' => (float) $acceptedOffer->total_amount,
                'total_savings' => (float) $bargainingRequest->total_savings,
                'ready_to_order' => true,
                'next_step' => 'Proceed to checkout using the regular place order API',
            ], 200);

        } catch (\Exception $e) {
            Log::error('Accept offer failed', [
                'user_id' => $request->user()->id,
                'request_code' => $request->input('request_code'),
                'offer_id' => $request->input('offer_id'),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'errors' => [['code' => 'accept_failed', 'message' => $e->getMessage()]]
            ], 400);
        }
    }

    /**
     * Cancel bargaining request
     *
     * POST /api/v2/bargaining/cancel/{requestCode}
     */
    public function cancel(Request $request, $requestCode)
    {
        try {
            $userId = $request->user()->id;

            $bargainingRequest = $this->bargainingService->cancelRequest(
                requestCode: $requestCode,
                userId: $userId,
                guestId: null
            );

            return response()->json([
                'message' => 'Bargaining cancelled successfully',
                'request_code' => $bargainingRequest->request_code,
                'status' => $bargainingRequest->status,
                'cancelled_at' => $bargainingRequest->cancelled_at?->toIso8601String(),
            ], 200);

        } catch (\Exception $e) {
            Log::error('Cancel bargaining failed', [
                'user_id' => $request->user()->id,
                'request_code' => $requestCode,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'errors' => [['code' => 'cancel_failed', 'message' => $e->getMessage()]]
            ], 400);
        }
    }

    /**
     * Get customer's bargaining history
     *
     * GET /api/v2/bargaining/history?limit=20&offset=0
     */
    public function history(Request $request)
    {
        $limit = $request->input('limit', 20);
        $offset = $request->input('offset', 0);
        $userId = $request->user()->id;

        $requests = BargainingRequest::where('user_id', $userId)
            ->with(['awardedStore:id,name,logo,rating', 'acceptedOffer'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->offset($offset)
            ->get();

        $totalCount = BargainingRequest::where('user_id', $userId)->count();

        $history = $requests->map(function ($req) {
            return [
                'request_code' => $req->request_code,
                'status' => $req->status,
                'mode' => $req->mode,
                'total_cart_items' => $req->total_cart_items,
                'original_cart_value' => (float) $req->original_cart_value,
                'final_price' => $req->final_price ? (float) $req->final_price : null,
                'total_savings' => $req->total_savings ? (float) $req->total_savings : null,
                'stores_matched' => $req->total_stores_matched,
                'offers_received' => $req->total_offers_received,
                'awarded_store' => $req->awardedStore ? [
                    'id' => $req->awardedStore->id,
                    'name' => $req->awardedStore->name,
                    'logo' => $req->awardedStore->logo_full_url,
                    'rating' => (float) ($req->awardedStore->rating ?? 0),
                ] : null,
                'created_at' => $req->created_at->toIso8601String(),
                'accepted_at' => $req->accepted_at?->toIso8601String(),
            ];
        });

        return response()->json([
            'history' => $history,
            'total_count' => $totalCount,
            'limit' => $limit,
            'offset' => $offset,
        ], 200);
    }

    /**
     * Get detailed offer information
     *
     * GET /api/v2/bargaining/offer/{offerId}
     */
    public function offerDetails(Request $request, $offerId)
    {
        try {
            $offer = BargainingStoreOffer::with([
                'store:id,name,logo,rating,delivery_time,tax,minimum_order',
                'offerItems.bargainingCartItem',
                'offerItems.matchedItem:id,name,image,description',
            ])->findOrFail($offerId);

            // Validate ownership
            $userId = $request->user()->id;
            if ($offer->bargainingRequest->user_id != $userId) {
                return response()->json([
                    'errors' => [['code' => 'unauthorized', 'message' => 'Unauthorized access']]
                ], 403);
            }

            $items = $offer->offerItems->map(function ($item) {
                return [
                    'cart_item_name' => $item->bargainingCartItem->item_name,
                    'is_available' => $item->is_available,
                    'matched_item' => $item->matchedItem ? [
                        'id' => $item->matchedItem->id,
                        'name' => $item->matchedItem->name,
                        'image' => $item->matchedItem->image_full_url ?? [],
                        'description' => $item->matchedItem->description,
                    ] : null,
                    'quantity' => $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'discount_per_unit' => (float) $item->discount_per_unit,
                    'final_price_per_unit' => (float) $item->final_price_per_unit,
                    'line_total' => (float) $item->line_total,
                    'flash_sale_applied' => $item->flash_sale_applied,
                    'substitute_suggested' => $item->substitute_suggested,
                    'substitute_notes' => $item->substitute_notes,
                ];
            });

            return response()->json([
                'offer_id' => $offer->id,
                'store' => [
                    'id' => $offer->store->id,
                    'name' => $offer->store->name,
                    'logo' => $offer->store->logo_full_url,
                    'rating' => (float) ($offer->store->rating ?? 0),
                    'delivery_time' => $offer->store->delivery_time,
                    'tax' => (float) ($offer->store->tax ?? 0),
                    'minimum_order' => (float) ($offer->store->minimum_order ?? 0),
                ],
                'offer_type' => $offer->offer_type,
                'is_vendor_offer' => $offer->isVendorOffer(),
                'fulfillment' => [
                    'items_available' => $offer->items_available,
                    'items_missing' => $offer->items_missing,
                    'fulfillment_percentage' => (float) $offer->fulfillment_percentage,
                    'missing_items_detail' => $offer->missing_items_detail ?? [],
                ],
                'pricing' => [
                    'subtotal' => (float) $offer->subtotal,
                    'item_discount' => (float) $offer->item_discount,
                    'store_discount' => (float) $offer->store_discount,
                    'flash_sale_discount' => (float) $offer->flash_sale_discount,
                    'special_discount' => (float) ($offer->special_discount ?? 0),
                    'total_discount' => (float) $offer->total_discount,
                    'tax_amount' => (float) $offer->tax_amount,
                    'delivery_charge' => (float) $offer->delivery_charge,
                    'packaging_charge' => (float) $offer->packaging_charge,
                    'total_amount' => (float) $offer->total_amount,
                ],
                'items' => $items,
                'vendor_notes' => $offer->vendor_notes,
                'estimated_delivery_time' => $offer->estimated_delivery_time,
                'rank' => $offer->rank,
                'is_best_offer' => $offer->is_best_offer,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'errors' => [['code' => 'not_found', 'message' => 'Offer not found']]
            ], 404);
        }
    }
}
