<?php
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "OPcache cleared!\n";
} else {
    echo "OPcache not enabled\n";
}

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

\Illuminate\Support\Facades\Cache::forget('business_settings_config_keys');
\Illuminate\Support\Facades\Artisan::call('cache:clear');
echo "Laravel cache cleared!\n";

unlink(__FILE__);
