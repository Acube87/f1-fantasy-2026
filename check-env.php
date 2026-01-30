<?php
// Emergency diagnostic page to check environment variables
// Access this page to see what Railway actually provides

header('Content-Type: text/plain; charset=utf-8');

echo "=== RAILWAY ENVIRONMENT VARIABLES CHECK ===\n\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . "\n\n";

// Check all Railway-related environment variables
$env_vars = [
    'RAILWAY_TCP_PROXY_DOMAIN',
    'RAILWAY_TCP_PROXY_PORT',
    'MYSQLHOST',
    'MYSQLPORT',
    'MYSQLUSER',
    'MYSQLPASSWORD',
    'MYSQL_ROOT_PASSWORD',
    'MYSQL_DATABASE',
    'MYSQLDATABASE',
    'DB_HOST',
    'DB_PORT',
    'DB_USER',
    'DB_PASS',
    'DB_NAME',
    'DATABASE_URL'
];

echo "=== Environment Variables ===\n";
foreach ($env_vars as $var) {
    $value = getenv($var);
    if ($value !== false) {
        // Hide password but show it exists
        if (strpos($var, 'PASS') !== false || strpos($var, 'PASSWORD') !== false) {
            echo "$var = " . (strlen($value) > 0 ? "[SET - " . strlen($value) . " chars]" : "[EMPTY]") . "\n";
        } else {
            echo "$var = $value\n";
        }
    } else {
        echo "$var = [NOT SET]\n";
    }
}

echo "\n=== Computed Values (what config.php will use) ===\n";
$host = getenv('RAILWAY_TCP_PROXY_DOMAIN') ?: getenv('MYSQLHOST') ?: getenv('DB_HOST') ?: 'localhost';
$port = getenv('RAILWAY_TCP_PROXY_PORT') ?: getenv('MYSQLPORT') ?: getenv('DB_PORT') ?: 3306;
$user = getenv('MYSQLUSER') ?: getenv('DB_USER') ?: 'root';
$pass_set = (getenv('MYSQL_ROOT_PASSWORD') ?: getenv('MYSQLPASSWORD') ?: getenv('DB_PASS') ?: '') !== '';
$dbname = getenv('MYSQL_DATABASE') ?: getenv('MYSQLDATABASE') ?: getenv('DB_NAME') ?: 'f1_fantasy';

echo "Host: $host\n";
echo "Port: $port\n";
echo "User: $user\n";
echo "Password: " . ($pass_set ? "[SET]" : "[EMPTY]") . "\n";
echo "Database: $dbname\n";

echo "\n=== Connection Type ===\n";
if ($host !== 'localhost' && $host !== '127.0.0.1') {
    echo "Type: Remote (TCP) - will use: new mysqli(\$host, \$user, \$pass, \$dbname, \$port)\n";
    echo "This is CORRECT for Railway\n";
} else {
    echo "Type: Local (Socket/TCP auto) - will use: new mysqli(\$host, \$user, \$pass, \$dbname)\n";
    echo "⚠️  WARNING: 'localhost' on Railway will cause 'No such file or directory' error!\n";
    echo "⚠️  Railway environment variables are NOT SET or not being read!\n";
}

echo "\n=== Connection Test ===\n";
try {
    $pass = getenv('MYSQL_ROOT_PASSWORD') ?: getenv('MYSQLPASSWORD') ?: getenv('DB_PASS') ?: '';
    
    if ($host !== 'localhost' && $host !== '127.0.0.1') {
        $test_conn = new mysqli($host, $user, $pass, $dbname, $port);
    } else {
        $test_conn = new mysqli($host, $user, $pass, $dbname);
    }
    
    if ($test_conn->connect_error) {
        echo "❌ Connection FAILED: " . $test_conn->connect_error . "\n";
        echo "Error Number: " . $test_conn->connect_errno . "\n";
    } else {
        echo "✅ Connection SUCCESSFUL!\n";
        echo "Server Version: " . $test_conn->server_info . "\n";
        $test_conn->close();
    }
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}

echo "\n=== PHP Info ===\n";
echo "PHP Version: " . phpversion() . "\n";
echo "mysqli extension: " . (extension_loaded('mysqli') ? "✅ Loaded" : "❌ NOT loaded") . "\n";

echo "\n=== ALL Environment Variables (filtered) ===\n";
$all_env = getenv();
$filtered = [];
foreach ($all_env as $key => $value) {
    if (stripos($key, 'MYSQL') !== false || 
        stripos($key, 'RAILWAY') !== false || 
        stripos($key, 'DB') !== false ||
        stripos($key, 'DATABASE') !== false) {
        if (stripos($key, 'PASS') !== false || stripos($key, 'PASSWORD') !== false) {
            $filtered[$key] = strlen($value) > 0 ? "[SET - " . strlen($value) . " chars]" : "[EMPTY]";
        } else {
            $filtered[$key] = $value;
        }
    }
}
ksort($filtered);
foreach ($filtered as $key => $value) {
    echo "$key = $value\n";
}

echo "\n=== SOLUTION ===\n";
if ($host === 'localhost' || $host === '127.0.0.1') {
    echo "❌ PROBLEM IDENTIFIED: Host is '$host' which causes socket errors on Railway\n\n";
    echo "TO FIX:\n";
    echo "1. Go to Railway Dashboard\n";
    echo "2. Select your F1 Fantasy project\n";
    echo "3. Click on your MySQL database service\n";
    echo "4. Go to 'Variables' tab\n";
    echo "5. Make sure these variables are set:\n";
    echo "   - MYSQLHOST (should be something like: containers-us-west-123.railway.app)\n";
    echo "   - MYSQLPORT (should be something like: 6789)\n";
    echo "   - MYSQLUSER (your database user)\n";
    echo "   - MYSQLPASSWORD (your database password)\n";
    echo "   - MYSQLDATABASE (should be 'railway' or your database name)\n";
    echo "6. Go to your web service settings\n";
    echo "7. Make sure it's linked to the MySQL service (should share variables)\n";
    echo "8. Redeploy your web service\n\n";
    echo "OR use Railway's newer TCP proxy variables:\n";
    echo "   - RAILWAY_TCP_PROXY_DOMAIN\n";
    echo "   - RAILWAY_TCP_PROXY_PORT\n";
} else {
    echo "✅ Host configuration looks correct for Railway\n";
    echo "If you still get errors, check the 'Connection Test' section above.\n";
}

echo "\n";
?>
