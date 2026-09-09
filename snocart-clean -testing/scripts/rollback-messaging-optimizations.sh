#!/bin/bash
echo "Choose rollback level:"
echo "1) Disable features only (keep indexes)"
echo "2) Rollback database indexes"
echo "3) Full rollback (restore backup)"
read -p "Enter level (1-3): " level

cd /var/www/html/new_public/new

case $level in
    1)
        sed -i 's/MESSAGING_OPTIMIZATIONS_ENABLED=true/MESSAGING_OPTIMIZATIONS_ENABLED=false/' .env
        php artisan config:clear
        echo "✓ Optimizations disabled"
        ;;
    2)
        php artisan migrate:rollback --step=1
        echo "✓ Indexes removed"
        ;;
    3)
        echo "Full rollback..."
        BACKUP_DIR="/var/backups/messaging-optimization-$(date +%Y%m%d)"
        if [ -d "$BACKUP_DIR" ]; then
            cp -r $BACKUP_DIR/app/Http/Controllers/Admin/ConversationController.php app/Http/Controllers/Admin/
            cp -r $BACKUP_DIR/resources/views/admin-views/messages/* resources/views/admin-views/messages/
            echo "✓ Full restore complete"
        else
            echo "ERROR: Backup not found"
            exit 1
        fi
        ;;
esac
php artisan cache:clear
