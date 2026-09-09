<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Admin;
use App\Models\BargainingRequest;
use App\Models\BargainingStoreOffer;
use App\Models\Store;
use App\Models\StoreBargainingSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class BargainingAdminTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // Create admin user
        $this->admin = Admin::factory()->create([
            'role_id' => 1, // Super admin
        ]);
    }

    /** @test */
    public function admin_can_access_bargaining_dashboard()
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.bargaining.dashboard'));

        $response->assertStatus(200);
        $response->assertViewIs('admin-views.bargaining.dashboard');
        $response->assertViewHas(['stats', 'statusBreakdown', 'modeBreakdown', 'recentRequests', 'topStores', 'dailyTrends']);
    }

    /** @test */
    public function dashboard_displays_correct_statistics()
    {
        // Create test data
        BargainingRequest::factory()->count(5)->create(['status' => 'accepted']);
        BargainingRequest::factory()->count(3)->create(['status' => 'initiated']);
        BargainingRequest::factory()->count(2)->create(['status' => 'cancelled']);

        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.bargaining.dashboard'));

        $response->assertStatus(200);

        $stats = $response->viewData('stats');
        $this->assertEquals(10, $stats['total_requests']);
        $this->assertEquals(3, $stats['active_requests']);
        $this->assertEquals(5, $stats['completed_requests']);
    }

    /** @test */
    public function admin_can_filter_dashboard_by_date_range()
    {
        // Create requests on different dates
        BargainingRequest::factory()->create(['created_at' => now()->subDays(5)]);
        BargainingRequest::factory()->create(['created_at' => now()->subDays(10)]);
        BargainingRequest::factory()->create(['created_at' => now()->subDays(40)]);

        $startDate = now()->subDays(7)->format('Y-m-d');
        $endDate = now()->format('Y-m-d');

        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.bargaining.dashboard', [
                'start_date' => $startDate,
                'end_date' => $endDate
            ]));

        $response->assertStatus(200);

        $stats = $response->viewData('stats');
        $this->assertEquals(1, $stats['total_requests']); // Only 1 request in last 7 days
    }

    /** @test */
    public function admin_can_view_requests_list()
    {
        BargainingRequest::factory()->count(15)->create();

        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.bargaining.requests'));

        $response->assertStatus(200);
        $response->assertViewIs('admin-views.bargaining.requests');
        $response->assertViewHas('requests');
    }

    /** @test */
    public function requests_list_can_be_filtered_by_status()
    {
        BargainingRequest::factory()->count(5)->create(['status' => 'accepted']);
        BargainingRequest::factory()->count(3)->create(['status' => 'initiated']);
        BargainingRequest::factory()->count(2)->create(['status' => 'cancelled']);

        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.bargaining.requests', ['status' => 'accepted']));

        $response->assertStatus(200);
        $requests = $response->viewData('requests');
        $this->assertEquals(5, $requests->total());
    }

    /** @test */
    public function requests_list_can_be_filtered_by_mode()
    {
        BargainingRequest::factory()->count(7)->create(['mode' => 'instant']);
        BargainingRequest::factory()->count(3)->create(['mode' => 'wait']);

        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.bargaining.requests', ['mode' => 'instant']));

        $response->assertStatus(200);
        $requests = $response->viewData('requests');
        $this->assertEquals(7, $requests->total());
    }

    /** @test */
    public function requests_list_can_be_searched_by_code()
    {
        $request1 = BargainingRequest::factory()->create(['request_code' => 'BR-123456']);
        $request2 = BargainingRequest::factory()->create(['request_code' => 'BR-789012']);

        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.bargaining.requests', ['search' => 'BR-123']));

        $response->assertStatus(200);
        $requests = $response->viewData('requests');
        $this->assertEquals(1, $requests->total());
        $this->assertEquals('BR-123456', $requests->first()->request_code);
    }

    /** @test */
    public function admin_can_view_request_details()
    {
        $request = BargainingRequest::factory()->create();

        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.bargaining.request-details', $request->id));

        $response->assertStatus(200);
        $response->assertViewIs('admin-views.bargaining.request-details');
        $response->assertViewHas('request');

        $viewRequest = $response->viewData('request');
        $this->assertEquals($request->id, $viewRequest->id);
    }

    /** @test */
    public function request_details_shows_all_relationships()
    {
        $request = BargainingRequest::factory()
            ->has(BargainingCartItem::factory()->count(3), 'cartItems')
            ->has(BargainingStoreOffer::factory()->count(5), 'storeOffers')
            ->create();

        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.bargaining.request-details', $request->id));

        $response->assertStatus(200);

        $viewRequest = $response->viewData('request');
        $this->assertCount(3, $viewRequest->cartItems);
        $this->assertCount(5, $viewRequest->storeOffers);
    }

    /** @test */
    public function admin_can_cancel_active_request()
    {
        $request = BargainingRequest::factory()->create(['status' => 'offers_received']);

        $response = $this->actingAs($this->admin, 'admin')
            ->postJson(route('admin.bargaining.cancel-request', $request->id));

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Request cancelled successfully']);

        $this->assertDatabaseHas('bargaining_requests', [
            'id' => $request->id,
            'status' => 'cancelled'
        ]);
    }

    /** @test */
    public function admin_cannot_cancel_completed_request()
    {
        $request = BargainingRequest::factory()->create(['status' => 'accepted']);

        $response = $this->actingAs($this->admin, 'admin')
            ->postJson(route('admin.bargaining.cancel-request', $request->id));

        $response->assertStatus(400);
        $response->assertJson(['error' => 'Cannot cancel request in current status']);
    }

    /** @test */
    public function admin_can_access_analytics_page()
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.bargaining.analytics'));

        $response->assertStatus(200);
        $response->assertViewIs('admin-views.bargaining.analytics');
        $response->assertViewHas([
            'totalRequests',
            'completedRequests',
            'conversionRate',
            'avgSavings',
            'avgSavingsPercentage',
            'storePerformance',
            'popularItems',
            'fulfillmentStats',
            'peakHours'
        ]);
    }

    /** @test */
    public function analytics_calculates_conversion_rate_correctly()
    {
        BargainingRequest::factory()->count(10)->create(['status' => 'initiated']);
        BargainingRequest::factory()->count(7)->create(['status' => 'accepted']);

        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.bargaining.analytics', ['period' => '30days']));

        $response->assertStatus(200);

        $conversionRate = $response->viewData('conversionRate');
        $this->assertEquals(41.18, round($conversionRate, 2)); // 7 / 17 * 100
    }

    /** @test */
    public function analytics_can_filter_by_period()
    {
        // Create requests across different time periods
        BargainingRequest::factory()->create(['created_at' => now()->subDays(5)]);
        BargainingRequest::factory()->create(['created_at' => now()->subDays(50)]);
        BargainingRequest::factory()->create(['created_at' => now()->subDays(100)]);

        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.bargaining.analytics', ['period' => '7days']));

        $response->assertStatus(200);
        $totalRequests = $response->viewData('totalRequests');
        $this->assertEquals(1, $totalRequests); // Only 1 in last 7 days
    }

    /** @test */
    public function admin_can_access_settings_page()
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.bargaining.settings'));

        $response->assertStatus(200);
        $response->assertViewIs('admin-views.bargaining.settings');
        $response->assertViewHas(['config', 'storeSettings']);
    }

    /** @test */
    public function admin_can_update_global_settings()
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->post(route('admin.bargaining.update-settings'), [
                'enabled' => true,
                'instant_mode_enabled' => true,
                'wait_mode_enabled' => false,
                'wait_duration' => 120,
                'max_requests_per_user_per_day' => 15,
                'min_cart_value' => 100,
                'max_cart_items' => 25,
                'fuzzy_similarity_threshold' => 90,
            ]);

        $response->assertRedirect(route('admin.bargaining.settings'));
        $response->assertSessionHas('success');
    }

    /** @test */
    public function global_settings_validation_works()
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->post(route('admin.bargaining.update-settings'), [
                'enabled' => true,
                'wait_duration' => 400, // Invalid: max 300
                'max_requests_per_user_per_day' => 150, // Invalid: max 100
            ]);

        $response->assertSessionHasErrors(['wait_duration', 'max_requests_per_user_per_day']);
    }

    /** @test */
    public function admin_can_update_store_settings()
    {
        $store = Store::factory()->create();
        $setting = StoreBargainingSetting::factory()->create(['store_id' => $store->id]);

        $response = $this->actingAs($this->admin, 'admin')
            ->postJson(route('admin.bargaining.update-store-settings', $store->id), [
                'bargaining_enabled' => true,
                'auto_participate' => true,
                'manual_bidding_enabled' => true,
                'auto_discount_percentage' => 10.5,
            ]);

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Store settings updated successfully']);

        $this->assertDatabaseHas('store_bargaining_settings', [
            'store_id' => $store->id,
            'bargaining_enabled' => 1,
            'auto_participate' => 1,
            'manual_bidding_enabled' => 1,
            'auto_discount_percentage' => 10.5,
        ]);
    }

    /** @test */
    public function store_settings_validation_works()
    {
        $store = Store::factory()->create();
        StoreBargainingSetting::factory()->create(['store_id' => $store->id]);

        $response = $this->actingAs($this->admin, 'admin')
            ->postJson(route('admin.bargaining.update-store-settings', $store->id), [
                'bargaining_enabled' => 'invalid', // Should be boolean
                'auto_discount_percentage' => 150, // Invalid: max 100
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['bargaining_enabled', 'auto_discount_percentage']);
    }

    /** @test */
    public function admin_can_export_requests_to_csv()
    {
        BargainingRequest::factory()->count(5)->create();

        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.bargaining.export', ['format' => 'csv']));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('attachment', $response->headers->get('content-disposition'));
    }

    /** @test */
    public function csv_export_contains_correct_headers()
    {
        BargainingRequest::factory()->create();

        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.bargaining.export', ['format' => 'csv']));

        $content = $response->getContent();
        $this->assertStringContainsString('Request Code', $content);
        $this->assertStringContainsString('Status', $content);
        $this->assertStringContainsString('Mode', $content);
        $this->assertStringContainsString('Total Items', $content);
        $this->assertStringContainsString('Original Value', $content);
    }

    /** @test */
    public function csv_export_can_filter_by_period()
    {
        BargainingRequest::factory()->create(['created_at' => now()->subDays(5)]);
        BargainingRequest::factory()->create(['created_at' => now()->subDays(50)]);

        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.bargaining.export', [
                'format' => 'csv',
                'period' => '7days'
            ]));

        $response->assertStatus(200);
        // Should only contain 1 record (within 7 days)
        $lines = explode("\n", $response->getContent());
        $this->assertCount(3, $lines); // Header + 1 data row + empty line
    }

    /** @test */
    public function unauthenticated_user_cannot_access_admin_panel()
    {
        $response = $this->get(route('admin.bargaining.dashboard'));
        $response->assertRedirect(route('admin.auth.login'));
    }

    /** @test */
    public function non_admin_user_cannot_access_admin_panel()
    {
        $customer = User::factory()->create();

        $response = $this->actingAs($customer)
            ->get(route('admin.bargaining.dashboard'));

        $response->assertStatus(403); // Or redirect based on your auth setup
    }
}
