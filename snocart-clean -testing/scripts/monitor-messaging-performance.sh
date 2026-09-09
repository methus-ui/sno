#!/bin/bash

# Messaging System Performance Monitor
# Usage: bash scripts/monitor-messaging-performance.sh

cd /var/www/html/new_public/new

echo "=== MESSAGING SYSTEM PERFORMANCE MONITOR ==="
echo "Started: $(date)"
echo ""

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Check if optimizations are enabled
echo "1. Configuration Check:"
if grep -q "MESSAGING_OPTIMIZATIONS_ENABLED=true" .env 2>/dev/null; then
    echo -e "   ${GREEN}✓${NC} Optimizations: ENABLED"
else
    echo -e "   ${YELLOW}⚠${NC} Optimizations: DISABLED (set MESSAGING_OPTIMIZATIONS_ENABLED=true in .env)"
fi
echo ""

# Check database indexes
echo "2. Database Indexes:"
INDEX_COUNT=$(mysql -u $(grep DB_USERNAME .env | cut -d '=' -f2) -p$(grep DB_PASSWORD .env | cut -d '=' -f2) -D $(grep DB_DATABASE .env | cut -d '=' -f2) -se "SELECT COUNT(DISTINCT Key_name) FROM information_schema.statistics WHERE table_name = 'messages' AND index_name LIKE 'idx_msg_%'" 2>/dev/null)

if [ "$INDEX_COUNT" -eq "5" ]; then
    echo -e "   ${GREEN}✓${NC} All 5 indexes present"
else
    echo -e "   ${RED}✗${NC} Only $INDEX_COUNT indexes found (expected 5)"
fi
echo ""

# Check cache driver
echo "3. Cache Status:"
CACHE_DRIVER=$(grep CACHE_DRIVER .env | cut -d '=' -f2)
echo "   Cache Driver: $CACHE_DRIVER"

# Test cache functionality
php artisan tinker --execute="Cache::put('test_monitor', 'ok', 60); echo Cache::get('test_monitor') === 'ok' ? 'Working' : 'Failed';" 2>/dev/null
echo ""

# Check error logs (last 10 lines with "message" or "conversation")
echo "4. Recent Errors (last 24 hours):"
ERROR_COUNT=$(grep -i "error\|exception\|failed" storage/logs/laravel-$(date +%Y-%m-%d).log 2>/dev/null | grep -i "message\|conversation" | wc -l)

if [ "$ERROR_COUNT" -eq "0" ]; then
    echo -e "   ${GREEN}✓${NC} No errors in logs"
else
    echo -e "   ${YELLOW}⚠${NC} Found $ERROR_COUNT errors:"
    grep -i "error\|exception\|failed" storage/logs/laravel-$(date +%Y-%m-%d).log 2>/dev/null | grep -i "message\|conversation" | tail -5
fi
echo ""

# Check database statistics
echo "5. Database Statistics:"
php artisan tinker --execute="
use Illuminate\Support\Facades\DB;
\$conversations = DB::table('conversations')->where('sender_type', 'admin')->orWhere('receiver_type', 'admin')->count();
\$messages = DB::table('messages')->count();
\$avgMessages = \$conversations > 0 ? round(\$messages / \$conversations, 1) : 0;
echo 'Conversations: ' . \$conversations . PHP_EOL;
echo 'Messages: ' . \$messages . PHP_EOL;
echo 'Avg per conversation: ' . \$avgMessages . PHP_EOL;
" 2>/dev/null
echo ""

# Check route exists
echo "6. Route Check:"
if php artisan route:list --name=admin.message.get-messages 2>/dev/null | grep -q "get-messages"; then
    echo -e "   ${GREEN}✓${NC} Pagination route registered"
else
    echo -e "   ${RED}✗${NC} Pagination route missing"
fi
echo ""

# Performance test (if data exists)
echo "7. Quick Performance Test:"
php artisan tinker --execute="
use Illuminate\Support\Facades\DB;
\$convId = DB::table('conversations')->where('sender_type', 'admin')->orWhere('receiver_type', 'admin')->value('id');
if (\$convId) {
    \$start = microtime(true);
    \$count = DB::table('messages')->where('conversation_id', \$convId)->count();
    \$duration = (microtime(true) - \$start) * 1000;
    echo 'Query time: ' . round(\$duration, 2) . 'ms (\$count messages)' . PHP_EOL;
    if (\$duration < 10) {
        echo 'Status: EXCELLENT (< 10ms)' . PHP_EOL;
    } elseif (\$duration < 50) {
        echo 'Status: GOOD (< 50ms)' . PHP_EOL;
    } else {
        echo 'Status: SLOW (> 50ms) - may need optimization' . PHP_EOL;
    }
} else {
    echo 'No test data available' . PHP_EOL;
}
" 2>/dev/null
echo ""

# Check cache statistics
echo "8. Template Cache Status:"
php artisan tinker --execute="
use Illuminate\Support\Facades\Cache;
if (Cache::has('message_templates_active')) {
    echo 'Templates cached: YES' . PHP_EOL;
    echo 'TTL: ' . config('messaging_performance.template_cache_ttl', 300) . ' seconds' . PHP_EOL;
} else {
    echo 'Templates cached: NO (will be cached on first access)' . PHP_EOL;
}
" 2>/dev/null
echo ""

# Recommendations
echo "9. Recommendations:"
if [ "$INDEX_COUNT" -lt "5" ]; then
    echo -e "   ${YELLOW}⚠${NC} Run migration: php artisan migrate --path=database/migrations/2026_02_23_000001_optimize_messages_table_indexes.php --force"
fi

if [ "$ERROR_COUNT" -gt "10" ]; then
    echo -e "   ${YELLOW}⚠${NC} High error rate - check logs: tail -f storage/logs/laravel-\$(date +%Y-%m-%d).log"
fi

if [ "$CACHE_DRIVER" = "file" ]; then
    echo -e "   ${YELLOW}ℹ${NC} Consider using Redis for better cache performance"
fi

echo ""
echo "=== MONITORING COMPLETE ==="
echo "Run this script periodically to ensure optimal performance"
echo ""
echo "Useful commands:"
echo "  - Watch logs: tail -f storage/logs/laravel-\$(date +%Y-%m-%d).log"
echo "  - Clear cache: php artisan cache:clear && php artisan config:clear"
echo "  - Verify setup: php scripts/verify-messaging-optimizations.php"
echo "  - Rollback: bash scripts/rollback-messaging-optimizations.sh"
