<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';

$db = getDB();
$userId = 1;  // Change to test different users
$raceId = 3;   // Japan

echo "<h3>Race $raceId Predictions for User $userId</h3>";

// Query matches race-results.php exactly
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

echo "Total predictions: " . count($predictions) . "<br><br>";

foreach ($predictions as $p) {
    echo "P{$p['predicted_position']}: ID={$p['driver_id']} | {$p['driver_name']} | team={$p['team']}<br>";
}
?>