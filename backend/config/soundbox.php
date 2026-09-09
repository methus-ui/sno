<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Sound Box Feature Flag
    |--------------------------------------------------------------------------
    |
    | Enable or disable the ESP32 sound box notification system.
    | When disabled, no webhooks will be sent to devices.
    |
    */
    'enabled' => env('SOUNDBOX_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Webhook Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for webhook delivery to ESP32 devices.
    |
    */
    'webhook' => [
        // HTTP timeout in seconds for webhook requests
        'timeout' => env('SOUNDBOX_WEBHOOK_TIMEOUT', 5),

        // Maximum number of retry attempts for failed webhooks
        'retry_attempts' => env('SOUNDBOX_WEBHOOK_RETRY_ATTEMPTS', 3),

        // Delay between retry attempts in seconds
        'retry_delay' => env('SOUNDBOX_WEBHOOK_RETRY_DELAY', 30),

        // Enable webhook signature for security
        'enable_signature' => env('SOUNDBOX_WEBHOOK_SIGNATURE', true),

        // Secret key for HMAC signature generation
        'signature_secret' => env('SOUNDBOX_WEBHOOK_SECRET', env('APP_KEY')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Device Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for ESP32 device management.
    |
    */
    'device' => [
        // Maximum number of devices allowed per store
        'max_per_store' => env('SOUNDBOX_MAX_DEVICES_PER_STORE', 5),

        // Expected heartbeat interval in seconds
        'heartbeat_interval' => env('SOUNDBOX_HEARTBEAT_INTERVAL', 60),

        // Offline threshold in seconds (device considered offline if no ping)
        'offline_threshold' => env('SOUNDBOX_OFFLINE_THRESHOLD', 300),

        // API key length (generated keys will be this long + prefix)
        'api_key_length' => 48,

        // API key prefix
        'api_key_prefix' => 'SK_',
    ],

    /*
    |--------------------------------------------------------------------------
    | Pairing Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for device pairing via QR code.
    |
    */
    'pairing' => [
        // QR code token expiry time in seconds (default: 5 minutes)
        'token_expiry' => env('SOUNDBOX_PAIRING_EXPIRY', 300),

        // QR code token length
        'token_length' => 20,

        // QR code token prefix
        'token_prefix' => 'PT-',

        // Automatically clean up expired tokens (hours)
        'cleanup_expired_after' => 24,
    ],

    /*
    |--------------------------------------------------------------------------
    | Auto-Accept Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for automatic order acceptance feature.
    |
    */
    'auto_accept' => [
        // Enable auto-accept feature globally
        'enabled' => env('SOUNDBOX_AUTO_ACCEPT_ENABLED', false),

        // Default timeout in seconds before auto-accepting order
        'timeout' => env('SOUNDBOX_AUTO_ACCEPT_TIMEOUT', 300),

        // Allow stores to override this setting
        'allow_store_override' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Order Notification Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for order notifications sent to sound box devices.
    |
    */
    'notifications' => [
        // Include customer phone number in webhook payload
        'include_customer_phone' => env('SOUNDBOX_INCLUDE_PHONE', true),

        // Include customer address in webhook payload
        'include_customer_address' => env('SOUNDBOX_INCLUDE_ADDRESS', true),

        // Include item details in webhook payload
        'include_item_details' => true,

        // Maximum items to include in webhook (truncate if more)
        'max_items_in_webhook' => 20,

        // Include add-ons and variations in item details
        'include_variations' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Rate limits for device API calls to prevent abuse.
    |
    */
    'rate_limits' => [
        // Heartbeat endpoint (requests per minute)
        'heartbeat' => 120,

        // Order action endpoints (requests per minute)
        'order_actions' => 60,

        // Webhook callbacks (requests per minute per device)
        'webhooks' => 100,
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Device Settings
    |--------------------------------------------------------------------------
    |
    | Default settings applied to newly paired devices.
    | Devices can override these via settings API.
    |
    */
    'default_settings' => [
        'volume' => 80,
        'language' => 'en',
        'auto_accept' => false,
        'auto_accept_timeout' => 300,
        'announcement_voice' => 'female',
        'play_sound' => true,
        'vibrate' => true,
        'led_color' => 'blue',
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for sound box event logging.
    |
    */
    'logging' => [
        // Log all webhook attempts
        'log_webhooks' => env('SOUNDBOX_LOG_WEBHOOKS', true),

        // Log device heartbeats
        'log_heartbeats' => env('SOUNDBOX_LOG_HEARTBEATS', false),

        // Log order actions (accept/reject)
        'log_order_actions' => true,

        // Log channel (defaults to application log)
        'channel' => env('SOUNDBOX_LOG_CHANNEL', 'single'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for webhook queue processing.
    |
    */
    'queue' => [
        // Enable queued webhook delivery
        'enabled' => env('SOUNDBOX_QUEUE_ENABLED', true),

        // Queue connection
        'connection' => env('SOUNDBOX_QUEUE_CONNECTION', 'database'),

        // Queue name
        'queue' => env('SOUNDBOX_QUEUE_NAME', 'soundbox'),

        // Retry failed jobs
        'retry_failed' => true,

        // Maximum job attempts
        'max_attempts' => 3,
    ],
];
