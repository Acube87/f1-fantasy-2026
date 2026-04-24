<?php
require_once __DIR__ . '/../config.php';
$db = getDB();

$raceId = 3;
$userId = 1;

// EXACT same query as race-results.php
$stmt = $db->prepare("
    SELECT p.*, d.driver_name, d.team, d.image_url
    FROM predictions p
    LEFT JOIN drivers d ON p.driver_id = d.id
    WHERE p.user_id = ? AND p.race_id = ?
    ORDER BY p.predicted_position ASC
");
$stmt->bind_param("ii", $userId, $raceId);
$stmt->execute();
$preds = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

echo "<h3>FULL EXACT query result</h3>";
echo "Total: " . count($preds) . " rows<br><br>";

for ($i = 0; $i < count($preds); $i++) {
    $p = $preds[$i];
    echo "[$i] P{$p['predicted_position']}: driver_id='{$p['driver_id']}' name='{$p['driver_name']}'<br>";
}
?>