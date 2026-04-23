<?php
header('Content-Type: application/json');
require_once __DIR__ . '/includes/functions.php';
$db = getDB();

$output = [];

$res = $db->query("SELECT id, driver_name, team FROM drivers ORDER BY team, driver_name");
$output['drivers'] = $res->fetch_all(MYSQLI_ASSOC);

$res = $db->query("SELECT * FROM race_results WHERE race_id = 2 ORDER BY position ASC");
if ($res) {
    $output['race_2_actual'] = $res->fetch_all(MYSQLI_ASSOC);
}

$res = $db->query("SELECT * FROM predictions WHERE race_id = 2 AND user_id = 1 ORDER BY predicted_position ASC");
if ($res) {
    $output['race_2_predictions_user_1'] = $res->fetch_all(MYSQLI_ASSOC);
}

echo json_encode($output, JSON_PRETTY_PRINT);
