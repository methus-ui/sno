<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Bargaining Mode Feature Flag
    |--------------------------------------------------------------------------
    |
    | Enable or disable the bargaining mode feature globally.
    | Set to false to instantly disable without code changes.
    |
    */

    'enabled' => env('BARGAINING_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Bargaining Modes
    |--------------------------------------------------------------------------
    |
    | Control which bargaining modes are available to customers.
    |
    */

    'instant_mode_enabled' => env('BARGAINING_INSTANT_MODE', true),
    'wait_mode_enabled' => env('BARGAINING_WAIT_MODE', true),

    /*
    |--------------------------------------------------------------------------
    | Timing Configuration
    |--------------------------------------------------------------------------
    |
    | wait_duration: Seconds to wait for vendor counter-offers (wait mode)
    | request_expiration: Minutes until bargaining request expires
    |
    */

    'wait_duration' => env('BARGAINING_WAIT_DURATION', 60),
    'request_expiration' => 15, // minutes

    /*
    |--------------------------------------------------------------------------
    | Item Matching Configuration
    |--------------------------------------------------------------------------
    |
    | fuzzy_similarity_threshold: Minimum similarity % for name matching (0-100)
    | max_matches_per_item: Max stores to match per cart item
    | require_barcode_match: Only match items with exact barcode match
    |
    */

    'fuzzy_similarity_threshold' => 85,
    'max_matches_per_item' => 50,
    'require_barcode_match' => env('BARGAINING_REQUIRE_BARCODE', false),

    /*
    |--------------------------------------------------------------------------
    | Customer Limits
    |--------------------------------------------------------------------------
    |
    | Prevent abuse and ensure system performance.
    |
    */

    'max_requests_per_user_per_day' => env('BARGAINING_MAX_REQUESTS_PER_DAY', 10),
    'min_cart_value' => env('BARGAINING_MIN_CART_VALUE', 0),
    'max_cart_items' => 50,

    /*
    |--------------------------------------------------------------------------
    | Offer Ranking Weights
    |--------------------------------------------------------------------------
    |
    | Customize how offers are ranked. Total should be 100.
    | Higher weight = more important in ranking.
    |
    */

    'ranking' => [
        'fulfillment_weight' => 50,  // % of items available
        'price_weight' => 30,         // Total price
        'rating_weight' => 10,        // Store rating
        'delivery_weight' => 10,      // Delivery charge
    ],

    /*
    |--------------------------------------------------------------------------
    | Caching
    |--------------------------------------------------------------------------
    |
    | Cache duration in seconds for various data.
    |
    */

    'cache' => [
        'item_matches' => 300,     // 5 minutes
        'offers' => 30,            // 30 seconds
        'store_settings' => 600,   // 10 minutes
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance
    |--------------------------------------------------------------------------
    |
    | query_timeout: Max seconds for matching queries
    | batch_size: Items to process per batch during matching
    |
    */

    'query_timeout' => 10,
    'batch_size' => 20,

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    |
    | Configure push notifications for bargaining events.
    |
    */

    'notifications' => [
        'customer' => [
            'new_offer_received' => true,
            'best_offer_changed' => true,
            'request_expired' => true,
        ],
        'vendor' => [
            'new_request_available' => false,  // Opt-in per store
            'offer_awarded' => true,
            'offer_rejected' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Vendor Defaults
    |--------------------------------------------------------------------------
    |
    | Default settings for new stores when they join bargaining.
    |
    */

    'vendor_defaults' => [
        'auto_participate' => true,
        'manual_bidding_enabled' => false,
        'auto_discount_percentage' => 0,
        'max_concurrent_bargains' => 50,
    ],

];
