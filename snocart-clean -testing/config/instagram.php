<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Instagram Graph API Configuration
    |--------------------------------------------------------------------------
    |
    | Configure your Instagram/Facebook App credentials here.
    | You need to create a Facebook App and add Instagram Graph API product.
    |
    | Steps to get credentials:
    | 1. Create a Facebook App at https://developers.facebook.com/
    | 2. Add Instagram Graph API product to your app
    | 3. Get your App ID and App Secret
    | 4. Generate access tokens for Instagram Business accounts
    |
    */

    'app_id' => env('INSTAGRAM_APP_ID', ''),
    'app_secret' => env('INSTAGRAM_APP_SECRET', ''),
    'app_access_token' => env('INSTAGRAM_APP_ACCESS_TOKEN', ''),

    /*
    |--------------------------------------------------------------------------
    | API Version
    |--------------------------------------------------------------------------
    |
    | Instagram Graph API version to use
    |
    */
    'api_version' => env('INSTAGRAM_API_VERSION', 'v21.0'),

    /*
    |--------------------------------------------------------------------------
    | Redirect URI
    |--------------------------------------------------------------------------
    |
    | OAuth redirect URI for Instagram authentication
    |
    */
    'redirect_uri' => env('INSTAGRAM_REDIRECT_URI', env('APP_URL') . '/admin/instagram/callback'),

    /*
    |--------------------------------------------------------------------------
    | Cache Duration
    |--------------------------------------------------------------------------
    |
    | Duration (in seconds) to cache Instagram reels data
    | Default: 3600 seconds (1 hour)
    |
    */
    'cache_duration' => env('INSTAGRAM_CACHE_DURATION', 3600),

    /*
    |--------------------------------------------------------------------------
    | Enable Instagram Integration
    |--------------------------------------------------------------------------
    |
    | Enable or disable Instagram integration globally
    |
    */
    'enabled' => env('INSTAGRAM_INTEGRATION_ENABLED', true),

];
