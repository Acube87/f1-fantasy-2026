<?php
// Database configuration
// Railway-compatible: Uses environment variables if available, falls back to defaults
// Prefer TCP proxy domain for Railway external connections
define('DB_HOST', getenv('RAILWAY_TCP_PROXY_DOMAIN') ?: getenv('MYSQLHOST') ?: getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('MYSQLUSER') ?: getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('MYSQL_ROOT_PASSWORD') ?: getenv('MYSQLPASSWORD') ?: getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('MYSQL_DATABASE') ?: getenv('MYSQLDATABASE') ?: getenv('DB_NAME') ?: 'f1_fantasy');
define('DB_PORT', getenv('RAILWAY_TCP_PROXY_PORT') ?: getenv('MYSQLPORT') ?: '3306');

// F1 API configuration
// Using Ergast F1 API (free, no auth required)
// Alternative: Official F1 API (requires authentication)
// Using 2025 season (2026 data not available until season starts)
define('F1_API_BASE', 'http://ergast.com/api/f1/2025');
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
            // Use environment variables (Railway sets these automatically)
            // Falls back to localhost for local development
            $host = DB_HOST;
            $port = DB_PORT;
            $user = DB_USER;
            $pass = DB_PASS;
            $dbname = DB_NAME;
            
            // Create connection with port
            $conn = @new mysqli($host, $user, $pass, $dbname, $port);
            
            if ($conn->connect_error) {
                // Provide detailed error for debugging
                $error_msg = "Connection failed: " . $conn->connect_error;
                $error_msg .= "\nHost: " . $host . ":" . $port;
                $error_msg .= "\nUser: " . $user;
                $error_msg .= "\nDatabase: " . $dbname;
                throw new Exception($error_msg);
            }
            
            $conn->set_charset("utf8mb4");
        } catch (Exception $e) {
            // Log error and throw with details
            error_log("Database connection error: " . $e->getMessage());
            throw new Exception("Database connection error: " . $e->getMessage());
        }
    }
    return $conn;
}

