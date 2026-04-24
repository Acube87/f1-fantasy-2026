<?php
require_once __DIR__ . '/../config.php';
$db = getDB();

echo "<h3>Check ALL drivers for any oddities</h3>";
$all = $db->query("SELECT id, driver_name, team FROM drivers ORDER BY id")->fetch_all(MYSQLI_ASSOC);
echo "Total drivers: " . count($all) . "<br><br>";
foreach ($all as $d) {
    $id = $d['id'];
    echo "id='$id' | {$d['driver_name']} | {$d['team']}<br>";
}

echo "<br><h3>Manual JOIN test - what's the actual result?</h3>";
$join = $db->query("
    SELECT p.id as pred_id, p.predicted_position, p.driver_id as pred_driver_id, d.id as driver_table_id, d.driver_name as driver_name
    FROM predictions p
    LEFT JOIN drivers d ON p.driver_id = d.id
    WHERE p.race_id = 3 AND p.user_id = 1
    ORDER BY p.predicted_position
")->fetch_all(MYSQLI_ASSOC);

echo "Join result count: " . count($join) . "<br><br>";
foreach ($join as $j) {
    echo "P{$j['predicted_position']}: pred_driver_id='{$j['pred_driver_id']}' -> driver_id='{$j['driver_table_id']}' = {$j['driver_name']}<br>";
}
?>