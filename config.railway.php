<?php
// Railway-compatible config
// This version uses environment variables for Railway deployment

// Database configuration - uses environment variables
define('DB_HOST', getenv('MYSQLHOST') ?: getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('MYSQLUSER') ?: getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('MYSQLPASSWORD') ?: getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('MYSQLDATABASE') ?: getenv('DB_NAME') ?: 'railway');
define('DB_PORT', getenv('MYSQLPORT') ?: '3306');

// F1 API configuration
define('F1_API_BASE', 'http://ergast.com/api/f1/2026');
define('F1_API_TIMEOUT', 30);

// Application settings
define('SITE_NAME', 'F1 2026 Fantasy');
define('SESSION_NAME', 'f1_fantasy_session');

// Scoring configuration
define('POINTS_EXACT_POSITION', 10);
define('POINTS_OFF_BY_ONE', 1);
define('POINTS_TOP3_BONUS', 30);
define('POINTS_CONSTRUCTOR_EXACT', 10);
define('POINTS_CONSTRUCTOR_TOP3', 30);

// Start session
session_name(SESSION_NAME);
session_start();

// Database connection
function getDB() {
    static $conn = null;
    if ($conn === null) {
        try {
            $host = DB_HOST;
            // If port is specified separately, append it
            if (defined('DB_PORT') && DB_PORT != '3306') {
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

