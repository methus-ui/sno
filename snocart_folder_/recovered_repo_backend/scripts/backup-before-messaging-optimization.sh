#!/bin/bash
BACKUP_DIR="/var/backups/messaging-optimization-$(date +%Y%m%d_%H%M%S)"
mkdir -p $BACKUP_DIR
cd /var/www/html/new_public/new

echo "Creating backup at $BACKUP_DIR..."
cp app/Http/Controllers/Admin/ConversationController.php $BACKUP_DIR/
mkdir -p $BACKUP_DIR/resources/views/admin-views/messages
cp -r resources/views/admin-views/messages/* $BACKUP_DIR/resources/views/admin-views/messages/

DB_NAME=$(grep DB_DATABASE .env | cut -d '=' -f2)
DB_USER=$(grep DB_USERNAME .env | cut -d '=' -f2)
DB_PASS=$(grep DB_PASSWORD .env | cut -d '=' -f2)
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME messages conversations > $BACKUP_DIR/database_backup.sql

echo "Backup complete: $BACKUP_DIR"
