<?php
// NO AUTH FOR DEBUGGING
require_once __DIR__ . '/../includes/functions.php';

$db = getDB();

echo "--- DRIVERS ---\n";
// Count drivers
$stmt = $db->query("SELECT COUNT(*) as cnt FROM drivers");
$total = $stmt->fetch_assoc()['cnt'];
echo "Total Drivers in DB: $total\n";

// Find duplicates by driver_name
$stmt = $db->query("SELECT driver_name, COUNT(*) as c FROM drivers GROUP BY driver_name HAVING c > 1");
if ($stmt->num_rows > 0) {
    while ($row = $stmt->fetch_assoc()) echo "DUP DRIVER: " . $row['driver_name'] . " (" . $row['c'] . ")\n";
} else echo "No duplicate driver names.\n";

echo "--- ACTUAL RACE 2 ---\n";
$stmt = $db->query("SELECT position, COUNT(*) as c, GROUP_CONCAT(driver_name SEPARATOR ', ') as names FROM race_results WHERE race_id = 2 GROUP BY position ORDER BY position ASC LIMIT 30");
while ($row = $stmt->fetch_assoc()) echo "POS " . $row['position'] . ": " . $row['names'] . "\n";

echo "--- PREDICTED RACE 2 USER 1 ---\n";
$stmt = $db->query("SELECT predicted_position, COUNT(*) as c, GROUP_CONCAT(driver_name SEPARATOR ', ') as names FROM predictions WHERE race_id = 2 AND user_id = 1 GROUP BY predicted_position ORDER BY predicted_position ASC LIMIT 30");
while ($row = $stmt->fetch_assoc()) echo "POS " . $row['predicted_position'] . ": " . $row['names'] . "\n";
