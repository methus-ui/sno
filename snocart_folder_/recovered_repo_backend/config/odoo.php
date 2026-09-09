<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Odoo Integration Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Odoo POS integration with Snocart vendors.
    | Supports multi-tenant architecture with one-click deployment.
    |
    */

    // Odoo server URL
    'url' => env('ODOO_URL', 'https://odoo.snocart.com'),

    // Odoo master password (for database management)
    'master_password' => env('ODOO_MASTER_PASSWORD'),

    // Enable/disable Odoo integration
    'enabled' => env('ODOO_INTEGRATION_ENABLED', false),

    // Sync intervals (minutes)
    'sync_intervals' => [
        'products' => 15, // Sync products every 15 minutes
        'orders' => 5,    // Sync orders every 5 minutes
        'inventory' => 30, // Sync inventory every 30 minutes
    ],

    // Database prefix for multi-tenant setup
    'db_prefix' => 'odoo_vendor_',

    // Default POS configuration
    'pos_config' => [
        'module_pos_restaurant' => false,
        'iface_tipproduct' => false,
        'iface_splitbill' => false,
        'iface_printbill' => false,
        'iface_orderline_notes' => false,
        'is_posbox' => false,
        'iface_scan_via_proxy' => false,
        'iface_electronic_scale' => false,
        'iface_vkeyboard' => false,
        'iface_customer_facing_display' => false,
        'restrict_price_control' => false,
        'cash_control' => false,
    ],

    // SSO token expiration (minutes)
    'sso_token_expiration' => 60,

    // Deployment timeout (seconds)
    'deployment_timeout' => 300, // 5 minutes

    // API timeout (seconds)
    'api_timeout' => 60,
];
