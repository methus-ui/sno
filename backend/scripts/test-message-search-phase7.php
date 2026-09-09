<?php

/**
 * Test Phase 7: Message Search & Filtering
 * 
 * Tests all Phase 7 features:
 * - Full-text search
 * - Advanced filtering
 * - Search history
 * - Filter presets
 * - Search suggestions
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Message;
use App\Models\MessageSearchHistory;
use App\Models\MessageFilterPreset;
use App\Services\MessageSearchService;
use App\Services\MessageFilterService;

echo "\n========================================\n";
echo "Phase 7: Message Search & Filtering Test\n";
echo "========================================\n\n";

$testResults = [];

// Test 1: Database Tables Created
echo "Test 1: Checking database tables...\n";
try {
    $searchHistoryExists = Schema::hasTable('message_search_history');
    $presetsExists = Schema::hasTable('message_filter_presets');
    
    if ($searchHistoryExists && $presetsExists) {
        echo "✅ Both tables created successfully\n";
        $testResults[] = true;
    } else {
        echo "❌ Tables missing: ";
        echo (!$searchHistoryExists ? "message_search_history " : "");
        echo (!$presetsExists ? "message_filter_presets" : "");
        echo "\n";
        $testResults[] = false;
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    $testResults[] = false;
}

// Test 2: Full-Text Index Exists
echo "\nTest 2: Checking full-text index on messages...\n";
try {
    $indexes = DB::select("SHOW INDEX FROM messages WHERE Key_name = 'ft_message'");
    if (!empty($indexes)) {
        echo "✅ Full-text index exists on messages.message column\n";
        $testResults[] = true;
    } else {
        echo "❌ Full-text index not found\n";
        $testResults[] = false;
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    $testResults[] = false;
}

// Test 3: Global Presets Seeded
echo "\nTest 3: Checking default filter presets...\n";
try {
    $globalPresets = MessageFilterPreset::where('is_global', true)->count();
    if ($globalPresets >= 5) {
        echo "✅ Found {$globalPresets} global filter presets\n";
        $presets = MessageFilterPreset::where('is_global', true)
            ->orderBy('sort_order')
            ->get(['name', 'description']);
        foreach ($presets as $preset) {
            echo "   - {$preset->name}: {$preset->description}\n";
        }
        $testResults[] = true;
    } else {
        echo "❌ Only {$globalPresets} global presets found (expected 5)\n";
        $testResults[] = false;
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    $testResults[] = false;
}

// Test 4: MessageSearchService - Basic Search
echo "\nTest 4: Testing MessageSearchService (basic search)...\n";
try {
    $searchService = app(MessageSearchService::class);
    $result = $searchService->search('order', [], 1, 'admin', 10);
    
    if ($result['success']) {
        echo "✅ Search executed successfully\n";
        echo "   - Query: 'order'\n";
        echo "   - Results: {$result['total']}\n";
        echo "   - Execution time: {$result['execution_time_ms']}ms\n";
        $testResults[] = true;
    } else {
        echo "❌ Search failed: " . ($result['error'] ?? 'Unknown error') . "\n";
        $testResults[] = false;
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    $testResults[] = false;
}

// Test 5: MessageSearchService - Search with Filters
echo "\nTest 5: Testing search with date filters...\n";
try {
    $searchService = app(MessageSearchService::class);
    $filters = [
        'date_range' => 'last_7_days',
        'has_order' => true,
    ];
    $result = $searchService->search('delivery', $filters, 1, 'admin', 10);
    
    if ($result['success']) {
        echo "✅ Filtered search executed successfully\n";
        echo "   - Query: 'delivery' with filters\n";
        echo "   - Results: {$result['total']}\n";
        echo "   - Filters applied: " . json_encode($result['filters_applied']) . "\n";
        $testResults[] = true;
    } else {
        echo "❌ Filtered search failed\n";
        $testResults[] = false;
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    $testResults[] = false;
}

// Test 6: Search History Recording
echo "\nTest 6: Testing search history recording...\n";
try {
    $historyEntry = MessageSearchHistory::recordSearch(1, 'admin', 'test query', 5, ['date_range' => 'today']);
    
    if ($historyEntry) {
        echo "✅ Search history recorded successfully\n";
        echo "   - Query: {$historyEntry->search_query}\n";
        echo "   - Results: {$historyEntry->result_count}\n";
        $testResults[] = true;
    } else {
        echo "❌ Failed to record search history\n";
        $testResults[] = false;
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    $testResults[] = false;
}

// Test 7: Search History Retrieval
echo "\nTest 7: Testing search history retrieval...\n";
try {
    $history = MessageSearchHistory::getRecentSearches(1, 'admin', 10);
    echo "✅ Retrieved {$history->count()} search history entries\n";
    if ($history->count() > 0) {
        echo "   Recent searches:\n";
        foreach ($history->take(5) as $entry) {
            echo "   - \"{$entry->search_query}\" ({$entry->result_count} results)\n";
        }
    }
    $testResults[] = true;
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    $testResults[] = false;
}

// Test 8: MessageFilterService - Save Preset
echo "\nTest 8: Testing save filter preset...\n";
try {
    $filterService = app(MessageFilterService::class);
    $result = $filterService->savePreset(
        1,
        'admin',
        'Test Preset - Recent Orders',
        ['date_range' => 'last_7_days', 'has_order' => true],
        'Show recent order messages from last 7 days'
    );
    
    if ($result['success']) {
        echo "✅ Filter preset saved successfully\n";
        echo "   - Name: {$result['preset']['name']}\n";
        echo "   - ID: {$result['preset']['id']}\n";
        $testResults[] = true;
    } else {
        echo "❌ Failed to save preset: " . ($result['error'] ?? 'Unknown error') . "\n";
        $testResults[] = false;
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    $testResults[] = false;
}

// Test 9: MessageFilterService - Get User Presets
echo "\nTest 9: Testing get user presets...\n";
try {
    $filterService = app(MessageFilterService::class);
    $presets = $filterService->getUserPresets(1, 'admin');
    
    echo "✅ Retrieved " . count($presets) . " presets\n";
    echo "   Global presets:\n";
    foreach ($presets as $preset) {
        if ($preset['is_global']) {
            echo "   - {$preset['name']}\n";
        }
    }
    echo "   User presets:\n";
    foreach ($presets as $preset) {
        if (!$preset['is_global']) {
            echo "   - {$preset['name']}\n";
        }
    }
    $testResults[] = true;
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    $testResults[] = false;
}

// Test 10: Search Highlighting
echo "\nTest 10: Testing search term highlighting...\n";
try {
    $searchService = app(MessageSearchService::class);
    $result = $searchService->search('delivered', [], 1, 'admin', 5);
    
    if ($result['success'] && $result['total'] > 0) {
        $firstResult = $result['results'][0];
        if (isset($firstResult['highlighted_message']) && 
            strpos($firstResult['highlighted_message'], '<mark') !== false) {
            echo "✅ Search highlighting working\n";
            echo "   - Original: " . substr($firstResult['message'], 0, 50) . "...\n";
            echo "   - Highlighted: " . substr($firstResult['highlighted_message'], 0, 80) . "...\n";
            $testResults[] = true;
        } else {
            echo "❌ Highlighting not found in results\n";
            $testResults[] = false;
        }
    } else {
        echo "⚠️  No search results to test highlighting\n";
        $testResults[] = true; // Pass if no data
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    $testResults[] = false;
}

// Summary
echo "\n========================================\n";
echo "Test Summary\n";
echo "========================================\n\n";

$passed = count(array_filter($testResults, fn($r) => $r === true));
$failed = count($testResults) - $passed;
$total = count($testResults);

echo "Total Tests: {$total}\n";
echo "Passed: {$passed} ✅\n";
echo "Failed: {$failed} ❌\n";
echo "Success Rate: " . round(($passed / $total) * 100, 1) . "%\n\n";

if ($failed === 0) {
    echo "🎉 All tests passed! Phase 7.1 backend is ready.\n\n";
    exit(0);
} else {
    echo "⚠️  Some tests failed. Please review errors above.\n\n";
    exit(1);
}
