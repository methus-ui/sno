#!/bin/bash
# Security Fix Rollback Script
# Created: 2026-01-28
# Run this script to revert all security fixes if issues occur

BACKUP_DIR="/var/www/html/new_public/new/storage/security_backups/20260128"
APP_DIR="/var/www/html/new_public/new"

echo "=== Security Fix Rollback Script ==="
echo "This will revert all security changes made on 2026-01-28"
echo ""

# Check if backup files exist
if [ ! -f "$BACKUP_DIR/PaymentController.php.bak" ]; then
    echo "ERROR: Backup files not found in $BACKUP_DIR"
    exit 1
fi

read -p "Are you sure you want to rollback? (yes/no): " confirm
if [ "$confirm" != "yes" ]; then
    echo "Rollback cancelled."
    exit 0
fi

echo ""
echo "Rolling back files..."

# Rollback PaymentController.php
cp "$BACKUP_DIR/PaymentController.php.bak" "$APP_DIR/app/Http/Controllers/PaymentController.php"
echo "[OK] PaymentController.php restored"

# Rollback cors.php
cp "$BACKUP_DIR/cors.php.bak" "$APP_DIR/config/cors.php"
echo "[OK] cors.php restored"

# Rollback Zone.php
cp "$BACKUP_DIR/Zone.php.bak" "$APP_DIR/app/Models/Zone.php"
echo "[OK] Zone.php restored"

# Rollback CustomerAuthController.php
cp "$BACKUP_DIR/CustomerAuthController.php.bak" "$APP_DIR/app/Http/Controllers/Api/V1/Auth/CustomerAuthController.php"
echo "[OK] CustomerAuthController.php restored"

# Rollback api.php routes
cp "$BACKUP_DIR/api.php.bak" "$APP_DIR/routes/api/v1/api.php"
echo "[OK] api.php restored"

# Rollback Kernel.php
cp "$BACKUP_DIR/Kernel.php.bak" "$APP_DIR/app/Http/Kernel.php"
echo "[OK] Kernel.php restored"

# Remove the new middleware (optional - won't cause issues if left)
if [ -f "$APP_DIR/app/Http/Middleware/ExternalApiAuth.php" ]; then
    rm "$APP_DIR/app/Http/Middleware/ExternalApiAuth.php"
    echo "[OK] ExternalApiAuth.php middleware removed"
fi

# Clear Laravel caches
echo ""
echo "Clearing Laravel caches..."
cd "$APP_DIR"
php artisan config:clear
php artisan cache:clear
php artisan route:clear

echo ""
echo "=== Rollback Complete ==="
echo "All security fixes have been reverted."
echo "Please test your application to ensure everything works correctly."
