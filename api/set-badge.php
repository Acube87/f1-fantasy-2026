<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/achievements.php';

header('Content-Type: application/json');

$user = getCurrentUser();
if (!$user) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$achievementId = $input['achievement_id'] ?? null;

if (!$achievementId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Achievement ID required']);
    exit;
}

$db = getDB();

// Verify user has unlocked this achievement
$stmt = $db->prepare("SELECT id FROM user_achievements WHERE user_id = ? AND achievement_id = ?");
$stmt->bind_param("is", $user['id'], $achievementId);
$stmt->execute();
if ($stmt->get_result()->num_rows === 0) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Achievement not unlocked']);
    exit;
}

if (toggleDisplayedBadge($user['id'], $achievementId, $db)) {
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
