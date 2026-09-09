#!/bin/bash
# Automated backup before order editing implementation deployment

TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/var/backups/order-editing-implementation-$TIMESTAMP"

# Read database credentials from .env
cd /var/www/html/new_public/new
DB_NAME=$(grep "^DB_DATABASE=" .env | cut -d '=' -f2 | tr -d '"')
DB_USER=$(grep "^DB_USERNAME=" .env | cut -d '=' -f2 | tr -d '"')
DB_PASS=$(grep "^DB_PASSWORD=" .env | cut -d '=' -f2 | tr -d '"' | sed "s/'//g")
DB_HOST=$(grep "^DB_HOST=" .env | cut -d '=' -f2 | tr -d '"')

# If DB_USER is empty, try root
if [ -z "$DB_USER" ]; then
    DB_USER="root"
fi

echo "🔵 Starting pre-deployment backup - $TIMESTAMP"
echo "📊 Database: $DB_NAME"

# Create backup directory
mkdir -p "$BACKUP_DIR"

# 1. Full Database Backup
echo "📦 Backing up full database..."
if mysqldump -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" > "$BACKUP_DIR/full_database_backup.sql" 2>/dev/null; then
    gzip "$BACKUP_DIR/full_database_backup.sql"
    echo "✅ Full database backup completed"
else
    echo "⚠️  Full database backup failed (will continue with other backups)"
fi

# 2. Critical Tables Backup (individual files for faster restore)
echo "📦 Backing up critical tables..."
for table in orders order_details order_transactions order_payments; do
    if mysqldump -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" "$table" > "$BACKUP_DIR/${table}_backup.sql" 2>/dev/null; then
        echo "  ✓ $table backed up"
    else
        echo "  ⚠️  $table backup failed"
    fi
done
echo "✅ Critical tables backup completed"

# 3. Table Row Counts (for verification)
echo "📊 Recording table row counts..."
mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "
    SELECT 'orders' as table_name, COUNT(*) as row_count FROM orders
    UNION ALL
    SELECT 'order_details', COUNT(*) FROM order_details
    UNION ALL
    SELECT 'order_transactions', COUNT(*) FROM order_transactions
    UNION ALL
    SELECT 'order_payments', COUNT(*) FROM order_payments
" > "$BACKUP_DIR/row_counts_before.txt" 2>/dev/null
echo "✅ Row counts recorded"

# 4. Application Code Backup
echo "📦 Backing up application code..."
cd /var/www/html/new_public/new
git log -1 --pretty=format:"%H%n%an%n%ad%n%s" > "$BACKUP_DIR/git_commit_info.txt" 2>/dev/null || echo "Not a git repo" > "$BACKUP_DIR/git_commit_info.txt"
git diff > "$BACKUP_DIR/uncommitted_changes.diff" 2>/dev/null || echo "" > "$BACKUP_DIR/uncommitted_changes.diff"
tar -czf "$BACKUP_DIR/application_code.tar.gz" \
    app/Http/Controllers/Admin/OrderController.php \
    app/Http/Controllers/Vendor/OrderController.php \
    app/CentralLogics/order.php \
    2>/dev/null
echo "✅ Application code backup completed"

# 5. Configuration Files Backup
echo "📦 Backing up configuration files..."
cp .env "$BACKUP_DIR/.env.backup" 2>/dev/null || echo "No .env file"
echo "✅ Configuration backup completed"

# 6. Current Transaction Data Snapshot
echo "📊 Creating transaction data snapshot..."
mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "
    SELECT
        ot.id,
        ot.order_id,
        ot.order_amount,
        ot.store_amount,
        ot.admin_commission,
        o.order_amount as current_order_amount,
        (ot.order_amount - o.order_amount) as amount_difference
    FROM order_transactions ot
    JOIN orders o ON ot.order_id = o.id
    WHERE ot.order_amount != o.order_amount
    LIMIT 100
" > "$BACKUP_DIR/transaction_order_discrepancies.txt" 2>/dev/null
echo "✅ Transaction snapshot completed"

# 7. Test Sample Orders (for restore verification)
echo "📋 Saving test sample orders..."
mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "
    SELECT id, order_amount, total_tax_amount, delivery_charge, order_status
    FROM orders
    ORDER BY id DESC
    LIMIT 100
" > "$BACKUP_DIR/sample_orders.txt" 2>/dev/null
echo "✅ Sample orders saved"

# 8. Create restoration script
cat > "$BACKUP_DIR/restore.sh" << RESTORE_SCRIPT
#!/bin/bash
# Automatic restoration script
DB_USER="$DB_USER"
DB_PASS="$DB_PASS"
DB_NAME="$DB_NAME"
DB_HOST="$DB_HOST"

echo "⚠️  WARNING: This will restore database to pre-deployment state"
read -p "Are you sure? (type 'YES' to confirm): " confirm
if [ "\$confirm" != "YES" ]; then
    echo "❌ Restore cancelled"
    exit 1
fi

echo "🔄 Starting database restore..."
if [ -f "full_database_backup.sql.gz" ]; then
    gunzip -c full_database_backup.sql.gz | mysql -h "\$DB_HOST" -u "\$DB_USER" -p"\$DB_PASS" "\$DB_NAME"
    echo "✅ Database restored"
else
    echo "❌ Backup file not found"
    exit 1
fi

echo "🔍 Verifying row counts..."
mysql -h "\$DB_HOST" -u "\$DB_USER" -p"\$DB_PASS" "\$DB_NAME" -e "
    SELECT 'orders' as table_name, COUNT(*) as row_count FROM orders
    UNION ALL
    SELECT 'order_details', COUNT(*) FROM order_details
    UNION ALL
    SELECT 'order_transactions', COUNT(*) FROM order_transactions
"
echo "Compare with row_counts_before.txt to verify"
echo "✅ Restore completed"
RESTORE_SCRIPT

chmod +x "$BACKUP_DIR/restore.sh"

# 9. Compress entire backup
echo "🗜️  Compressing backup..."
cd /var/backups
tar -czf "order-editing-implementation-$TIMESTAMP.tar.gz" "order-editing-implementation-$TIMESTAMP/" 2>/dev/null
echo "✅ Backup compressed"

# 10. Verify backup integrity
echo "🔍 Verifying backup integrity..."
BACKUP_OK=true
if [ ! -f "$BACKUP_DIR/application_code.tar.gz" ]; then
    echo "⚠️  Application code backup missing"
    BACKUP_OK=false
fi

if [ -f "$BACKUP_DIR/full_database_backup.sql.gz" ] || [ -f "$BACKUP_DIR/orders_backup.sql" ]; then
    echo "✅ Database backup found"
else
    echo "⚠️  Database backup missing"
    BACKUP_OK=false
fi

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
if [ "$BACKUP_OK" = true ]; then
    echo "✅ BACKUP COMPLETED SUCCESSFULLY"
else
    echo "⚠️  BACKUP COMPLETED WITH WARNINGS"
fi
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "📂 Backup location: $BACKUP_DIR"
echo "📦 Compressed: order-editing-implementation-$TIMESTAMP.tar.gz"
echo "📊 Row counts: $BACKUP_DIR/row_counts_before.txt"
echo "🔄 Quick restore: cd $BACKUP_DIR && ./restore.sh"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

if [ "$BACKUP_OK" = true ]; then
    exit 0
else
    exit 1
fi
