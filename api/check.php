<?php
require_once __DIR__ . '/../includes/functions.php';
$db = getDB();
$raceId = 2;
echo "=== ACTUAL RESULTS ===\n";
$stmt = $db->query("SELECT position, driver_name, constructor_name FROM race_results WHERE race_id = $raceId ORDER BY position ASC");
if($stmt) while($r = $stmt->fetch_assoc()) echo $r['position'] . ' - ' . $r['driver_name'] . "\n";

echo "\n=== PREDICTIONS (user 1) ===\n";
$stmt = $db->query("SELECT predicted_position, driver_name FROM predictions WHERE race_id = $raceId AND user_id = 1 ORDER BY predicted_position ASC");
if($stmt) while($r = $stmt->fetch_assoc()) echo $r['predicted_position'] . ' - ' . $r['driver_name'] . "\n";
