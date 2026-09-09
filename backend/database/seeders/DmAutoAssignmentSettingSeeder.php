<?php

namespace Database\Seeders;

use App\Models\DmAutoAssignmentSetting;
use App\Models\Zone;
use Illuminate\Database\Seeder;

class DmAutoAssignmentSettingSeeder extends Seeder
{
    public function run()
    {
        $zones = Zone::all();

        foreach ($zones as $zone) {
            DmAutoAssignmentSetting::firstOrCreate(
                ['zone_id' => $zone->id],
                [
                    'is_enabled' => true,
                    'max_radius_km' => $this->getRadiusForZone($zone),
                    'priority_factors' => [
                        'distance_weight' => 0.4,
                        'rating_weight' => 0.25,
                        'acceptance_rate_weight' => 0.2,
                        'tier_weight' => 0.15,
                    ],
                    'fallback_to_manual_after_seconds' => 120,
                    'max_orders_per_dm' => 3,
                ]
            );
        }

        // Also create a global default setting (zone_id = null)
        DmAutoAssignmentSetting::firstOrCreate(
            ['zone_id' => null],
            [
                'is_enabled' => true,
                'max_radius_km' => 5.00,
                'priority_factors' => [
                    'distance_weight' => 0.4,
                    'rating_weight' => 0.25,
                    'acceptance_rate_weight' => 0.2,
                    'tier_weight' => 0.15,
                ],
                'fallback_to_manual_after_seconds' => 120,
                'max_orders_per_dm' => 3,
            ]
        );
    }

    private function getRadiusForZone(Zone $zone): float
    {
        // Express delivery zones get smaller radius for faster delivery
        if (str_contains(strtolower($zone->name), 'express')) {
            return 3.00;
        }

        // Normal delivery zones get standard radius
        return 5.00;
    }
}
