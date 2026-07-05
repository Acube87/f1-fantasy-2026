<?php
// Database configuration
// Railway-compatible: Uses environment variables if available, falls back to defaults

// Helper function to get env variable from various sources
function get_env_var($key, $default = null) {
    if (getenv($key) !== false) return getenv($key);
    if (isset($_ENV[$key])) return $_ENV[$key];
    if (isset($_SERVER[$key])) return $_SERVER[$key];
    return $default;
}

// Prefer TCP proxy domain for Railway external connections
define('DB_HOST', get_env_var('RAILWAY_TCP_PROXY_DOMAIN') ?: get_env_var('MYSQLHOST') ?: get_env_var('DB_HOST') ?: '127.0.0.1');
define('DB_USER', get_env_var('MYSQLUSER') ?: get_env_var('DB_USER') ?: 'root');
define('DB_PASS', get_env_var('MYSQL_ROOT_PASSWORD') ?: get_env_var('MYSQLPASSWORD') ?: get_env_var('DB_PASS') ?: '');
define('DB_NAME', get_env_var('MYSQL_DATABASE') ?: get_env_var('MYSQLDATABASE') ?: get_env_var('DB_NAME') ?: 'f1_fantasy');
define('DB_PORT', get_env_var('RAILWAY_TCP_PROXY_PORT') ?: get_env_var('MYSQLPORT') ?: '3306');

// F1 API configuration
define('F1_API_BASE', 'http://ergast.com/api/f1/2024'); // Using 2024 as 2026 isn't available
define('F1_API_TIMEOUT', 30);

// Application settings
define('SITE_NAME', 'Paddock Picks');
define('SESSION_NAME', 'f1_fantasy_session');

// ============================================================
// MAINTENANCE MODE
// Set to TRUE to show the maintenance page to all users.
// Admin account (Angrycube) and /admin/ pages are always exempt.
// ============================================================
define('MAINTENANCE_MODE', false);  // ← SET TO true TO LOCK THE APP FOR POST-RACE PROCESSING
// Scoring configuration (F1-based system)
define('POINTS_PRECISION_BONUS', 3);      // +3 pts for exact position match
define('POINTS_PODIUM_SWEEP', 10);        // +10 pts for all top 3 correct in exact order
define('POINTS_CONSTRUCTOR_BONUS', 5);    // +5 pts for predicting top constructor

// F1 Standard Points (used as base when position is correctly predicted)
// Position => Points
global $F1_POINTS;
$F1_POINTS = [
    1 => 25, 2 => 18, 3 => 15, 4 => 12, 5 => 10,
    6 => 8, 7 => 6, 8 => 4, 9 => 2, 10 => 1
];

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

// Database connection with auto-reconnect
function getDB() {
    static $conn = null;
    
    // Helper function to create fresh connection
    $createConnection = function() {
        $host = DB_HOST;
        $port = DB_PORT;
        $user = DB_USER;
        $pass = DB_PASS;
        $dbname = DB_NAME;

        // DEBUG: Check if we are incorrectly falling back to localhost on production
        if ($host === 'localhost' && getenv('RAILWAY_ENVIRONMENT')) {
            die("<div style='font-family:sans-serif; padding:20px; background:#ffebeb; border:1px solid #ff0000; color:#c00;'>
                <h2>🚨 Configuration Error</h2>
                <p>The application is trying to connect to <strong>localhost</strong>, but it should be connecting to the Railway Database.</p>
                <p><strong>Status of Environment Variables:</strong></p>
                <ul>
                    <li>MYSQLHOST: " . (getenv('MYSQLHOST') ? '✅ Found' : '❌ MISSING') . "</li>
                    <li>MYSQLUSER: " . (getenv('MYSQLUSER') ? '✅ Found' : '❌ MISSING') . "</li>
                    <li>MYSQLDATABASE: " . (getenv('MYSQLDATABASE') ? '✅ Found' : '❌ MISSING') . "</li>
                </ul>
                <p>👉 <strong>Solution:</strong> Go to Railway -> Service -> Variables and add the MySQL variables.</p>
            </div>");
        }
        
        try {
            $port = (int)$port;
            
            $newConn = @new mysqli($host, $user, $pass, $dbname, $port);
            
            if ($newConn->connect_error) {
                $isSocketError = strpos($newConn->connect_error, 'No such file or directory') !== false;
                
                $errorDetails = "";
                if ($isSocketError && $host !== 'localhost') {
                     $errorDetails = " (Looks like PHP tried to use a socket despite HOST being set to '$host'. Ensure TCP is forced.)";
                } elseif ($isSocketError) {
                     $errorDetails = " (System tried to connect to Localhost Socket and failed. This usually means Env Vars are missing.)";
                }

                throw new Exception("Connection failed: " . $newConn->connect_error . $errorDetails . " (Host: $host)");
            }
            
            $newConn->set_charset("utf8mb4");
            return $newConn;
            
        } catch (Exception $e) {
            error_log("Database connection error: " . $e->getMessage());
            $clean_message = str_replace($pass, '********', $e->getMessage());
            throw new Exception("Database connection error: " . $clean_message);
        }
    };
    
    // Create connection if null
    if ($conn === null) {
        $conn = $createConnection();
    } else {
        // Validate existing connection is still alive
        try {
            // Lightweight health check. mysqli::ping() emits deprecation output
            // on PHP 8.4+, which can corrupt JSON API responses.
            $healthCheck = @$conn->query('SELECT 1');
            if (!$healthCheck) {
                error_log("Database connection lost - reconnecting...");
                $conn->close();
                $conn = $createConnection();
            } else {
                $healthCheck->free();
            }
        } catch (Exception $e) {
            // Connection is dead, recreate it
            error_log("Database connection validation failed - reconnecting: " . $e->getMessage());
            try {
                $conn->close();
            } catch (Exception $closeError) {
                // Ignore close errors
            }
            $conn = $createConnection();
        }
    }
    
    return $conn;
}
