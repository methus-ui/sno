<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

'flare' => [
    'key' => env('FLARE_KEY'),
],
'serpapi' => [
    'key' => env('SERPAPI_KEY'),
],

'gemini' => [
    'api_key' => env('GEMINI_API_KEY'),
    'model' => env('GEMINI_MODEL', 'gemini-2.0-flash'),
],

'anthropic' => [
    'api_key' => env('ANTHROPIC_API_KEY'),
    'bill_scan_model' => env('ANTHROPIC_BILL_SCAN_MODEL', 'claude-3-haiku-20240307'),
],

'bill_scan' => [
    'tesseract_enabled' => env('BILL_SCAN_TESSERACT_ENABLED', true),
    'ai_fallback_enabled' => env('BILL_SCAN_AI_FALLBACK_ENABLED', true),
    'confidence_threshold' => env('BILL_SCAN_CONFIDENCE_THRESHOLD', 70),
],

'instagram' => [
    'download_enabled' => env('INSTAGRAM_DOWNLOAD_ENABLED', true),
    'download_timeout' => env('INSTAGRAM_DOWNLOAD_TIMEOUT', 180), // seconds
    'max_file_size' => env('INSTAGRAM_MAX_FILE_SIZE', 100 * 1024 * 1024), // 100MB
],

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

];
