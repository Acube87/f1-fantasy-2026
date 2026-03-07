#!/bin/bash

# Load Environment Variables from a file if necessary (optional)
# source .env

# Configuration
BACKUP_DIR="/Users/angrycube/Sites/F1/backups"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
FILENAME="f1_fantasy_db_$TIMESTAMP.sql"

# DB Connection Info (Fallback to Railway environment variables)
DB_HOST=${MYSQLHOST:-"localhost"}
DB_USER=${MYSQLUSER:-"root"}
DB_PASS=${MYSQLPASSWORD:-""}
DB_NAME=${MYSQLDATABASE:-"f1_fantasy"}
DB_PORT=${MYSQLPORT:-"3306"}

mkdir -p "$BACKUP_DIR"

echo "🗄️ Starting database backup for $DB_NAME..."

# Execute mysqldump
if [ -n "$DB_PASS" ]; then
    mysqldump -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" > "$BACKUP_DIR/$FILENAME"
else
    mysqldump -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" "$DB_NAME" > "$BACKUP_DIR/$FILENAME"
fi

# Check if successful
if [ $? -eq 0 ]; then
    # Compress the SQL file
    gzip "$BACKUP_DIR/$FILENAME"
    echo "✅ Database backup completed: $BACKUP_DIR/$FILENAME.gz"
else
    echo "❌ Database backup failed!"
    exit 1
fi

# Clean up old backups (keep last 14 days)
find "$BACKUP_DIR" -name "f1_fantasy_db_*.sql.gz" -mtime +14 -delete
echo "🧹 Old database backups cleaned up."
