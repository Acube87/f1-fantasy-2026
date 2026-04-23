<?php
// Debug: check what's in race_results for Lance Stroll
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';

$db = getDB();
$results = $db->query("
    SELECT id, race_id, driver_id, driver_name, position 
    FROM race_results 
    WHERE driver_name LIKE '%Stroll%' OR driver_name LIKE '%Lance%'
    ORDER BY race_id, position
")->fetch_all(MYSQLI_ASSOC);

echo "<pre>";
print_r($results);
echo "</pre>";

echo "<h3>Check predictions too</h3>";
$preds = $db->query("
    SELECT id, race_id, user_id, driver_id, driver_name, predicted_position 
    FROM predictions 
    WHERE driver_name LIKE '%Stroll%' OR driver_name LIKE '%Lance%'
    ORDER BY race_id, predicted_position
")->fetch_all(MYSQLI_ASSOC);

echo "<pre>";
print_r($preds);
echo "</pre>";
?>