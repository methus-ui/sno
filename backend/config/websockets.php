<?php

use BeyondCode\LaravelWebSockets\Dashboard\Http\Middleware\Authorize;

return [

    'dashboard' => [
        'port' => env('LARAVEL_WEBSOCKETS_PORT', 6001),
    ],

    /*
     * Register your apps here
     */
'apps' => [
    [
        'id' => env('PUSHER_APP_ID'),
        'name' => 'Snocart',
        'key' => env('PUSHER_APP_KEY'),
        'secret' => env('PUSHER_APP_SECRET'),
        'path' => null,
        'capacity' => null,
        'enable_client_messages' => true,
        'enable_statistics' => true,
    ],
],

    'handlers' => [
        'delivery-man/live-location' => \App\WebSockets\Handler\DMLocationSocketHandler::class,
    ],

    'app_provider' => BeyondCode\LaravelWebSockets\Apps\ConfigAppProvider::class,

    'allowed_origins' => [
        'https://new.snocart.com',
        'http://new.snocart.com',
        'new.snocart.com',
    ],

    'max_request_size_in_kb' => 250,

    'path' => 'laravel-websockets',

'middleware' => [
    'web',
],
    'statistics' => [
        'model' => \BeyondCode\LaravelWebSockets\Statistics\Models\WebSocketsStatisticsEntry::class,
        'logger' => BeyondCode\LaravelWebSockets\Statistics\Logger\HttpStatisticsLogger::class,
        'interval_in_seconds' => 60,
        'delete_statistics_older_than_days' => 60,
        'perform_dns_lookup' => false,
    ],

    'ssl' => [
        'local_cert' => env('LARAVEL_WEBSOCKETS_SSL_LOCAL_CERT', null),
        'local_pk' => env('LARAVEL_WEBSOCKETS_SSL_LOCAL_PK', null),
        'passphrase' => env('LARAVEL_WEBSOCKETS_SSL_PASSPHRASE', null),
        'allow_self_signed' => true,
        'verify_peer' => false,
    ],

    'channel_manager' => \BeyondCode\LaravelWebSockets\WebSockets\Channels\ChannelManagers\ArrayChannelManager::class,
];
