#!/usr/bin/env php
<?php

/**
 * Test WhatsApp Dashboard Access
 * Simulates accessing /admin/whatsapp to diagnose 500 error
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  WhatsApp Dashboard - 500 Error Diagnosis                     ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "\n";

try {
    echo "📋 Testing WhatsApp Dashboard Access...\n\n";

    // Create a test request
    $request = Illuminate\Http\Request::create('/admin/whatsapp', 'GET');

    // Process the request
    $response = $kernel->handle($request);

    echo "✅ Status Code: " . $response->getStatusCode() . "\n";

    if ($response->getStatusCode() === 200) {
        echo "✅ Dashboard loaded successfully!\n";
        echo "\nResponse contains " . strlen($response->getContent()) . " bytes\n";
    } elseif ($response->getStatusCode() === 500) {
        echo "❌ 500 Internal Server Error detected!\n";
        echo "\nError details:\n";
        echo $response->getContent() . "\n";
    } else {
        echo "⚠️  Unexpected status code\n";
        echo "\nResponse preview:\n";
        echo substr($response->getContent(), 0, 500) . "...\n";
    }

} catch (\Exception $e) {
    echo "❌ EXCEPTION CAUGHT!\n\n";
    echo "Error Type: " . get_class($e) . "\n";
    echo "Error Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n\n";
    echo "Stack Trace:\n";
    echo $e->getTraceAsString() . "\n";
}

echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  Test Complete                                                 ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "\n";
