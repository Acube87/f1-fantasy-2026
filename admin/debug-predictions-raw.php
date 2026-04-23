<?php
require_once __DIR__ . '/../config.php';
$db = getDB();
$raceId = 3;  // Japan
$userId = 1;  // User 1

echo "<h3>Direct query on predictions table - Race $raceId User $userId</h3>";

$all = $db->query("
    SELECT id, predicted_position, driver_id, driver_name 
    FROM predictions 
    WHERE race_id = $raceId AND user_id = $userId
    ORDER BY predicted_position
")->fetch_all(MYSQLI_ASSOC);

echo "Total rows: " . count($all) . "<br><br>";

foreach ($all as $a) {
    echo "ID: {$a['id']} | P{$a['predicted_position']} | driver_id={$a['driver_id']} | name={$a['driver_name']}<br>";
}

echo "<br><h3>Looking for duplicates by driver_name</h3>";
$dupes = $db->query("
    SELECT driver_name, COUNT(*) as cnt
    FROM predictions 
    WHERE race_id = $raceId AND user_id = $userId
    GROUP BY driver_name 
    HAVING COUNT(*) > 1
")->fetch_all(MYSQLI_ASSOC);

if (!empty($dupes)) {
    echo "<b style='color:red'>DUPLICATES FOUND:</b><br>";
    print_r($dupes);
} else {
    echo "No duplicates found<br>";
}
?>