<?php
require_once __DIR__ . '/../config.php';
$db = getDB();
$raceId = 3;  // Japan

echo "<h3>Race Results Table - Race $raceId</h3>";

$results = $db->query("
    SELECT id, driver_id, driver_name, position 
    FROM race_results 
    WHERE race_id = $raceId
    ORDER BY position
")->fetch_all(MYSQLI_ASSOC);

echo "Total rows: " . count($results) . "<br><br>";

foreach ($results as $r) {
    echo "P{$r['position']}: ID={$r['driver_id']} | {$r['driver_name']}<br>";
}

echo "<br><h3>Check for duplicates by driver</h3>";
$dupes = $db->query("
    SELECT driver_id, COUNT(*) as cnt
    FROM race_results 
    WHERE race_id = $raceId
    GROUP BY driver_id 
    HAVING COUNT(*) > 1
")->fetch_all(MYSQLI_ASSOC);

if (!empty($dupes)) {
    echo "<b style='color:red'>DUPLICATES FOUND:</b><br>";
    print_r($dupes);
} else {
    echo "No duplicates in race_results<br>";
}
?>