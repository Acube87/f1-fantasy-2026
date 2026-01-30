<?php
/**
 * Database Connection Test Script
 * Run this to verify your Railway MySQL connection is working
 */

require_once __DIR__ . '/config.php';

echo "=== F1 Fantasy Database Connection Test ===\n\n";

// Display configuration (without password)
echo "Configuration:\n";
echo "- DB_HOST: " . DB_HOST . "\n";
echo "- DB_PORT: " . DB_PORT . "\n";
echo "- DB_USER: " . DB_USER . "\n";
echo "- DB_PASS: " . (DB_PASS ? str_repeat('*', 8) : '(empty)') . "\n";
echo "- DB_NAME: " . DB_NAME . "\n\n";

// Test connection
echo "Testing connection...\n";
try {
    $db = getDB();
    echo "✅ SUCCESS! Connected to database.\n\n";
    
    // Test query - check if tables exist
    echo "Checking database tables:\n";
    $result = $db->query("SHOW TABLES");
    
    if ($result && $result->num_rows > 0) {
        echo "Found " . $result->num_rows . " tables:\n";
        while ($row = $result->fetch_array()) {
            echo "  - " . $row[0] . "\n";
        }
    } else {
        echo "⚠️  No tables found. You need to import database.sql\n";
    }
    
    echo "\n";
    
    // Check for users
    $result = $db->query("SELECT COUNT(*) as count FROM users");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "Users in database: " . $row['count'] . "\n";
    }
    
    // Check for races
    $result = $db->query("SELECT COUNT(*) as count FROM races");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "Races in database: " . $row['count'] . "\n";
    }
    
    echo "\n✅ Database is ready!\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n\n";
    
    echo "Troubleshooting:\n";
    echo "1. Check that Railway MySQL service is running\n";
    echo "2. Verify environment variables are set in Railway\n";
    echo "3. For local testing, update config.php with local MySQL credentials\n";
    echo "4. Make sure database 'f1_fantasy' exists\n";
    
    exit(1);
}
?>
