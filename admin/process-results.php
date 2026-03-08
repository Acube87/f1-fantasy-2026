<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/achievements.php';

// Very basic security check - in a real app, you'd check for is_admin
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$raceId = $data['race_id'] ?? null;
$results = $data['results'] ?? []; // pos => driver_id

if (!$raceId || empty($results)) {
    echo json_encode(['success' => false, 'message' => 'Missing race ID or results data']);
    exit;
}

$db = getDB();

try {
    $db->begin_transaction();

    // 1. Get Race Details
    $stmt = $db->prepare("SELECT * FROM races WHERE id = ?");
    $stmt->bind_param("i", $raceId);
    $stmt->execute();
    $race = $stmt->get_result()->fetch_assoc();

    if (!$race) throw new Exception("Race not found");

    // 1.5 Auto-migrate race_results table if columns are missing (Railway DB compatibility)
    // Check and add columns independently to prevent duplicate column crashed
    $cols = $db->query("SHOW COLUMNS FROM race_results LIKE 'driver_name'")->num_rows;
    if ($cols === 0) {
        $db->query("ALTER TABLE race_results ADD COLUMN driver_name VARCHAR(100) AFTER driver_id");
    }
    
    $cols2 = $db->query("SHOW COLUMNS FROM race_results LIKE 'constructor_id'")->num_rows;
    if ($cols2 === 0) {
        $db->query("ALTER TABLE race_results ADD COLUMN constructor_id VARCHAR(50) AFTER driver_name");
    }

    $cols3 = $db->query("SHOW COLUMNS FROM race_results LIKE 'constructor_name'")->num_rows;
    if ($cols3 === 0) {
        $db->query("ALTER TABLE race_results ADD COLUMN constructor_name VARCHAR(100) AFTER constructor_id");
    }

    $cols4 = $db->query("SHOW COLUMNS FROM scores LIKE 'constructor_points'")->num_rows;
    if ($cols4 === 0) {
        $db->query("ALTER TABLE scores ADD COLUMN constructor_points INT DEFAULT 0 AFTER driver_points");
    }

    // 2. Clear Existing Results
    $db->query("DELETE FROM race_results WHERE race_id = $raceId");

    // 3. Insert New Results
    $f1Points = [1 => 25, 2 => 18, 3 => 15, 4 => 12, 5 => 10, 6 => 8, 7 => 6, 8 => 4, 9 => 2, 10 => 1];
    $stmt = $db->prepare("INSERT INTO race_results (race_id, driver_id, driver_name, constructor_name, position, points, status) VALUES (?, ?, ?, ?, ?, ?, 'Finished')");
    
    foreach ($results as $pos => $driverId) {
        // Fetch driver details for recording
        $dStmt = $db->prepare("SELECT driver_name, team FROM drivers WHERE id = ?");
        $dStmt->bind_param("s", $driverId);
        $dStmt->execute();
        $driver = $dStmt->get_result()->fetch_assoc();
        
        if ($driver) {
            $pts = $f1Points[$pos] ?? 0;
            $stmt->bind_param("isssid", $raceId, $driverId, $driver['driver_name'], $driver['team'], $pos, $pts);
            $stmt->execute();
        }
    }

    // 4. Mark Race as Completed
    $db->query("UPDATE races SET status = 'completed', results_fetched = 1 WHERE id = $raceId");

    // 5. Run Scoring Engine for all users using the documented point system
    $scoringResult = calculateRaceScores($raceId);
    if (!$scoringResult['success']) {
        throw new Exception("Scoring failed: " . $scoringResult['message']);
    }

    // 6. Check Achievements for all users who participated
    $users = $db->query("SELECT DISTINCT user_id FROM predictions WHERE race_id = $raceId")->fetch_all(MYSQLI_ASSOC);
    foreach ($users as $u) {
        checkAndUnlockAchievements($u['user_id'], $db);
    }

    $db->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    if (isset($db)) $db->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
