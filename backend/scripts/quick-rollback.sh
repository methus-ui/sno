#!/bin/bash
# Quick rollback script for emergency situations
# Usage: bash scripts/quick-rollback.sh [level1|level2|level3]

ROLLBACK_LEVEL="${1:-level1}"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
LOG_FILE="/var/log/rollback_$TIMESTAMP.log"
DB_USER="snocartapp_dev"
DB_PASS="snocart@1234"
DB_NAME="snocartapp_new"

log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a "$LOG_FILE"
}

log "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
log "🔴 EMERGENCY ROLLBACK INITIATED - $ROLLBACK_LEVEL"
log "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

case "$ROLLBACK_LEVEL" in
    "level1")
        log "🔧 Level 1: Disabling transaction sync via feature flag"

        cd /var/www/html/new_public/new

        # Add feature flag to .env
        if ! grep -q "ENABLE_TRANSACTION_SYNC" .env; then
            echo "ENABLE_TRANSACTION_SYNC=false" >> .env
            log "✅ Feature flag added to .env"
        else
            sed -i 's/ENABLE_TRANSACTION_SYNC=.*/ENABLE_TRANSACTION_SYNC=false/' .env
            log "✅ Feature flag set to false"
        fi

        # Clear config cache
        php artisan config:clear
        log "✅ Config cache cleared"

        log "✅ Level 1 rollback completed - Transaction sync disabled"
        log "ℹ️  System continues working with old behavior (bug returns but no crashes)"
        ;;

    "level2")
        log "🔧 Level 2: Disabling inline editing UI"

        cd /var/www/html/new_public/new

        # Add UI feature flag
        if ! grep -q "ENABLE_INLINE_EDITING" .env; then
            echo "ENABLE_INLINE_EDITING=false" >> .env
            log "✅ UI feature flag added"
        else
            sed -i 's/ENABLE_INLINE_EDITING=.*/ENABLE_INLINE_EDITING=false/' .env
            log "✅ UI feature flag set to false"
        fi

        # Clear caches
        php artisan cache:clear
        php artisan view:clear
        log "✅ Caches cleared"

        log "✅ Level 2 rollback completed - UI reverted to old flow"
        ;;

    "level3")
        log "🔧 Level 3: FULL SYSTEM ROLLBACK"
        log "⚠️  This will revert ALL changes"

        read -p "⚠️  Type 'ROLLBACK' to confirm full rollback: " confirm
        if [ "$confirm" != "ROLLBACK" ]; then
            log "❌ Rollback cancelled by user"
            exit 1
        fi

        log "🔄 Step 1: Finding latest backup..."
        LATEST_BACKUP=$(ls -t /var/backups/order-editing-implementation-*.tar.gz 2>/dev/null | head -1)

        if [ -z "$LATEST_BACKUP" ]; then
            log "❌ ERROR: No backup found in /var/backups/"
            log "❌ Cannot proceed with rollback"
            exit 1
        fi

        log "📦 Using backup: $LATEST_BACKUP"

        # Extract backup
        BACKUP_DIR=$(mktemp -d)
        tar -xzf "$LATEST_BACKUP" -C "$BACKUP_DIR"
        EXTRACTED=$(find "$BACKUP_DIR" -maxdepth 1 -type d | tail -1)

        cd /var/www/html/new_public/new

        log "🔄 Step 2: Putting site in maintenance mode..."
        php artisan down --message="Emergency rollback in progress" --retry=60

        log "🔄 Step 3: Rolling back database migration..."
        php artisan migrate:rollback --step=1 --force

        log "🔄 Step 4: Restoring database from backup..."
        gunzip -c "$EXTRACTED/full_database_backup.sql.gz" | \
            mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME"
        log "✅ Database restored"

        log "🔄 Step 5: Reverting code changes..."
        # Remove new service file
        rm -f app/Services/OrderTransactionService.php
        log "✅ Service file removed"

        log "🔄 Step 6: Clearing all caches..."
        php artisan cache:clear
        php artisan config:clear
        php artisan view:clear
        php artisan route:clear
        php artisan optimize:clear

        log "🔄 Step 7: Verifying database integrity..."
        mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "
            SELECT
                'orders' as tbl,
                COUNT(*) as rows
            FROM orders
            UNION ALL
            SELECT 'order_transactions', COUNT(*) FROM order_transactions
        "

        log "🔄 Step 8: Bringing site back online..."
        php artisan up

        log "✅ Level 3 rollback completed - System fully restored"
        log "⚠️  Review logs at: $LOG_FILE"
        ;;

    *)
        log "❌ Invalid rollback level: $ROLLBACK_LEVEL"
        log "Usage: bash scripts/quick-rollback.sh [level1|level2|level3]"
        exit 1
        ;;
esac

log "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
log "✅ ROLLBACK COMPLETED SUCCESSFULLY"
log "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
log "📋 Log file: $LOG_FILE"
log "🔍 Next steps:"
log "  1. Verify order editing works (old flow)"
log "  2. Check no errors in: tail -f storage/logs/laravel.log"
log "  3. Monitor for 30 minutes"
log "  4. Notify team of rollback completion"

exit 0
