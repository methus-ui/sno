<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Message Search Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Phase 7: Advanced Search & Filtering
    |
    */

    // Enable/disable search features
    'enabled' => env('ENABLE_MESSAGE_SEARCH', true),

    // Search settings
    'max_results' => env('MESSAGE_SEARCH_MAX_RESULTS', 100),
    'context_length' => 80, // Characters before/after match in snippet
    'min_query_length' => 2, // Minimum characters to search
    'search_timeout' => env('MESSAGE_SEARCH_TIMEOUT', 10), // Seconds

    // Search history
    'history_enabled' => env('ENABLE_SEARCH_HISTORY', true),
    'history_retention_days' => env('SEARCH_HISTORY_RETENTION_DAYS', 90),
    'max_history_entries' => 100,

    // Suggestions
    'suggestions_enabled' => true,
    'suggestion_count' => env('SEARCH_SUGGESTION_COUNT', 10),
    'suggestion_debounce_ms' => 300,

    // Filter presets
    'presets_enabled' => env('ENABLE_FILTER_PRESETS', true),
    'max_user_presets' => 20,

    // Advanced filters
    'filters_enabled' => env('ENABLE_ADVANCED_FILTERS', true),

    // Available date range presets
    'date_ranges' => [
        'today' => 'Today',
        'yesterday' => 'Yesterday',
        'last_7_days' => 'Last 7 Days',
        'last_30_days' => 'Last 30 Days',
        'this_week' => 'This Week',
        'this_month' => 'This Month',
    ],

    // Available file types for filtering
    'file_types' => [
        'jpg' => 'JPG Images',
        'jpeg' => 'JPEG Images',
        'png' => 'PNG Images',
        'gif' => 'GIF Images',
        'pdf' => 'PDF Documents',
        'doc' => 'Word Documents',
        'docx' => 'Word Documents',
        'xls' => 'Excel Spreadsheets',
        'xlsx' => 'Excel Spreadsheets',
    ],

    // User types for filtering
    'user_types' => [
        'admin' => 'Admin',
        'vendor' => 'Vendor',
        'store' => 'Store',
        'customer' => 'Customer',
        'delivery_man' => 'Delivery Man',
    ],
];
