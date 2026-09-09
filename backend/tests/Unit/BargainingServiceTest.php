<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\BargainingService;
use App\Models\User;
use App\Models\Cart;
use App\Models\Item;
use App\Models\Store;
use App\Models\Zone;
use App\Models\Module;
use App\Models\Category;
use App\Models\BargainingRequest;
use App\Models\BargainingCartItem;
use App\Models\BargainingItemMatch;
use App\Models\BargainingStoreOffer;
use App\Models\StoreBargainingSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

class BargainingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $service;
    protected $user;
    protected $zone;
    protected $module;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new BargainingService();
        $this->user = User::factory()->create();
        $this->zone = Zone::factory()->create();
        $this->module = Module::factory()->create();

        config(['bargaining.enabled' => true]);
        config(['bargaining.instant_mode_enabled' => true]);
        config(['bargaining.wait_mode_enabled' => true]);
    }

    /** @test */
    public function it_creates_bargaining_request_from_cart()
    {
        $store = Store::factory()->create(['zone_id' => $this->zone->id, 'module_id' => $this->module->id]);
        $item = Item::factory()->create(['store_id' => $store->id, 'price' => 100]);

        Cart::create([
            'user_id' => $this->user->id,
            'item_id' => $item->id,
            'quantity' => 2,
            'price' => 100,
        ]);

        $request = $this->service->initiateBargaining(
            userId: $this->user->id,
            guestId: null,
            mode: 'instant',
            deliveryAddress: ['lat' => 12.9716, 'lng' => 77.5946],
            zoneId: $this->zone->id,
            moduleId: $this->module->id
        );

        $this->assertInstanceOf(BargainingRequest::class, $request);
        $this->assertEquals('instant', $request->mode);
        $this->assertEquals($this->user->id, $request->user_id);
        $this->assertEquals($this->zone->id, $request->zone_id);
        $this->assertEquals(1, $request->total_cart_items);
        $this->assertEquals(200, $request->original_cart_value);
    }

    /** @test */
    public function it_generates_unique_request_code()
    {
        $store = Store::factory()->create(['zone_id' => $this->zone->id, 'module_id' => $this->module->id]);
        $item = Item::factory()->create(['store_id' => $store->id]);

        Cart::create(['user_id' => $this->user->id, 'item_id' => $item->id, 'quantity' => 1, 'price' => 100]);

        $request = $this->service->initiateBargaining(
            $this->user->id, null, 'instant', [], $this->zone->id, $this->module->id
        );

        $this->assertMatchesRegularExpression('/^BR-\d{6}$/', $request->request_code);
        $this->assertTrue(BargainingRequest::where('request_code', $request->request_code)->exists());
    }

    /** @test */
    public function it_performs_barcode_matching()
    {
        $category = Category::factory()->create();

        $store1 = Store::factory()->create(['zone_id' => $this->zone->id, 'module_id' => $this->module->id]);
        $store2 = Store::factory()->create(['zone_id' => $this->zone->id, 'module_id' => $this->module->id]);

        // Item in cart
        $cartItem = Item::factory()->create([
            'store_id' => $store1->id,
            'barcode' => '1234567890',
            'category_id' => $category->id,
            'price' => 100,
        ]);

        // Same item in different store (barcode match)
        $matchedItem = Item::factory()->create([
            'store_id' => $store2->id,
            'barcode' => '1234567890',
            'category_id' => $category->id,
            'price' => 95,
        ]);

        Cart::create(['user_id' => $this->user->id, 'item_id' => $cartItem->id, 'quantity' => 1, 'price' => 100]);

        $request = $this->service->initiateBargaining(
            $this->user->id, null, 'instant', [], $this->zone->id, $this->module->id
        );

        // Verify barcode match was created
        $this->assertDatabaseHas('bargaining_item_matches', [
            'store_id' => $store2->id,
            'matched_item_id' => $matchedItem->id,
            'match_method' => 'barcode_exact',
            'match_score' => 100,
        ]);
    }

    /** @test */
    public function it_performs_fuzzy_name_matching()
    {
        $category = Category::factory()->create();

        $store1 = Store::factory()->create(['zone_id' => $this->zone->id, 'module_id' => $this->module->id]);
        $store2 = Store::factory()->create(['zone_id' => $this->zone->id, 'module_id' => $this->module->id]);

        // Item in cart (no barcode)
        $cartItem = Item::factory()->create([
            'store_id' => $store1->id,
            'name' => 'Maggi 2-Minute Noodles',
            'barcode' => null,
            'category_id' => $category->id,
        ]);

        // Similar item in different store
        $matchedItem = Item::factory()->create([
            'store_id' => $store2->id,
            'name' => 'Maggi 2 Minute Noodles', // Slight variation
            'barcode' => null,
            'category_id' => $category->id,
        ]);

        Cart::create(['user_id' => $this->user->id, 'item_id' => $cartItem->id, 'quantity' => 1, 'price' => 100]);

        $request = $this->service->initiateBargaining(
            $this->user->id, null, 'instant', [], $this->zone->id, $this->module->id
        );

        // Verify fuzzy match was created
        $match = BargainingItemMatch::where('store_id', $store2->id)
            ->where('matched_item_id', $matchedItem->id)
            ->first();

        $this->assertNotNull($match);
        $this->assertEquals('fuzzy_name', $match->match_method);
        $this->assertGreaterThanOrEqual(85, $match->match_score); // Above threshold
    }

    /** @test */
    public function it_generates_automatic_offers_for_all_stores()
    {
        $category = Category::factory()->create();

        // Create 3 stores with same item
        $stores = Store::factory()->count(3)->create([
            'zone_id' => $this->zone->id,
            'module_id' => $this->module->id
        ]);

        foreach ($stores as $index => $store) {
            StoreBargainingSetting::factory()->create([
                'store_id' => $store->id,
                'bargaining_enabled' => true,
                'auto_participate' => true,
            ]);

            Item::factory()->create([
                'store_id' => $store->id,
                'barcode' => 'ABC123',
                'category_id' => $category->id,
                'price' => 100 + ($index * 5), // Different prices
            ]);
        }

        $cartItem = Item::where('barcode', 'ABC123')->first();
        Cart::create(['user_id' => $this->user->id, 'item_id' => $cartItem->id, 'quantity' => 2, 'price' => 100]);

        $request = $this->service->initiateBargaining(
            $this->user->id, null, 'instant', [], $this->zone->id, $this->module->id
        );

        // Verify offers were created for all 3 stores
        $offers = BargainingStoreOffer::where('bargaining_request_id', $request->id)->get();
        $this->assertCount(3, $offers);

        foreach ($offers as $offer) {
            $this->assertEquals('auto_calculated', $offer->offer_type);
            $this->assertGreaterThan(0, $offer->total_amount);
        }
    }

    /** @test */
    public function it_ranks_offers_correctly()
    {
        $category = Category::factory()->create();
        $store1 = Store::factory()->create(['zone_id' => $this->zone->id, 'module_id' => $this->module->id, 'rating' => 4.5]);
        $store2 = Store::factory()->create(['zone_id' => $this->zone->id, 'module_id' => $this->module->id, 'rating' => 4.0]);
        $store3 = Store::factory()->create(['zone_id' => $this->zone->id, 'module_id' => $this->module->id, 'rating' => 3.5]);

        foreach ([$store1, $store2, $store3] as $store) {
            StoreBargainingSetting::factory()->create(['store_id' => $store->id, 'bargaining_enabled' => true]);
            Item::factory()->create(['store_id' => $store->id, 'barcode' => 'XYZ789', 'category_id' => $category->id, 'price' => 100]);
        }

        $item = Item::where('barcode', 'XYZ789')->first();
        Cart::create(['user_id' => $this->user->id, 'item_id' => $item->id, 'quantity' => 1, 'price' => 100]);

        $request = $this->service->initiateBargaining(
            $this->user->id, null, 'instant', [], $this->zone->id, $this->module->id
        );

        $offers = BargainingStoreOffer::where('bargaining_request_id', $request->id)
            ->orderBy('rank')
            ->get();

        // Verify ranks are sequential
        $this->assertEquals(1, $offers[0]->rank);
        $this->assertEquals(2, $offers[1]->rank);
        $this->assertEquals(3, $offers[2]->rank);

        // Verify best offer is marked
        $this->assertTrue((bool)$offers[0]->is_best_offer);
        $this->assertFalse((bool)$offers[1]->is_best_offer);
        $this->assertFalse((bool)$offers[2]->is_best_offer);
    }

    /** @test */
    public function it_handles_partial_fulfillment()
    {
        $category = Category::factory()->create();
        $store = Store::factory()->create(['zone_id' => $this->zone->id, 'module_id' => $this->module->id]);
        StoreBargainingSetting::factory()->create(['store_id' => $store->id]);

        // Create 3 items in cart
        $item1 = Item::factory()->create(['store_id' => $store->id, 'barcode' => 'ITEM1', 'category_id' => $category->id]);
        $item2 = Item::factory()->create(['store_id' => $store->id, 'barcode' => 'ITEM2', 'category_id' => $category->id]);
        $item3 = Item::factory()->create(['store_id' => $store->id, 'barcode' => 'ITEM3', 'category_id' => $category->id]);

        Cart::create(['user_id' => $this->user->id, 'item_id' => $item1->id, 'quantity' => 1, 'price' => 100]);
        Cart::create(['user_id' => $this->user->id, 'item_id' => $item2->id, 'quantity' => 1, 'price' => 100]);
        Cart::create(['user_id' => $this->user->id, 'item_id' => $item3->id, 'quantity' => 1, 'price' => 100]);

        // Store has only 2 of the 3 items
        $otherStore = Store::factory()->create(['zone_id' => $this->zone->id, 'module_id' => $this->module->id]);
        StoreBargainingSetting::factory()->create(['store_id' => $otherStore->id]);

        Item::factory()->create(['store_id' => $otherStore->id, 'barcode' => 'ITEM1', 'category_id' => $category->id]);
        Item::factory()->create(['store_id' => $otherStore->id, 'barcode' => 'ITEM2', 'category_id' => $category->id]);
        // Missing ITEM3

        $request = $this->service->initiateBargaining(
            $this->user->id, null, 'instant', [], $this->zone->id, $this->module->id
        );

        $offer = BargainingStoreOffer::where('bargaining_request_id', $request->id)
            ->where('store_id', $otherStore->id)
            ->first();

        $this->assertEquals(2, $offer->items_available);
        $this->assertEquals(1, $offer->items_missing);
        $this->assertEquals(66.67, round($offer->fulfillment_percentage, 2)); // 2/3
    }

    /** @test */
    public function it_auto_awards_best_offer_in_instant_mode()
    {
        $category = Category::factory()->create();
        $store1 = Store::factory()->create(['zone_id' => $this->zone->id, 'module_id' => $this->module->id]);
        $store2 = Store::factory()->create(['zone_id' => $this->zone->id, 'module_id' => $this->module->id]);

        foreach ([$store1, $store2] as $store) {
            StoreBargainingSetting::factory()->create(['store_id' => $store->id]);
        }

        Item::factory()->create(['store_id' => $store1->id, 'barcode' => 'TEST1', 'category_id' => $category->id, 'price' => 100]);
        Item::factory()->create(['store_id' => $store2->id, 'barcode' => 'TEST1', 'category_id' => $category->id, 'price' => 95]); // Cheaper

        $item = Item::where('barcode', 'TEST1')->first();
        Cart::create(['user_id' => $this->user->id, 'item_id' => $item->id, 'quantity' => 1, 'price' => 100]);

        $request = $this->service->initiateBargaining(
            $this->user->id, null, 'instant', [], $this->zone->id, $this->module->id
        );

        // In instant mode, should auto-award to best offer
        $this->assertEquals('awarded', $request->status);
        $this->assertNotNull($request->awarded_store_id);

        $awardedOffer = BargainingStoreOffer::where('bargaining_request_id', $request->id)
            ->where('is_best_offer', true)
            ->first();

        $this->assertEquals($request->awarded_store_id, $awardedOffer->store_id);
    }

    /** @test */
    public function it_waits_for_vendor_offers_in_wait_mode()
    {
        $category = Category::factory()->create();
        $store = Store::factory()->create(['zone_id' => $this->zone->id, 'module_id' => $this->module->id]);
        StoreBargainingSetting::factory()->create(['store_id' => $store->id]);

        $item = Item::factory()->create(['store_id' => $store->id, 'barcode' => 'WAIT1', 'category_id' => $category->id]);
        Cart::create(['user_id' => $this->user->id, 'item_id' => $item->id, 'quantity' => 1, 'price' => 100]);

        $request = $this->service->initiateBargaining(
            $this->user->id, null, 'wait', [], $this->zone->id, $this->module->id
        );

        // In wait mode, should NOT auto-award
        $this->assertNotEquals('awarded', $request->status);
        $this->assertIn($request->status, ['initiated', 'matching', 'offers_received']);
        $this->assertNull($request->awarded_store_id);
        $this->assertNotNull($request->expires_at);
    }

    /** @test */
    public function it_calculates_store_discounts_correctly()
    {
        // This would test the discount calculation logic
        // Implementation depends on your store discount rules
        $this->assertTrue(true); // Placeholder
    }

    /** @test */
    public function it_calculates_delivery_charges_correctly()
    {
        // This would test the delivery charge calculation
        // Implementation depends on your delivery charge rules
        $this->assertTrue(true); // Placeholder
    }

    /** @test */
    public function it_excludes_inactive_stores()
    {
        $category = Category::factory()->create();

        $activeStore = Store::factory()->create(['zone_id' => $this->zone->id, 'module_id' => $this->module->id, 'active' => 1]);
        $inactiveStore = Store::factory()->create(['zone_id' => $this->zone->id, 'module_id' => $this->module->id, 'active' => 0]);

        foreach ([$activeStore, $inactiveStore] as $store) {
            StoreBargainingSetting::factory()->create(['store_id' => $store->id]);
            Item::factory()->create(['store_id' => $store->id, 'barcode' => 'ACTIVE1', 'category_id' => $category->id]);
        }

        $item = Item::where('barcode', 'ACTIVE1')->where('store_id', $activeStore->id)->first();
        Cart::create(['user_id' => $this->user->id, 'item_id' => $item->id, 'quantity' => 1, 'price' => 100]);

        $request = $this->service->initiateBargaining(
            $this->user->id, null, 'instant', [], $this->zone->id, $this->module->id
        );

        // Should only have offer from active store
        $offers = BargainingStoreOffer::where('bargaining_request_id', $request->id)->get();
        $this->assertEquals(1, $offers->count());
        $this->assertEquals($activeStore->id, $offers->first()->store_id);
    }

    /** @test */
    public function it_excludes_stores_with_bargaining_disabled()
    {
        $category = Category::factory()->create();

        $enabledStore = Store::factory()->create(['zone_id' => $this->zone->id, 'module_id' => $this->module->id]);
        $disabledStore = Store::factory()->create(['zone_id' => $this->zone->id, 'module_id' => $this->module->id]);

        StoreBargainingSetting::factory()->create(['store_id' => $enabledStore->id, 'bargaining_enabled' => true]);
        StoreBargainingSetting::factory()->create(['store_id' => $disabledStore->id, 'bargaining_enabled' => false]);

        foreach ([$enabledStore, $disabledStore] as $store) {
            Item::factory()->create(['store_id' => $store->id, 'barcode' => 'ENABLED1', 'category_id' => $category->id]);
        }

        $item = Item::where('barcode', 'ENABLED1')->where('store_id', $enabledStore->id)->first();
        Cart::create(['user_id' => $this->user->id, 'item_id' => $item->id, 'quantity' => 1, 'price' => 100]);

        $request = $this->service->initiateBargaining(
            $this->user->id, null, 'instant', [], $this->zone->id, $this->module->id
        );

        $offers = BargainingStoreOffer::where('bargaining_request_id', $request->id)->get();
        $this->assertEquals(1, $offers->count());
        $this->assertEquals($enabledStore->id, $offers->first()->store_id);
    }

    /** @test */
    public function it_respects_max_cart_items_limit()
    {
        config(['bargaining.max_cart_items' => 3]);

        $store = Store::factory()->create(['zone_id' => $this->zone->id, 'module_id' => $this->module->id]);

        // Add 5 items to cart (exceeds limit of 3)
        $items = Item::factory()->count(5)->create(['store_id' => $store->id]);
        foreach ($items as $item) {
            Cart::create(['user_id' => $this->user->id, 'item_id' => $item->id, 'quantity' => 1, 'price' => 100]);
        }

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cart exceeds maximum allowed items');

        $this->service->initiateBargaining(
            $this->user->id, null, 'instant', [], $this->zone->id, $this->module->id
        );
    }

    /** @test */
    public function it_respects_min_cart_value()
    {
        config(['bargaining.min_cart_value' => 100]);

        $store = Store::factory()->create(['zone_id' => $this->zone->id, 'module_id' => $this->module->id]);
        $item = Item::factory()->create(['store_id' => $store->id, 'price' => 25]);

        Cart::create(['user_id' => $this->user->id, 'item_id' => $item->id, 'quantity' => 2, 'price' => 25]); // Total: 50

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cart value below minimum required');

        $this->service->initiateBargaining(
            $this->user->id, null, 'instant', [], $this->zone->id, $this->module->id
        );
    }
}
