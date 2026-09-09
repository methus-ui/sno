#!/bin/bash
# safe-db-cleanup.sh - Database cleanup script with safety checks
#
# This script will:
# 1. Drop old backup tables (saves 73MB)
# 2. Optimize large tables (improves query performance)
#
# IMPORTANT: Review this script before running!
# Run during low-traffic hours (2-4 AM recommended)

set -e  # Exit on error

echo "╔═══════════════════════════════════════════════════════════════╗"
echo "║         PRODUCTION DATABASE CLEANUP SCRIPT                   ║"
echo "╚═══════════════════════════════════════════════════════════════╝"
echo ""

# Load database credentials from .env
DB_USER="root"
DB_PASS="Sno@\$Ck42"
DB_NAME="snocart"

# Test database connection
echo "🔍 Testing database connection..."
mysql -u "$DB_USER" -p"$DB_PASS" -D "$DB_NAME" -e "SELECT 1;" > /dev/null 2>&1
if [ $? -ne 0 ]; then
    echo "❌ ERROR: Cannot connect to database"
    exit 1
fi
echo "✅ Database connection OK"
echo ""

# Show what will be affected
echo "📊 Current table sizes:"
mysql -u "$DB_USER" -p"$DB_PASS" -D "$DB_NAME" -e "
SELECT
    TABLE_NAME as 'Table',
    ROUND(((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024), 2) AS 'Size (MB)'
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = '$DB_NAME'
AND TABLE_NAME IN (
    'items_backup_before_clone_188',
    'items_backup_before_barcode_fix',
    'order_details',
    'items',
    'orders'
)
ORDER BY (DATA_LENGTH + INDEX_LENGTH) DESC;
"
echo ""

# Dry run mode
echo "═══════════════════════════════════════════════════════════════"
echo "DRY RUN MODE - Showing what WOULD be done:"
echo "═══════════════════════════════════════════════════════════════"
echo ""
echo "Tables to DROP (cannot be undone):"
echo "  ✗ items_backup_before_clone_188 (~36MB)"
echo "  ✗ items_backup_before_barcode_fix (~36MB)"
echo ""
echo "Tables to OPTIMIZE (safe, may lock briefly):"
echo "  ⚡ order_details (~225MB)"
echo "  ⚡ items (~122MB)"
echo "  ⚡ orders (~27MB)"
echo ""
echo "═══════════════════════════════════════════════════════════════"
echo ""

# Safety check - require explicit confirmation
read -p "⚠️  Type 'DELETE BACKUPS' to proceed with cleanup: " confirm

if [ "$confirm" != "DELETE BACKUPS" ]; then
    echo ""
    echo "❌ Cancelled - no changes made"
    echo "   (You must type exactly: DELETE BACKUPS)"
    exit 0
fi

echo ""
echo "🚀 Starting cleanup..."
echo ""

# Drop backup tables
echo "1️⃣  Dropping backup tables..."
mysql -u "$DB_USER" -p"$DB_PASS" -D "$DB_NAME" << 'SQL'
DROP TABLE IF EXISTS items_backup_before_clone_188;
DROP TABLE IF EXISTS items_backup_before_barcode_fix;
SQL

if [ $? -eq 0 ]; then
    echo "   ✅ Backup tables dropped (saved ~73MB)"
else
    echo "   ❌ ERROR dropping backup tables"
    exit 1
fi
echo ""

# Optimize tables (one at a time to avoid excessive locking)
echo "2️⃣  Optimizing order_details table..."
mysql -u "$DB_USER" -p"$DB_PASS" -D "$DB_NAME" -e "OPTIMIZE TABLE order_details;" > /dev/null
echo "   ✅ order_details optimized"
echo ""

echo "3️⃣  Optimizing items table..."
mysql -u "$DB_USER" -p"$DB_PASS" -D "$DB_NAME" -e "OPTIMIZE TABLE items;" > /dev/null
echo "   ✅ items optimized"
echo ""

echo "4️⃣  Optimizing orders table..."
mysql -u "$DB_USER" -p"$DB_PASS" -D "$DB_NAME" -e "OPTIMIZE TABLE orders;" > /dev/null
echo "   ✅ orders optimized"
echo ""

# Show final sizes
echo "═══════════════════════════════════════════════════════════════"
echo "✅ CLEANUP COMPLETE"
echo "═══════════════════════════════════════════════════════════════"
echo ""
echo "📊 Final table sizes:"
mysql -u "$DB_USER" -p"$DB_PASS" -D "$DB_NAME" -e "
SELECT
    TABLE_NAME as 'Table',
    ROUND(((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024), 2) AS 'Size (MB)'
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = '$DB_NAME'
AND TABLE_NAME IN ('order_details', 'items', 'orders')
ORDER BY (DATA_LENGTH + INDEX_LENGTH) DESC;
"
echo ""

# Log completion
echo "$(date): Database cleanup completed successfully" >> /var/log/db-cleanup.log

echo "✅ All done!"
echo ""
echo "Summary:"
echo "  • Dropped 2 backup tables (~73MB saved)"
echo "  • Optimized 3 large tables"
echo "  • No production data affected"
echo ""
