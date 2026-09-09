<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DmPerformanceTierSeeder extends Seeder
{
    /**
     * Seed delivery man performance tiers.
     *
     * Tier system to motivate delivery boys:
     * - Bronze: New joiners
     * - Silver: Regular performers
     * - Gold: Top performers
     * - Platinum: Elite performers
     * - Diamond: Exceptional (rare)
     *
     * @return void
     */
    public function run()
    {
        $tiers = [
            [
                'name' => 'Bronze',
                'rank_order' => 1,
                'icon' => 'bronze-badge.png',
                'color' => '#CD7F32',
                'min_rating' => 3.00,
                'min_deliveries' => 0,          // New joiners
                'min_acceptance_rate' => 50,
                'bonus_per_delivery' => 0.00,   // No extra bonus
                'priority_boost' => 0,
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Silver',
                'rank_order' => 2,
                'icon' => 'silver-badge.png',
                'color' => '#C0C0C0',
                'min_rating' => 3.50,
                'min_deliveries' => 50,         // 50+ lifetime deliveries
                'min_acceptance_rate' => 60,
                'bonus_per_delivery' => 2.00,   // ₹2 extra per order
                'priority_boost' => 5,          // 5% priority in order assignment
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Gold',
                'rank_order' => 3,
                'icon' => 'gold-badge.png',
                'color' => '#FFD700',
                'min_rating' => 4.00,
                'min_deliveries' => 150,        // 150+ lifetime deliveries
                'min_acceptance_rate' => 70,
                'bonus_per_delivery' => 5.00,   // ₹5 extra per order
                'priority_boost' => 15,         // 15% priority
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Platinum',
                'rank_order' => 4,
                'icon' => 'platinum-badge.png',
                'color' => '#E5E4E2',
                'min_rating' => 4.30,
                'min_deliveries' => 300,        // 300+ lifetime deliveries
                'min_acceptance_rate' => 80,
                'bonus_per_delivery' => 8.00,   // ₹8 extra per order
                'priority_boost' => 25,         // 25% priority
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Diamond',
                'rank_order' => 5,
                'icon' => 'diamond-badge.png',
                'color' => '#B9F2FF',
                'min_rating' => 4.50,
                'min_deliveries' => 500,        // 500+ lifetime deliveries
                'min_acceptance_rate' => 85,
                'bonus_per_delivery' => 12.00,  // ₹12 extra per order
                'priority_boost' => 40,         // 40% priority (first choice)
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($tiers as $tier) {
            DB::table('dm_performance_tiers')->updateOrInsert(
                ['name' => $tier['name']],
                $tier
            );
        }
    }
}
