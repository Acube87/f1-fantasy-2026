<?php
// Database configuration
// Railway-compatible: Uses environment variables if available, falls back to defaults
// Prefer TCP proxy domain for Railway external connections
define('DB_HOST', getenv('RAILWAY_TCP_PROXY_DOMAIN') ?: getenv('MYSQLHOST') ?: getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('MYSQLUSER') ?: getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('MYSQL_ROOT_PASSWORD') ?: getenv('MYSQLPASSWORD') ?: getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('MYSQL_DATABASE') ?: getenv('MYSQLDATABASE') ?: getenv('DB_NAME') ?: 'railway');
define('DB_PORT', getenv('RAILWAY_TCP_PROXY_PORT') ?: getenv('MYSQLPORT') ?: '3306');

// F1 API configuration
// Using Ergast F1 API (free, no auth required)
// Alternative: Official F1 API (requires authentication)
define('F1_API_BASE', 'http://ergast.com/api/f1/2026');
define('F1_API_TIMEOUT', 30);

// Application settings
define('SITE_NAME', 'Formula 1 - Paddock Picks');
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
            // For Railway: use the known public proxy connection
            $host = 'metro.proxy.rlwy.net';
            $port = 40739;
            $user = 'root';
            $pass = 'ryKCglHSFcskNaRRpCooVWkxRqyKIyHt';
            $dbname = 'f1_fantasy';  // The database that has our schema
            
            $conn = @new mysqli($host, $user, $pass, $dbname, $port);
            if ($conn->connect_error) {
                throw new Exception("Connection failed: " . $conn->connect_error);
            }
            $conn->set_charset("utf8mb4");
        } catch (Exception $e) {
            throw new Exception("Database connection error: " . $e->getMessage());
        }
    }
    return $conn;
}

