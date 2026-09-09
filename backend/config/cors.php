<?php

/**
 * CORS Configuration
 *
 * Security Fix (2026-01-28): Added environment-based CORS configuration.
 * Set CORS_ALLOWED_ORIGINS in .env to restrict origins in production.
 * Example: CORS_ALLOWED_ORIGINS=https://yourdomain.com,https://app.yourdomain.com
 *
 * Rollback: Run storage/security_backups/20260128/ROLLBACK.sh
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    /*
    |--------------------------------------------------------------------------
    | Allowed Origins
    |--------------------------------------------------------------------------
    |
    | Set CORS_ALLOWED_ORIGINS in your .env file to restrict which domains
    | can access your API. Use comma-separated values for multiple origins.
    |
    | Example: CORS_ALLOWED_ORIGINS=https://yourdomain.com,https://app.yourdomain.com
    |
    | If not set, defaults to '*' for backwards compatibility.
    | IMPORTANT: Set this in production for security!
    |
    */
    'allowed_origins' => env('CORS_ALLOWED_ORIGINS')
        ? explode(',', env('CORS_ALLOWED_ORIGINS'))
        : ['*'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => [
        'Accept',
        'Authorization',
        'Content-Type',
        'X-Requested-With',
        'X-CSRF-TOKEN',
        'zoneId',
        'moduleId',
        'latitude',
        'longitude',
        'X-localization',
    ],

    'exposed_headers' => [],

    'max_age' => 86400, // Cache preflight for 24 hours

    'supports_credentials' => false,

];
