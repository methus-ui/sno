<?php
return [
    // Performance Optimizations (Phase 1-3)
    'enabled' => env('MESSAGING_OPTIMIZATIONS_ENABLED', true),
    'lazy_load_messages' => env('MESSAGING_LAZY_LOAD', true),
    'cache_templates' => env('MESSAGING_CACHE_TEMPLATES', true),
    'debounce_polling' => env('MESSAGING_DEBOUNCE_POLLING', true),
    'optimized_search' => env('MESSAGING_OPTIMIZED_SEARCH', true),
    'messages_per_page' => env('MESSAGING_PER_PAGE', 50),
    'template_cache_ttl' => env('MESSAGING_TEMPLATE_CACHE_TTL', 300),

    // UX Enhancements (Phase 1: Quick Wins)
    'expanded_previews' => env('MESSAGING_EXPANDED_PREVIEWS', true),
    'smart_timestamps' => env('MESSAGING_SMART_TIMESTAMPS', true),
    'quick_actions' => env('MESSAGING_QUICK_ACTIONS', true),
    'filter_tabs' => env('MESSAGING_FILTER_TABS', true),
    'advanced_search' => env('MESSAGING_ADVANCED_SEARCH', true),
    'bulk_actions' => env('MESSAGING_BULK_ACTIONS', true),

    // UX Enhancements (Phase 2: Keyboard Shortcuts)
    'keyboard_shortcuts' => env('MESSAGING_KEYBOARD_SHORTCUTS', true),

    // UX Enhancements (Phase 3: Loading & Feedback)
    'skeleton_screens' => env('MESSAGING_SKELETON_SCREENS', true),
    'optimistic_ui' => env('MESSAGING_OPTIMISTIC_UI', true),
];
