#!/bin/bash
# Performance Monitoring Script
# Monitors the impact of the 2026-02-08 performance fixes

echo "========================================="
echo "Performance Monitor - $(date)"
echo "========================================="
echo ""

LOG_FILE="storage/logs/laravel-$(date +%Y-%m-%d).log"

echo "📊 SLOW QUERY STATISTICS (Last 100 log entries)"
echo "================================================"

# Count slow queries in last 100 lines
RECENT_SLOW=$(tail -100 "$LOG_FILE" | grep -c "Slow query" || echo "0")
echo "Recent slow queries (last 100 lines): $RECENT_SLOW"

# Count slow queries today
TOTAL_SLOW=$(grep -c "Slow query" "$LOG_FILE" || echo "0")
echo "Total slow queries today: $TOTAL_SLOW"

echo ""
echo "🔍 BREAKDOWN BY QUERY TYPE (Today)"
echo "===================================="

# GET_LOCK queries
LOCK_QUERIES=$(grep "Slow query" "$LOG_FILE" | grep -c "GET_LOCK" || echo "0")
echo "GET_LOCK queries: $LOCK_QUERIES"

# Cart queries
CART_QUERIES=$(grep "Slow query" "$LOG_FILE" | grep -c "carts" || echo "0")
echo "Cart queries: $CART_QUERIES"

# Delivery history queries
DELIVERY_QUERIES=$(grep "Slow query" "$LOG_FILE" | grep -c "delivery_histories" || echo "0")
echo "Delivery history queries: $DELIVERY_QUERIES"

echo ""
echo "✅ NULL PROPERTY ERRORS"
echo "======================="
NULL_ERRORS=$(grep -c "Attempt to read property" "$LOG_FILE" || echo "0")
echo "Null property access errors today: $NULL_ERRORS"

echo ""
echo "📈 DATABASE INDEXES"
echo "==================="
mysql -u snocart_app_user -p'Sno@$Ck42' snocart -se "
SELECT
    'carts_user_guest_module_idx' as index_name,
    CASE WHEN COUNT(*) > 0 THEN '✅ EXISTS' ELSE '❌ MISSING' END as status
FROM information_schema.statistics
WHERE table_schema = 'snocart'
    AND table_name = 'carts'
    AND index_name = 'carts_user_guest_module_idx'
UNION ALL
SELECT
    'delivery_histories_unique_dm_id' as index_name,
    CASE WHEN COUNT(*) > 0 THEN '✅ EXISTS' ELSE '❌ MISSING' END as status
FROM information_schema.statistics
WHERE table_schema = 'snocart'
    AND table_name = 'delivery_histories'
    AND index_name = 'delivery_histories_delivery_man_id_unique'
" 2>/dev/null

echo ""
echo "⏱️  AVERAGE QUERY TIMES (if data available)"
echo "==========================================="
echo "Monitoring... check back in 1 hour for meaningful data"

echo ""
echo "💡 TIP: Run this script hourly to track improvements"
echo "     Example: watch -n 3600 ./monitor_performance.sh"
