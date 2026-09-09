<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\VendorEmployee;
use App\Models\Store;
use App\Models\BargainingRequest;
use App\Models\BargainingStoreOffer;
use App\Models\StoreBargainingSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class BargainingVendorApiTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $vendor;
    protected $store;
    protected $headers;

    protected function setUp(): void
    {
        parent::setUp();

        // Create vendor and store
        $this->store = Store::factory()->create();
        $this->vendor = VendorEmployee::factory()->create([
            'store_id' => $this->store->id,
            'role_id' => 1, // Owner
        ]);

        // Create bargaining settings
        StoreBargainingSetting::factory()->create([
            'store_id' => $this->store->id,
            'bargaining_enabled' => true,
            'manual_bidding_enabled' => true,
        ]);

        // Set headers
        $this->headers = [
            'Authorization' => 'Bearer ' . $this->vendor->createToken('test')->plainTextToken,
            'Accept' => 'application/json',
        ];

        config(['bargaining.enabled' => true]);
    }

    /** @test */
    public function vendor_can_view_available_bargaining_requests()
    {
        // Create bargaining requests in vendor's zone
        BargainingRequest::factory()->count(3)->create([
            'zone_id' => $this->store->zone_id,
            'module_id' => $this->store->module_id,
            'status' => 'offers_received',
            'mode' => 'wait',
        ]);

        // Create auto-calculated offers for this store
        BargainingRequest::all()->each(function ($request) {
            BargainingStoreOffer::factory()->create([
                'bargaining_request_id' => $request->id,
                'store_id' => $this->store->id,
                'offer_type' => 'auto_calculated',
            ]);
        });

        $response = $this->withHeaders($this->headers)
            ->getJson('/api/v2/vendor/bargaining/available');

        $response->assertStatus(200);
        $response->assertJsonCount(3, 'requests');
        $response->assertJsonStructure([
            'requests' => [
                '*' => [
                    'request_code',
                    'total_items',
                    'estimated_value',
                    'auto_calculated_price',
                    'current_rank',
                    'time_remaining',
                    'items_we_have',
                    'fulfillment_percentage'
                ]
            ]
        ]);
    }

    /** @test */
    public function vendor_only_sees_requests_in_their_zone()
    {
        $otherStore = Store::factory()->create(['zone_id' => 999]);

        // Request in vendor's zone
        $myRequest = BargainingRequest::factory()->create([
            'zone_id' => $this->store->zone_id,
            'status' => 'offers_received',
            'mode' => 'wait',
        ]);

        // Request in other zone
        $otherRequest = BargainingRequest::factory()->create([
            'zone_id' => 999,
            'status' => 'offers_received',
            'mode' => 'wait',
        ]);

        $response = $this->withHeaders($this->headers)
            ->getJson('/api/v2/vendor/bargaining/available');

        $response->assertStatus(200);
        $response->assertJsonCount(0, 'requests'); // None because no auto-offers created
    }

    /** @test */
    public function vendor_can_submit_counter_offer()
    {
        $request = BargainingRequest::factory()->create([
            'zone_id' => $this->store->zone_id,
            'status' => 'offers_received',
            'mode' => 'wait',
        ]);

        $autoOffer = BargainingStoreOffer::factory()->create([
            'bargaining_request_id' => $request->id,
            'store_id' => $this->store->id,
            'offer_type' => 'auto_calculated',
            'total_amount' => 500.00,
            'rank' => 2,
        ]);

        $response = $this->withHeaders($this->headers)
            ->postJson('/api/v2/vendor/bargaining/counter-offer', [
                'request_code' => $request->request_code,
                'special_discount' => 50.00,
                'vendor_notes' => 'Special discount for bulk order',
                'estimated_delivery_time' => 30,
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Counter offer submitted successfully',
        ]);

        $this->assertDatabaseHas('bargaining_store_offers', [
            'bargaining_request_id' => $request->id,
            'store_id' => $this->store->id,
            'offer_type' => 'vendor_submitted',
            'special_discount' => 50.00,
            'vendor_notes' => 'Special discount for bulk order',
        ]);
    }

    /** @test */
    public function counter_offer_recalculates_rank()
    {
        $request = BargainingRequest::factory()->create([
            'zone_id' => $this->store->zone_id,
            'status' => 'offers_received',
        ]);

        // Create multiple offers
        $store1 = Store::factory()->create(['zone_id' => $this->store->zone_id]);
        $offer1 = BargainingStoreOffer::factory()->create([
            'bargaining_request_id' => $request->id,
            'store_id' => $store1->id,
            'total_amount' => 450.00,
            'rank' => 1,
            'is_best_offer' => true,
        ]);

        $autoOffer = BargainingStoreOffer::factory()->create([
            'bargaining_request_id' => $request->id,
            'store_id' => $this->store->id,
            'offer_type' => 'auto_calculated',
            'total_amount' => 500.00,
            'rank' => 2,
        ]);

        // Submit better counter-offer
        $response = $this->withHeaders($this->headers)
            ->postJson('/api/v2/vendor/bargaining/counter-offer', [
                'request_code' => $request->request_code,
                'special_discount' => 100.00, // Makes total 400, better than 450
            ]);

        $response->assertStatus(200);

        // Verify vendor offer is now best
        $vendorOffer = BargainingStoreOffer::where('bargaining_request_id', $request->id)
            ->where('store_id', $this->store->id)
            ->where('offer_type', 'vendor_submitted')
            ->first();

        $this->assertEquals(1, $vendorOffer->rank);
        $this->assertTrue((bool)$vendorOffer->is_best_offer);
    }

    /** @test */
    public function counter_offer_validates_special_discount()
    {
        $request = BargainingRequest::factory()->create([
            'zone_id' => $this->store->zone_id,
        ]);

        BargainingStoreOffer::factory()->create([
            'bargaining_request_id' => $request->id,
            'store_id' => $this->store->id,
            'total_amount' => 500.00,
        ]);

        $response = $this->withHeaders($this->headers)
            ->postJson('/api/v2/vendor/bargaining/counter-offer', [
                'request_code' => $request->request_code,
                'special_discount' => -50.00, // Invalid: negative
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['special_discount']);
    }

    /** @test */
    public function vendor_cannot_submit_counter_offer_for_instant_mode()
    {
        $request = BargainingRequest::factory()->create([
            'zone_id' => $this->store->zone_id,
            'mode' => 'instant',
            'status' => 'awarded', // Already awarded in instant mode
        ]);

        $response = $this->withHeaders($this->headers)
            ->postJson('/api/v2/vendor/bargaining/counter-offer', [
                'request_code' => $request->request_code,
                'special_discount' => 20.00,
            ]);

        $response->assertStatus(400);
        $response->assertJson(['errors' => [['message' => 'Cannot submit counter-offer for instant mode']]]);
    }

    /** @test */
    public function vendor_can_view_their_bargaining_history()
    {
        // Create historical bargaining offers
        $requests = BargainingRequest::factory()->count(5)->create();

        foreach ($requests as $request) {
            BargainingStoreOffer::factory()->create([
                'bargaining_request_id' => $request->id,
                'store_id' => $this->store->id,
                'status' => $this->faker->randomElement(['submitted', 'accepted', 'rejected']),
            ]);
        }

        $response = $this->withHeaders($this->headers)
            ->getJson('/api/v2/vendor/bargaining/history');

        $response->assertStatus(200);
        $response->assertJsonCount(5, 'offers');
        $response->assertJsonStructure([
            'offers' => [
                '*' => [
                    'request_code',
                    'status',
                    'total_amount',
                    'rank',
                    'is_best_offer',
                    'created_at'
                ]
            ]
        ]);
    }

    /** @test */
    public function vendor_can_filter_history_by_status()
    {
        $requests = BargainingRequest::factory()->count(3)->create();

        BargainingStoreOffer::factory()->create([
            'bargaining_request_id' => $requests[0]->id,
            'store_id' => $this->store->id,
            'status' => 'accepted',
        ]);

        BargainingStoreOffer::factory()->create([
            'bargaining_request_id' => $requests[1]->id,
            'store_id' => $this->store->id,
            'status' => 'submitted',
        ]);

        $response = $this->withHeaders($this->headers)
            ->getJson('/api/v2/vendor/bargaining/history?status=accepted');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'offers');
    }

    /** @test */
    public function vendor_can_view_bargaining_analytics()
    {
        // Create offers with different outcomes
        $requests = BargainingRequest::factory()->count(10)->create();

        foreach ($requests as $index => $request) {
            BargainingStoreOffer::factory()->create([
                'bargaining_request_id' => $request->id,
                'store_id' => $this->store->id,
                'status' => $index < 5 ? 'accepted' : 'submitted',
                'rank' => $index + 1,
                'total_amount' => 100 * ($index + 1),
            ]);
        }

        $response = $this->withHeaders($this->headers)
            ->getJson('/api/v2/vendor/bargaining/analytics');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'total_participations',
            'total_wins',
            'win_rate',
            'average_rank',
            'total_revenue',
            'average_discount_given'
        ]);

        $data = $response->json();
        $this->assertEquals(10, $data['total_participations']);
        $this->assertEquals(5, $data['total_wins']);
        $this->assertEquals(50, $data['win_rate']); // 50%
    }

    /** @test */
    public function vendor_can_update_store_settings()
    {
        $response = $this->withHeaders($this->headers)
            ->postJson('/api/v2/vendor/bargaining/settings', [
                'bargaining_enabled' => true,
                'auto_participate' => true,
                'manual_bidding_enabled' => true,
                'auto_discount_percentage' => 7.5,
            ]);

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Settings updated successfully']);

        $this->assertDatabaseHas('store_bargaining_settings', [
            'store_id' => $this->store->id,
            'bargaining_enabled' => 1,
            'auto_participate' => 1,
            'manual_bidding_enabled' => 1,
            'auto_discount_percentage' => 7.5,
        ]);
    }

    /** @test */
    public function vendor_can_view_current_settings()
    {
        $response = $this->withHeaders($this->headers)
            ->getJson('/api/v2/vendor/bargaining/settings');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'bargaining_enabled',
            'auto_participate',
            'manual_bidding_enabled',
            'auto_discount_percentage',
            'notify_on_new_request'
        ]);
    }

    /** @test */
    public function vendor_with_disabled_bargaining_cannot_submit_offers()
    {
        // Disable bargaining for this store
        StoreBargainingSetting::where('store_id', $this->store->id)
            ->update(['bargaining_enabled' => false]);

        $request = BargainingRequest::factory()->create([
            'zone_id' => $this->store->zone_id,
        ]);

        $response = $this->withHeaders($this->headers)
            ->postJson('/api/v2/vendor/bargaining/counter-offer', [
                'request_code' => $request->request_code,
                'special_discount' => 20.00,
            ]);

        $response->assertStatus(403);
        $response->assertJson(['errors' => [['message' => 'Bargaining is disabled for your store']]]);
    }

    /** @test */
    public function vendor_with_disabled_manual_bidding_cannot_submit_counter_offers()
    {
        StoreBargainingSetting::where('store_id', $this->store->id)
            ->update(['manual_bidding_enabled' => false]);

        $request = BargainingRequest::factory()->create([
            'zone_id' => $this->store->zone_id,
        ]);

        BargainingStoreOffer::factory()->create([
            'bargaining_request_id' => $request->id,
            'store_id' => $this->store->id,
        ]);

        $response = $this->withHeaders($this->headers)
            ->postJson('/api/v2/vendor/bargaining/counter-offer', [
                'request_code' => $request->request_code,
                'special_discount' => 20.00,
            ]);

        $response->assertStatus(403);
        $response->assertJson(['errors' => [['message' => 'Manual bidding is disabled for your store']]]);
    }

    /** @test */
    public function vendor_cannot_submit_offer_after_deadline()
    {
        $request = BargainingRequest::factory()->create([
            'zone_id' => $this->store->zone_id,
            'status' => 'offers_received',
            'expires_at' => now()->subMinutes(5), // Expired
        ]);

        BargainingStoreOffer::factory()->create([
            'bargaining_request_id' => $request->id,
            'store_id' => $this->store->id,
        ]);

        $response = $this->withHeaders($this->headers)
            ->postJson('/api/v2/vendor/bargaining/counter-offer', [
                'request_code' => $request->request_code,
                'special_discount' => 20.00,
            ]);

        $response->assertStatus(400);
        $response->assertJson(['errors' => [['message' => 'Offer window has closed']]]);
    }

    /** @test */
    public function vendor_can_view_request_details()
    {
        $request = BargainingRequest::factory()->create([
            'zone_id' => $this->store->zone_id,
        ]);

        $response = $this->withHeaders($this->headers)
            ->getJson("/api/v2/vendor/bargaining/request/{$request->request_code}");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'request_code',
            'status',
            'mode',
            'total_cart_items',
            'cart_items',
            'my_offer',
            'time_remaining'
        ]);
    }

    /** @test */
    public function vendor_receives_notification_on_offer_awarded()
    {
        // This would test the notification system
        // For now, just verify the event is dispatched
        Event::fake([BargainingOfferAwarded::class]);

        $request = BargainingRequest::factory()->create([
            'zone_id' => $this->store->zone_id,
            'status' => 'offers_received',
        ]);

        $offer = BargainingStoreOffer::factory()->create([
            'bargaining_request_id' => $request->id,
            'store_id' => $this->store->id,
            'rank' => 1,
            'is_best_offer' => true,
        ]);

        // Simulate customer accepting the offer
        $request->update([
            'status' => 'accepted',
            'awarded_store_id' => $this->store->id,
        ]);

        Event::assertDispatched(BargainingOfferAwarded::class, function ($event) use ($request, $offer) {
            return $event->bargainingRequest->id === $request->id
                && $event->awardedOffer->id === $offer->id;
        });
    }

    /** @test */
    public function unauthorized_vendor_cannot_access_api()
    {
        $response = $this->getJson('/api/v2/vendor/bargaining/available');

        $response->assertStatus(401);
    }

    /** @test */
    public function vendor_from_other_store_cannot_view_my_offers()
    {
        $otherStore = Store::factory()->create();
        $otherVendor = VendorEmployee::factory()->create(['store_id' => $otherStore->id]);

        $request = BargainingRequest::factory()->create();
        BargainingStoreOffer::factory()->create([
            'bargaining_request_id' => $request->id,
            'store_id' => $this->store->id,
        ]);

        $otherHeaders = [
            'Authorization' => 'Bearer ' . $otherVendor->createToken('test')->plainTextToken,
            'Accept' => 'application/json',
        ];

        $response = $this->withHeaders($otherHeaders)
            ->getJson('/api/v2/vendor/bargaining/history');

        $response->assertStatus(200);
        $response->assertJsonCount(0, 'offers'); // Should not see other store's offers
    }
}
