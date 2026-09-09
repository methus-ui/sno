<?php

namespace Database\Factories;

use App\Models\BargainingRequest;
use App\Models\User;
use App\Models\Zone;
use App\Models\Module;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

class BargainingRequestFactory extends Factory
{
    protected $model = BargainingRequest::class;

    public function definition()
    {
        $status = $this->faker->randomElement([
            'initiated',
            'matching',
            'offers_received',
            'awarded',
            'accepted',
            'cancelled',
            'expired'
        ]);

        $mode = $this->faker->randomElement(['instant', 'wait']);

        $originalValue = $this->faker->numberBetween(100, 5000);
        $savings = $status === 'accepted' ? $this->faker->numberBetween(10, 200) : null;
        $finalPrice = $savings ? $originalValue - $savings : null;

        return [
            'request_code' => 'BR-' . $this->faker->unique()->numberBetween(100000, 999999),
            'user_id' => User::factory(),
            'guest_id' => null,
            'zone_id' => Zone::factory(),
            'module_id' => Module::factory(),
            'status' => $status,
            'mode' => $mode,
            'original_cart_snapshot' => json_encode([
                'items' => [],
                'subtotal' => $originalValue,
            ]),
            'total_cart_items' => $this->faker->numberBetween(1, 20),
            'original_cart_value' => $originalValue,
            'final_price' => $finalPrice,
            'total_savings' => $savings,
            'total_stores_matched' => $this->faker->numberBetween(1, 10),
            'total_offers_received' => $this->faker->numberBetween(1, 10),
            'awarded_store_id' => $status === 'awarded' || $status === 'accepted' ? Store::factory() : null,
            'expires_at' => $mode === 'wait' ? now()->addSeconds(60) : null,
            'created_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'updated_at' => now(),
        ];
    }

    public function initiated()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'initiated',
                'awarded_store_id' => null,
                'final_price' => null,
                'total_savings' => null,
            ];
        });
    }

    public function offerReceived()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'offers_received',
                'awarded_store_id' => null,
            ];
        });
    }

    public function accepted()
    {
        return $this->state(function (array $attributes) {
            $originalValue = $attributes['original_cart_value'];
            $savings = rand(10, 200);

            return [
                'status' => 'accepted',
                'awarded_store_id' => Store::factory(),
                'final_price' => $originalValue - $savings,
                'total_savings' => $savings,
            ];
        });
    }

    public function cancelled()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'awarded_store_id' => null,
            ];
        });
    }

    public function instantMode()
    {
        return $this->state(function (array $attributes) {
            return [
                'mode' => 'instant',
                'expires_at' => null,
            ];
        });
    }

    public function waitMode()
    {
        return $this->state(function (array $attributes) {
            return [
                'mode' => 'wait',
                'expires_at' => now()->addSeconds(60),
            ];
        });
    }
}
