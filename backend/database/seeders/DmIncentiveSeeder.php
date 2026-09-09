<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DmIncentiveSeeder extends Seeder
{
    /**
     * Seed delivery man incentives for Kashmir Grocery App.
     *
     * Incentive structure designed for:
     * - 100-150 orders/day target
     * - 16-20 delivery boys
     * - Kashmir local market rates
     * - Grocery delivery (longer delivery times than food)
     *
     * @return void
     */
    public function run()
    {
        $this->seedDailyIncentiveRules();
        $this->seedIncentiveSlabs();
        $this->seedTimeBasedIncentives();
        $this->seedRushIncentives();
    }

    /**
     * Daily milestone bonuses - reward for completing X orders per day.
     */
    private function seedDailyIncentiveRules()
    {
        $rules = [
            [
                'title' => 'Starter Bonus',
                'delivery_count' => 5,
                'bonus_amount' => 50.00,    // ₹50 for first 5 orders
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Daily Target Bonus',
                'delivery_count' => 8,
                'bonus_amount' => 100.00,   // ₹100 for 8 orders (avg target)
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'High Performer Bonus',
                'delivery_count' => 12,
                'bonus_amount' => 200.00,   // ₹200 for 12 orders
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Super Star Bonus',
                'delivery_count' => 15,
                'bonus_amount' => 350.00,   // ₹350 for 15+ orders
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Champion Bonus',
                'delivery_count' => 20,
                'bonus_amount' => 500.00,   // ₹500 for 20+ orders (exceptional)
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($rules as $rule) {
            DB::table('dm_daily_incentive_rules')->updateOrInsert(
                ['delivery_count' => $rule['delivery_count']],
                $rule
            );
        }
    }

    /**
     * Incentive slabs - tiered bonuses based on delivery count or speed.
     */
    private function seedIncentiveSlabs()
    {
        $slabs = [
            // Delivery count based slabs (monthly performance)
            [
                'title' => 'Bronze Slab (100-150 deliveries/month)',
                'type' => 'delivery_count',
                'min_value' => 100,
                'max_value' => 150,
                'bonus_amount' => 500.00,
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Silver Slab (151-200 deliveries/month)',
                'type' => 'delivery_count',
                'min_value' => 151,
                'max_value' => 200,
                'bonus_amount' => 1000.00,
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Gold Slab (201-250 deliveries/month)',
                'type' => 'delivery_count',
                'min_value' => 201,
                'max_value' => 250,
                'bonus_amount' => 1500.00,
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Platinum Slab (251+ deliveries/month)',
                'type' => 'delivery_count',
                'min_value' => 251,
                'max_value' => null,
                'bonus_amount' => 2500.00,
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Speed based slabs (avg delivery time in minutes)
            [
                'title' => 'Speed Bonus - Under 30 min avg',
                'type' => 'avg_delivery_time',
                'min_value' => 0,
                'max_value' => 30,
                'bonus_amount' => 15.00,    // ₹15 per order if avg < 30 min
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Speed Bonus - 30-45 min avg',
                'type' => 'avg_delivery_time',
                'min_value' => 30,
                'max_value' => 45,
                'bonus_amount' => 10.00,    // ₹10 per order if avg 30-45 min
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Speed Bonus - 45-60 min avg',
                'type' => 'avg_delivery_time',
                'min_value' => 45,
                'max_value' => 60,
                'bonus_amount' => 5.00,     // ₹5 per order if avg 45-60 min
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($slabs as $slab) {
            DB::table('dm_incentive_slabs')->updateOrInsert(
                ['title' => $slab['title']],
                $slab
            );
        }
    }

    /**
     * Time-based incentives - extra pay during peak/difficult hours.
     */
    private function seedTimeBasedIncentives()
    {
        $incentives = [
            // Morning peak incentive (7-10 AM) - all days
            [
                'title' => 'Morning Rush Bonus',
                'time_from' => '07:00:00',
                'time_to' => '10:00:00',
                'days_of_week' => json_encode([0, 1, 2, 3, 4, 5, 6]), // All days
                'bonus_type' => 'fixed',
                'bonus_value' => 10.00,     // ₹10 extra per order
                'zone_id' => null,          // All zones
                'module_id' => null,
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Evening peak incentive (5-8 PM) - all days
            [
                'title' => 'Evening Rush Bonus',
                'time_from' => '17:00:00',
                'time_to' => '20:00:00',
                'days_of_week' => json_encode([0, 1, 2, 3, 4, 5, 6]),
                'bonus_type' => 'fixed',
                'bonus_value' => 15.00,     // ₹15 extra per order (higher evening demand)
                'zone_id' => null,
                'module_id' => null,
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Weekend bonus (Saturday & Sunday)
            [
                'title' => 'Weekend Warrior Bonus',
                'time_from' => '09:00:00',
                'time_to' => '20:00:00',
                'days_of_week' => json_encode([0, 6]),  // Sunday=0, Saturday=6
                'bonus_type' => 'fixed',
                'bonus_value' => 8.00,      // ₹8 extra per order on weekends
                'zone_id' => null,
                'module_id' => null,
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Friday special (busy day in Kashmir)
            [
                'title' => 'Friday Special Bonus',
                'time_from' => '10:00:00',
                'time_to' => '14:00:00',
                'days_of_week' => json_encode([5]),     // Friday only
                'bonus_type' => 'fixed',
                'bonus_value' => 12.00,     // ₹12 extra (post-prayer shopping rush)
                'zone_id' => null,
                'module_id' => null,
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Early bird bonus (first orders of the day)
            [
                'title' => 'Early Bird Bonus',
                'time_from' => '07:00:00',
                'time_to' => '08:00:00',
                'days_of_week' => json_encode([1, 2, 3, 4, 5, 6]), // Mon-Sat
                'bonus_type' => 'fixed',
                'bonus_value' => 20.00,     // ₹20 extra for very early orders
                'zone_id' => null,
                'module_id' => null,
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Night owl bonus (late deliveries)
            [
                'title' => 'Night Owl Bonus',
                'time_from' => '20:00:00',
                'time_to' => '21:00:00',
                'days_of_week' => json_encode([0, 1, 2, 3, 4, 5, 6]),
                'bonus_type' => 'fixed',
                'bonus_value' => 20.00,     // ₹20 extra for late night
                'zone_id' => null,
                'module_id' => null,
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($incentives as $incentive) {
            DB::table('dm_time_based_incentives')->updateOrInsert(
                ['title' => $incentive['title']],
                $incentive
            );
        }
    }

    /**
     * Rush/Surge incentives - triggered when orders pile up.
     */
    private function seedRushIncentives()
    {
        $rushIncentives = [
            // Order count based surge
            [
                'title' => 'High Demand Surge - Level 1',
                'trigger_type' => 'order_count',
                'min_threshold' => 15,      // Triggered when 15+ pending orders
                'max_threshold' => 25,
                'zone_id' => null,          // All zones
                'bonus_type' => 'fixed',
                'bonus_value' => 20.00,     // ₹20 extra per order during surge
                'duration_minutes' => 60,   // Surge lasts 1 hour
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'High Demand Surge - Level 2',
                'trigger_type' => 'order_count',
                'min_threshold' => 26,      // 26+ pending orders
                'max_threshold' => 40,
                'zone_id' => null,
                'bonus_type' => 'fixed',
                'bonus_value' => 35.00,     // ₹35 extra per order
                'duration_minutes' => 90,
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Critical Demand Surge',
                'trigger_type' => 'order_count',
                'min_threshold' => 41,      // 41+ pending orders (critical)
                'max_threshold' => null,
                'zone_id' => null,
                'bonus_type' => 'fixed',
                'bonus_value' => 50.00,     // ₹50 extra per order
                'duration_minutes' => 120,
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Pending time based surge (orders waiting too long)
            [
                'title' => 'Long Wait Surge',
                'trigger_type' => 'pending_time',
                'min_threshold' => 20,      // Orders pending 20+ minutes
                'max_threshold' => 30,
                'zone_id' => null,
                'bonus_type' => 'fixed',
                'bonus_value' => 15.00,     // ₹15 extra
                'duration_minutes' => 45,
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Critical Wait Surge',
                'trigger_type' => 'pending_time',
                'min_threshold' => 31,      // Orders pending 31+ minutes
                'max_threshold' => null,
                'zone_id' => null,
                'bonus_type' => 'fixed',
                'bonus_value' => 30.00,     // ₹30 extra
                'duration_minutes' => 60,
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($rushIncentives as $incentive) {
            DB::table('dm_rush_incentives')->updateOrInsert(
                ['title' => $incentive['title']],
                $incentive
            );
        }
    }
}
