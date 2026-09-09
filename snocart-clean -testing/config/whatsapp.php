<?php

return [
    /*
    |--------------------------------------------------------------------------
    | WhatsApp Business API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for WhatsApp Business API integration with secure
    | credential management via environment variables.
    |
    */

    'api_token' => env('WHATSAPP_API_TOKEN'),
    'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
    'api_version' => env('WHATSAPP_API_VERSION', 'v19.0'),
    'api_base_url' => env('WHATSAPP_API_BASE_URL', 'https://graph.facebook.com'),

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | WhatsApp Business API rate limits to prevent throttling.
    | Default: 5 messages per second with burst limit of 50.
    |
    */

    'rate_limit' => [
        'messages_per_second' => env('WHATSAPP_RATE_LIMIT', 5),
        'burst_limit' => env('WHATSAPP_BURST_LIMIT', 50),
    ],

    /*
    |--------------------------------------------------------------------------
    | Timeouts
    |--------------------------------------------------------------------------
    |
    | Connection and request timeouts for WhatsApp API calls.
    |
    */

    'timeouts' => [
        'connect_timeout' => 10,
        'request_timeout' => 30,
    ],

    /*
    |--------------------------------------------------------------------------
    | SSL Configuration
    |--------------------------------------------------------------------------
    |
    | CRITICAL: SSL verification MUST be enabled in production.
    | Never disable peer verification in production environments.
    |
    */

    'ssl' => [
        'verify_peer' => true,
        'verify_host' => 2,
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    |
    | Queue names for WhatsApp campaign processing.
    |
    */

    'queue' => [
        'campaign_queue' => 'whatsapp',
        'message_queue' => 'whatsapp-messages',
        'analytics_queue' => 'whatsapp-analytics',
    ],

    /*
    |--------------------------------------------------------------------------
    | Analytics Configuration
    |--------------------------------------------------------------------------
    |
    | Campaign analytics and ROI tracking settings.
    |
    */

    'analytics' => [
        'calculate_after_minutes' => 5,
        'track_periods' => [24, 168, 720], // 24h, 7d, 30d (in hours)
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    |
    | Enable/disable WhatsApp features.
    |
    */

    'enabled' => env('WHATSAPP_ENABLED', true),
];
