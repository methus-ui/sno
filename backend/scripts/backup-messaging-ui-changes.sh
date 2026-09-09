#!/bin/bash
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/var/backups/messaging-ui-enhancement-$TIMESTAMP"

echo "🔵 Starting backup - $TIMESTAMP"
mkdir -p "$BACKUP_DIR"

# 1. Backup critical files
echo "📦 Backing up views and controllers..."
cp -r resources/views/admin-views/messages "$BACKUP_DIR/messages-views"
cp app/Http/Controllers/Admin/ConversationController.php "$BACKUP_DIR/"
cp routes/admin.php "$BACKUP_DIR/"
cp public/assets/admin/css/style.css "$BACKUP_DIR/"

# 2. Backup database (conversations table only)
DB_NAME=$(grep "^DB_DATABASE=" .env | cut -d '=' -f2 | tr -d '"')
DB_USER=$(grep "^DB_USERNAME=" .env | cut -d '=' -f2 | tr -d '"')
DB_PASS=$(grep "^DB_PASSWORD=" .env | cut -d '=' -f2 | tr -d '"')
DB_HOST=$(grep "^DB_HOST=" .env | cut -d '=' -f2 | tr -d '"')

echo "📦 Backing up conversations table..."
mysqldump -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" conversations > "$BACKUP_DIR/conversations_backup.sql"

echo "✅ Backup completed: $BACKUP_DIR"
echo ""
echo "To restore, run:"
echo "BACKUP_DIR=\"$BACKUP_DIR\""
echo "cp -r \$BACKUP_DIR/messages-views resources/views/admin-views/messages"
echo "cp \$BACKUP_DIR/ConversationController.php app/Http/Controllers/Admin/"
echo "cp \$BACKUP_DIR/admin.php routes/"
echo "cp \$BACKUP_DIR/style.css public/assets/admin/css/"
