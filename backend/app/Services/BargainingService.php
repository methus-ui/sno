<?php

namespace App\Services;

use App\Models\BargainingRequest;
use App\Models\BargainingCartItem;
use App\Models\BargainingItemMatch;
use App\Models\BargainingStoreOffer;
use App\Models\BargainingOfferItem;
use App\Models\StoreBargainingSetting;
use App\Models\Store;
use App\Models\Item;
use App\Models\Campaign;
use App\Models\Cart;
use App\CentralLogics\Helpers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use App\Events\BargainingStatusChanged;
use App\Events\NewBargainingOffer;
use App\Events\BargainingRequestAvailable;
use App\Events\BargainingOfferAwarded;

class BargainingService
{
    /**
     * Initiate a new bargaining request from customer's cart
     */
    public function initiateBargaining($userId, $guestId, $mode, $deliveryAddress, $latitude, $longitude, $customerBudget = null)
    {
        try {
            DB::beginTransaction();

            // Get cart items
            $cartItems = $this->getCarts($userId, $guestId);

            if ($cartItems->isEmpty()) {
                throw new \Exception('Cart is empty');
            }

            // Validate cart size
            if ($cartItems->count() > config('bargaining.max_cart_items', 50)) {
                throw new \Exception('Cart has too many items for bargaining mode');
            }

            // Get zone and module from first cart item's store
            $firstItem = $cartItems->first();
            $store = Store::find($firstItem->item?->store_id);

            if (!$store) {
                throw new \Exception('Unable to determine zone from cart');
            }

            // Calculate original cart value
            $originalCartValue = $cartItems->sum(function ($cartItem) {
                $price = $cartItem->price ?? 0;
                $discount = $cartItem->discount_amount ?? 0;
                return ($price - $discount) * $cartItem->quantity;
            });

            // Validate minimum cart value
            if ($originalCartValue < config('bargaining.min_cart_value', 0)) {
                throw new \Exception('Cart value too low for bargaining mode');
            }

            // Calculate expiration time
            $expiresAt = $mode === 'wait'
                ? now()->addSeconds(config('bargaining.wait_duration', 60))
                : now()->addMinutes(config('bargaining.request_expiration', 15));

            // Create bargaining request
            $request = BargainingRequest::create([
                'user_id' => $userId,
                'guest_id' => $guestId,
                'zone_id' => $store->zone_id,
                'module_id' => $store->module_id,
                'original_cart_snapshot' => $this->snapshotCart($cartItems),
                'original_cart_value' => $originalCartValue,
                'total_cart_items' => $cartItems->count(),
                'delivery_address' => $deliveryAddress,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'mode' => $mode,
                'customer_budget' => $customerBudget,
                'status' => 'initiated',
                'expires_at' => $expiresAt,
            ]);

            // Create cart item records
            foreach ($cartItems as $cartItem) {
                $this->createBargainingCartItem($request->id, $cartItem);
            }

            DB::commit();

            // Trigger async matching and offer generation
            $this->performItemMatching($request->id);
            $this->generateAutomaticOffers($request->id);

            return $request;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bargaining initiation failed: ' . $e->getMessage(), [
                'user_id' => $userId,
                'guest_id' => $guestId,
            ]);
            throw $e;
        }
    }

    /**
     * Get customer's cart items
     */
    protected function getCarts($userId, $guestId)
    {
        $query = Cart::with(['item']); // morphTo relationship

        if ($userId) {
            $query->where('user_id', $userId);
        } else {
            $query->where('guest_id', $guestId);
        }

        return $query->get();
    }

    /**
     * Snapshot cart state as JSON
     */
    protected function snapshotCart($cartItems)
    {
        return $cartItems->map(function ($cartItem) {
            return [
                'id' => $cartItem->id,
                'item_id' => $cartItem->item_id,
                'item_type' => $cartItem->item_type,
                'name' => $cartItem->item?->name ?? $cartItem->item?->title,
                'price' => $cartItem->price,
                'discount' => $cartItem->discount_amount,
                'quantity' => $cartItem->quantity,
                'variations' => $cartItem->variations,
                'add_ons' => $cartItem->add_ons,
            ];
        })->toArray();
    }

    /**
     * Create bargaining cart item record
     */
    protected function createBargainingCartItem($requestId, $cartItem)
    {
        $relatedItem = $cartItem->item; // Can be Item or Campaign based on item_type
        $isCampaign = $cartItem->item_type === 'App\Models\Campaign' || str_contains($cartItem->item_type, 'Campaign');

        return BargainingCartItem::create([
            'bargaining_request_id' => $requestId,
            'original_item_id' => $cartItem->item_id,
            'original_campaign_id' => $isCampaign ? $cartItem->item_id : null,
            'item_type' => $isCampaign ? 'campaign' : 'item',
            'item_name' => $relatedItem?->name ?? $relatedItem?->title,
            'item_barcode' => $relatedItem?->barcode ?? null,
            'category_id' => $relatedItem?->category_id,
            'sub_category_id' => $relatedItem?->sub_category_id ?? null,
            'item_description' => $relatedItem?->description ?? null,
            'quantity' => $cartItem->quantity,
            'original_price' => $cartItem->price ?? 0,
            'original_discount' => $cartItem->discount_amount ?? 0,
            'variations' => $cartItem->variations,
            'add_ons' => $cartItem->add_ons,
        ]);
    }

    /**
     * Perform item matching across all stores in zone
     */
    public function performItemMatching($requestId)
    {
        try {
            $request = BargainingRequest::find($requestId);
            if (!$request) {
                throw new \Exception("Bargaining request not found: {$requestId}");
            }

            $oldStatus = $request->status;
            $request->update(['status' => 'matching']);

            // Broadcast status change
            event(new BargainingStatusChanged($request, $oldStatus, 'matching', [
                'message' => 'Matching items across stores...'
            ]));

            $cartItems = $request->cartItems;

            // Get all active stores in zone with bargaining enabled
            $stores = Store::where('zone_id', $request->zone_id)
                ->where('module_id', $request->module_id)
                ->active()
                ->whereHas('bargainingSetting', function ($q) {
                    $q->where('bargaining_enabled', true)
                        ->where('auto_participate', true);
                })
                ->get();

            Log::info("Bargaining: Matching across {$stores->count()} stores", [
                'request_id' => $requestId,
                'zone_id' => $request->zone_id,
            ]);

            foreach ($cartItems as $cartItem) {
                $this->matchCart($cartItem, $stores);
            }

            $oldStatus = $request->status;
            $request->update([
                'status' => 'matching_completed',
                'total_stores_matched' => $this->countStoresWithMatches($requestId),
            ]);

            // Broadcast status change
            event(new BargainingStatusChanged($request->fresh(), $oldStatus, 'matching_completed', [
                'message' => 'Matching completed',
                'stores_matched' => $request->total_stores_matched
            ]));

            Log::info("Bargaining: Matching completed", [
                'request_id' => $requestId,
                'stores_matched' => $request->total_stores_matched,
            ]);

        } catch (\Exception $e) {
            Log::error('Item matching failed: ' . $e->getMessage(), [
                'request_id' => $requestId,
            ]);
            throw $e;
        }
    }

    /**
     * Match a single cart item across stores
     */
    protected function matchCart($cartItem, $stores)
    {
        $matchesFound = 0;
        $matchMethod = 'none';

        // Try barcode match first (most accurate)
        if ($cartItem->item_barcode) {
            $matchesFound = $this->matchByBarcode($cartItem, $stores);
            if ($matchesFound > 0) {
                $matchMethod = 'barcode';
            }
        }

        // Fallback to fuzzy name match if no barcode matches
        if ($matchesFound === 0 && !config('bargaining.require_barcode_match', false)) {
            $matchesFound = $this->matchByName($cartItem, $stores);
            if ($matchesFound > 0) {
                $matchMethod = 'fuzzy';
            }
        }

        $cartItem->update([
            'match_method' => $matchMethod,
            'total_matches_found' => $matchesFound,
        ]);

        return $matchesFound;
    }

    /**
     * Match by exact barcode
     */
    protected function matchByBarcode($cartItem, $stores)
    {
        $storeIds = $stores->pluck('id')->toArray();

        $items = Item::where('barcode', $cartItem->item_barcode)
            ->whereIn('store_id', $storeIds)
            ->where('status', 1)
            ->limit(config('bargaining.max_matches_per_item', 50))
            ->get();

        foreach ($items as $item) {
            $this->createItemMatch($cartItem, $item, 'barcode_exact', 100);
        }

        return $items->count();
    }

    /**
     * Match by fuzzy name similarity
     */
    protected function matchByName($cartItem, $stores)
    {
        $storeIds = $stores->pluck('id')->toArray();
        $threshold = config('bargaining.fuzzy_similarity_threshold', 85);

        $items = Item::whereIn('store_id', $storeIds)
            ->where('status', 1)
            ->when($cartItem->category_id, function ($q) use ($cartItem) {
                $q->where('category_id', $cartItem->category_id);
            })
            ->limit(200) // Get more for fuzzy matching
            ->get();

        $matches = [];

        foreach ($items as $item) {
            similar_text(
                strtolower($item->name),
                strtolower($cartItem->item_name),
                $similarity
            );

            if ($similarity >= $threshold) {
                $matches[] = [
                    'item' => $item,
                    'score' => $similarity,
                ];
            }
        }

        // Sort by similarity and limit
        usort($matches, function ($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        $matches = array_slice($matches, 0, config('bargaining.max_matches_per_item', 50));

        foreach ($matches as $match) {
            $this->createItemMatch($cartItem, $match['item'], 'name_fuzzy', $match['score']);
        }

        return count($matches);
    }

    /**
     * Create item match record with pricing snapshot
     */
    protected function createItemMatch($cartItem, $item, $matchMethod, $matchScore)
    {
        // Calculate flash sale price if active
        $flashSaleActive = false;
        $flashSalePrice = null;
        $flashSaleEndsAt = null;

        if ($item->flash_sale && $item->flash_sale_end_date && Carbon::parse($item->flash_sale_end_date)->isFuture()) {
            $flashSaleActive = true;
            $flashSalePrice = $item->flash_sale_price;
            $flashSaleEndsAt = $item->flash_sale_end_date;
        }

        return BargainingItemMatch::create([
            'bargaining_cart_item_id' => $cartItem->id,
            'store_id' => $item->store_id,
            'matched_item_id' => $item->id,
            'matched_type' => 'item',
            'match_method' => $matchMethod,
            'match_score' => $matchScore,
            'base_price' => $item->price,
            'discounted_price' => $item->discount > 0 ? ($item->price - $item->discount) : $item->price,
            'discount_amount' => $item->discount ?? 0,
            'discount_percentage' => $item->discount_type === 'percent' ? $item->discount : 0,
            'flash_sale_active' => $flashSaleActive,
            'flash_sale_price' => $flashSalePrice,
            'flash_sale_ends_at' => $flashSaleEndsAt,
            'in_stock' => ($item->stock ?? 0) > 0,
            'stock_quantity' => $item->stock ?? 0,
            'max_order_quantity' => $item->maximum_cart_quantity,
            'store_tax' => $item->store?->tax ?? 0,
            'store_discount' => $item->store?->discount?->discount ?? 0,
        ]);
    }

    /**
     * Count how many stores can fulfill at least one item
     */
    protected function countStoresWithMatches($requestId)
    {
        return BargainingItemMatch::whereHas('bargainingCartItem', function ($q) use ($requestId) {
            $q->where('bargaining_request_id', $requestId);
        })
            ->distinct('store_id')
            ->count('store_id');
    }

    /**
     * Generate automatic offers for all matched stores
     */
    public function generateAutomaticOffers($requestId)
    {
        try {
            $request = BargainingRequest::with('cartItems')->find($requestId);
            if (!$request) {
                throw new \Exception("Bargaining request not found: {$requestId}");
            }

            // Group matches by store
            $storeMatches = BargainingItemMatch::whereHas('bargainingCartItem', function ($q) use ($request) {
                $q->where('bargaining_request_id', $request->id);
            })
                ->with(['bargainingCartItem', 'store'])
                ->get()
                ->groupBy('store_id');

            Log::info("Bargaining: Generating offers for {$storeMatches->count()} stores", [
                'request_id' => $requestId,
            ]);

            $eligibleStoreIds = [];
            foreach ($storeMatches as $storeId => $matches) {
                $offer = $this->generateStoreOffer($request, $storeId, $matches);
                $eligibleStoreIds[] = $storeId;

                // Broadcast new offer event
                if ($offer) {
                    event(new NewBargainingOffer($request, $offer, false));
                }
            }

            // Rank all offers
            $this->rankOffers($request);

            // Update request
            $oldStatus = $request->status;
            $request->update([
                'status' => 'offers_received',
                'total_offers_received' => BargainingStoreOffer::where('bargaining_request_id', $request->id)->count(),
            ]);

            // Broadcast status change
            event(new BargainingStatusChanged($request->fresh(), $oldStatus, 'offers_received', [
                'message' => 'Offers received from stores',
                'total_offers' => $request->total_offers_received
            ]));

            // If wait mode, notify vendors
            if ($request->mode === 'wait') {
                event(new BargainingRequestAvailable($request, $eligibleStoreIds));
            }

            // If instant mode, auto-award best offer
            if ($request->mode === 'instant') {
                $this->autoAwardBestOffer($request);
            }

            Log::info("Bargaining: Offers generated", [
                'request_id' => $requestId,
                'total_offers' => $request->total_offers_received,
            ]);

        } catch (\Exception $e) {
            Log::error('Offer generation failed: ' . $e->getMessage(), [
                'request_id' => $requestId,
            ]);
            throw $e;
        }
    }

    /**
     * Generate offer for a single store
     */
    protected function generateStoreOffer($request, $storeId, $matches)
    {
        DB::beginTransaction();

        try {
            $store = Store::find($storeId);
            $cartItems = $request->cartItems;

            $itemsAvailable = 0;
            $itemsMissing = 0;
            $subtotal = 0;
            $itemDiscount = 0;
            $flashSaleDiscount = 0;

            $offerItems = [];
            $missingItemsDetail = [];

            foreach ($cartItems as $cartItem) {
                $match = $matches->where('bargaining_cart_item_id', $cartItem->id)->first();

                if ($match && $match->in_stock) {
                    // Item available
                    $itemsAvailable++;

                    $finalPrice = $match->final_price;
                    $lineTotal = $finalPrice * $cartItem->quantity;
                    $subtotal += $lineTotal;
                    $itemDiscount += $match->savings * $cartItem->quantity;

                    if ($match->flash_sale_active) {
                        $flashSaleDiscount += ($match->discounted_price - $match->flash_sale_price) * $cartItem->quantity;
                    }

                    $offerItems[] = [
                        'bargaining_cart_item_id' => $cartItem->id,
                        'matched_item_id' => $match->matched_item_id,
                        'matched_type' => 'item',
                        'is_available' => true,
                        'quantity' => $cartItem->quantity,
                        'unit_price' => $match->base_price,
                        'discount_per_unit' => $match->savings,
                        'final_price_per_unit' => $finalPrice,
                        'line_total' => $lineTotal,
                        'flash_sale_applied' => $match->flash_sale_active,
                        'flash_sale_savings' => $match->flash_sale_active ? ($match->discounted_price - $match->flash_sale_price) * $cartItem->quantity : 0,
                    ];
                } else {
                    // Item not available
                    $itemsMissing++;
                    $missingItemsDetail[] = $cartItem->item_name;

                    $offerItems[] = [
                        'bargaining_cart_item_id' => $cartItem->id,
                        'is_available' => false,
                        'quantity' => $cartItem->quantity,
                        'unit_price' => $cartItem->original_price,
                        'final_price_per_unit' => 0,
                        'line_total' => 0,
                    ];
                }
            }

            $fulfillmentPercentage = ($itemsAvailable / $cartItems->count()) * 100;

            // Get store-specific costs
            $storeDiscount = $this->calculateStoreDiscount($store, $subtotal);
            $deliveryCharge = $this->calculateDeliveryCharge($store, $request->delivery_address, $request->latitude, $request->longitude);
            $taxAmount = ($subtotal - $itemDiscount - $storeDiscount) * (($store->tax ?? 0) / 100);
            $packagingCharge = $store->packaging_charge ?? 0;

            $totalAmount = $subtotal - $itemDiscount - $storeDiscount - $flashSaleDiscount + $taxAmount + $deliveryCharge + $packagingCharge;

            // Create offer
            $offer = BargainingStoreOffer::create([
                'bargaining_request_id' => $request->id,
                'store_id' => $storeId,
                'offer_type' => 'auto_calculated',
                'items_available' => $itemsAvailable,
                'items_missing' => $itemsMissing,
                'fulfillment_percentage' => round($fulfillmentPercentage, 2),
                'subtotal' => $subtotal,
                'item_discount' => $itemDiscount,
                'store_discount' => $storeDiscount,
                'flash_sale_discount' => $flashSaleDiscount,
                'tax_amount' => $taxAmount,
                'delivery_charge' => $deliveryCharge,
                'packaging_charge' => $packagingCharge,
                'total_amount' => $totalAmount,
                'status' => 'submitted',
                'missing_items_detail' => $missingItemsDetail,
                'submitted_at' => now(),
            ]);

            // Create offer items
            foreach ($offerItems as $itemData) {
                $offer->offerItems()->create($itemData);
            }

            DB::commit();

            return $offer;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Store offer generation failed: ' . $e->getMessage(), [
                'request_id' => $request->id,
                'store_id' => $storeId,
            ]);
            throw $e;
        }
    }

    /**
     * Calculate store-wide discount
     */
    protected function calculateStoreDiscount($store, $subtotal)
    {
        $discount = 0;

        // Store's own discount
        if ($store->discount && $store->discount->discount > 0) {
            $storeDiscount = $store->discount;

            if ($storeDiscount->min_purchase <= $subtotal && $storeDiscount->max_discount >= $subtotal) {
                if ($storeDiscount->discount_type === 'percent') {
                    $discount = ($subtotal * $storeDiscount->discount) / 100;
                    if ($storeDiscount->max_discount_amount) {
                        $discount = min($discount, $storeDiscount->max_discount_amount);
                    }
                } else {
                    $discount = $storeDiscount->discount;
                }
            }
        }

        // Bargaining-specific auto discount
        $setting = $store->bargainingSetting;
        if ($setting && $setting->auto_discount_percentage > 0) {
            $autoDiscount = $setting->getAutoDiscountAmount($subtotal);
            $discount += $autoDiscount;
        }

        return round($discount, 2);
    }

    /**
     * Calculate delivery charge
     */
    protected function calculateDeliveryCharge($store, $deliveryAddress, $latitude, $longitude)
    {
        // Use existing delivery charge calculation logic
        if ($store->free_delivery) {
            return 0;
        }

        // Basic delivery charge
        $deliveryCharge = $store->minimum_shipping_charge ?? 0;

        // TODO: Calculate distance-based charges if needed
        // Can use Helpers::get_distance() if lat/lng available

        return $deliveryCharge;
    }

    /**
     * Rank offers - prioritizes fulfillment first, then price
     */
    protected function rankOffers($request)
    {
        $offers = BargainingStoreOffer::where('bargaining_request_id', $request->id)
            ->where('status', 'submitted')
            ->with('store:id,name,rating')
            ->get();

        // Sort by multiple criteria
        $offers = $offers->sortByDesc(function ($offer) {
            // Primary: Fulfillment percentage (higher is better)
            $fulfillmentScore = $offer->fulfillment_percentage * 1000;

            // Secondary: Lower price (subtract to make lower better)
            $priceScore = -($offer->total_amount);

            // Tertiary: Store rating (calculate average if array)
            $rating = $offer->store->rating ?? 0;
            if (is_array($rating)) {
                // Calculate weighted average from [1-star, 2-star, 3-star, 4-star, 5-star]
                $total = array_sum($rating);
                $rating = $total > 0 ? array_sum(array_map(fn($count, $stars) => $count * $stars, $rating, [1,2,3,4,5])) / $total : 0;
            }
            $ratingScore = $rating * 10;

            // Quaternary: Lower delivery charge
            $deliveryScore = -($offer->delivery_charge);

            return $fulfillmentScore + ($priceScore / 10) + $ratingScore + ($deliveryScore / 10);
        })->values();

        // Update ranks
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
     * Auto-award best offer in instant mode
     */
    protected function autoAwardBestOffer($request)
    {
        $bestOffer = BargainingStoreOffer::where('bargaining_request_id', $request->id)
            ->where('is_best_offer', true)
            ->first();

        if ($bestOffer) {
            $oldStatus = $request->status;
            $request->update([
                'status' => 'awarded',
                'awarded_store_id' => $bestOffer->store_id,
                'accepted_offer_id' => $bestOffer->id,
                'final_price' => $bestOffer->total_amount,
                'total_savings' => $request->original_cart_value - $bestOffer->total_amount,
                'awarded_at' => now(),
            ]);

            // Broadcast status change
            event(new BargainingStatusChanged($request->fresh(), $oldStatus, 'awarded', [
                'message' => 'Best offer auto-awarded',
                'awarded_store_id' => $bestOffer->store_id
            ]));

            // Broadcast offer awarded
            event(new BargainingOfferAwarded($request, $bestOffer));

            Log::info("Bargaining: Best offer auto-awarded", [
                'request_id' => $request->id,
                'store_id' => $bestOffer->store_id,
                'total_amount' => $bestOffer->total_amount,
            ]);
        }
    }

    /**
     * Accept an offer and prepare cart for checkout
     */
    public function acceptOffer($requestCode, $offerId, $userId, $guestId)
    {
        DB::beginTransaction();

        try {
            $request = BargainingRequest::where('request_code', $requestCode)->firstOrFail();

            // Validate request belongs to user
            if ($userId && $request->user_id != $userId) {
                throw new \Exception('Unauthorized');
            }
            if ($guestId && $request->guest_id != $guestId) {
                throw new \Exception('Unauthorized');
            }

            // Validate request can accept offers
            if (!$request->canAcceptOffers()) {
                throw new \Exception('Cannot accept offers at this time');
            }

            $offer = BargainingStoreOffer::findOrFail($offerId);

            // Validate offer belongs to request
            if ($offer->bargaining_request_id != $request->id) {
                throw new \Exception('Invalid offer');
            }

            // Clear customer's existing cart
            Cart::where($userId ? 'user_id' : 'guest_id', $userId ?? $guestId)->delete();

            // Populate cart with items from winning offer
            foreach ($offer->offerItems()->where('is_available', true)->get() as $offerItem) {
                Cart::create([
                    'user_id' => $userId,
                    'guest_id' => $guestId,
                    'item_id' => $offerItem->matched_item_id,
                    'price' => $offerItem->final_price_per_unit,
                    'quantity' => $offerItem->quantity,
                    'discount_amount' => $offerItem->discount_per_unit,
                    'variations' => $offerItem->bargainingCartItem->variations,
                    'add_ons' => $offerItem->bargainingCartItem->add_ons,
                ]);
            }

            // Update request status
            $oldStatus = $request->status;
            $request->update([
                'status' => 'accepted',
                'accepted_offer_id' => $offerId,
                'awarded_store_id' => $offer->store_id,
                'final_price' => $offer->total_amount,
                'total_savings' => $request->original_cart_value - $offer->total_amount,
                'accepted_at' => now(),
            ]);

            // Update offer status
            $offer->update([
                'status' => 'accepted',
                'accepted_at' => now(),
            ]);

            DB::commit();

            // Broadcast status change
            event(new BargainingStatusChanged($request->fresh(), $oldStatus, 'accepted', [
                'message' => 'Offer accepted',
                'store_id' => $offer->store_id
            ]));

            // Broadcast offer awarded (if not already awarded)
            if ($oldStatus !== 'awarded') {
                event(new BargainingOfferAwarded($request, $offer));
            }

            Log::info("Bargaining: Offer accepted", [
                'request_id' => $request->id,
                'offer_id' => $offerId,
                'store_id' => $offer->store_id,
            ]);

            return $request;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Accept offer failed: ' . $e->getMessage(), [
                'request_code' => $requestCode,
                'offer_id' => $offerId,
            ]);
            throw $e;
        }
    }

    /**
     * Cancel a bargaining request
     */
    public function cancelRequest($requestCode, $userId, $guestId)
    {
        $request = BargainingRequest::where('request_code', $requestCode)->firstOrFail();

        // Validate ownership
        if ($userId && $request->user_id != $userId) {
            throw new \Exception('Unauthorized');
        }
        if ($guestId && $request->guest_id != $guestId) {
            throw new \Exception('Unauthorized');
        }

        if (!$request->canCancel()) {
            throw new \Exception('Cannot cancel at this stage');
        }

        $request->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        return $request;
    }

    /**
     * Get request status with offers
     */
    public function getRequestStatus($requestCode)
    {
        $request = BargainingRequest::where('request_code', $requestCode)
            ->with([
                'storeOffers' => function ($q) {
                    $q->where('status', 'submitted')
                        ->orderBy('rank', 'asc')
                        ->with('store:id,name,logo,rating,delivery_time');
                },
                'bestOffer.store:id,name,logo,rating,delivery_time',
            ])
            ->firstOrFail();

        return $request;
    }

    /**
     * Link order to bargaining request after order creation
     * Call this from OrderController->place_order() after order is created
     *
     * @param \App\Models\Order $order
     * @param int|null $userId
     * @param string|null $guestId
     * @return void
     */
    public function linkOrderToBargaining($order, $userId, $guestId)
    {
        // Find most recent accepted bargaining for this user/guest
        $request = BargainingRequest::where('status', 'accepted')
            ->where(function($q) use ($userId, $guestId) {
                if ($userId) {
                    $q->where('user_id', $userId);
                } else {
                    $q->where('guest_id', $guestId);
                }
            })
            ->where('accepted_at', '>=', now()->subHours(2))
            ->orderBy('accepted_at', 'desc')
            ->first();

        if ($request && $request->awarded_store_id == $order->store_id) {
            $order->update([
                'bargaining_request_id' => $request->id,
                'bargaining_accepted_offer_id' => $request->accepted_offer_id,
                'is_bargaining_order' => true,
            ]);

            $request->update(['status' => 'completed']);

            \Log::info('Order linked to bargaining', [
                'order_id' => $order->id,
                'request_code' => $request->request_code,
                'bargaining_request_id' => $request->id,
            ]);
        }
    }
}
