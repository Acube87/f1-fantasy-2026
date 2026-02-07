<?php
/**
 * Automatic Race Results Fetcher
 * Fetches results from Ergast F1 API and calculates scores for all users
 * Can be run manually or via cron job
 */

require_once __DIR__ . '/../config.php';

// Allow running from command line or web
$isCliMode = php_sapi_name() === 'cli';

if (!$isCliMode) {
    echo "<!DOCTYPE html><html><head><title>Fetch Race Results</title>";
    echo "<style>body{font-family:monospace;background:#1a1a1a;color:#0f0;padding:20px;}h1{color:#0ff;}.success{color:#0f0;}.error{color:#f00;}.info{color:#ff0;}</style>";
    echo "</head><body><h1>🏎️ F1 Race Results Fetcher</h1>";
}

function logMessage($message, $type = 'info') {
    global $isCliMode;
    
    $prefix = match($type) {
        'success' => '✅',
        'error' => '❌',
        'warning' => '⚠️',
        default => 'ℹ️'
    };
    
    if ($isCliMode) {
        echo "$prefix $message\n";
    } else {
        $class = $type;
        echo "<p class='$class'>$prefix $message</p>";
    }
}

$db = getDB();

// Get races that need results (completed but not fetched, or race date has passed)
$today = date('Y-m-d');
$stmt = $db->prepare("
    SELECT * FROM races 
    WHERE race_date <= ? 
    AND (results_fetched = FALSE OR results_fetched IS NULL)
    ORDER BY race_date ASC
");
$stmt->bind_param("s", $today);
$stmt->execute();
$racesToFetch = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

if (empty($racesToFetch)) {
    logMessage("No races need results fetching.", 'info');
    exit;
}

logMessage("Found " . count($racesToFetch) . " race(s) to fetch results for.", 'info');

foreach ($racesToFetch as $race) {
    logMessage("\n--- Processing: {$race['race_name']} ({$race['country']}) ---", 'info');
    
    // Extract year and round from race_date or f1_race_id
    $year = date('Y', strtotime($race['race_date']));
    $round = $race['race_number'];
    
    // Ergast API endpoint
    $apiUrl = "http://ergast.com/api/f1/{$year}/{$round}/results.json";
    
    logMessage("Fetching from API: $apiUrl", 'info');
    
    // Fetch data from API
    $response = @file_get_contents($apiUrl);
    
    if ($response === false) {
        logMessage("Failed to fetch data from API for {$race['race_name']}", 'error');
        continue;
    }
    
    $data = json_decode($response, true);
    
    // Check if we have results
    if (!isset($data['MRData']['RaceTable']['Races'][0]['Results'])) {
        logMessage("No results available yet for {$race['race_name']}", 'warning');
        continue;
    }
    
    $results = $data['MRData']['RaceTable']['Races'][0]['Results'];
    
    logMessage("Found " . count($results) . " results", 'success');
    
    // Clear existing results for this race (in case of re-fetch)
    $db->query("DELETE FROM race_results WHERE race_id = {$race['id']}");
    
    $insertedCount = 0;
    
    // Insert each result
    foreach ($results as $result) {
        $driverId = $result['Driver']['driverId'];
        $driverName = $result['Driver']['givenName'] . ' ' . $result['Driver']['familyName'];
        $constructorId = $result['Constructor']['constructorId'];
        $constructorName = $result['Constructor']['name'];
        $position = intval($result['position']);
        $points = floatval($result['points']);
        $status = $result['status'];
        
        // Check for fastest lap
        $fastestLap = isset($result['FastestLap']['rank']) && $result['FastestLap']['rank'] == '1' ? 1 : 0;
        
        // Insert into race_results
        $insertStmt = $db->prepare("
            INSERT INTO race_results 
            (race_id, driver_id, driver_name, constructor_id, constructor_name, position, points, fastest_lap, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $insertStmt->bind_param(
            "issssidis",
            $race['id'],
            $driverId,
            $driverName,
            $constructorId,
            $constructorName,
            $position,
            $points,
            $fastestLap,
            $status
        );
        
        if ($insertStmt->execute()) {
            $insertedCount++;
        } else {
            logMessage("Failed to insert result for $driverName", 'error');
        }
    }
    
    logMessage("Inserted $insertedCount results into database", 'success');
    
    // Update race status
    $updateStmt = $db->prepare("
        UPDATE races 
        SET status = 'completed', 
            results_fetched = TRUE, 
            results_fetched_at = NOW()
        WHERE id = ?
    ");
    $updateStmt->bind_param("i", $race['id']);
    $updateStmt->execute();
    
    logMessage("Updated race status to 'completed'", 'success');
    
    // Now calculate scores for all users who made predictions
    logMessage("\n--- Calculating Scores ---", 'info');
    
    // Get all users who made predictions for this race
    $userStmt = $db->prepare("
        SELECT DISTINCT user_id 
        FROM predictions 
        WHERE race_id = ?
    ");
    $userStmt->bind_param("i", $race['id']);
    $userStmt->execute();
    $users = $userStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    logMessage("Found " . count($users) . " users with predictions", 'info');
    
    $scoresCalculated = 0;
    
    foreach ($users as $userData) {
        $userId = $userData['user_id'];
        
        // Get user's predictions
        $predStmt = $db->prepare("
            SELECT * FROM predictions 
            WHERE user_id = ? AND race_id = ?
        ");
        $predStmt->bind_param("ii", $userId, $race['id']);
        $predStmt->execute();
        $predictions = $predStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Get actual results
        $resultStmt = $db->prepare("
            SELECT driver_id, position 
            FROM race_results 
            WHERE race_id = ?
        ");
        $resultStmt->bind_param("i", $race['id']);
        $resultStmt->execute();
        $actualResults = $resultStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Build lookup map
        $actualPositions = [];
        foreach ($actualResults as $res) {
            $actualPositions[$res['driver_id']] = $res['position'];
        }
        
        // F1 Standard Points System (for positions 1-10)
        $f1Points = [
            1 => 25, 2 => 18, 3 => 15, 4 => 12, 5 => 10,
            6 => 8, 7 => 6, 8 => 4, 9 => 2, 10 => 1
        ];
        
        // Check if this is a double points race
        $isDoublePoints = in_array($race['country'], ['China', 'UK', 'Singapore']);
        
        // Calculate points
        $basePoints = 0;
        $strategyBonus = 0;
        $podiumBonus = 0;
        $constructorBonus = 0;
        
        // Track podium predictions (P1, P2, P3)
        $podiumCorrect = [1 => false, 2 => false, 3 => false];
        
        // Calculate driver position points
        foreach ($predictions as $pred) {
            if (isset($actualPositions[$pred['driver_id']])) {
                $actualPos = $actualPositions[$pred['driver_id']];
                $predictedPos = $pred['predicted_position'];
                
                // Exact match
                if ($actualPos == $predictedPos) {
                    // Add F1 base points (positions 1-10 only)
                    if (isset($f1Points[$actualPos])) {
                        $basePoints += $f1Points[$actualPos];
                    }
                    
                    // Add +3 strategy bonus for ANY exact position match (P1-P22)
                    $strategyBonus += 3;
                    
                    // Track if podium position is correct
                    if ($actualPos >= 1 && $actualPos <= 3) {
                        $podiumCorrect[$actualPos] = true;
                    }
                }
            }
        }
        
        // Check for Podium Sweep Bonus (+10 if ALL three podium positions correct)
        if ($podiumCorrect[1] && $podiumCorrect[2] && $podiumCorrect[3]) {
            $podiumBonus = 10;
        }
        
        // Check for Constructor Bonus (+5 if correct)
        // Get user's constructor prediction
        $constructorPredStmt = $db->prepare("
            SELECT constructor_name, predicted_position 
            FROM constructor_predictions 
            WHERE user_id = ? AND race_id = ? 
            ORDER BY predicted_position ASC 
            LIMIT 1
        ");
        $constructorPredStmt->bind_param("ii", $userId, $race['id']);
        $constructorPredStmt->execute();
        $userConstructorPred = $constructorPredStmt->get_result()->fetch_assoc();
        
        if ($userConstructorPred) {
            // Calculate actual constructor standings for this race
            $constructorStmt = $db->prepare("
                SELECT constructor_name, SUM(points) as total_points 
                FROM race_results 
                WHERE race_id = ? 
                GROUP BY constructor_name 
                ORDER BY total_points DESC 
                LIMIT 1
            ");
            $constructorStmt->bind_param("i", $race['id']);
            $constructorStmt->execute();
            $winningConstructor = $constructorStmt->get_result()->fetch_assoc();
            
            if ($winningConstructor && $winningConstructor['constructor_name'] == $userConstructorPred['constructor_name']) {
                $constructorBonus = 5;
            }
        }
        
        // Calculate total BEFORE multiplier
        $subtotal = $basePoints + $strategyBonus + $podiumBonus + $constructorBonus;
        
        // Apply 2x multiplier for double points races
        if ($isDoublePoints) {
            $totalPoints = $subtotal * 2;
        } else {
            $totalPoints = $subtotal;
        }
        
        // Insert or update score
        $scoreStmt = $db->prepare("
            INSERT INTO scores (user_id, race_id, driver_points, top3_bonus, total_points, calculated_at)
            VALUES (?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE 
                driver_points = VALUES(driver_points),
                top3_bonus = VALUES(top3_bonus),
                total_points = VALUES(total_points),
                calculated_at = NOW()
        ");
        
        // Store base+strategy in driver_points, podium+constructor in top3_bonus for backward compatibility
        $scoreStmt->bind_param("iiiii", $userId, $race['id'], $basePoints + $strategyBonus, $podiumBonus + $constructorBonus, $totalPoints);
        
        if ($scoreStmt->execute()) {
            $scoresCalculated++;
        }
        
        // Update user totals
        updateUserTotals($userId, $db);
    }
    
    logMessage("Calculated scores for $scoresCalculated users", 'success');
    logMessage("✅ Completed processing for {$race['race_name']}\n", 'success');
}

/**
 * Update user's total points and stats
 */
function updateUserTotals($userId, $db) {
    // Calculate totals from scores table
    $stmt = $db->prepare("
        SELECT 
            COALESCE(SUM(total_points), 0) as total_points,
            COUNT(*) as races_participated
        FROM scores
        WHERE user_id = ?
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $totals = $stmt->get_result()->fetch_assoc();
    
    $totalPoints = $totals['total_points'];
    $racesParticipated = $totals['races_participated'];
    $avgPoints = $racesParticipated > 0 ? $totalPoints / $racesParticipated : 0;
    
    // Update user_totals table
    $updateStmt = $db->prepare("
        INSERT INTO user_totals (user_id, total_points, races_participated, average_points)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            total_points = VALUES(total_points),
            races_participated = VALUES(races_participated),
            average_points = VALUES(average_points)
    ");
    
    $updateStmt->bind_param("iiid", $userId, $totalPoints, $racesParticipated, $avgPoints);
    $updateStmt->execute();
}

logMessage("\n🏁 All done! Results fetched and scores calculated.", 'success');

if (!$isCliMode) {
    echo "<hr><p><a href='../dashboard.php'>Go to Dashboard</a> | <a href='fetch-race-results.php'>Run Again</a></p>";
    echo "</body></html>";
}
?>
