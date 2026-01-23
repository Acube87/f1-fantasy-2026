<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

$raceId = $_GET['race_id'] ?? null;

if (!$raceId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Race ID required']);
    exit;
}

$result = calculateRaceScores($raceId);

if ($result['success']) {
    echo json_encode(['success' => true, 'message' => 'Scores calculated successfully']);
} else {
    http_response_code(500);
    echo json_encode($result);
}
?>

