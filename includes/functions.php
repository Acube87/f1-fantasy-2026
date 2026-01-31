<?php
require_once __DIR__ . '/../config.php';

/**
 * Fetch F1 race results from API
 */
function fetchRaceResults($raceId) {
    $db = getDB();
    
    // Get race info
    $stmt = $db->prepare("SELECT race_number, f1_race_id FROM races WHERE id = ?");
    $stmt->bind_param("i", $raceId);
    $stmt->execute();
    $race = $stmt->get_result()->fetch_assoc();
    
    if (!$race) {
        return ['success' => false, 'message' => 'Race not found'];
    }
    
    // Fetch from Ergast API
    $url = F1_API_BASE . "/races/" . $race['race_number'] . "/results.json";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, F1_API_TIMEOUT);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200 || !$response) {
        return ['success' => false, 'message' => 'Failed to fetch results from F1 API'];
    }
    
    $data = json_decode($response, true);
    
    if (!isset($data['MRData']['RaceTable']['Races'][0]['Results'])) {
        return ['success' => false, 'message' => 'No results available yet'];
    }
    
    $results = $data['MRData']['RaceTable']['Races'][0]['Results'];
    
    // Clear existing results
    $deleteStmt = $db->prepare("DELETE FROM race_results WHERE race_id = ?");
    $deleteStmt->bind_param("i", $raceId);
    $deleteStmt->execute();
    
    // Insert new results
    $insertStmt = $db->prepare("INSERT INTO race_results (race_id, driver_id, driver_name, constructor_id, constructor_name, position, points, fastest_lap, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    foreach ($results as $result) {
        $driverId = $result['Driver']['driverId'] ?? '';
        $driverName = ($result['Driver']['givenName'] ?? '') . ' ' . ($result['Driver']['familyName'] ?? '');
        $constructorId = $result['Constructor']['constructorId'] ?? '';
        $constructorName = $result['Constructor']['name'] ?? '';
        $position = (int)($result['position'] ?? 0);
        $points = (float)($result['points'] ?? 0);
        $fastestLap = isset($result['FastestLap']) ? 1 : 0;
        $status = $result['status'] ?? 'Finished';
        
        $insertStmt->bind_param("issssidis", $raceId, $driverId, $driverName, $constructorId, $constructorName, $position, $points, $fastestLap, $status);
        $insertStmt->execute();
    }
    
    // Update race status
    $updateStmt = $db->prepare("UPDATE races SET status = 'completed', results_fetched = TRUE, results_fetched_at = NOW() WHERE id = ?");
    $updateStmt->bind_param("i", $raceId);
    $updateStmt->execute();
    
    return ['success' => true, 'results' => count($results)];
}

/**
 * Get F1 standard points for positions 1-10
 */
function getF1Points() {
    return [
        1 => 25, 2 => 18, 3 => 15, 4 => 12, 5 => 10,
        6 => 8, 7 => 6, 8 => 4, 9 => 2, 10 => 1
    ];
}

/**
 * Calculate constructor standings for a race based on driver results
 * Returns constructor_id => total_points (sorted descending)
 */
function calculateConstructorStandingsForRace($raceResults) {
    $F1_POINTS = getF1Points();
    $constructorPoints = [];
    
    foreach ($raceResults as $result) {
        $position = $result['position'];
        $constructorId = $result['constructor_id'];
        
        // Only positions 1-10 score F1 points
        if ($position <= 10 && isset($F1_POINTS[$position])) {
            if (!isset($constructorPoints[$constructorId])) {
                $constructorPoints[$constructorId] = 0;
            }
            $constructorPoints[$constructorId] += $F1_POINTS[$position];
        }
    }
    
    arsort($constructorPoints);  // Sort by points descending
    return $constructorPoints;
}

/**
 * Calculate scores for a race - F1 Based System
 * 
 * Scoring Rules:
 * 1. Exact driver position (1-10): F1 points + 3 precision bonus
 * 2. Podium Sweep (P1, P2, P3 all correct): +10 bonus
 * 3. Top Constructor prediction: +5 bonus
 */
function calculateRaceScores($raceId) {
    $db = getDB();
    $F1_POINTS = getF1Points();
    
    // Get all users who made predictions
    $stmt = $db->prepare("SELECT DISTINCT user_id FROM predictions WHERE race_id = ?");
    $stmt->bind_param("i", $raceId);
    $stmt->execute();
    $users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Get actual race results
    $resultsStmt = $db->prepare("SELECT driver_id, position, constructor_id FROM race_results WHERE race_id = ? ORDER BY position");
    $resultsStmt->bind_param("i", $raceId);
    $resultsStmt->execute();
    $actualResults = $resultsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Build driver position map
    $driverPositions = [];
    foreach ($actualResults as $result) {
        $driverPositions[$result['driver_id']] = $result['position'];
    }
    
    // Calculate constructor standings for this race
    $constructorStandings = calculateConstructorStandingsForRace($actualResults);
    $topConstructor = array_key_first($constructorStandings);  // Constructor with most points
    
    // Score each user
    foreach ($users as $user) {
        $userId = $user['user_id'];
        $totalPoints = 0;
        $driverPoints = 0;
        $podiumBonus = 0;
        $constructorBonus = 0;
        
        // Get user's driver predictions
        $predStmt = $db->prepare("SELECT driver_id, predicted_position FROM predictions WHERE user_id = ? AND race_id = ?");
        $predStmt->bind_param("ii", $userId, $raceId);
        $predStmt->execute();
        $predictions = $predStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Track top 3 for podium sweep
        $predictedTop3 = [];  // position => driver_id
        $actualTop3 = [];     // position => driver_id
        
        // Score each driver prediction
        foreach ($predictions as $pred) {
            $driverId = $pred['driver_id'];
            $predictedPos = $pred['predicted_position'];
            $actualPos = $driverPositions[$driverId] ?? null;
            
            // Award points for exact position matches
            if ($actualPos !== null && $predictedPos == $actualPos) {
                // Always award +3 precision bonus for exact match (any position)
                $driverPoints += POINTS_PRECISION_BONUS;
                
                // Award F1 base points ONLY for positions 1-10
                if ($actualPos <= 10 && isset($F1_POINTS[$actualPos])) {
                    $driverPoints += $F1_POINTS[$actualPos];
                }
                
                // Track for podium sweep (only top 3)
                if ($predictedPos <= 3) {
                    $predictedTop3[$predictedPos] = $driverId;
                    $actualTop3[$actualPos] = $driverId;
                }
            }
        }
        
        // Check for Podium Sweep Bonus (+10 pts)
        if (count($predictedTop3) == 3 && count($actualTop3) == 3) {
            if ($predictedTop3[1] === $actualTop3[1] && 
                $predictedTop3[2] === $actualTop3[2] && 
                $predictedTop3[3] === $actualTop3[3]) {
                $podiumBonus = POINTS_PODIUM_SWEEP;
            }
        }
        
        // Check for Constructor Bonus (+5 pts)
        // Get user's predicted top constructor (position 1)
        $constStmt = $db->prepare("SELECT constructor_id FROM constructor_predictions WHERE user_id = ? AND race_id = ? AND predicted_position = 1");
        $constStmt->bind_param("ii", $userId, $raceId);
        $constStmt->execute();
        $constResult = $constStmt->get_result()->fetch_assoc();
        
        if ($constResult && $constResult['constructor_id'] == $topConstructor) {
            $constructorBonus = POINTS_CONSTRUCTOR_BONUS;
        }
        
        $totalPoints = $driverPoints + $podiumBonus + $constructorBonus;
        
        // Save score
        $scoreStmt = $db->prepare("INSERT INTO scores (user_id, race_id, driver_points, constructor_points, top3_bonus, constructor_top3_bonus, total_points) VALUES (?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE driver_points = ?, constructor_points = ?, top3_bonus = ?, constructor_top3_bonus = ?, total_points = ?, calculated_at = NOW()");
        
        // Map to old column names for compatibility
        $constructorPoints = $constructorBonus;  // Store constructor bonus in constructor_points
        $top3Bonus = $podiumBonus;               // Store podium bonus in top3_bonus
        $constructorTop3Bonus = 0;               // Not used in new system
        
        $scoreStmt->bind_param("iiiiiiiiiiii", $userId, $raceId, $driverPoints, $constructorPoints, $top3Bonus, $constructorTop3Bonus, $totalPoints, $driverPoints, $constructorPoints, $top3Bonus, $constructorTop3Bonus, $totalPoints);
        $scoreStmt->execute();
        
        // Update user totals
        updateUserTotals($userId);
    }
    
    return ['success' => true, 'message' => 'Scores calculated using F1-based system'];
}

/**
 * Update user total scores
 */
function updateUserTotals($userId) {
    $db = getDB();
    
    $stmt = $db->prepare("SELECT SUM(total_points) as total, COUNT(*) as races FROM scores WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    $totalPoints = (int)($result['total'] ?? 0);
    $racesParticipated = (int)($result['races'] ?? 0);
    
    $updateStmt = $db->prepare("INSERT INTO user_totals (user_id, total_points, races_participated) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE total_points = ?, races_participated = ?");
    $updateStmt->bind_param("iiiii", $userId, $totalPoints, $racesParticipated, $totalPoints, $racesParticipated);
    $updateStmt->execute();
}

/**
 * Get upcoming races
 */
function getUpcomingRaces($limit = 10) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM races WHERE status = 'upcoming' ORDER BY race_date ASC LIMIT ?");
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Get completed races
 */
function getCompletedRaces($limit = 10) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM races WHERE status = 'completed' ORDER BY race_date DESC LIMIT ?");
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Get user predictions for a race
 */
function getUserPredictions($userId, $raceId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM predictions WHERE user_id = ? AND race_id = ? ORDER BY predicted_position");
    $stmt->bind_param("ii", $userId, $raceId);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Get leaderboard
 */
function getLeaderboard($limit = 50) {
    $db = getDB();
    $stmt = $db->prepare("SELECT u.id, u.username, u.full_name, u.avatar_style, ut.total_points, ut.races_participated FROM users u LEFT JOIN user_totals ut ON u.id = ut.user_id ORDER BY ut.total_points DESC, ut.races_participated DESC LIMIT ?");
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}


/**
 * Get single next upcoming race
 */
function getNextRace() {
    $races = getUpcomingRaces(1);
    return !empty($races) ? $races[0] : null;
}

/**
 * Get user stats (total points and global rank)
 */
function getUserStats($userId) {
    $db = getDB();
    
    // Get total points
    $stmt = $db->prepare("SELECT total_points FROM user_totals WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $totalPoints = $result ? (int)$result['total_points'] : 0;
    
    // Calculate Rank
    // Rank is 1 + count of people with MORE points than me
    $stmt = $db->prepare("SELECT COUNT(*) as rank_above FROM user_totals WHERE total_points > ?");
    $stmt->bind_param("i", $totalPoints);
    $stmt->execute();
    $rankData = $stmt->get_result()->fetch_assoc();
    $rank = ($rankData['rank_above'] ?? 0) + 1;
    
    return [
        'total_points' => $totalPoints,
        'rank' => $rank
    ];
}
