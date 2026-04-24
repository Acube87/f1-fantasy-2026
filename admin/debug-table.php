<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';

$db = getDB();
$raceId = 3;
$userId = 1;

// The Exact Query from race-results.php
$stmt = $db->prepare("
    SELECT p.*, d.driver_name, d.team, d.image_url
    FROM predictions p
    LEFT JOIN drivers d ON p.driver_id = d.id
    WHERE p.user_id = ? AND p.race_id = ?
    ORDER BY p.predicted_position ASC
");
$stmt->bind_param("ii", $userId, $raceId);
$stmt->execute();
$predictions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Build HTML exactly like race-results.php does
echo "<table border='1'>";
echo "<tr><th>P</th><th>Driver</th><th>Team</th></tr>";

$count = 0;
foreach ($predictions as $pIdx => $pred) {
    $count++;
    $pDisplay = $pIdx + 1;
    $driverName = htmlspecialchars($pred['driver_name'] ?? 'Unknown');
    $team = htmlspecialchars($pred['team'] ?? '');
    echo "<tr><td>P{$pDisplay}</td><td>{$driverName}</td><td>{$team}</td></tr>";
}

echo "</table>";
echo "<p>Total rows rendered: $count</p>";
?>