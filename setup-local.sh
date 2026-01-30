#!/bin/bash

# F1 Fantasy - Local Database Setup Script
# This script sets up a local MySQL database for testing

echo "🏎️  F1 Fantasy - Local Database Setup"
echo "======================================"
echo ""

# Database configuration
DB_NAME="f1_fantasy"
DB_USER="root"
DB_PASS=""

echo "Step 1: Creating database..."
mysql -u $DB_USER -e "CREATE DATABASE IF NOT EXISTS $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>/dev/null

if [ $? -eq 0 ]; then
    echo "✅ Database '$DB_NAME' created/verified"
else
    echo "❌ Failed to create database. Check MySQL is running:"
    echo "   brew services start mysql"
    exit 1
fi

echo ""
echo "Step 2: Importing schema from database.sql..."
mysql -u $DB_USER $DB_NAME < database.sql 2>/dev/null

if [ $? -eq 0 ]; then
    echo "✅ Schema imported successfully"
else
    echo "❌ Failed to import schema"
    exit 1
fi

echo ""
echo "Step 3: Verifying tables..."
TABLE_COUNT=$(mysql -u $DB_USER $DB_NAME -e "SHOW TABLES;" 2>/dev/null | wc -l)
TABLE_COUNT=$((TABLE_COUNT - 1))  # Subtract header row

if [ $TABLE_COUNT -gt 0 ]; then
    echo "✅ Found $TABLE_COUNT tables"
    mysql -u $DB_USER $DB_NAME -e "SHOW TABLES;" 2>/dev/null
else
    echo "❌ No tables found"
    exit 1
fi

echo ""
echo "Step 4: Testing PHP connection..."
php test-db.php

echo ""
echo "======================================"
echo "✅ Local setup complete!"
echo ""
echo "Next steps:"
echo "1. Start PHP server: php -S localhost:8000"
echo "2. Visit: http://localhost:8000"
echo "3. Sign up and test the app"
echo ""
echo "To populate data:"
echo "- Races: http://localhost:8000/admin/setup-races.php"
echo "- Drivers: http://localhost:8000/admin/fetch-drivers.php"
echo ""
