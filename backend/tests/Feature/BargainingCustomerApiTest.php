<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Cart;
use App\Models\Item;
use App\Models\Store;
use App\Models\Zone;
use App\Models\Module;
use App\Models\BargainingRequest;
use App\Models\BargainingStoreOffer;
use App\Services\BargainingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class BargainingCustomerApiTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $zone;
    protected $module;
    protected $headers;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test user
        $this->user = User::factory()->create();

        // Create zone and module
        $this->zone = Zone::factory()->create();
        $this->module = Module::factory()->create();

        // Set headers
        $this->headers = [
            'Authorization' => 'Bearer ' . $this->user->createToken('test')->plainTextToken,
            'zoneId' => json_encode([$this->zone->id]),
            'moduleId' => $this->module->id,
            'Accept' => 'application/json',
        ];

        // Enable bargaining mode
        config(['bargaining.enabled' => true]);
        config(['bargaining.instant_mode_enabled' => true]);
        config(['bargaining.wait_mode_enabled' => true]);
    }

    /** @test */
    public function user_can_initiate_bargaining_in_instant_mode()
    {
        // Create cart items
        $store = Store::factory()->create(['zone_id' => $this->zone->id, 'module_id' => $this->module->id]);
        $items = Item::factory()->count(3)->create(['store_id' => $store->id]);

        foreach ($items as $item) {
            Cart::create([
                'user_id' => $this->user->id,
                'item_id' => $item->id,
                'quantity' => 2,
                'price' => $item->price,
            ]);
        }

        $response = $this->withHeaders($this->headers)
            ->postJson('/api/v2/bargaining/initiate', [
                'mode' => 'instant',
                'latitude' => 12.9716,
                'longitude' => 77.5946,
            ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'request_code',
            'status',
            'total_cart_items',
            'wait_duration',
            'expires_at'
        ]);

        $this->assertDatabaseHas('bargaining_requests', [
            'user_id' => $this->user->id,
            'mode' => 'instant',
            'zone_id' => $this->zone->id,
            'module_id' => $this->module->id,
        ]);
    }

    /** @test */
    public function user_can_initiate_bargaining_in_wait_mode()
    {
        $store = Store::factory()->create(['zone_id' => $this->zone->id, 'module_id' => $this->module->id]);
        $item = Item::factory()->create(['store_id' => $store->id]);

        Cart::create([
            'user_id' => $this->user->id,
            'item_id' => $item->id,
            'quantity' => 1,
            'price' => $item->price,
        ]);

        $response = $this->withHeaders($this->headers)
            ->postJson('/api/v2/bargaining/initiate', [
                'mode' => 'wait',
                'latitude' => 12.9716,
                'longitude' => 77.5946,
            ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'initiated']);

        $this->assertDatabaseHas('bargaining_requests', [
            'user_id' => $this->user->id,
            'mode' => 'wait',
        ]);
    }

    /** @test */
    public function initiate_requires_non_empty_cart()
    {
        $response = $this->withHeaders($this->headers)
            ->postJson('/api/v2/bargaining/initiate', [
                'mode' => 'instant',
            ]);

        $response->assertStatus(400);
        $response->assertJson(['errors' => [['message' => 'Your cart is empty']]]);
    }

    /** @test */
    public function initiate_validates_mode_parameter()
    {
        $response = $this->withHeaders($this->headers)
            ->postJson('/api/v2/bargaining/initiate', [
                'mode' => 'invalid_mode',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['mode']);
    }

    /** @test */
    public function initiate_enforces_rate_limiting()
    {
        $store = Store::factory()->create(['zone_id' => $this->zone->id, 'module_id' => $this->module->id]);
        $item = Item::factory()->create(['store_id' => $store->id]);

        // Create max requests for today
        $maxRequests = config('bargaining.max_requests_per_user_per_day', 10);
        BargainingRequest::factory()->count($maxRequests)->create([
            'user_id' => $this->user->id,
            'created_at' => now(),
        ]);

        Cart::create([
            'user_id' => $this->user->id,
            'item_id' => $item->id,
            'quantity' => 1,
            'price' => $item->price,
        ]);

        $response = $this->withHeaders($this->headers)
            ->postJson('/api/v2/bargaining/initiate', [
                'mode' => 'instant',
            ]);

        $response->assertStatus(429);
        $response->assertJson(['errors' => [['message' => 'Rate limit exceeded']]]);
    }

    /** @test */
    public function user_can_get_bargaining_status()
    {
        $request = BargainingRequest::factory()->create([
            'user_id' => $this->user->id,
            'request_code' => 'BR-123456',
            'status' => 'offers_received',
        ]);

        $response = $this->withHeaders($this->headers)
            ->getJson("/api/v2/bargaining/status/{$request->request_code}");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'total_stores_matched',
            'total_offers',
            'best_offer',
            'all_offers',
            'expires_at'
        ]);
    }

    /** @test */
    public function status_includes_best_offer_details()
    {
        $request = BargainingRequest::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'offers_received',
        ]);

        $store = Store::factory()->create();
        $bestOffer = BargainingStoreOffer::factory()->create([
            'bargaining_request_id' => $request->id,
            'store_id' => $store->id,
            'rank' => 1,
            'is_best_offer' => true,
            'total_amount' => 450.00,
            'fulfillment_percentage' => 100,
        ]);

        $response = $this->withHeaders($this->headers)
            ->getJson("/api/v2/bargaining/status/{$request->request_code}");

        $response->assertStatus(200);
        $response->assertJson([
            'best_offer' => [
                'store_id' => $store->id,
                'total_amount' => 450.00,
                'rank' => 1,
                'fulfillment_percentage' => 100,
            ]
        ]);
    }

    /** @test */
    public function user_cannot_view_other_users_bargaining_status()
    {
        $otherUser = User::factory()->create();
        $request = BargainingRequest::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->withHeaders($this->headers)
            ->getJson("/api/v2/bargaining/status/{$request->request_code}");

        $response->assertStatus(403);
    }

    /** @test */
    public function user_can_accept_offer()
    {
        $request = BargainingRequest::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'offers_received',
        ]);

        $store = Store::factory()->create();
        $offer = BargainingStoreOffer::factory()->create([
            'bargaining_request_id' => $request->id,
            'store_id' => $store->id,
            'is_best_offer' => true,
        ]);

        $response = $this->withHeaders($this->headers)
            ->postJson('/api/v2/bargaining/accept-offer', [
                'request_code' => $request->request_code,
                'offer_id' => $offer->id,
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Offer accepted - cart updated',
            'store_id' => $store->id,
            'ready_to_order' => true,
        ]);

        $this->assertDatabaseHas('bargaining_requests', [
            'id' => $request->id,
            'status' => 'accepted',
            'awarded_store_id' => $store->id,
        ]);
    }

    /** @test */
    public function accept_offer_validates_request_ownership()
    {
        $otherUser = User::factory()->create();
        $request = BargainingRequest::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $offer = BargainingStoreOffer::factory()->create([
            'bargaining_request_id' => $request->id,
        ]);

        $response = $this->withHeaders($this->headers)
            ->postJson('/api/v2/bargaining/accept-offer', [
                'request_code' => $request->request_code,
                'offer_id' => $offer->id,
            ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function accept_offer_validates_offer_belongs_to_request()
    {
        $request = BargainingRequest::factory()->create(['user_id' => $this->user->id]);
        $otherRequest = BargainingRequest::factory()->create();
        $offer = BargainingStoreOffer::factory()->create([
            'bargaining_request_id' => $otherRequest->id,
        ]);

        $response = $this->withHeaders($this->headers)
            ->postJson('/api/v2/bargaining/accept-offer', [
                'request_code' => $request->request_code,
                'offer_id' => $offer->id,
            ]);

        $response->assertStatus(400);
        $response->assertJson(['errors' => [['message' => 'Offer does not belong to this request']]]);
    }

    /** @test */
    public function user_can_cancel_bargaining()
    {
        $request = BargainingRequest::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'offers_received',
        ]);

        $response = $this->withHeaders($this->headers)
            ->postJson("/api/v2/bargaining/cancel/{$request->request_code}");

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Bargaining cancelled successfully']);

        $this->assertDatabaseHas('bargaining_requests', [
            'id' => $request->id,
            'status' => 'cancelled',
        ]);
    }

    /** @test */
    public function user_cannot_cancel_accepted_bargaining()
    {
        $request = BargainingRequest::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'accepted',
        ]);

        $response = $this->withHeaders($this->headers)
            ->postJson("/api/v2/bargaining/cancel/{$request->request_code}");

        $response->assertStatus(400);
        $response->assertJson(['errors' => [['message' => 'Cannot cancel at this stage']]]);
    }

    /** @test */
    public function bargaining_disabled_returns_503()
    {
        config(['bargaining.enabled' => false]);

        $response = $this->withHeaders($this->headers)
            ->postJson('/api/v2/bargaining/initiate', [
                'mode' => 'instant',
            ]);

        $response->assertStatus(503);
        $response->assertJson(['message' => 'Bargaining mode is currently unavailable']);
    }

    /** @test */
    public function instant_mode_disabled_returns_400()
    {
        config(['bargaining.instant_mode_enabled' => false]);

        $store = Store::factory()->create(['zone_id' => $this->zone->id]);
        $item = Item::factory()->create(['store_id' => $store->id]);
        Cart::create(['user_id' => $this->user->id, 'item_id' => $item->id, 'quantity' => 1, 'price' => $item->price]);

        $response = $this->withHeaders($this->headers)
            ->postJson('/api/v2/bargaining/initiate', [
                'mode' => 'instant',
            ]);

        $response->assertStatus(400);
        $response->assertJson(['errors' => [['message' => 'Instant mode is not available']]]);
    }

    /** @test */
    public function guest_users_can_initiate_bargaining()
    {
        $guestId = 'guest-' . uniqid();

        $store = Store::factory()->create(['zone_id' => $this->zone->id, 'module_id' => $this->module->id]);
        $item = Item::factory()->create(['store_id' => $store->id]);

        Cart::create([
            'guest_id' => $guestId,
            'item_id' => $item->id,
            'quantity' => 1,
            'price' => $item->price,
        ]);

        // Remove auth header for guest
        unset($this->headers['Authorization']);

        $response = $this->withHeaders($this->headers)
            ->postJson('/api/v2/bargaining/initiate', [
                'mode' => 'instant',
                'guest_id' => $guestId,
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('bargaining_requests', [
            'guest_id' => $guestId,
            'user_id' => null,
        ]);
    }

    /** @test */
    public function expired_requests_cannot_accept_offers()
    {
        $request = BargainingRequest::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'offers_received',
            'expires_at' => now()->subMinutes(5), // Expired
        ]);

        $offer = BargainingStoreOffer::factory()->create([
            'bargaining_request_id' => $request->id,
        ]);

        $response = $this->withHeaders($this->headers)
            ->postJson('/api/v2/bargaining/accept-offer', [
                'request_code' => $request->request_code,
                'offer_id' => $offer->id,
            ]);

        $response->assertStatus(400);
        $response->assertJson(['errors' => [['message' => 'Offer window has closed']]]);
    }

    /** @test */
    public function initiate_requires_valid_zone_header()
    {
        $invalidHeaders = $this->headers;
        $invalidHeaders['zoneId'] = json_encode([9999]); // Non-existent zone

        $response = $this->withHeaders($invalidHeaders)
            ->postJson('/api/v2/bargaining/initiate', [
                'mode' => 'instant',
            ]);

        $response->assertStatus(400);
    }

    /** @test */
    public function initiate_requires_valid_module_header()
    {
        $invalidHeaders = $this->headers;
        $invalidHeaders['moduleId'] = 9999; // Non-existent module

        $response = $this->withHeaders($invalidHeaders)
            ->postJson('/api/v2/bargaining/initiate', [
                'mode' => 'instant',
            ]);

        $response->assertStatus(400);
    }
}
