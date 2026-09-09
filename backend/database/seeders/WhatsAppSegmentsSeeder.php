<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Services\CustomerSegmentationService;

class WhatsAppSegmentsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->command->info('Seeding WhatsApp customer segments...');

        $segments = [
            [
                'name' => 'Inactive 7 days',
                'slug' => 'inactive_7d',
                'type' => 'predefined',
                'filters' => json_encode([
                    'last_order_days_ago' => ['min' => 7, 'max' => 14]
                ]),
                'customer_count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Inactive 15 days',
                'slug' => 'inactive_15d',
                'type' => 'predefined',
                'filters' => json_encode([
                    'last_order_days_ago' => ['min' => 15, 'max' => 29]
                ]),
                'customer_count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Inactive 30 days',
                'slug' => 'inactive_30d',
                'type' => 'predefined',
                'filters' => json_encode([
                    'last_order_days_ago' => ['min' => 30, 'max' => 59]
                ]),
                'customer_count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Inactive 60+ days (churned)',
                'slug' => 'inactive_60d',
                'type' => 'predefined',
                'filters' => json_encode([
                    'last_order_days_ago' => ['min' => 60]
                ]),
                'customer_count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'VIP Customers',
                'slug' => 'vip',
                'type' => 'predefined',
                'filters' => json_encode([
                    'lifetime_spend' => ['min' => 10000]
                ]),
                'customer_count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'High Spenders',
                'slug' => 'high_spenders',
                'type' => 'predefined',
                'filters' => json_encode([
                    'lifetime_spend' => ['min' => 5000, 'max' => 9999]
                ]),
                'customer_count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'At Risk Customers',
                'slug' => 'at_risk',
                'type' => 'predefined',
                'filters' => json_encode([
                    'order_count' => ['min' => 3],
                    'last_order_days_ago' => ['min' => 14]
                ]),
                'customer_count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Frequent Buyers',
                'slug' => 'frequent_buyers',
                'type' => 'predefined',
                'filters' => json_encode([
                    'orders_last_30_days' => ['min' => 5]
                ]),
                'customer_count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'One-Time Buyers',
                'slug' => 'one_time_buyers',
                'type' => 'predefined',
                'filters' => json_encode([
                    'order_count' => ['exact' => 1]
                ]),
                'customer_count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'New Customers',
                'slug' => 'new_customers',
                'type' => 'predefined',
                'filters' => json_encode([
                    'registration_days_ago' => ['max' => 7]
                ]),
                'customer_count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => '5th Order Milestone',
                'slug' => 'milestone_5th',
                'type' => 'predefined',
                'filters' => json_encode([
                    'order_count' => ['exact' => 5],
                    'last_order_days_ago' => ['max' => 1]
                ]),
                'customer_count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => '10th Order Milestone',
                'slug' => 'milestone_10th',
                'type' => 'predefined',
                'filters' => json_encode([
                    'order_count' => ['exact' => 10],
                    'last_order_days_ago' => ['max' => 1]
                ]),
                'customer_count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => '50th Order Milestone',
                'slug' => 'milestone_50th',
                'type' => 'predefined',
                'filters' => json_encode([
                    'order_count' => ['exact' => 50],
                    'last_order_days_ago' => ['max' => 1]
                ]),
                'customer_count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Cart Abandoners',
                'slug' => 'cart_abandoners',
                'type' => 'predefined',
                'filters' => json_encode([
                    'has_cart_items' => true,
                    'last_order_hours_ago' => ['min' => 24]
                ]),
                'customer_count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'First-Time Discount Users',
                'slug' => 'first_time_discount_users',
                'type' => 'predefined',
                'filters' => json_encode([
                    'order_count' => ['exact' => 1],
                    'used_coupon_on_first_order' => true
                ]),
                'customer_count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Birthday This Month',
                'slug' => 'birthday_customers',
                'type' => 'predefined',
                'filters' => json_encode([
                    'birthday_month' => date('m')
                ]),
                'customer_count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        $segmentationService = app(CustomerSegmentationService::class);
        $count = 0;

        foreach ($segments as $segment) {
            DB::table('wa_customer_segments')->updateOrInsert(
                ['slug' => $segment['slug']],
                $segment
            );

            $this->command->info("✓ Seeded segment: {$segment['name']} ({$segment['slug']})");

            // Refresh segment cache
            try {
                $segmentRecord = DB::table('wa_customer_segments')
                    ->where('slug', $segment['slug'])
                    ->first();

                if ($segmentRecord) {
                    $segmentationService->refreshSegmentCache($segmentRecord->id);
                    $this->command->info("  → Cache refreshed for {$segment['name']}");
                }
            } catch (\Exception $e) {
                $this->command->warn("  → Could not refresh cache: {$e->getMessage()}");
            }

            $count++;
        }

        $this->command->info("\n✓ Successfully seeded {$count} WhatsApp customer segments");
        $this->command->info("Run 'php artisan whatsapp:refresh-segments --all' to calculate customer counts");
    }
}
