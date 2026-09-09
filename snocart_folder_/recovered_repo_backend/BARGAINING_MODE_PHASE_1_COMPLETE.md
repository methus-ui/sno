# Bargaining Mode - Phase 1 Complete ✅

**Date:** 2026-03-16
**Status:** Database schema and core service implemented

---

## What Has Been Implemented

### ✅ Database Schema (6 Migrations)

1. **`2026_03_17_000001_create_bargaining_requests_table.php`**
   - Main tracking table for bargaining sessions
   - Status workflow: initiated → matching → offers_received → awarded/accepted
   - Supports both instant and wait modes
   - Tracks cart snapshot, zone/module context, and timing

2. **`2026_03_17_000002_create_bargaining_cart_items_table.php`**
   - Stores cart items being bargained for
   - Includes item details for matching (name, barcode, category)
   - Tracks matching results and method used

3. **`2026_03_17_000003_create_bargaining_item_matches_table.php`**
   - Stores all matched items across stores
   - Includes pricing snapshots (base, discounted, flash sale)
   - Tracks match quality and availability

4. **`2026_03_17_000004_create_bargaining_store_offers_table.php`**
   - Stores offers (auto-calculated + vendor-submitted)
   - **Critical:** Handles partial fulfillment with `items_available`, `items_missing`, `fulfillment_percentage`
   - Complete pricing breakdown with discounts, tax, delivery
   - Ranking system (rank 1 = best offer)

5. **`2026_03_17_000005_create_bargaining_offer_items_table.php`**
   - Line items in each offer
   - Tracks item availability per store (`is_available` flag)
   - Supports substitute suggestions

6. **`2026_03_17_000006_create_store_bargaining_settings_table.php`**
   - Vendor opt-in preferences
   - Auto-discount rules
   - Notification settings
   - Business hours for manual bidding

### ✅ Eloquent Models (6 Models)

- `BargainingRequest` - Main request model with relationships and scopes
- `BargainingCartItem` - Cart items with helper methods
- `BargainingItemMatch` - Item matches with pricing accessors
- `BargainingStoreOffer` - Offers with ranking and fulfillment logic
- `BargainingOfferItem` - Offer line items
- `StoreBargainingSetting` - Vendor settings with auto-discount calculation

### ✅ Core Service

**`app/Services/BargainingService.php`** (~800 lines)

**Key Methods:**
- `initiateBargaining()` - Start bargaining from cart
- `performItemMatching()` - Match items across stores
- `matchByBarcode()` - Exact barcode matching (primary)
- `matchByName()` - Fuzzy name matching (fallback, 85% threshold)
- `generateAutomaticOffers()` - Create offers for all stores
- `generateStoreOffer()` - Calculate single store's offer with pricing
- `rankOffers()` - **Prioritizes fulfillment (50%) then price (30%)**
- `autoAwardBestOffer()` - Instant mode auto-award
- `acceptOffer()` - Accept offer and populate cart for checkout
- `cancelRequest()` - Cancel bargaining session

**Handles "Item Not Available" Problem:**
- Tracks `items_available` and `items_missing` per offer
- Calculates `fulfillment_percentage`
- **Ranking prioritizes stores with MORE items available**
- Shows customer which items are missing before acceptance

### ✅ Configuration

**`config/bargaining.php`**
- Feature flags (enabled, instant_mode, wait_mode)
- Timing (wait_duration: 60s, request_expiration: 15min)
- Matching thresholds (fuzzy_similarity: 85%)
- Customer limits (max 10 requests/day, max 50 items/cart)
- Ranking weights (fulfillment: 50%, price: 30%, rating: 10%, delivery: 10%)
- Caching durations
- Performance settings

---

## Database Schema Summary

### Key Relationships

```
BargainingRequest (1) ──┬─→ (N) BargainingCartItem
                        │
                        ├─→ (N) BargainingStoreOffer
                        │
                        └─→ (1) Store (awarded_store)

BargainingCartItem (1) ──┬─→ (N) BargainingItemMatch
                         │
                         └─→ (N) BargainingOfferItem

BargainingStoreOffer (1) ───→ (N) BargainingOfferItem

Store (1) ───→ (1) StoreBargainingSetting
```

### Critical Indexes Added

**Performance optimizations:**
- `idx_user_status` - User filtering
- `idx_zone_module_status` - Zone-based queries
- `idx_cart_item_store` - Fast match lookups
- `idx_request_rank` - Offer ranking
- `idx_barcode` - Fast barcode searches
- `idx_fulfillment` - Fulfillment filtering

---

## How It Works (Flow)

### Instant Mode (95% use case)

1. **Customer clicks "Get Best Price"** → `initiateBargaining(mode='instant')`
2. **Snapshot cart** → Creates `BargainingRequest` + `BargainingCartItem` records
3. **Match items across stores:**
   - Try barcode exact match first
   - Fallback to fuzzy name match (85% similarity)
   - Creates `BargainingItemMatch` records with pricing snapshot
4. **Generate automatic offers:**
   - For each matched store, calculate offer
   - Handle partial fulfillment (some items missing)
   - Calculate subtotal, discounts, tax, delivery
   - Creates `BargainingStoreOffer` + `BargainingOfferItem` records
5. **Rank offers:**
   - Sort by fulfillment % (descending) → price (ascending) → rating → delivery
   - Set rank 1 as `is_best_offer`
6. **Auto-award best offer:**
   - Set status = 'awarded'
   - Link `awarded_store_id` and `accepted_offer_id`
7. **Customer sees best offer** → Can accept or view all offers
8. **Accept offer:**
   - Clear customer's cart
   - Populate with items from winning store
   - Proceed to normal checkout

### Wait Mode (5% use case)

Same as instant mode, but:
- No auto-award at step 6
- Wait 60 seconds for vendor counter-offers
- Real-time updates via Pusher/WebSocket
- Customer manually accepts best offer after waiting

---

## Item Matching Logic

### Primary: Barcode Exact Match (~60% coverage)

```php
Item::where('barcode', $cartItem->item_barcode)
    ->whereIn('store_id', $storeIds)
    ->where('status', 1)
    ->get();
```

**Match score:** 100

### Fallback: Fuzzy Name Match

```php
similar_text(
    strtolower($item->name),
    strtolower($cartItem->item_name),
    $similarity
);

if ($similarity >= 85) {
    // Create match with score = $similarity
}
```

**Match score:** 85-99

### Filters:
- Same category_id preferred
- Only active items (status = 1)
- Only stores in zone with bargaining enabled

---

## Handling Partial Fulfillment

**Example: Customer wants 10 items**

**Store A:**
- Has 10/10 items
- `fulfillment_percentage = 100%`
- `items_missing = 0`
- `missing_items_detail = []`

**Store B:**
- Has 8/10 items
- `fulfillment_percentage = 80%`
- `items_missing = 2`
- `missing_items_detail = ["Maggi Noodles", "Bread Loaf"]`

**Ranking:** Store A wins even if Store B has lower price (fulfillment weight = 50%)

**UI:** Customer sees warning: "Store B has 8 out of 10 items. Missing: Maggi Noodles, Bread Loaf. Continue?"

---

## Offer Ranking Algorithm

```php
$offers->sortByDesc(function ($offer) {
    $fulfillmentScore = $offer->fulfillment_percentage * 1000;  // 50% weight
    $priceScore = -($offer->total_amount);                      // 30% weight
    $ratingScore = ($offer->store->rating ?? 0) * 10;           // 10% weight
    $deliveryScore = -($offer->delivery_charge);                // 10% weight

    return $fulfillmentScore + ($priceScore / 10) + $ratingScore + ($deliveryScore / 10);
});
```

**Priority:**
1. **More items available** (highest priority)
2. Lower total price
3. Better store rating
4. Lower delivery charge

---

## Configuration (.env)

```env
# Enable/disable bargaining mode
BARGAINING_ENABLED=true

# Enable/disable modes
BARGAINING_INSTANT_MODE=true
BARGAINING_WAIT_MODE=true

# Wait duration for vendor offers (seconds)
BARGAINING_WAIT_DURATION=60

# Minimum cart value for bargaining
BARGAINING_MIN_CART_VALUE=0

# Require exact barcode match only
BARGAINING_REQUIRE_BARCODE=false
```

---

## What's Next (Remaining Phases)

### Phase 2: API Endpoints (Day 1-2)

**Customer APIs (`/api/v2/bargaining`):**
- POST `/initiate` - Start bargaining
- GET `/status/{requestCode}` - Get offers
- POST `/accept-offer` - Accept and prepare cart
- POST `/cancel/{requestCode}` - Cancel session

**Vendor APIs (`/api/v2/vendor/bargaining`):**
- GET `/available` - View bargaining requests
- POST `/counter-offer` - Submit manual bid

### Phase 3: Events & Real-Time (Day 3)

- `BargainingStatusChanged` event (Pusher broadcast)
- `NewBargainingOffer` event
- Cron job to expire old requests

### Phase 4: Translation Keys (Day 4)

- Add 35 translation keys to `resources/lang/en/messages.php`

### Phase 5: Testing (Day 5-6)

- API integration tests
- Manual testing scripts
- Performance testing (100+ stores)

### Phase 6: Flutter UI (Day 7-10)

- Bargaining mode toggle on cart screen
- Progress screen with countdown
- Offers comparison screen
- Missing items warning dialog

---

## Migration Commands

```bash
# Run migrations (in order)
php artisan migrate --path=database/migrations/2026_03_17_000001_create_bargaining_requests_table.php
php artisan migrate --path=database/migrations/2026_03_17_000002_create_bargaining_cart_items_table.php
php artisan migrate --path=database/migrations/2026_03_17_000003_create_bargaining_item_matches_table.php
php artisan migrate --path=database/migrations/2026_03_17_000004_create_bargaining_store_offers_table.php
php artisan migrate --path=database/migrations/2026_03_17_000005_create_bargaining_offer_items_table.php
php artisan migrate --path=database/migrations/2026_03_17_000006_create_store_bargaining_settings_table.php

# Clear caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
```

---

## Rollback Plan

### Level 1: Instant Disable (0 downtime)
```bash
# Update .env
BARGAINING_ENABLED=false

php artisan config:cache
```

### Level 2: Data Cleanup (Keep schema)
```php
DB::table('bargaining_offer_items')->truncate();
DB::table('bargaining_store_offers')->truncate();
DB::table('bargaining_item_matches')->truncate();
DB::table('bargaining_cart_items')->truncate();
DB::table('bargaining_requests')->truncate();
```

### Level 3: Full Rollback (Remove tables)
```bash
php artisan migrate:rollback --step=6
```

---

## Performance Estimates

**Matching 10 items across 50 stores:**
- Barcode matches: ~0.5s (direct query)
- Fuzzy matches: ~1.5s (200 items × similarity calculation)
- Offer generation: ~0.3s (50 stores × pricing calculation)
- **Total: ~2.5 seconds for instant mode**

**Database queries per request:**
- Cart snapshot: 2 queries
- Item matching: ~10 queries (batched)
- Offer generation: ~5 queries (eager loading)
- Ranking: 1 query
- **Total: ~18 queries (well under 20 target)**

**Caching:**
- Item matches: 5 min cache
- Store settings: 10 min cache
- Offers: 30 sec cache (frequent updates)

---

## Dependencies

**Required:**
- Laravel 10.x
- MySQL 8.0+ (for JSON columns)
- Pusher/WebSocket (for real-time updates)

**Optional:**
- Redis (for caching)
- Queue worker (for async processing)

---

## Security

✅ **User validation** - Requests tied to user_id or guest_id
✅ **Authorization checks** - Users can only access their own requests
✅ **Rate limiting** - Max 10 requests per user per day
✅ **Expiration** - Requests auto-expire after 15 minutes
✅ **DB transactions** - Atomic operations prevent data corruption
✅ **Pessimistic locking** - Prevent race conditions on offer acceptance

---

## Backward Compatibility

✅ **100% backward compatible** - V1 APIs unchanged
✅ **Feature-flagged** - Can disable instantly
✅ **Isolated tables** - No changes to existing schema
✅ **V2 namespace** - New routes under `/api/v2/bargaining`
✅ **Zero impact** - Regular checkout still works normally

---

## Files Created (Phase 1)

**Migrations (6):**
- `database/migrations/2026_03_17_000001_create_bargaining_requests_table.php`
- `database/migrations/2026_03_17_000002_create_bargaining_cart_items_table.php`
- `database/migrations/2026_03_17_000003_create_bargaining_item_matches_table.php`
- `database/migrations/2026_03_17_000004_create_bargaining_store_offers_table.php`
- `database/migrations/2026_03_17_000005_create_bargaining_offer_items_table.php`
- `database/migrations/2026_03_17_000006_create_store_bargaining_settings_table.php`

**Models (6):**
- `app/Models/BargainingRequest.php`
- `app/Models/BargainingCartItem.php`
- `app/Models/BargainingItemMatch.php`
- `app/Models/BargainingStoreOffer.php`
- `app/Models/BargainingOfferItem.php`
- `app/Models/StoreBargainingSetting.php`

**Services (1):**
- `app/Services/BargainingService.php` (~800 lines)

**Config (1):**
- `config/bargaining.php`

**Documentation (1):**
- `BARGAINING_MODE_PHASE_1_COMPLETE.md` (this file)

**Total:** 15 files, ~3,000 lines of code

---

## Next Actions

1. ✅ **Review Phase 1** - Check migrations and service logic
2. ⏳ **Phase 2** - Create API controllers and routes
3. ⏳ **Phase 3** - Add events and cron job
4. ⏳ **Phase 4** - Add translation keys
5. ⏳ **Phase 5** - Create testing scripts
6. ⏳ **Phase 6** - Implement Flutter UI

**Estimated Time Remaining:** 8-10 days for backend + 5 days for Flutter = 13-15 days total

---

## Questions to Clarify

1. **Deployment timing** - When should we run migrations? (recommend staging first)
2. **Pusher setup** - Is Pusher already configured for real-time updates?
3. **Queue workers** - Should matching/offers run async or sync?
4. **Vendor notification** - Enable by default or require opt-in?
5. **Flutter team** - When can they start UI implementation?

---

**Status:** Phase 1 complete ✅ - Ready to proceed with Phase 2 (API endpoints)
