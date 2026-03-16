<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$db = getDB();

echo "<h2>Driver Diagnostics</h2>";

// Count drivers
$stmt = $db->query("SELECT COUNT(*) as cnt FROM drivers");
$total = $stmt->fetch_assoc()['cnt'];
echo "Total Drivers in DB: $total<br><br>";

// Find duplicates by driver_name
$stmt = $db->query("SELECT driver_name, COUNT(*) as c FROM drivers GROUP BY driver_name HAVING c > 1");
if ($stmt->num_rows > 0) {
    echo "<b>FOUND DUPLICATES:</b><br>";
    while ($row = $stmt->fetch_assoc()) {
        echo " - " . htmlspecialchars($row['driver_name']) . " (" . $row['c'] . " times)<br>";
        // Delete the extra one
        $name = $db->real_escape_string($row['driver_name']);
        // keep one
        $q = $db->query("SELECT id FROM drivers WHERE driver_name = '$name' ORDER BY id LIMIT 1");
        $keepId = $q->fetch_assoc()['id'];
        
        $db->query("DELETE FROM drivers WHERE driver_name = '$name' AND id != '$keepId'");
        echo "&nbsp;&nbsp;-> Cleaned up duplicates for $name.<br>";
    }
} else {
    echo "No duplicate driver names found.<br>";
}

// Check race results grouping by position
echo "<br><b>Race 2 Positions:</b><br>";
$stmt = $db->query("SELECT position, COUNT(*) as c, GROUP_CONCAT(driver_name SEPARATOR ', ') as names FROM race_results WHERE race_id = 2 GROUP BY position ORDER BY position ASC");
while ($row = $stmt->fetch_assoc()) {
    if ($row['c'] > 1) {
        echo "<span style='color:red;'>Position " . $row['position'] . " has " . $row['c'] . " drivers: " . htmlspecialchars($row['names']) . "</span><br>";
    } else {
        echo "Position " . $row['position'] . " -> " . htmlspecialchars($row['names']) . "<br>";
    }
}

// Check predictions grouping by predicted_position for user 1
echo "<br><b>User 1 Predictions for Race 2:</b><br>";
$stmt = $db->query("SELECT predicted_position, COUNT(*) as c, GROUP_CONCAT(driver_name SEPARATOR ', ') as names FROM predictions WHERE race_id = 2 AND user_id = 1 GROUP BY predicted_position ORDER BY predicted_position ASC");
while ($row = $stmt->fetch_assoc()) {
    if ($row['c'] > 1) {
        echo "<span style='color:red;'>Pos " . $row['predicted_position'] . " has " . $row['c'] . " drivers: " . htmlspecialchars($row['names']) . "</span><br>";
    } else {
        echo "Pos " . $row['predicted_position'] . " -> " . htmlspecialchars($row['names']) . "<br>";
    }
}
