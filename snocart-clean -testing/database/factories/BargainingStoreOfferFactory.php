<?php

namespace Database\Factories;

use App\Models\BargainingStoreOffer;
use App\Models\BargainingRequest;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

class BargainingStoreOfferFactory extends Factory
{
    protected $model = BargainingStoreOffer::class;

    public function definition()
    {
        $subtotal = $this->faker->numberBetween(200, 2000);
        $itemDiscount = $this->faker->numberBetween(10, 100);
        $storeDiscount = $this->faker->numberBetween(0, 50);
        $deliveryCharge = $this->faker->numberBetween(20, 50);
        $taxAmount = ($subtotal - $itemDiscount - $storeDiscount) * 0.05; // 5% tax
        $totalAmount = $subtotal - $itemDiscount - $storeDiscount + $taxAmount + $deliveryCharge;

        $totalItems = $this->faker->numberBetween(1, 10);
        $itemsAvailable = $this->faker->numberBetween(1, $totalItems);
        $fulfillmentPercentage = ($itemsAvailable / $totalItems) * 100;

        return [
            'bargaining_request_id' => BargainingRequest::factory(),
            'store_id' => Store::factory(),
            'offer_type' => $this->faker->randomElement(['auto_calculated', 'vendor_submitted']),
            'items_available' => $itemsAvailable,
            'items_missing' => $totalItems - $itemsAvailable,
            'fulfillment_percentage' => $fulfillmentPercentage,
            'subtotal' => $subtotal,
            'item_discount' => $itemDiscount,
            'store_discount' => $storeDiscount,
            'special_discount' => $this->faker->optional()->numberBetween(0, 100),
            'flash_sale_discount' => 0,
            'tax_amount' => $taxAmount,
            'delivery_charge' => $deliveryCharge,
            'total_amount' => $totalAmount,
            'rank' => $this->faker->numberBetween(1, 10),
            'is_best_offer' => false,
            'vendor_notes' => $this->faker->optional()->sentence(),
            'estimated_delivery_time' => $this->faker->numberBetween(20, 60),
            'status' => $this->faker->randomElement(['submitted', 'accepted', 'rejected']),
            'created_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'updated_at' => now(),
        ];
    }

    public function autoCalculated()
    {
        return $this->state(function (array $attributes) {
            return [
                'offer_type' => 'auto_calculated',
                'special_discount' => null,
                'vendor_notes' => null,
            ];
        });
    }

    public function vendorSubmitted()
    {
        return $this->state(function (array $attributes) {
            return [
                'offer_type' => 'vendor_submitted',
                'special_discount' => $this->faker->numberBetween(10, 100),
                'vendor_notes' => $this->faker->sentence(),
            ];
        });
    }

    public function bestOffer()
    {
        return $this->state(function (array $attributes) {
            return [
                'rank' => 1,
                'is_best_offer' => true,
            ];
        });
    }

    public function fullFulfillment()
    {
        return $this->state(function (array $attributes) {
            $totalItems = 10;
            return [
                'items_available' => $totalItems,
                'items_missing' => 0,
                'fulfillment_percentage' => 100,
            ];
        });
    }

    public function partialFulfillment()
    {
        return $this->state(function (array $attributes) {
            $totalItems = 10;
            $available = $this->faker->numberBetween(5, 9);
            return [
                'items_available' => $available,
                'items_missing' => $totalItems - $available,
                'fulfillment_percentage' => ($available / $totalItems) * 100,
            ];
        });
    }

    public function accepted()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'accepted',
            ];
        });
    }
}
