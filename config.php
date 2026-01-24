<?php
// Database configuration
// Railway-compatible: Uses environment variables if available, falls back to defaults
define('DB_HOST', getenv('MYSQLHOST') ?: getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('MYSQLUSER') ?: getenv('DB_USER') ?: 'your_db_username');
define('DB_PASS', getenv('MYSQLPASSWORD') ?: getenv('DB_PASS') ?: 'your_db_password');
define('DB_NAME', getenv('MYSQLDATABASE') ?: getenv('DB_NAME') ?: 'f1_fantasy');
define('DB_PORT', getenv('MYSQLPORT') ?: '3306');

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
            $host = DB_HOST;
            // Append port if it's not the default 3306
            if (defined('DB_PORT') && DB_PORT != '3306' && strpos($host, ':') === false) {
                $host .= ':' . DB_PORT;
            }
            $conn = new mysqli($host, DB_USER, DB_PASS, DB_NAME);
            if ($conn->connect_error) {
                die("Connection failed: " . $conn->connect_error);
            }
            $conn->set_charset("utf8mb4");
        } catch (Exception $e) {
            die("Database connection error: " . $e->getMessage());
        }
    }
    return $conn;
}
?>

