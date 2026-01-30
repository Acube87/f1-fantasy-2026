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
define('DB_HOST', get_env_var('RAILWAY_TCP_PROXY_DOMAIN') ?: get_env_var('MYSQLHOST') ?: get_env_var('DB_HOST') ?: 'localhost');
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

// Scoring configuration
define('POINTS_EXACT_POSITION', 10);
define('POINTS_OFF_BY_ONE', 1);
define('POINTS_TOP3_BONUS', 3); 
define('POINTS_CONSTRUCTOR_EXACT', 10);
define('POINTS_CONSTRUCTOR_TOP3', 30);

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

// Database connection
function getDB() {
    static $conn = null;
    if ($conn === null) {
        $host = DB_HOST;
        $port = DB_PORT;
        $user = DB_USER;
        $pass = DB_PASS;
        $dbname = DB_NAME;

        // DEBUG: Check if we are incorrectly falling back to localhost on production
        // If we are on Railway (detected by RAILWAY_ENVIRONMENT or just assumption if not local), 
        // and host is localhost, we have a problem.
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
            // port must be integer
            $port = (int)$port;
            
            $conn = @new mysqli($host, $user, $pass, $dbname, $port);
            
            if ($conn->connect_error) {
                // If the error is "No such file or directory", it means it's trying to use a socket (localhost default)
                // but failed. This confirms the host is wrong or network is unreachable.
                $isSocketError = strpos($conn->connect_error, 'No such file or directory') !== false;
                
                $errorDetails = "";
                if ($isSocketError && $host !== 'localhost') {
                     $errorDetails = " (Looks like PHP tried to use a socket despite HOST being set to '$host'. Ensure TCP is forced.)";
                } elseif ($isSocketError) {
                     $errorDetails = " (System tried to connect to Localhost Socket and failed. This usually means Env Vars are missing.)";
                }

                throw new Exception("Connection failed: " . $conn->connect_error . $errorDetails . " (Host: $host)");
            }
            
            $conn->set_charset("utf8mb4");
        } catch (Exception $e) {
            // Log error
            error_log("Database connection error: " . $e->getMessage());
            
            // In production, don't show password, but show host for debugging
            $clean_message = str_replace($pass, '********', $e->getMessage());
            throw new Exception("Database connection error: " . $clean_message);
        }
    }
    return $conn;
}
