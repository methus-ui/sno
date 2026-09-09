<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Messaging System Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the enhanced messaging system with real-time features,
    | delivery tracking, reactions, and more.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    |
    | Enable/disable messaging features. Use these for gradual rollout.
    |
    */

    'enabled' => env('MESSAGING_ENABLED', true),
    'revamp_enabled' => env('MESSAGING_REVAMP_ENABLED', false), // Main kill switch

    'features' => [
        'delivery_tracking' => env('MESSAGING_DELIVERY_TRACKING', true),
        'typing_indicators' => env('MESSAGING_TYPING_INDICATORS', true),
        'presence_tracking' => env('MESSAGING_PRESENCE_TRACKING', true),
        'reactions' => env('MESSAGING_REACTIONS', true),
        'message_editing' => env('MESSAGING_EDITING', true),
        'message_threading' => env('MESSAGING_THREADING', false), // Coming soon
        'search' => env('MESSAGING_SEARCH', true),
        'notifications' => env('MESSAGING_NOTIFICATIONS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Delivery Configuration
    |--------------------------------------------------------------------------
    |
    | Message delivery settings and retry logic.
    |
    */

    'delivery' => [
        'max_retries' => env('MESSAGING_MAX_RETRIES', 3),
        'retry_intervals' => [5, 30, 300], // seconds: 5s, 30s, 5min
        'timeout' => env('MESSAGING_DELIVERY_TIMEOUT', 30), // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Channels Configuration
    |--------------------------------------------------------------------------
    |
    | Available delivery channels and their priority.
    |
    */

    'pusher_enabled' => env('MESSAGING_PUSHER_ENABLED', true),
    'fcm_enabled' => env('MESSAGING_FCM_ENABLED', false),

    'channels' => [
        'pusher' => [
            'enabled' => env('MESSAGING_PUSHER_ENABLED', true),
            'priority' => 1,
        ],
        'fcm' => [
            'enabled' => env('MESSAGING_FCM_ENABLED', false),
            'priority' => 2,
        ],
        'database' => [
            'enabled' => true, // Always enabled as fallback
            'priority' => 3,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Typing Indicators
    |--------------------------------------------------------------------------
    |
    | Configuration for typing indicators (Redis-based with TTL).
    |
    */

    'typing' => [
        'ttl' => env('MESSAGING_TYPING_TTL', 5), // seconds
        'debounce' => env('MESSAGING_TYPING_DEBOUNCE', 300), // milliseconds
        'cleanup_interval' => env('MESSAGING_TYPING_CLEANUP', 600), // seconds (10 min)
    ],

    /*
    |--------------------------------------------------------------------------
    | Presence Tracking
    |--------------------------------------------------------------------------
    |
    | Configuration for user online/offline status.
    |
    */

    'presence' => [
        'ttl' => env('MESSAGING_PRESENCE_TTL', 60), // seconds
        'heartbeat_interval' => env('MESSAGING_PRESENCE_HEARTBEAT', 30), // seconds
        'offline_threshold' => env('MESSAGING_PRESENCE_OFFLINE_THRESHOLD', 90), // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Message Reactions
    |--------------------------------------------------------------------------
    |
    | Configuration for emoji reactions.
    |
    */

    'reactions' => [
        'allowed_types' => [
            'thumbs_up',
            'heart',
            'laugh',
            'sad',
            'angry',
            'wow',
        ],
        'max_per_message' => env('MESSAGING_MAX_REACTIONS_PER_MESSAGE', 1000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Search Configuration
    |--------------------------------------------------------------------------
    |
    */

    'search' => [
        'fulltext_enabled' => env('MESSAGING_FULLTEXT_SEARCH', true),
        'cache_results' => env('MESSAGING_CACHE_SEARCH', true),
        'cache_ttl' => env('MESSAGING_SEARCH_CACHE_TTL', 300), // seconds (5 min)
        'results_per_page' => env('MESSAGING_SEARCH_PER_PAGE', 20),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    */

    'cache' => [
        'enabled' => env('MESSAGING_CACHE_ENABLED', true),
        'ttl' => env('MESSAGING_CACHE_TTL', 300), // seconds (5 min)
        'driver' => env('MESSAGING_CACHE_DRIVER', 'redis'),

        'keys' => [
            'conversations' => 'messaging:conversations:',
            'messages' => 'messaging:messages:',
            'templates' => 'messaging:templates:',
            'presence' => 'messaging:presence:',
            'typing' => 'messaging:typing:',
            'unread_count' => 'messaging:unread:',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    |
    */

    'queue' => [
        'enabled' => env('MESSAGING_QUEUE_ENABLED', true),
        'connection' => env('MESSAGING_QUEUE_CONNECTION', 'redis'),
        'queue_name' => env('MESSAGING_QUEUE_NAME', 'messaging'),
        'retry_after' => env('MESSAGING_QUEUE_RETRY_AFTER', 90), // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Message Limits
    |--------------------------------------------------------------------------
    |
    */

    'limits' => [
        'message_length' => env('MESSAGING_MAX_MESSAGE_LENGTH', 5000),
        'file_size' => env('MESSAGING_MAX_FILE_SIZE', 10240), // KB (10MB)
        'files_per_message' => env('MESSAGING_MAX_FILES_PER_MESSAGE', 5),
        'messages_per_page' => env('MESSAGING_MESSAGES_PER_PAGE', 50),
        'conversations_per_page' => env('MESSAGING_CONVERSATIONS_PER_PAGE', 20),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    */

    'rate_limit' => [
        'enabled' => env('MESSAGING_RATE_LIMIT_ENABLED', true),
        'max_messages_per_minute' => env('MESSAGING_MAX_MESSAGES_PER_MINUTE', 60),
        'max_typing_events_per_minute' => env('MESSAGING_MAX_TYPING_EVENTS_PER_MINUTE', 120),
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Settings
    |--------------------------------------------------------------------------
    |
    */

    'notifications' => [
        'enabled' => env('MESSAGING_NOTIFICATIONS_ENABLED', true),
        'sound' => env('MESSAGING_NOTIFICATION_SOUND', true),
        'desktop' => env('MESSAGING_DESKTOP_NOTIFICATIONS', true),
        'push' => env('MESSAGING_PUSH_NOTIFICATIONS', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | UI Configuration
    |--------------------------------------------------------------------------
    |
    */

    'ui' => [
        'polling_interval' => env('MESSAGING_POLLING_INTERVAL', 5000), // milliseconds
        'adaptive_polling' => env('MESSAGING_ADAPTIVE_POLLING', true),
        'reconnect_interval' => env('MESSAGING_RECONNECT_INTERVAL', 1000), // milliseconds
        'max_reconnect_attempts' => env('MESSAGING_MAX_RECONNECT_ATTEMPTS', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | Debug & Logging
    |--------------------------------------------------------------------------
    |
    */

    'debug' => env('MESSAGING_DEBUG', false),
    'log_deliveries' => env('MESSAGING_LOG_DELIVERIES', true),
    'log_presence' => env('MESSAGING_LOG_PRESENCE', false),
    'log_typing' => env('MESSAGING_LOG_TYPING', false),

];
