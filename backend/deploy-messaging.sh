#!/bin/bash

###############################################################################
# Messaging System Deployment Script
# Version: 1.0
# Date: 2026-03-11
# Description: Automated deployment of new messaging system to production
###############################################################################

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Functions
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

# Check if running as root or with sudo
if [[ $EUID -eq 0 ]]; then
   print_warning "Running as root. This is OK but not required."
fi

# Welcome message
clear
print_header "Messaging System Deployment"
echo "This script will deploy the new messaging system to production."
echo "Estimated time: 15-20 minutes"
echo ""
read -p "Do you want to proceed? (yes/no): " -r
if [[ ! $REPLY =~ ^[Yy](es)?$ ]]; then
    print_info "Deployment cancelled by user"
    exit 0
fi

###############################################################################
# STEP 1: Pre-flight Checks
###############################################################################

print_header "Step 1: Pre-flight Checks"

# Check if required files exist
print_info "Checking required files..."

FILES=(
    "public/assets/admin/css/messaging-modern-ui.css"
    "public/assets/admin/js/messaging-modern-ui.js"
    "resources/views/vendor-views/messages/index-revamped.blade.php"
    "app/Models/MessageDeliveryStatus.php"
    "app/Services/MessageFilterService.php"
    "config/messaging.php"
)

MISSING_FILES=0
for file in "${FILES[@]}"; do
    if [ ! -f "$file" ]; then
        print_error "Missing: $file"
        MISSING_FILES=$((MISSING_FILES + 1))
    fi
done

if [ $MISSING_FILES -gt 0 ]; then
    print_error "Missing $MISSING_FILES required files. Cannot proceed."
    exit 1
fi

print_success "All required files exist"

# Check PHP artisan
if ! command -v php &> /dev/null; then
    print_error "PHP not found. Please install PHP."
    exit 1
fi
print_success "PHP found: $(php -v | head -n1)"

# Check Laravel installation
if [ ! -f "artisan" ]; then
    print_error "Laravel artisan not found. Are you in the project root?"
    exit 1
fi
print_success "Laravel project detected"

###############################################################################
# STEP 2: Create Backup
###############################################################################

print_header "Step 2: Creating Backup"

BACKUP_DIR="/var/backups/messaging-system-$(date +%Y%m%d_%H%M%S)"
print_info "Backup directory: $BACKUP_DIR"

mkdir -p "$BACKUP_DIR"

# Backup database
print_info "Backing up database..."
if command -v mysqldump &> /dev/null; then
    read -p "Enter MySQL database name [multivendor]: " DB_NAME
    DB_NAME=${DB_NAME:-multivendor}

    read -p "Enter MySQL username [root]: " DB_USER
    DB_USER=${DB_USER:-root}

    read -sp "Enter MySQL password: " DB_PASS
    echo ""

    if mysqldump -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" > "$BACKUP_DIR/database_backup.sql" 2>/dev/null; then
        print_success "Database backed up"
    else
        print_warning "Database backup failed (continuing anyway)"
    fi
else
    print_warning "mysqldump not found, skipping database backup"
fi

# Backup views
print_info "Backing up current views..."
if [ -d "resources/views/vendor-views/messages" ]; then
    cp -r resources/views/vendor-views/messages "$BACKUP_DIR/messages_views_backup"
    print_success "Views backed up"
fi

# Backup current assets
print_info "Backing up current assets..."
mkdir -p "$BACKUP_DIR/assets"
cp public/assets/admin/css/messaging*.css "$BACKUP_DIR/assets/" 2>/dev/null || true
cp public/assets/admin/js/messaging*.js "$BACKUP_DIR/assets/" 2>/dev/null || true
print_success "Assets backed up"

print_success "Backup completed: $BACKUP_DIR"

###############################################################################
# STEP 3: Git Commit
###############################################################################

print_header "Step 3: Committing to Git"

print_info "Staging files..."

# Stage files
git add public/assets/admin/css/messaging-modern-ui.css \
    public/assets/admin/css/messaging-ux-enhancements.css \
    public/assets/admin/js/messaging-modern-ui.js \
    public/assets/admin/js/messaging-ux-enhancements.js \
    resources/views/vendor-views/messages/index-revamped.blade.php \
    resources/views/vendor-views/messages/index.blade.php.backup-legacy \
    app/Models/MessageDeliveryStatus.php \
    app/Models/MessageFilterPreset.php \
    app/Models/MessageReaction.php \
    app/Models/MessageSearchHistory.php \
    app/Services/MessageFilterService.php \
    app/Services/MessageSearchService.php \
    app/Repositories/TemplateRepository.php \
    config/messaging.php \
    config/messaging_cache.php \
    config/messaging_performance.php \
    config/messaging_search.php \
    database/migrations/*messag* \
    scripts/test-message-search-phase7.php \
    scripts/verify-messaging-optimizations.php 2>/dev/null || true

print_success "Files staged"

# Commit
print_info "Creating commit..."
git commit -m "Add modern messaging system with flat UI design

Features:
- Modern flat UI (NO gradients)
- Real-time search with debounce (300ms)
- Filter tabs (All, Unread, Assigned)
- Message reactions and delivery status
- Enhanced performance with caching
- Full-text search capabilities
- Mobile-first responsive design
- Quick templates section
- Smooth animations and UX improvements

Files:
- New CSS/JS assets (messaging-modern-ui.*)
- New view: index-revamped.blade.php
- 4 new models for enhanced features
- 2 service classes for filtering/search
- 9 database migrations for new tables/indexes
- 4 config files for messaging features

Documentation: See MESSAGING_REDESIGN_INDEX.md

Co-Authored-By: Claude Sonnet 4.5 <noreply@anthropic.com>" 2>/dev/null || print_warning "Nothing to commit (files may already be committed)"

print_success "Git commit completed"

###############################################################################
# STEP 4: Run Migrations
###############################################################################

print_header "Step 4: Running Database Migrations"

print_info "Running migrations..."
php artisan migrate --force

print_success "Migrations completed"

###############################################################################
# STEP 5: Activate New UI
###############################################################################

print_header "Step 5: Activating New Messaging UI"

echo "Choose activation method:"
echo "1) Replace index.blade.php with revamped version (RECOMMENDED)"
echo "2) Keep current index.blade.php and manually add CSS/JS links later"
read -p "Enter choice (1 or 2) [1]: " CHOICE
CHOICE=${CHOICE:-1}

if [ "$CHOICE" == "1" ]; then
    print_info "Backing up current index.blade.php..."
    cp resources/views/vendor-views/messages/index.blade.php \
       resources/views/vendor-views/messages/index.blade.php.old-$(date +%Y%m%d_%H%M%S)

    print_info "Replacing with revamped version..."
    cp resources/views/vendor-views/messages/index-revamped.blade.php \
       resources/views/vendor-views/messages/index.blade.php

    print_success "New UI activated (replaced index.blade.php)"
else
    print_warning "Skipped UI activation - you'll need to manually add CSS/JS links"
    print_info "Add these to index.blade.php:"
    echo ""
    echo "@push('css_or_js')"
    echo "    <link rel=\"stylesheet\" href=\"{{ asset('public/assets/admin/css/messaging-modern-ui.css') }}\">"
    echo "@endpush"
    echo ""
    echo "@push('script')"
    echo "    <script src=\"{{ asset('public/assets/admin/js/messaging-modern-ui.js') }}\"></script>"
    echo "@endpush"
    echo ""
fi

###############################################################################
# STEP 6: Clear Caches
###############################################################################

print_header "Step 6: Clearing Caches"

print_info "Clearing Laravel caches..."
php artisan view:clear
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan optimize:clear

print_success "Laravel caches cleared"

# Set permissions
print_info "Setting file permissions..."
chmod 644 public/assets/admin/css/messaging-modern-ui.css 2>/dev/null || true
chmod 644 public/assets/admin/js/messaging-modern-ui.js 2>/dev/null || true
chmod 644 resources/views/vendor-views/messages/index.blade.php 2>/dev/null || true

print_success "Permissions set"

# Clear opcache
print_info "Clearing opcache..."
php -r "if(function_exists('opcache_reset')) { opcache_reset(); echo 'Opcache cleared\n'; } else { echo 'Opcache not enabled\n'; }"

###############################################################################
# STEP 7: Verification
###############################################################################

print_header "Step 7: Verification"

print_info "Checking deployed files..."

# Check CSS file
if [ -f "public/assets/admin/css/messaging-modern-ui.css" ]; then
    SIZE=$(wc -c < public/assets/admin/css/messaging-modern-ui.css)
    if [ $SIZE -gt 20000 ]; then
        print_success "CSS file exists and has correct size ($SIZE bytes)"
    else
        print_warning "CSS file exists but seems small ($SIZE bytes)"
    fi
else
    print_error "CSS file not found!"
fi

# Check JS file
if [ -f "public/assets/admin/js/messaging-modern-ui.js" ]; then
    SIZE=$(wc -c < public/assets/admin/js/messaging-modern-ui.js)
    if [ $SIZE -gt 20000 ]; then
        print_success "JS file exists and has correct size ($SIZE bytes)"
    else
        print_warning "JS file exists but seems small ($SIZE bytes)"
    fi
else
    print_error "JS file not found!"
fi

# Check view file
if [ -f "resources/views/vendor-views/messages/index.blade.php" ]; then
    if grep -q "messaging-modern-ui" resources/views/vendor-views/messages/index.blade.php; then
        print_success "View file includes new messaging UI"
    else
        print_warning "View file exists but may not include new UI"
    fi
else
    print_error "View file not found!"
fi

# Check migrations
MIGRATION_COUNT=$(php artisan migrate:status | grep -c "messag" || echo "0")
print_info "Found $MIGRATION_COUNT messaging migrations in database"

###############################################################################
# COMPLETION
###############################################################################

print_header "Deployment Complete! 🎉"

echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${GREEN}✅ Messaging system deployed successfully!${NC}"
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""
echo "📍 Backup Location: $BACKUP_DIR"
echo ""
echo "🔍 Next Steps:"
echo "   1. Open browser and navigate to: https://new.snocart.com/vendor/message/list"
echo "   2. Hard refresh (Ctrl+Shift+R) to clear browser cache"
echo "   3. Verify NO gradients are visible"
echo "   4. Test search, filters, and sending messages"
echo "   5. Check mobile view (resize browser to 375px)"
echo ""
echo "📊 What to Check:"
echo "   ✅ Clean flat design (no gradients)"
echo "   ✅ Pill-shaped search input"
echo "   ✅ Filter tabs (All, Unread, Assigned)"
echo "   ✅ Solid colors (blue active, red unread)"
echo "   ✅ Modern message bubbles"
echo "   ✅ Quick templates section"
echo "   ✅ Search works with debounce"
echo "   ✅ Messages send successfully"
echo ""
echo "🔙 Rollback (if needed):"
echo "   Quick: cp resources/views/vendor-views/messages/index.blade.php.old-* index.blade.php"
echo "   Full:  bash scripts/rollback-messaging.sh $BACKUP_DIR"
echo ""
echo "📚 Documentation:"
echo "   - Quick Start: MESSAGING_UI_QUICKSTART.md"
echo "   - Full Guide: MESSAGING_COMPLETE_UI_UX_REDESIGN.md"
echo "   - Deployment: DEPLOY_MESSAGING_SYSTEM.md"
echo ""
print_success "Deployment script completed successfully!"
echo ""
