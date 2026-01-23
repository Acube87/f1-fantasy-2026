<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// This endpoint can be called manually or via cron job to fetch race results
header('Content-Type: application/json');

// Optional: Add admin check here if you want to restrict access
// requireLogin();
// $user = getCurrentUser();
// if ($user['username'] !== 'admin') {
//     http_response_code(403);
//     echo json_encode(['success' => false, 'message' => 'Unauthorized']);
//     exit;
// }

$raceId = $_GET['race_id'] ?? null;

if (!$raceId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Race ID required']);
    exit;
}

$result = fetchRaceResults($raceId);

if ($result['success']) {
    // Calculate scores after fetching results
    calculateRaceScores($raceId);
    echo json_encode(['success' => true, 'message' => 'Results fetched and scores calculated', 'results_count' => $result['results']]);
} else {
    http_response_code(500);
    echo json_encode($result);
}
?>

