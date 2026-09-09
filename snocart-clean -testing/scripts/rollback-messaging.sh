#!/bin/bash

###############################################################################
# Messaging System Rollback Script
# Version: 1.0
# Date: 2026-03-11
# Description: Rollback messaging system to previous version
###############################################################################

set -e  # Exit on error

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

print_header() {
    echo -e "\n${BLUE}========================================${NC}"
    echo -e "${BLUE}$1${NC}"
    echo -e "${BLUE}========================================${NC}\n"
}

print_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

print_info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

# Check backup directory
BACKUP_DIR="$1"

if [ -z "$BACKUP_DIR" ]; then
    print_error "Usage: $0 <backup_directory>"
    echo ""
    echo "Available backups:"
    ls -ld /var/backups/messaging-system-* 2>/dev/null || echo "No backups found"
    exit 1
fi

if [ ! -d "$BACKUP_DIR" ]; then
    print_error "Backup directory not found: $BACKUP_DIR"
    exit 1
fi

print_header "Messaging System Rollback"
print_warning "This will restore the old messaging system"
print_info "Backup: $BACKUP_DIR"
echo ""
read -p "Are you sure you want to rollback? (yes/no): " -r
if [[ ! $REPLY =~ ^[Yy](es)?$ ]]; then
    print_info "Rollback cancelled"
    exit 0
fi

print_header "Step 1: Restore Views"

if [ -d "$BACKUP_DIR/messages_views_backup" ]; then
    print_info "Restoring views..."
    rm -rf resources/views/vendor-views/messages
    cp -r "$BACKUP_DIR/messages_views_backup" resources/views/vendor-views/messages
    print_success "Views restored"
else
    print_warning "No view backup found, skipping"
fi

print_header "Step 2: Restore Assets"

if [ -d "$BACKUP_DIR/assets" ]; then
    print_info "Removing new assets..."
    rm -f public/assets/admin/css/messaging-modern-ui.css
    rm -f public/assets/admin/js/messaging-modern-ui.js

    print_info "Restoring old assets..."
    cp "$BACKUP_DIR/assets/"* public/assets/admin/css/ 2>/dev/null || true
    cp "$BACKUP_DIR/assets/"* public/assets/admin/js/ 2>/dev/null || true
    print_success "Assets restored"
else
    print_warning "No asset backup found, skipping"
fi

print_header "Step 3: Rollback Database"

read -p "Do you want to rollback database migrations? (yes/no) [no]: " -r
if [[ $REPLY =~ ^[Yy](es)?$ ]]; then
    print_info "Rolling back 9 messaging migrations..."
    php artisan migrate:rollback --step=9
    print_success "Database rolled back"

    if [ -f "$BACKUP_DIR/database_backup.sql" ]; then
        read -p "Restore full database backup? (CAUTION) (yes/no) [no]: " -r
        if [[ $REPLY =~ ^[Yy](es)?$ ]]; then
            read -p "Enter database name [multivendor]: " DB_NAME
            DB_NAME=${DB_NAME:-multivendor}

            read -p "Enter username [root]: " DB_USER
            DB_USER=${DB_USER:-root}

            read -sp "Enter password: " DB_PASS
            echo ""

            mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < "$BACKUP_DIR/database_backup.sql"
            print_success "Database fully restored"
        fi
    fi
else
    print_info "Skipping database rollback"
fi

print_header "Step 4: Clear Caches"

print_info "Clearing all caches..."
php artisan view:clear
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan optimize:clear

php -r "if(function_exists('opcache_reset')) opcache_reset();"

print_success "Caches cleared"

print_header "Rollback Complete!"

echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${GREEN}✅ System restored to previous version${NC}"
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""
print_info "Next steps:"
echo "   1. Hard refresh browser (Ctrl+Shift+R)"
echo "   2. Test messaging system"
echo "   3. Verify old UI is back"
echo ""
print_success "Rollback completed successfully!"
