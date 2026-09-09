<?php

use Illuminate\Support\Facades\DB;

// Get Razorpay configuration from addon_settings
$razorpayConfig = null;
try {
    $config = DB::table('addon_settings')
        ->where('key_name', 'razor_pay')
        ->first();

    if ($config) {
        $mode = $config->mode ?? 'live';
        $values = json_decode($mode === 'live' ? $config->live_values : $config->test_values, true);
        $razorpayConfig = $values;
    }
} catch (\Exception $e) {
    // Database not available yet (during migration, etc.)
    $razorpayConfig = null;
}

return [
    'webhook_secret' => $razorpayConfig['webhook_secret'] ?? env('RAZORPAY_WEBHOOK_SECRET', null),
    'api_key' => $razorpayConfig['api_key'] ?? env('RAZORPAY_KEY', null),
    'api_secret' => $razorpayConfig['api_secret'] ?? env('RAZORPAY_SECRET', null),
    'mode' => $razorpayConfig['mode'] ?? 'live',
];
