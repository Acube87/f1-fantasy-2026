<?php
require_once __DIR__ . '/../config.php';
$db = getDB();

// Check if drivers table has multiple entries for same driver_id
echo "<h3>Drivers table duplicates by driver_id</h3>";
$dupes = $db->query("SELECT driver_id, COUNT(*) as cnt FROM drivers GROUP BY driver_id HAVING cnt > 1")->fetch_all(MYSQLI_ASSOC);
print_r($dupes);

echo "<br><h3>JOIN test - what happens when we join predictions with drivers?</h3>";
$test = $db->query("
    SELECT p.id, p.predicted_position, p.driver_id as pred_driver_id, d.id as driver_table_id, d.driver_name
    FROM predictions p
    LEFT JOIN drivers d ON p.driver_id = d.id
    WHERE p.race_id = 3 AND p.user_id = 1 AND p.driver_id = 'stroll'
")->fetch_all(MYSQLI_ASSOC);
print_r($test);
?>