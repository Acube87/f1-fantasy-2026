<?php
/**
 * Database Connection Test Script
 * Use this to diagnose database connection issues
 */

echo "=== Database Connection Test ===\n\n";

// Test environment variables
echo "Environment Variables:\n";
echo "RAILWAY_TCP_PROXY_DOMAIN: " . (getenv('RAILWAY_TCP_PROXY_DOMAIN') ?: 'not set') . "\n";
echo "RAILWAY_TCP_PROXY_PORT: " . (getenv('RAILWAY_TCP_PROXY_PORT') ?: 'not set') . "\n";
echo "MYSQLHOST: " . (getenv('MYSQLHOST') ?: 'not set') . "\n";
echo "MYSQLPORT: " . (getenv('MYSQLPORT') ?: 'not set') . "\n";
echo "MYSQLUSER: " . (getenv('MYSQLUSER') ?: 'not set') . "\n";
echo "MYSQLPASSWORD: " . (getenv('MYSQLPASSWORD') ? '***set***' : 'not set') . "\n";
echo "MYSQL_DATABASE: " . (getenv('MYSQL_DATABASE') ?: 'not set') . "\n";
echo "MYSQLDATABASE: " . (getenv('MYSQLDATABASE') ?: 'not set') . "\n";
echo "\n";

// Determine connection parameters
$host = getenv('RAILWAY_TCP_PROXY_DOMAIN') ?: getenv('MYSQLHOST') ?: getenv('DB_HOST') ?: 'localhost';
$port = getenv('RAILWAY_TCP_PROXY_PORT') ?: getenv('MYSQLPORT') ?: getenv('DB_PORT') ?: 3306;
$user = getenv('MYSQLUSER') ?: getenv('DB_USER') ?: 'root';
$pass = getenv('MYSQL_ROOT_PASSWORD') ?: getenv('MYSQLPASSWORD') ?: getenv('DB_PASS') ?: '';
$dbname = getenv('MYSQL_DATABASE') ?: getenv('MYSQLDATABASE') ?: getenv('DB_NAME') ?: 'f1_fantasy';

echo "Connection Parameters:\n";
echo "Host: $host\n";
echo "Port: $port\n";
echo "User: $user\n";
echo "Password: " . ($pass ? '***set***' : 'not set') . "\n";
echo "Database: $dbname\n";
echo "\n";

// Test connection
echo "Testing connection...\n";
try {
    // Check if we should use TCP
    if ($host !== 'localhost' && $host !== '127.0.0.1') {
        echo "Using TCP connection with explicit port\n";
        $conn = new mysqli($host, $user, $pass, $dbname, $port);
    } else {
        echo "Using local connection (socket or TCP)\n";
        $conn = new mysqli($host, $user, $pass, $dbname);
    }
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    echo "✓ Successfully connected to database!\n";
    echo "Server version: " . $conn->server_info . "\n";
    echo "Character set: " . $conn->character_set_name() . "\n";
    
    // Test a simple query
    $result = $conn->query("SELECT 1 as test");
    if ($result) {
        echo "✓ Query test successful\n";
    }
    
    $conn->close();
    echo "\n=== Connection Test PASSED ===\n";
    
} catch (Exception $e) {
    echo "✗ Connection failed!\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "\n=== Connection Test FAILED ===\n";
    exit(1);
}
?>
