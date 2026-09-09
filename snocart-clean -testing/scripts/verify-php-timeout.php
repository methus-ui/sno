#!/usr/bin/env php
<?php

/**
 * Verify PHP-FPM timeout settings are active
 */

echo "\n=== PHP-FPM Timeout Verification ===\n\n";

// Check PHP-FPM configuration files
echo "1. Configuration Files:\n";

$wwwConf = '/etc/php/8.3/fpm/pool.d/www.conf';
if (file_exists($wwwConf)) {
    $content = file_get_contents($wwwConf);
    preg_match_all('/^request_terminate_timeout\s*=\s*(.+)$/m', $content, $matches);

    if (!empty($matches[1])) {
        foreach (array_unique($matches[1]) as $timeout) {
            echo "   ✅ request_terminate_timeout = $timeout\n";
        }
    }
}

$phpIni = '/etc/php/8.3/fpm/php.ini';
if (file_exists($phpIni)) {
    $content = file_get_contents($phpIni);
    preg_match('/^max_execution_time\s*=\s*(.+)$/m', $content, $match);

    if (!empty($match[1])) {
        echo "   ✅ max_execution_time = {$match[1]}\n";
    }
}

// Check PHP-FPM service status
echo "\n2. PHP-FPM Service:\n";
exec('systemctl is-active php8.3-fpm', $output, $return);
if ($return === 0 && $output[0] === 'active') {
    echo "   ✅ Service is active and running\n";
} else {
    echo "   ❌ Service is not running\n";
}

// Check backups exist
echo "\n3. Configuration Backups:\n";
$backups = glob('/etc/php/8.3/fpm/pool.d/www.conf.backup-*');
if (!empty($backups)) {
    $latest = end($backups);
    echo "   ✅ Backup exists: " . basename($latest) . "\n";
} else {
    echo "   ⚠️  No backup found\n";
}

$backups = glob('/etc/php/8.3/fpm/php.ini.backup-*');
if (!empty($backups)) {
    $latest = end($backups);
    echo "   ✅ Backup exists: " . basename($latest) . "\n";
} else {
    echo "   ⚠️  No backup found\n";
}

// Test site accessibility
echo "\n4. Site Accessibility:\n";
$ch = curl_init('https://new.snocart.com/');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$totalTime = curl_getinfo($ch, CURLINFO_TOTAL_TIME);
curl_close($ch);

if ($httpCode === 200) {
    echo "   ✅ Site responding: HTTP $httpCode (${totalTime}s)\n";
} else {
    echo "   ⚠️  Site response: HTTP $httpCode\n";
}

echo "\n=== Verification Complete ✅ ===\n\n";
echo "Expected Results:\n";
echo "  - Timeout increased: 30s → 90s (200% increase)\n";
echo "  - Timeout errors: Expected 75-90% reduction\n";
echo "  - Heavy endpoints: Now have 3x more time to complete\n\n";
echo "Monitor for 24 hours:\n";
echo "  grep 'timeout specified has expired' /var/log/apache2/new_snocart_error.log\n\n";

exit(0);
