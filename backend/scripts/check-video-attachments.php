#!/usr/bin/env php
<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n🔍 Checking Video Attachments Issue\n";
echo "=====================================\n\n";

// Check video advertisements
$videoAds = DB::table('advertisements')
    ->where('add_type', 'video_promotion')
    ->orderBy('id', 'desc')
    ->limit(10)
    ->get();

echo "📊 Recent Video Advertisements (Last 10):\n\n";

if ($videoAds->isEmpty()) {
    echo "❌ No video advertisements found in database\n";
} else {
    foreach ($videoAds as $ad) {
        echo "Ad #{$ad->id} - {$ad->title}\n";
        echo "  Status: {$ad->status}\n";
        echo "  Video Source: " . ($ad->video_source ?? 'upload') . "\n";
        echo "  Video Attachment: " . ($ad->video_attachment ?? '❌ NULL') . "\n";
        echo "  Instagram URL: " . ($ad->instagram_reel_url ?? '❌ NULL') . "\n";

        // Check if file exists
        if ($ad->video_attachment) {
            $filePath = storage_path('app/public/advertisement/' . $ad->video_attachment);
            $exists = file_exists($filePath);
            echo "  File Exists: " . ($exists ? '✅ YES' : '❌ NO') . "\n";
            if ($exists) {
                $size = filesize($filePath);
                echo "  File Size: " . number_format($size / 1024 / 1024, 2) . " MB\n";
            }
        }
        echo "\n";
    }
}

// Check storage directory
echo "\n📁 Storage Directory Check:\n\n";
$dir = storage_path('app/public/advertisement/');
if (is_dir($dir)) {
    echo "✅ Directory exists: {$dir}\n";
    echo "   Permissions: " . substr(sprintf('%o', fileperms($dir)), -4) . "\n";
    echo "   Writable: " . (is_writable($dir) ? '✅ YES' : '❌ NO') . "\n";

    // Count files
    $files = glob($dir . '*');
    echo "   Total files: " . count($files) . "\n";

    // Show video files
    $videoFiles = glob($dir . '*.{mp4,webm,mkv,avi,mov}', GLOB_BRACE);
    echo "   Video files: " . count($videoFiles) . "\n";

    if (!empty($videoFiles)) {
        echo "\n   Recent video files:\n";
        foreach (array_slice($videoFiles, 0, 5) as $file) {
            $filename = basename($file);
            $size = filesize($file) / 1024 / 1024;
            echo "   - {$filename} (" . number_format($size, 2) . " MB)\n";
        }
    }
} else {
    echo "❌ Directory does NOT exist: {$dir}\n";
    echo "   Creating directory...\n";
    mkdir($dir, 0755, true);
    echo "   ✅ Directory created\n";
}

// Check symlink
echo "\n🔗 Symlink Check:\n\n";
$linkPath = public_path('storage');
if (is_link($linkPath)) {
    echo "✅ Symlink exists: {$linkPath}\n";
    echo "   Points to: " . readlink($linkPath) . "\n";
} else {
    echo "❌ Symlink does NOT exist\n";
    echo "   Run: php artisan storage:link\n";
}

echo "\n";
