<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Messaging Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Detailed cache settings for messaging components.
    | Uses Redis for optimal performance.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Cache Drivers
    |--------------------------------------------------------------------------
    */

    'driver' => env('MESSAGING_CACHE_DRIVER', 'redis'),

    /*
    |--------------------------------------------------------------------------
    | Conversation Caching
    |--------------------------------------------------------------------------
    */

    'conversations' => [
        'enabled' => env('MESSAGING_CACHE_CONVERSATIONS', true),
        'ttl' => env('MESSAGING_CACHE_CONVERSATIONS_TTL', 300), // 5 minutes
        'key_prefix' => 'messaging:conversations:',
        'tags' => ['messaging', 'conversations'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Message Caching
    |--------------------------------------------------------------------------
    */

    'messages' => [
        'enabled' => env('MESSAGING_CACHE_MESSAGES', true),
        'ttl' => env('MESSAGING_CACHE_MESSAGES_TTL', 300), // 5 minutes
        'key_prefix' => 'messaging:messages:',
        'tags' => ['messaging', 'messages'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Template Caching
    |--------------------------------------------------------------------------
    */

    'templates' => [
        'enabled' => env('MESSAGING_CACHE_TEMPLATES', true),
        'ttl' => env('MESSAGING_CACHE_TEMPLATES_TTL', 3600), // 1 hour (templates change rarely)
        'key_prefix' => 'messaging:templates:',
        'tags' => ['messaging', 'templates'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Presence Caching (Online/Offline Status)
    |--------------------------------------------------------------------------
    */

    'presence' => [
        'enabled' => true, // Always enabled (core feature)
        'ttl' => env('MESSAGING_PRESENCE_TTL', 60), // 60 seconds
        'key_prefix' => 'messaging:presence:',
        'tags' => ['messaging', 'presence'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Typing Indicators Caching
    |--------------------------------------------------------------------------
    */

    'typing' => [
        'enabled' => true, // Always enabled (core feature)
        'ttl' => env('MESSAGING_TYPING_TTL', 5), // 5 seconds (auto-expires)
        'key_prefix' => 'messaging:typing:',
        'tags' => ['messaging', 'typing'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Unread Count Caching
    |--------------------------------------------------------------------------
    */

    'unread_count' => [
        'enabled' => env('MESSAGING_CACHE_UNREAD_COUNT', true),
        'ttl' => env('MESSAGING_CACHE_UNREAD_TTL', 300), // 5 minutes
        'key_prefix' => 'messaging:unread:',
        'tags' => ['messaging', 'unread'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Search Results Caching
    |--------------------------------------------------------------------------
    */

    'search' => [
        'enabled' => env('MESSAGING_CACHE_SEARCH', true),
        'ttl' => env('MESSAGING_SEARCH_CACHE_TTL', 300), // 5 minutes
        'key_prefix' => 'messaging:search:',
        'tags' => ['messaging', 'search'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Reaction Caching
    |--------------------------------------------------------------------------
    */

    'reactions' => [
        'enabled' => env('MESSAGING_CACHE_REACTIONS', true),
        'ttl' => env('MESSAGING_CACHE_REACTIONS_TTL', 300), // 5 minutes
        'key_prefix' => 'messaging:reactions:',
        'tags' => ['messaging', 'reactions'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Statistics Caching
    |--------------------------------------------------------------------------
    */

    'stats' => [
        'enabled' => env('MESSAGING_CACHE_STATS', true),
        'ttl' => env('MESSAGING_CACHE_STATS_TTL', 600), // 10 minutes
        'key_prefix' => 'messaging:stats:',
        'tags' => ['messaging', 'stats'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Warming
    |--------------------------------------------------------------------------
    |
    | Pre-load frequently accessed data into cache.
    |
    */

    'warming' => [
        'enabled' => env('MESSAGING_CACHE_WARMING', false),
        'schedule' => '*/15 * * * *', // Every 15 minutes

        'warm' => [
            'templates' => true,
            'popular_conversations' => true,
            'active_users_count' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Invalidation Rules
    |--------------------------------------------------------------------------
    |
    | Automatic cache invalidation when data changes.
    |
    */

    'invalidation' => [
        'on_message_sent' => ['conversations', 'messages', 'unread_count'],
        'on_message_read' => ['unread_count'],
        'on_reaction_added' => ['reactions'],
        'on_template_updated' => ['templates'],
        'on_conversation_archived' => ['conversations'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Monitoring
    |--------------------------------------------------------------------------
    */

    'monitoring' => [
        'enabled' => env('MESSAGING_CACHE_MONITORING', false),
        'log_hits' => false,
        'log_misses' => false,
        'track_performance' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Redis Connection
    |--------------------------------------------------------------------------
    |
    | Dedicated Redis connection for messaging (optional).
    |
    */

    'redis' => [
        'connection' => env('MESSAGING_REDIS_CONNECTION', 'default'),
        'database' => env('MESSAGING_REDIS_DATABASE', 1), // Separate database for messaging
    ],

];
