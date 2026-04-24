<?php
require_once __DIR__ . '/../config.php';
$db = getDB();

$raceId = 3;

// Check for duplicate predicted positions
echo "<h3>Check for duplicate predicted_position values</h3>";
$dupes = $db->query("
    SELECT predicted_position, COUNT(*) as cnt, GROUP_CONCAT(driver_name ORDER BY id) as drivers
    FROM predictions 
    WHERE race_id = $raceId AND user_id = 1
    GROUP BY predicted_position 
    HAVING COUNT(*) > 1
")->fetch_all(MYSQLI_ASSOC);

if (!empty($dupes)) {
    echo "<b style='color:red'>FOUND DUPLICATE POSITIONS:</b><br>";
    print_r($dupes);
} else {
    echo "No duplicate positions found<br>";
}

echo "<br><h3>All positions for user 1, race 3</h3>";
$all = $db->query("
    SELECT predicted_position, driver_name
    FROM predictions 
    WHERE race_id = $raceId AND user_id = 1
    ORDER BY predicted_position
")->fetch_all(MYSQLI_ASSOC);
foreach ($all as $a) {
    echo "P{$a['predicted_position']}: {$a['driver_name']}<br>";
}
?>