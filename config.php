<?php
// Database configuration
// Railway-compatible: Uses environment variables if available, falls back to defaults
// Priority: RAILWAY_TCP_PROXY_DOMAIN > MYSQLHOST > DB_HOST > localhost
define('DB_HOST', getenv('RAILWAY_TCP_PROXY_DOMAIN') ?: getenv('MYSQLHOST') ?: getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('MYSQLUSER') ?: getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('MYSQL_ROOT_PASSWORD') ?: getenv('MYSQLPASSWORD') ?: getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('MYSQL_DATABASE') ?: getenv('MYSQLDATABASE') ?: getenv('DB_NAME') ?: 'f1_fantasy');
define('DB_PORT', getenv('RAILWAY_TCP_PROXY_PORT') ?: getenv('MYSQLPORT') ?: getenv('DB_PORT') ?: '3306');

// F1 API configuration
// Using Ergast F1 API (free, no auth required)
// Alternative: Official F1 API (requires authentication)
define('F1_API_BASE', 'http://ergast.com/api/f1/2026');
define('F1_API_TIMEOUT', 30);

// Application settings
define('SITE_NAME', 'F1 2026 Fantasy');
define('SESSION_NAME', 'f1_fantasy_session');

// Scoring configuration
define('POINTS_EXACT_POSITION', 10);      // Points for exact position match
define('POINTS_OFF_BY_ONE', 1);          // Points if off by 1 position
define('POINTS_TOP3_BONUS', 30);         // Triple points bonus for correct top 3 (3x10)
define('POINTS_CONSTRUCTOR_EXACT', 10);   // Points for exact constructor position
define('POINTS_CONSTRUCTOR_TOP3', 30);    // Triple points bonus for top 3 constructor prediction

// Start session
session_name(SESSION_NAME);
session_start();

// Database connection
function getDB() {
    static $conn = null;
    if ($conn === null) {
        try {
            // Use environment variables for Railway/cloud deployment
            // Priority order for Railway: RAILWAY_TCP_PROXY_DOMAIN > MYSQLHOST > localhost
            $host = getenv('RAILWAY_TCP_PROXY_DOMAIN') ?: getenv('MYSQLHOST') ?: getenv('DB_HOST') ?: 'localhost';
            $port = getenv('RAILWAY_TCP_PROXY_PORT') ?: getenv('MYSQLPORT') ?: getenv('DB_PORT') ?: 3306;
            $user = getenv('MYSQLUSER') ?: getenv('DB_USER') ?: 'root';
            $pass = getenv('MYSQL_ROOT_PASSWORD') ?: getenv('MYSQLPASSWORD') ?: getenv('DB_PASS') ?: '';
            $dbname = getenv('MYSQL_DATABASE') ?: getenv('MYSQLDATABASE') ?: getenv('DB_NAME') ?: 'f1_fantasy';
            
            // For Railway or remote connections, force TCP by using host:port format
            // This prevents "No such file or directory" socket errors
            if ($host !== 'localhost' && $host !== '127.0.0.1') {
                // Remote connection - use TCP with explicit port
                $conn = new mysqli($host, $user, $pass, $dbname, $port);
            } else {
                // Local connection - let MySQL decide socket vs TCP
                $conn = new mysqli($host, $user, $pass, $dbname);
            }
            
            if ($conn->connect_error) {
                throw new Exception("Connection failed: " . $conn->connect_error . " (Host: $host, Port: $port, User: $user, DB: $dbname)");
            }
            $conn->set_charset("utf8mb4");
        } catch (Exception $e) {
            throw new Exception("Database connection error: " . $e->getMessage());
        }
    }
    return $conn;
}

