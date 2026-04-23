<?php
require_once __DIR__ . '/../config.php';
$db = getDB();

echo "<h3>Drivers Table Check</h3>";
$dupes = $db->query("SELECT driver_name, COUNT(*) as cnt FROM drivers GROUP BY driver_name HAVING cnt > 1")->fetch_all(MYSQLI_ASSOC);

if (!empty($dupes)) {
    echo "<b style='color:red'>DUPLICATE DRIVERS FOUND:</b><br>";
    print_r($dupes);
} else {
    echo "No duplicate drivers in table<br>";
}

echo "<h3>Sample drivers</h3>";
$drivers = $db->query("SELECT id, driver_name, team FROM drivers ORDER BY driver_name")->fetch_all(MYSQLI_ASSOC);
foreach ($drivers as $d) {
    echo "ID: {$d['id']} | {$d['driver_name']} | {$d['team']}<br>";
}
?>