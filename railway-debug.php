<?php
/**
 * Railway Debug Page
 * Shows environment variables and connection parameters
 * Access this page to see what Railway environment has
 */

header('Content-Type: text/plain');
echo "=================================\n";
echo "RAILWAY DEBUG INFORMATION\n";
echo "=================================\n\n";

echo "Timestamp: " . date('Y-m-d H:i:s') . " UTC\n\n";

echo "--- Railway Environment Variables ---\n";
$railway_vars = [
    'RAILWAY_TCP_PROXY_DOMAIN',
    'RAILWAY_TCP_PROXY_PORT',
    'RAILWAY_SERVICE_NAME',
    'RAILWAY_ENVIRONMENT',
    'RAILWAY_PROJECT_ID',
];

foreach ($railway_vars as $var) {
    $value = getenv($var);
    if ($value) {
        echo "$var = $value\n";
    } else {
        echo "$var = (not set)\n";
    }
}

echo "\n--- MySQL Environment Variables ---\n";
$mysql_vars = [
    'MYSQLHOST',
    'MYSQLPORT',
    'MYSQLUSER',
    'MYSQL_ROOT_PASSWORD',
    'MYSQLPASSWORD',
    'MYSQL_DATABASE',
    'MYSQLDATABASE',
];

foreach ($mysql_vars as $var) {
    $value = getenv($var);
    if ($value) {
        // Hide password
        if (strpos($var, 'PASS') !== false) {
            echo "$var = " . str_repeat('*', min(strlen($value), 20)) . "\n";
        } else {
            echo "$var = $value\n";
        }
    } else {
        echo "$var = (not set)\n";
    }
}

echo "\n--- Custom Environment Variables ---\n";
$custom_vars = [
    'DB_HOST',
    'DB_PORT',
    'DB_USER',
    'DB_PASS',
    'DB_NAME',
];

foreach ($custom_vars as $var) {
    $value = getenv($var);
    if ($value) {
        if (strpos($var, 'PASS') !== false) {
            echo "$var = " . str_repeat('*', min(strlen($value), 20)) . "\n";
        } else {
            echo "$var = $value\n";
        }
    } else {
        echo "$var = (not set)\n";
    }
}

echo "\n--- Connection Parameters (as config.php would use) ---\n";
$host = getenv('RAILWAY_TCP_PROXY_DOMAIN') ?: getenv('MYSQLHOST') ?: getenv('DB_HOST') ?: 'localhost';
$port = getenv('RAILWAY_TCP_PROXY_PORT') ?: getenv('MYSQLPORT') ?: getenv('DB_PORT') ?: 3306;
$user = getenv('MYSQLUSER') ?: getenv('DB_USER') ?: 'root';
$pass = getenv('MYSQL_ROOT_PASSWORD') ?: getenv('MYSQLPASSWORD') ?: getenv('DB_PASS') ?: '';
$dbname = getenv('MYSQL_DATABASE') ?: getenv('MYSQLDATABASE') ?: getenv('DB_NAME') ?: 'f1_fantasy';

echo "Host: $host\n";
echo "Port: $port\n";
echo "User: $user\n";
echo "Password: " . (empty($pass) ? "(empty)" : str_repeat('*', min(strlen($pass), 20))) . "\n";
echo "Database: $dbname\n";

echo "\n--- Connection Type ---\n";
if ($host !== 'localhost' && $host !== '127.0.0.1') {
    echo "Type: Remote (will use TCP with port $port)\n";
    echo "Method: new mysqli('$host', '$user', '****', '$dbname', $port)\n";
} else {
    echo "Type: Local (will use socket or TCP auto-detect)\n";
    echo "Method: new mysqli('$host', '$user', '****', '$dbname')\n";
}

echo "\n--- Test Database Connection ---\n";
try {
    if ($host !== 'localhost' && $host !== '127.0.0.1') {
        $conn = @new mysqli($host, $user, $pass, $dbname, $port);
    } else {
        $conn = @new mysqli($host, $user, $pass, $dbname);
    }
    
    if ($conn->connect_error) {
        echo "❌ CONNECTION FAILED\n";
        echo "Error: " . $conn->connect_error . "\n";
        echo "Errno: " . $conn->connect_errno . "\n";
    } else {
        echo "✅ CONNECTION SUCCESSFUL\n";
        echo "Server info: " . $conn->server_info . "\n";
        echo "Host info: " . $conn->host_info . "\n";
        echo "Protocol version: " . $conn->protocol_version . "\n";
        $conn->close();
    }
} catch (Exception $e) {
    echo "❌ EXCEPTION OCCURRED\n";
    echo "Message: " . $e->getMessage() . "\n";
}

echo "\n--- PHP Information ---\n";
echo "PHP Version: " . phpversion() . "\n";
echo "mysqli extension: " . (extension_loaded('mysqli') ? 'Loaded' : 'NOT LOADED') . "\n";

echo "\n=================================\n";
echo "END DEBUG INFORMATION\n";
echo "=================================\n";
?>
