#!/usr/bin/env php
<?php

// Find duplicate route names in routes/admin.php
$file = __DIR__ . '/../routes/admin.php';
$content = file_get_contents($file);

// Extract all route names
preg_match_all("/->name\('([^']+)'\)/", $content, $matches);

$routeNames = $matches[1];
$counts = array_count_values($routeNames);

// Find duplicates
$duplicates = array_filter($counts, function($count) {
    return $count > 1;
});

if (empty($duplicates)) {
    echo "✅ No duplicate route names found!\n";
    exit(0);
}

echo "❌ Found " . count($duplicates) . " duplicate route names:\n\n";

foreach ($duplicates as $name => $count) {
    echo "  - '$name' appears $count times\n";

    // Find line numbers
    $lines = explode("\n", $content);
    foreach ($lines as $lineNum => $line) {
        if (strpos($line, "->name('$name')") !== false) {
            $actualLineNum = $lineNum + 1;
            $method = 'UNKNOWN';
            if (preg_match('/Route::(GET|POST|PUT|DELETE|PATCH)/i', $line, $methodMatch)) {
                $method = strtoupper($methodMatch[1]);
            }
            echo "      Line $actualLineNum: $method\n";
        }
    }
    echo "\n";
}

exit(1);
