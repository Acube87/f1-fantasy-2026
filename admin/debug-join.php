<?php
require_once __DIR__ . '/../config.php';
$db = getDB();

echo "<h3>Drivers table structure</h3>";
$cols = $db->query("DESCRIBE drivers")->fetch_all(MYSQLI_ASSOC);
foreach ($cols as $c) {
    echo $c['Field'] . " (" . $c['Type'] . ")<br>";
}

echo "<br><h3>Check drivers table for 'stroll'</h3>";
$stroll = $db->query("SELECT * FROM drivers WHERE driver_name LIKE '%stroll%'")->fetch_all(MYSQLI_ASSOC);
print_r($stroll);

echo "<br><h3>Check predictions for 'stroll' in race 3</h3>";
$preds = $db->query("
    SELECT p.id, p.predicted_position, p.driver_id
    FROM predictions p
    WHERE p.race_id = 3 AND p.user_id = 1 AND p.driver_id = 'stroll'
")->fetch_all(MYSQLI_ASSOC);
print_r($preds);
?>