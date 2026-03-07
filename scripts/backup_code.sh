#!/bin/bash

# Configuration
PROJECT_DIR="/Users/angrycube/Sites/F1"
BACKUP_DIR="$PROJECT_DIR/backups"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
FILENAME="f1_fantasy_code_$TIMESTAMP.tar.gz"

# Create backup directory if it doesn't exist
mkdir -p "$BACKUP_DIR"

echo "📦 Starting codebase backup..."

# Create compressed archive, excluding unnecessary files
tar -czf "$BACKUP_DIR/$FILENAME" \
    --exclude=".git" \
    --exclude=".DS_Store" \
    --exclude="backups" \
    -C "$PROJECT_DIR" .

if [ $? -eq 0 ]; then
    echo "✅ Backup completed: $BACKUP_DIR/$FILENAME"
else
    echo "❌ Backup failed!"
    exit 1
fi

# Clean up old backups (keep last 7 days)
find "$BACKUP_DIR" -name "f1_fantasy_code_*.tar.gz" -mtime +7 -delete
echo "🧹 Old backups cleaned up."
