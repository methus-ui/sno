#!/bin/bash

# Rollback script for messaging UI enhancements
# Usage: bash scripts/rollback-messaging-ui.sh [level]
#   level1 - Disable features via config (instant, < 1 minute)
#   level2 - Restore files from backup (< 5 minutes)
#   level3 - Rollback database migration (< 2 minutes)

LEVEL=${1:-level1}
BACKUP_DIR="/var/backups/messaging-ui-enhancement-20260224_101732"

echo "🔴 Starting rollback - Level: $LEVEL"

case $LEVEL in
    level1)
        echo "📝 Level 1: Disabling UX enhancements via config..."
        php artisan down --message="Maintenance in progress"

        # Disable all UX features in .env
        sed -i 's/MESSAGING_EXPANDED_PREVIEWS=true/MESSAGING_EXPANDED_PREVIEWS=false/g' .env
        sed -i 's/MESSAGING_SMART_TIMESTAMPS=true/MESSAGING_SMART_TIMESTAMPS=false/g' .env
        sed -i 's/MESSAGING_QUICK_ACTIONS=true/MESSAGING_QUICK_ACTIONS=false/g' .env
        sed -i 's/MESSAGING_FILTER_TABS=true/MESSAGING_FILTER_TABS=false/g' .env
        sed -i 's/MESSAGING_ADVANCED_SEARCH=true/MESSAGING_ADVANCED_SEARCH=false/g' .env
        sed -i 's/MESSAGING_BULK_ACTIONS=true/MESSAGING_BULK_ACTIONS=false/g' .env
        sed -i 's/MESSAGING_KEYBOARD_SHORTCUTS=true/MESSAGING_KEYBOARD_SHORTCUTS=false/g' .env
        sed -i 's/MESSAGING_SKELETON_SCREENS=true/MESSAGING_SKELETON_SCREENS=false/g' .env
        sed -i 's/MESSAGING_OPTIMISTIC_UI=true/MESSAGING_OPTIMISTIC_UI=false/g' .env

        php artisan config:clear
        php artisan cache:clear
        php artisan up

        echo "✅ Level 1 rollback complete (features disabled)"
        ;;

    level2)
        echo "📦 Level 2: Restoring files from backup..."
        php artisan down --message="Maintenance in progress"

        if [ ! -d "$BACKUP_DIR" ]; then
            echo "❌ Backup directory not found: $BACKUP_DIR"
            exit 1
        fi

        # Restore files
        cp -r "$BACKUP_DIR/messages-views/"* resources/views/admin-views/messages/
        cp "$BACKUP_DIR/ConversationController.php" app/Http/Controllers/Admin/
        cp "$BACKUP_DIR/admin.php" routes/
        cp "$BACKUP_DIR/style.css" public/assets/admin/css/

        # Remove new files
        rm -f public/assets/admin/js/messaging-ux-enhancements.js
        rm -f public/assets/admin/css/messaging-ux-enhancements.css

        php artisan config:clear
        php artisan cache:clear
        php artisan view:clear
        php artisan up

        echo "✅ Level 2 rollback complete (files restored)"
        ;;

    level3)
        echo "🗄️  Level 3: Rolling back database migration..."
        php artisan down --message="Maintenance in progress"

        # Rollback the migration
        php artisan migrate:rollback --step=1 --force

        # Restore files (includes level 2)
        if [ -d "$BACKUP_DIR" ]; then
            cp -r "$BACKUP_DIR/messages-views/"* resources/views/admin-views/messages/
            cp "$BACKUP_DIR/ConversationController.php" app/Http/Controllers/Admin/
            cp "$BACKUP_DIR/admin.php" routes/
            cp "$BACKUP_DIR/style.css" public/assets/admin/css/

            rm -f public/assets/admin/js/messaging-ux-enhancements.js
            rm -f public/assets/admin/css/messaging-ux-enhancements.css
        fi

        php artisan config:clear
        php artisan cache:clear
        php artisan view:clear
        php artisan up

        echo "✅ Level 3 rollback complete (full restoration)"
        ;;

    *)
        echo "❌ Invalid level. Use: level1, level2, or level3"
        exit 1
        ;;
esac

echo ""
echo "🟢 Rollback completed successfully"
echo "ℹ️  Check application status: curl -I https://new.snocart.com/admin/message/list"
