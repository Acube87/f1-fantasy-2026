<?php
/**
 * Post-Race Monday Simulation Script
 * Simulates: 
 * 1. Australian GP results being available
 * 2. Automated scoring for all users
 * 3. Advancement to the next race (China)
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/achievements.php';

$db = getDB();

echo "<!DOCTYPE html><html><head><title>F1 Monday Simulation</title>";
echo "<style>body{font-family:monospace;background:#1a1a1a;color:#0f0;padding:20px;}h1{color:#0ff;}.success{color:#0f0;}.info{color:#ff0;}</style>";
echo "</head><body><h1>🏎️ F1 POST-RACE SIMULATION (MONDAY MORNING)</h1>";

function logSim($msg, $typ = 'info') {
    $pre = ($typ == 'success') ? "✅" : "ℹ️";
    echo "<p class='$typ'>$pre $msg</p>";
}

// 1. Define the "Official" Results for Australia 2026
// Simulation using SLUG-based IDs (hamilton, leclerc, etc) as seen in DB
$results = [
    ['driver_id' => 'norris',   'name' => 'Lando Norris',    'team' => 'McLaren', 'pos' => 1],      // User Match P1!
    ['driver_id' => 'leclerc',  'name' => 'Charles Leclerc', 'team' => 'Ferrari', 'pos' => 2],      
    ['driver_id' => 'gasly',    'name' => 'Pierre Gasly',    'team' => 'Alpine',  'pos' => 3],       // User Match P3!
    ['driver_id' => 'verstappen', 'name' => 'Max Verstappen', 'team' => 'Red Bull', 'pos' => 4],
    ['driver_id' => 'russell',  'name' => 'George Russell',  'team' => 'Mercedes', 'pos' => 5],
    ['driver_id' => 'hamilton', 'name' => 'Lewis Hamilton',  'team' => 'Ferrari',  'pos' => 6],
    ['driver_id' => 'piastri',  'name' => 'Oscar Piastri',   'team' => 'McLaren',  'pos' => 7],
    ['driver_id' => 'alonso',   'name' => 'Fernando Alonso', 'team' => 'Aston Martin', 'pos' => 8],
    ['driver_id' => 'zhou',     'name' => 'Guanyu Zhou',     'team' => 'Kick Sauber', 'pos' => 9],
    ['driver_id' => 'hulkenberg','name' => 'Nico Hulkenberg','team' => 'Haas', 'pos' => 10],
];

logSim("Simulating Australian GP Finish Order with DB-compliant Slugs...", 'info');

$db->begin_transaction();

try {
    // 2. Clear exiting results and insert new ones
    $db->query("DELETE FROM race_results WHERE race_id = 1");
    
    $stmt = $db->prepare("INSERT INTO race_results (race_id, driver_id, driver_name, constructor_name, position, points, status) VALUES (1, ?, ?, ?, ?, ?, 'Finished')");
    
    $f1Points = [1 => 25, 2 => 18, 3 => 15, 4 => 12, 5 => 10, 6 => 8, 7 => 6, 8 => 4, 9 => 2, 10 => 1];
    
    foreach ($results as $res) {
        $pts = $f1Points[$res['pos']] ?? 0;
        $stmt->bind_param("sssii", $res['driver_id'], $res['name'], $res['team'], $res['pos'], $pts);
        $stmt->execute();
    }
    logSim("Inserted 10 race results with correct slugs.", 'success');

    // 3. Mark the race as COMPLETED
    $db->query("UPDATE races SET status = 'completed', results_fetched = 1 WHERE id = 1");
    logSim("Australia Status updated to COMPLETED.", 'success');

    // 4. Calculate Scores for Everyone
    logSim("Running Paddock Picks Scoring Engine...", 'info');
    
    $users = $db->query("SELECT id FROM users")->fetch_all(MYSQLI_ASSOC);
    $scoresCalculated = 0;

    foreach ($users as $u) {
        $userId = $u['id'];
        
        // Get user predictions
        $predStmt = $db->prepare("SELECT driver_id, predicted_position FROM predictions WHERE user_id = ? AND race_id = 1");
        $predStmt->bind_param("i", $userId);
        $predStmt->execute();
        $predictions = $predStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        if (empty($predictions)) continue;

        // Scoring Logic
        $basePoints = 0;
        $strategyBonus = 0;
        $podiumCorrect = [1 => false, 2 => false, 3 => false];

        foreach ($predictions as $pred) {
            foreach ($results as $actual) {
                if ($pred['driver_id'] == $actual['driver_id'] && $pred['predicted_position'] == $actual['pos']) {
                    // Exact match
                    if (isset($f1Points[$actual['pos']])) {
                        $basePoints += $f1Points[$actual['pos']];
                    }
                    $strategyBonus += 3;
                    if ($actual['pos'] <= 3) $podiumCorrect[$actual['pos']] = true;
                }
            }
        }

        $podiumBonus = ($podiumCorrect[1] && $podiumCorrect[2] && $podiumCorrect[3]) ? 10 : 0;
        $total = $basePoints + $strategyBonus + $podiumBonus;

        // Insert score
        $insScore = $db->prepare("INSERT INTO scores (user_id, race_id, driver_points, top3_bonus, total_points, calculated_at) VALUES (?, 1, ?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE total_points = VALUES(total_points)");
        $driverPts = $basePoints + $strategyBonus;
        $insScore->bind_param("iiii", $userId, $driverPts, $podiumBonus, $total);
        $insScore->execute();
        
        // Update user totals and badges
        $db->query("INSERT INTO user_totals (user_id, total_points, races_participated) VALUES ($userId, $total, 1) ON DUPLICATE KEY UPDATE total_points = total_points + $total, races_participated = races_participated + 1");
        
        $scoresCalculated++;
    }

    $db->commit();
    logSim("Successfully calculated scores for $scoresCalculated users!", 'success');
    logSim("Simulation Complete. Redirecting in 3 seconds...", 'info');
    
    echo "<meta http-equiv='refresh' content='3;url=../dashboard.php'>";
    echo "<p><a href='../dashboard.php' style='color:#0ff'>[CLICK HERE TO VIEW DASHBOARD]</a></p>";

} catch (Exception $e) {
    $db->rollback();
    logSim("CRITICAL ERROR: " . $e->getMessage(), 'error');
}

echo "</body></html>";
