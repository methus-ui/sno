<?php

namespace Database\Factories;

use App\Models\StoreBargainingSetting;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

class StoreBargainingSettingFactory extends Factory
{
    protected $model = StoreBargainingSetting::class;

    public function definition()
    {
        return [
            'store_id' => Store::factory(),
            'bargaining_enabled' => true,
            'auto_participate' => $this->faker->boolean(70), // 70% chance true
            'manual_bidding_enabled' => $this->faker->boolean(50),
            'notify_on_new_request' => $this->faker->boolean(80),
            'auto_discount_percentage' => $this->faker->randomFloat(2, 0, 15),
            'min_order_value_for_bargaining' => $this->faker->numberBetween(0, 500),
            'max_discount_allowed' => $this->faker->numberBetween(10, 30),
        ];
    }

    public function enabled()
    {
        return $this->state(function (array $attributes) {
            return [
                'bargaining_enabled' => true,
                'auto_participate' => true,
            ];
        });
    }

    public function disabled()
    {
        return $this->state(function (array $attributes) {
            return [
                'bargaining_enabled' => false,
                'auto_participate' => false,
                'manual_bidding_enabled' => false,
            ];
        });
    }

    public function manualOnly()
    {
        return $this->state(function (array $attributes) {
            return [
                'bargaining_enabled' => true,
                'auto_participate' => false,
                'manual_bidding_enabled' => true,
            ];
        });
    }
}
