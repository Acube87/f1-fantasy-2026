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

    // 5. Run Scoring Engine for all users
    $users = $db->query("SELECT id FROM users")->fetch_all(MYSQLI_ASSOC);
    $isDoublePoints = in_array($race['country'], ['China', 'UK', 'Singapore']);

    foreach ($users as $u) {
        $userId = $u['id'];
        
        // Get user predictions
        $predStmt = $db->prepare("SELECT driver_id, predicted_position FROM predictions WHERE user_id = ? AND race_id = ?");
        $predStmt->bind_param("ii", $userId, $raceId);
        $predStmt->execute();
        $userPreds = $predStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        if (empty($userPreds)) continue;

        $basePoints = 0;
        $strategyBonus = 0;
        $podiumCorrect = [1 => false, 2 => false, 3 => false];

        foreach ($userPreds as $pred) {
            $actualPos = null;
            foreach ($results as $pos => $did) {
                if ($did === $pred['driver_id']) {
                    $actualPos = (int)$pos;
                    break;
                }
            }

            if ($actualPos && $actualPos === (int)$pred['predicted_position']) {
                $basePoints += $f1Points[$actualPos] ?? 0;
                $strategyBonus += 3;
                if ($actualPos <= 3) $podiumCorrect[$actualPos] = true;
            }
        }

        // Podium Sweep Bonus: User predicts the correct 3 drivers on the podium, regardless of their exact P1/P2/P3 order
        $actualTop3 = [$results[1] ?? '', $results[2] ?? '', $results[3] ?? ''];
        $predictedTop3 = [];
        foreach ($userPreds as $pred) {
            if ((int)$pred['predicted_position'] <= 3) {
                $predictedTop3[] = $pred['driver_id'];
            }
        }
        $podiumBonus = (count(array_intersect($actualTop3, $predictedTop3)) === 3) ? 10 : 0;
        
        // Constructor Bonus
        $constructorBonus = 0;
        $cPredStmt = $db->prepare("SELECT constructor_name FROM constructor_predictions WHERE user_id = ? AND race_id = ? LIMIT 1");
        $cPredStmt->bind_param("ii", $userId, $raceId);
        $cPredStmt->execute();
        $cPred = $cPredStmt->get_result()->fetch_assoc();
        
        if ($cPred) {
            $winnerId = $results[1] ?? null;
            if ($winnerId) {
                $wStmt = $db->prepare("SELECT team FROM drivers WHERE id = ?");
                $wStmt->bind_param("s", $winnerId);
                $wStmt->execute();
                $winnerTeam = $wStmt->get_result()->fetch_assoc();
                if ($winnerTeam && $winnerTeam['team'] === $cPred['constructor_name']) {
                    $constructorBonus = 5;
                }
            }
        }

        $subtotal = $basePoints + $strategyBonus + $podiumBonus + $constructorBonus;
        $total = $isDoublePoints ? ($subtotal * 2) : $subtotal;

        // Save Score
        $scoreStmt = $db->prepare("
            INSERT INTO scores (user_id, race_id, driver_points, constructor_points, top3_bonus, total_points, calculated_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE 
                driver_points = VALUES(driver_points),
                constructor_points = VALUES(constructor_points),
                top3_bonus = VALUES(top3_bonus),
                total_points = VALUES(total_points),
                calculated_at = NOW()
        ");
        $driverPtsField = $basePoints + $strategyBonus;
        $scoreStmt->bind_param("iiiiii", $userId, $raceId, $driverPtsField, $constructorBonus, $podiumBonus, $total);
        $scoreStmt->execute();

        // Update User Totals
        $totalsStmt = $db->prepare("
            SELECT COALESCE(SUM(total_points), 0) as grand_total, COUNT(*) as races 
            FROM scores WHERE user_id = ?
        ");
        $totalsStmt->bind_param("i", $userId);
        $totalsStmt->execute();
        $totals = $totalsStmt->get_result()->fetch_assoc();
        
        $updTotals = $db->prepare("
            INSERT INTO user_totals (user_id, total_points, races_participated)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE
                total_points = VALUES(total_points),
                races_participated = VALUES(races_participated)
        ");
        $updTotals->bind_param("iii", $userId, $totals['grand_total'], $totals['races']);
        $updTotals->execute();

        // Check Achievements
        checkAndUnlockAchievements($userId, $db);
    }

    $db->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    if (isset($db)) $db->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
