<?php

namespace Database\Seeders;

use App\Models\SubscriptionPackage;
use Illuminate\Database\Seeder;

class SubscriptionPackageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates Basic and Advance subscription plans as per requirements
     */
    public function run(): void
    {
        // Basic Plan - ₹1500/month
        SubscriptionPackage::updateOrCreate(
            ['package_name' => 'Basic'],
            [
                'price' => 1500.00,
                'validity' => 30, // 30 days
                'max_order' => 'unlimited',
                'max_product' => 'unlimited',
                'pos' => true,
                'mobile_app' => true,
                'chat' => true,
                'review' => true,
                'self_delivery' => false, // No self-delivery for Basic
                'status' => true,
                'default' => false,
                'colour' => '#4CAF50',
                'text' => 'Bring your local business online. Perfect for small vendors starting their digital journey.',
            ]
        );

        // Advance Plan - ₹3500/month
        SubscriptionPackage::updateOrCreate(
            ['package_name' => 'Advance'],
            [
                'price' => 3500.00,
                'validity' => 30, // 30 days
                'max_order' => 'unlimited',
                'max_product' => 'unlimited',
                'pos' => true,
                'mobile_app' => true,
                'chat' => true,
                'review' => true,
                'self_delivery' => true, // Self-delivery enabled for Advance
                'status' => true,
                'default' => true, // Set as recommended plan
                'colour' => '#D82E5E',
                'text' => 'Show offers to your customers, increase revenue. Includes self-delivery and priority support.',
            ]
        );
    }
}
