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
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    $databaseUrl = getenv('DATABASE_URL');

    if (!$databaseUrl) {
        throw new Exception("DATABASE_URL not set");
    }

    $pdo = new PDO($databaseUrl, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    return $pdo;
}


