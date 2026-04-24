<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';

// Exact same query as race-results.php
$db = getDB();
$raceId = 3;
$userId = 1;

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

echo "<h3>Array content for predictions 20-22 (0-indexed 19-21)</h3>";
for ($i = 19; $i <= 21; $i++) {
    $p = $predictions[$i];
    echo "<b>Index $i (P{$p['predicted_position']}):</b><br>";
    echo "  driver_name: '{$p['driver_name']}'<br>";
    echo "  team: '{$p['team']}'<br>";
    echo "  driver_id: '{$p['driver_id']}'<br><br>";
}
?>