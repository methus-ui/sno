#!/bin/bash
# Rollback script for performance fixes applied on 2026-01-12
# Run this script if you experience any issues after the performance update

BACKUP_DIR="/var/www/html/new_public/new/storage/backups/performance_fix_20260112"
APP_DIR="/var/www/html/new_public/new"

echo "=== Rolling back performance fixes ==="

# Restore helpers.php
if [ -f "$BACKUP_DIR/helpers.php.bak" ]; then
    cp "$BACKUP_DIR/helpers.php.bak" "$APP_DIR/app/CentralLogics/helpers.php"
    echo "Restored: helpers.php"
else
    echo "ERROR: helpers.php backup not found"
fi

# Restore store.php
if [ -f "$BACKUP_DIR/store.php.bak" ]; then
    cp "$BACKUP_DIR/store.php.bak" "$APP_DIR/app/CentralLogics/store.php"
    echo "Restored: store.php"
else
    echo "ERROR: store.php backup not found"
fi

# Restore ItemController.php
if [ -f "$BACKUP_DIR/ItemController.php.bak" ]; then
    cp "$BACKUP_DIR/ItemController.php.bak" "$APP_DIR/app/Http/Controllers/Api/V1/ItemController.php"
    echo "Restored: ItemController.php"
else
    echo "ERROR: ItemController.php backup not found"
fi

# Clear caches
echo ""
echo "Clearing caches..."
php "$APP_DIR/artisan" cache:clear
php "$APP_DIR/artisan" config:clear
php "$APP_DIR/artisan" route:clear

echo ""
echo "=== Rollback complete ==="
echo "The files have been restored to their original state."
