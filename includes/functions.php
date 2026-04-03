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
 * Returns constructor_key => total_points (sorted descending)
 * Uses constructor_name as fallback when constructor_id is NULL (Race Control manual entries)
 */
function calculateConstructorStandingsForRace($raceResults) {
    $F1_POINTS = getF1Points();
    $constructorPoints = [];
    
    foreach ($raceResults as $result) {
        $position = $result['position'];
        // Use constructor_id if available, fall back to constructor_name
        $constructorKey = !empty($result['constructor_id']) ? $result['constructor_id'] : ($result['constructor_name'] ?? null);
        
        if (!$constructorKey) continue;
        
        // Only positions 1-10 score F1 points
        if ($position <= 10 && isset($F1_POINTS[$position])) {
            if (!isset($constructorPoints[$constructorKey])) {
                $constructorPoints[$constructorKey] = 0;
            }
            $constructorPoints[$constructorKey] += $F1_POINTS[$position];
        }
    }
    
    arsort($constructorPoints);  // Sort by points descending
    return $constructorPoints;
}

/**
 * Calculate scores for a race - F1 Based System (as per points-system.php)
 *
 * Scoring Rules:
 * 1. Exact driver position match: F1 base points (25-18-15-12-10-8-6-4-2-1 for P1-P10) + 3 strategy bonus
 * 2. Podium Sweep (P1, P2, P3 all correct): +10 bonus
 * 3. Top Constructor prediction: +5 bonus
 * 4. Double points races (China, UK, Singapore): 2x multiplier
 */
function calculateRaceScores($raceId) {
    $db = getDB();
    $F1_POINTS = getF1Points();

    // Get race details for double points check
    $raceStmt = $db->prepare("SELECT country FROM races WHERE id = ?");
    $raceStmt->bind_param("i", $raceId);
    $raceStmt->execute();
    $race = $raceStmt->get_result()->fetch_assoc();
    $isDoublePoints = in_array($race['country'], ['China', 'UK', 'Singapore']);

    // Get all users who made predictions
    $stmt = $db->prepare("SELECT DISTINCT user_id FROM predictions WHERE race_id = ?");
    $stmt->bind_param("i", $raceId);
    $stmt->execute();
    $users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Get actual race results (fetch constructor_name too as fallback for Race Control entries)
    $resultsStmt = $db->prepare("SELECT driver_id, position, constructor_id, constructor_name FROM race_results WHERE race_id = ? ORDER BY position");
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
                // Always award +3 strategy bonus for exact match (any position)
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
        // Calculate user's predicted constructor winner based on their driver predictions
        $userConstructorPoints = [];
        foreach ($predictions as $pred) {
            $driverId = $pred['driver_id'];
            $predictedPos = $pred['predicted_position'];
            $actualPos = $driverPositions[$driverId] ?? null;

            if ($actualPos !== null && $predictedPos == $actualPos) {
                // Find which constructor this driver belongs to
                // Use constructor_id if set, fallback to constructor_name (Race Control manual entries)
                $driverConstructor = null;
                foreach ($actualResults as $result) {
                    if ($result['driver_id'] === $driverId) {
                        $driverConstructor = !empty($result['constructor_id'])
                            ? $result['constructor_id']
                            : ($result['constructor_name'] ?? null);
                        break;
                    }
                }

                if ($driverConstructor) {
                    if (!isset($userConstructorPoints[$driverConstructor])) {
                        $userConstructorPoints[$driverConstructor] = 0;
                    }
                    // Use F1 points for constructor calculation
                    if ($predictedPos <= 10 && isset($F1_POINTS[$predictedPos])) {
                        $userConstructorPoints[$driverConstructor] += $F1_POINTS[$predictedPos];
                    }
                }
            }
        }

        // Determine user's predicted winning constructor
        if (!empty($userConstructorPoints)) {
            arsort($userConstructorPoints);
            $userPredictedWinner = array_key_first($userConstructorPoints);

            if ($userPredictedWinner == $topConstructor) {
                $constructorBonus = POINTS_CONSTRUCTOR_BONUS;
            }
        }

        $subtotal = $driverPoints + $podiumBonus + $constructorBonus;
        $totalPoints = $isDoublePoints ? ($subtotal * 2) : $subtotal;

        // Save score
        $scoreStmt = $db->prepare("INSERT INTO scores (user_id, race_id, driver_points, constructor_points, top3_bonus, constructor_top3_bonus, total_points) VALUES (?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE driver_points = ?, constructor_points = ?, top3_bonus = ?, constructor_top3_bonus = ?, total_points = ?, calculated_at = NOW()");

        // Map to old column names for compatibility
        $constructorPoints = $constructorBonus;  // Store constructor bonus in constructor_points
        $top3Bonus = $podiumBonus;               // Store podium bonus in top3_bonus
        $constructorTop3Bonus = 0;               // Not used in this system

        $scoreStmt->bind_param("iiiiiiiiiiii", $userId, $raceId, $driverPoints, $constructorPoints, $top3Bonus, $constructorTop3Bonus, $totalPoints, $driverPoints, $constructorPoints, $top3Bonus, $constructorTop3Bonus, $totalPoints);
        $scoreStmt->execute();

        // Update user totals
        updateUserTotals($userId);
    }

    return ['success' => true, 'message' => 'Scores calculated using F1-based system with double points for ' . ($isDoublePoints ? 'yes' : 'no')];
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
    
    // Check if achievements table exists to avoid errors before migration
    $check = $db->query("SHOW TABLES LIKE 'achievements'");
    $hasAchievements = $check && $check->num_rows > 0;
    
    if ($hasAchievements) {
        $sql = "
            SELECT 
                u.id, u.username, u.full_name, u.avatar_style, 
                ut.total_points, ut.races_participated,
                GROUP_CONCAT(CONCAT(a.icon, ':', a.tier, ':', a.name) SEPARATOR '|') as badges_data
            FROM users u 
            LEFT JOIN user_totals ut ON u.id = ut.user_id 
            LEFT JOIN user_achievements ua ON u.id = ua.user_id AND ua.is_displayed = 1
            LEFT JOIN achievements a ON ua.achievement_id = a.id
            GROUP BY u.id
            ORDER BY ut.total_points DESC, ut.races_participated DESC 
            LIMIT ?
        ";
    } else {
        $sql = "
            SELECT u.id, u.username, u.full_name, u.avatar_style, ut.total_points, ut.races_participated 
            FROM users u 
            LEFT JOIN user_totals ut ON u.id = ut.user_id 
            ORDER BY ut.total_points DESC, ut.races_participated DESC 
            LIMIT ?
        ";
    }
    
    $stmt = $db->prepare($sql);
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

/**
 * Get dynamic hero image based on country
 */
function getRaceHeroImage($country) {
    // normalize the country string so keys are matched reliably
    $key = trim($country);
    $key = ucfirst(strtolower($key));

    $images = [
        'Australia' => 'https://images.unsplash.com/photo-1506126613408-eca07ce68773?q=80&w=2070&auto=format&fit=crop',
        'China' => 'https://images.unsplash.com/photo-1618631954937-526e872edb89?q=80&w=2070&auto=format&fit=crop',
        'Japan' => 'https://images.unsplash.com/photo-1493976040374-85c8e12f0c0e?q=80&w=2070&auto=format&fit=crop',
        'Bahrain' => 'https://images.unsplash.com/photo-1517404215738-15263e9f9178?q=80&w=2070&auto=format&fit=crop',
        'Saudi Arabia' => 'https://images.unsplash.com/photo-1551041777-ed07f99c67d1?q=80&w=2070&auto=format&fit=crop',
        'Saudi arabia' => 'https://images.unsplash.com/photo-1551041777-ed07f99c67d1?q=80&w=2070&auto=format&fit=crop',
        // Miami GP has country='United States' in DB — normalization → 'United states'
        'United states' => 'https://images.unsplash.com/photo-1514362545857-3bc16c4c7d1b?q=80&w=2070&auto=format&fit=crop',
        'Miami' => 'https://images.unsplash.com/photo-1514362545857-3bc16c4c7d1b?q=80&w=2070&auto=format&fit=crop',
        'Monaco' => 'https://images.unsplash.com/photo-1555436169-20e93ea9a7ff?q=80&w=2070&auto=format&fit=crop',
        'Canada' => 'https://images.unsplash.com/photo-1503416997304-7f8bf166c121?q=80&w=2070&auto=format&fit=crop',
        'Spain' => 'https://images.unsplash.com/photo-1543783232-26037dd78bfc?q=80&w=2070&auto=format&fit=crop',
        'Austria' => 'https://images.unsplash.com/photo-1527004013197-933c4bb611b3?q=80&w=2070&auto=format&fit=crop',
        'Uk' => 'https://images.unsplash.com/photo-1486299267070-83823f5448dd?q=80&w=2070&auto=format&fit=crop',
        'UK' => 'https://images.unsplash.com/photo-1486299267070-83823f5448dd?q=80&w=2070&auto=format&fit=crop',
        'United kingdom' => 'https://images.unsplash.com/photo-1486299267070-83823f5448dd?q=80&w=2070&auto=format&fit=crop',
        'Hungary' => 'https://images.unsplash.com/photo-1503602642458-232111445657?q=80&w=2070&auto=format&fit=crop',
        'Belgium' => 'https://images.unsplash.com/photo-1563821735430-664440af4592?q=80&w=2070&auto=format&fit=crop',
        'Netherlands' => 'https://images.unsplash.com/photo-1512470876302-972faa2aa9a4?q=80&w=2070&auto=format&fit=crop',
        'Italy' => 'https://images.unsplash.com/photo-1529260830199-42c24126f198?q=80&w=2070&auto=format&fit=crop',
        'Azerbaijan' => 'https://images.unsplash.com/photo-1549420070-5b1b4b2cb82b?q=80&w=2070&auto=format&fit=crop',
        'Singapore' => 'https://images.unsplash.com/photo-1525625230556-3a56cc544253?q=80&w=2070&auto=format&fit=crop',
        'Usa' => 'https://images.unsplash.com/photo-1485738422979-f5c462d49f74?q=80&w=2070&auto=format&fit=crop',
        'USA' => 'https://images.unsplash.com/photo-1485738422979-f5c462d49f74?q=80&w=2070&auto=format&fit=crop',
        'Mexico' => 'https://images.unsplash.com/photo-1518105779142-d975f22f1b0a?q=80&w=2070&auto=format&fit=crop',
        'Brazil' => 'https://images.unsplash.com/photo-1483728642387-aa95229753c4?q=80&w=2070&auto=format&fit=crop',
        'Las Vegas' => 'https://images.unsplash.com/photo-1581351123004-757df051db8e?q=80&w=2070&auto=format&fit=crop',
        'Las vegas' => 'https://images.unsplash.com/photo-1581351123004-757df051db8e?q=80&w=2070&auto=format&fit=crop',
        'Qatar' => 'https://images.unsplash.com/photo-1511222409051-bd12c6686146?q=80&w=2070&auto=format&fit=crop',
        'Abu Dhabi' => 'https://images.unsplash.com/photo-1512453979798-5ea266f8880c?q=80&w=2070&auto=format&fit=crop',
        'Abu dhabi' => 'https://images.unsplash.com/photo-1512453979798-5ea266f8880c?q=80&w=2070&auto=format&fit=crop',
    ];

    if (isset($images[$key])) {
        return $images[$key];
    }

    // Generic fallback — motorsport image
    return 'https://images.unsplash.com/photo-1568605117036-5fe5e7bab0b7?q=80&w=2070&auto=format&fit=crop';
}

/**
 * Get country flag emoji
 */
function getRaceFlag($country) {
    $flags = [
        'Australia' => '🇦🇺',
        'China' => '🇨🇳',
        'Japan' => '🇯🇵',
        'Bahrain' => '🇧🇭',
        'Saudi Arabia' => '🇸🇦',
        'Miami' => '🇺🇸',
        // DB stores country as 'United States' for Miami, Las Vegas, COTA
        'United States' => '🇺🇸',
        'Italy' => '🇮🇹',
        'Monaco' => '🇲🇨',
        'Canada' => '🇨🇦',
        'Spain' => '🇪🇸',
        'Austria' => '🇦🇹',
        'UK' => '🇬🇧',
        'United Kingdom' => '🇬🇧',
        'Hungary' => '🇭🇺',
        'Belgium' => '🇧🇪',
        'Netherlands' => '🇳🇱',
        'Azerbaijan' => '🇦🇿',
        'Singapore' => '🇸🇬',
        'USA' => '🇺🇸',
        'Mexico' => '🇲🇽',
        'Brazil' => '🇧🇷',
        'Las Vegas' => '🇺🇸',
        'Qatar' => '🇶🇦',
        'Abu Dhabi' => '🇦🇪',
        'United Arab Emirates' => '🇦🇪',
    ];
    return $flags[$country] ?? '🏁';
}

/**
 * Calculate the prediction deadline for a race.
 * Standard: Saturday 00:00:00 UTC before the race.
 */
function getPredictionDeadline($raceDateStr) {
    $raceDate = new DateTime($raceDateStr, new DateTimeZone('UTC'));
    $dayOfWeek = (int)$raceDate->format('N'); // 1=Monday, 7=Sunday
    
    $deadline = clone $raceDate;
    
    if ($dayOfWeek == 7) {
        // Race is on Sunday, deadline is Saturday 00:00 (1 day before)
        $deadline->modify('-1 day')->setTime(0, 0, 0);
    } elseif ($dayOfWeek == 6) {
        // Race is on Saturday (Sprint weekend), deadline is Saturday 00:00 (same day)
        $deadline->setTime(0, 0, 0);
    } else {
        // Race on other day, find previous Saturday
        $daysToSubtract = ($dayOfWeek == 0 ? 1 : 8 - $dayOfWeek);
        $deadline->modify("-{$daysToSubtract} days")->setTime(0, 0, 0);
    }
    
    return $deadline;
}

/**
 * Generate and create post-race debrief post
 */
function createPostRaceDebrief($raceId, $db = null) {
    if (!$db) {
        $db = getDB();
    }

    // Get race details
    $raceStmt = $db->prepare("SELECT * FROM races WHERE id = ?");
    $raceStmt->bind_param("i", $raceId);
    $raceStmt->execute();
    $race = $raceStmt->get_result()->fetch_assoc();
    
    if (!$race) return false;

    // Use Race Control system user as author (ID 3)
    // If it doesn't exist, use admin user
    $authorId = 3; // Race Control system account
    $checkStmt = $db->prepare("SELECT id FROM users WHERE id = ?");
    $checkStmt->bind_param("i", $authorId);
    $checkStmt->execute();
    $check = $checkStmt->get_result()->fetch_assoc();
    if (!$check) {
        // Fallback to admin if Race Control doesn't exist
        $adminStmt = $db->prepare("SELECT id FROM users WHERE is_admin = 1 LIMIT 1");
        $adminStmt->execute();
        $admin = $adminStmt->get_result()->fetch_assoc();
        $authorId = $admin['id'] ?? 1;
    }

    // Get top 3 users by points in this race
    $topStmt = $db->prepare("
        SELECT s.user_id, u.username, s.total_points, 
               s.driver_points, s.constructor_points, s.top3_bonus
        FROM scores s
        JOIN users u ON s.user_id = u.id
        WHERE s.race_id = ?
        ORDER BY s.total_points DESC
        LIMIT 3
    ");
    $topStmt->bind_param("i", $raceId);
    $topStmt->execute();
    $topUsers = $topStmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Get race winner
    $winnerStmt = $db->prepare("
        SELECT driver_name FROM race_results WHERE race_id = ? AND position = 1 LIMIT 1
    ");
    $winnerStmt->bind_param("i", $raceId);
    $winnerStmt->execute();
    $winner = $winnerStmt->get_result()->fetch_assoc();
    $winnerName = $winner['driver_name'] ?? 'Unknown Driver';

    // Get constructor winner
    $constructorStmt = $db->prepare("
        SELECT constructor_name, SUM(points) as total_points 
        FROM race_results 
        WHERE race_id = ? 
        GROUP BY constructor_name 
        ORDER BY total_points DESC 
        LIMIT 1
    ");
    $constructorStmt->bind_param("i", $raceId);
    $constructorStmt->execute();
    $constructor = $constructorStmt->get_result()->fetch_assoc();
    $constructorWinner = $constructor['constructor_name'] ?? 'Unknown Team';

    // Get next upcoming race
    $nextStmt = $db->query("SELECT id, race_name, country, race_date FROM races WHERE status = 'upcoming' ORDER BY race_date ASC LIMIT 1");
    $nextRace = $nextStmt->fetch_assoc();

    // Count users who submitted predictions
    $predStmt = $db->prepare("SELECT COUNT(DISTINCT user_id) as count FROM predictions WHERE race_id = ?");
    $predStmt->bind_param("i", $raceId);
    $predStmt->execute();
    $predCount = $predStmt->get_result()->fetch_assoc();
    $participantCount = $predCount['count'] ?? 0;

    // Get user with most correct predictions
    $maxStmt = $db->prepare("
        SELECT u.username, COUNT(DISTINCT p.driver_id) as correct_predictions
        FROM predictions p
        JOIN users u ON p.user_id = u.id
        JOIN race_results rr ON rr.race_id = p.race_id AND rr.driver_id = p.driver_id AND rr.position = p.predicted_position
        WHERE p.race_id = ?
        GROUP BY p.user_id
        ORDER BY correct_predictions DESC
        LIMIT 1
    ");
    $maxStmt->bind_param("i", $raceId);
    $maxStmt->execute();
    $mostCorrect = $maxStmt->get_result()->fetch_assoc();

    // Build the debrief content
    $content = "<div class='post-race-debrief'>\n\n";
    
    // Title Section
    $content .= "🏁 <strong>" . htmlspecialchars($race['race_name']) . " - POST-RACE DEBRIEF 🏁</strong>\n\n";
    
    // Race Summary
    $content .= "📊 <strong>The Verdict From " . htmlspecialchars($race['country']) . ":</strong>\n\n";
    $content .= "On the track, <strong>$winnerName</strong> absolutely sent it and took the chequered flag! 🏆\n\n";
    $content .= "<strong>$constructorWinner</strong> brought the constructors' championship energy with some serious points-scoring action. 💪\n\n";
    
    // Prediction Stats
    $content .= "📈 <strong>Your Predictions (The Real Race):</strong>\n";
    $content .= "$participantCount of you crystal-ball gazing legends made your predictions. Respect! 👀\n\n";
    
    // Most Correct
    if ($mostCorrect) {
        $content .= "🎯 <strong>" . htmlspecialchars($mostCorrect['username']) . "</strong> had " . (int)$mostCorrect['correct_predictions'] . " drivers spot-on. Tactical mastermind detected! 🧠\n\n";
    }
    
    // Top 3 Rankings
    $content .= "🥇 <strong>PODIUM FINISHERS (Top 3 Prediction Scorers):</strong>\n\n";
    
    $medals = ["🥇", "🥈", "🥉"];
    foreach ($topUsers as $idx => $topUser) {
        $medal = $medals[$idx] ?? "•";
        $userName = htmlspecialchars($topUser['username']);
        $totalPoints = (int)$topUser['total_points'];
        $driverPoints = (int)$topUser['driver_points'];
        $constructorBonusNote = ($topUser['constructor_points'] > 0) ? " ⭐ (incl. constructor bonus!)" : "";
        $podiumBonusNote = ($topUser['top3_bonus'] > 0) ? " 👑 (podium sweep!)" : "";
        
        $content .= "$medal <strong>P" . ($idx + 1) . ": " . $userName . "</strong> - $totalPoints Points$constructorBonusNote$podiumBonusNote\n";
        $content .= "    └─ Driver Accuracy: $driverPoints pts | Strategy Bonus Activated ✅\n\n";
    }
    
    // Engagement Section
    $content .= " <strong>Community Challenge:</strong>\nThink you can beat the odds next time? The competition's heating up faster than a DRS zone! Show us what you've got! 🚀\n\n";
    
    $content .= "---\n";
    $content .= "<em>Data is beautiful. Predictions are educated guesses. Memes are eternal. See you next race! 🏎️</em>\n\n";
    
    $content .= "</div>";

    // Insert the post
    $postStmt = $db->prepare("
        INSERT INTO posts (race_id, title, content, author_id, is_manual) 
        VALUES (?, ?, ?, ?, 0)
    ");
    
    $title = "🏁 " . $race['race_name'] . " - Race Debrief & Winner Showcase";
    $postStmt->bind_param("issi", $raceId, $title, $content, $authorId);
    
    return $postStmt->execute();
}

function generateDebriefPreview($raceId, $db = null) {
    if (!$db) {
        $db = getDB();
    }

    // Get race details
    $raceStmt = $db->prepare("SELECT * FROM races WHERE id = ?");
    $raceStmt->bind_param("i", $raceId);
    $raceStmt->execute();
    $race = $raceStmt->get_result()->fetch_assoc();
    
    if (!$race) return false;

    // Get top 3 users by points in this race
    $topStmt = $db->prepare("
        SELECT s.user_id, u.username, s.total_points, 
               s.driver_points, s.constructor_points, s.top3_bonus
        FROM scores s
        JOIN users u ON s.user_id = u.id
        WHERE s.race_id = ?
        ORDER BY s.total_points DESC
        LIMIT 3
    ");
    $topStmt->bind_param("i", $raceId);
    $topStmt->execute();
    $topUsers = $topStmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Get race winner
    $winnerStmt = $db->prepare("
        SELECT driver_name FROM race_results WHERE race_id = ? AND position = 1 LIMIT 1
    ");
    $winnerStmt->bind_param("i", $raceId);
    $winnerStmt->execute();
    $winner = $winnerStmt->get_result()->fetch_assoc();
    $winnerName = $winner['driver_name'] ?? 'Unknown Driver';

    // Get constructor winner
    $constructorStmt = $db->prepare("
        SELECT constructor_name, SUM(points) as total_points 
        FROM race_results 
        WHERE race_id = ? 
        GROUP BY constructor_name 
        ORDER BY total_points DESC 
        LIMIT 1
    ");
    $constructorStmt->bind_param("i", $raceId);
    $constructorStmt->execute();
    $constructor = $constructorStmt->get_result()->fetch_assoc();
    $constructorWinner = $constructor['constructor_name'] ?? 'Unknown Team';

    // Get next upcoming race
    $nextStmt = $db->query("SELECT id, race_name, country, race_date FROM races WHERE status = 'upcoming' ORDER BY race_date ASC LIMIT 1");
    $nextRace = $nextStmt->fetch_assoc();

    // Count users who submitted predictions
    $predStmt = $db->prepare("SELECT COUNT(DISTINCT user_id) as count FROM predictions WHERE race_id = ?");
    $predStmt->bind_param("i", $raceId);
    $predStmt->execute();
    $predCount = $predStmt->get_result()->fetch_assoc();
    $participantCount = $predCount['count'] ?? 0;

    // Get user with most correct predictions
    $maxStmt = $db->prepare("
        SELECT u.username, COUNT(DISTINCT p.driver_id) as correct_predictions
        FROM predictions p
        JOIN users u ON p.user_id = u.id
        JOIN race_results rr ON rr.race_id = p.race_id AND rr.driver_id = p.driver_id AND rr.position = p.predicted_position
        WHERE p.race_id = ?
        GROUP BY p.user_id
        ORDER BY correct_predictions DESC
        LIMIT 1
    ");
    $maxStmt->bind_param("i", $raceId);
    $maxStmt->execute();
    $mostCorrect = $maxStmt->get_result()->fetch_assoc();

    // Build the debrief content
    $content = "<div class='post-race-debrief'>\n\n";
    
    // Title Section
    $content .= "🏁 <strong>" . htmlspecialchars($race['race_name']) . " - POST-RACE DEBRIEF 🏁</strong>\n\n";
    
    // Race Summary
    $content .= "📊 <strong>The Verdict From " . htmlspecialchars($race['country']) . ":</strong>\n\n";
    $content .= "On the track, <strong>$winnerName</strong> absolutely sent it and took the chequered flag! 🏆\n\n";
    $content .= "<strong>$constructorWinner</strong> brought the constructors' championship energy with some serious points-scoring action. 💪\n\n";
    
    // Prediction Stats
    $content .= "📈 <strong>Your Predictions (The Real Race):</strong>\n";
    $content .= "$participantCount of you crystal-ball gazing legends made your predictions. Respect! 👀\n\n";
    
    // Most Correct
    if ($mostCorrect) {
        $content .= "🎯 <strong>" . htmlspecialchars($mostCorrect['username']) . "</strong> had " . (int)$mostCorrect['correct_predictions'] . " drivers spot-on. Tactical mastermind detected! 🧠\n\n";
    }
    
    // Top 3 Rankings
    $content .= "🥇 <strong>PODIUM FINISHERS (Top 3 Prediction Scorers):</strong>\n\n";
    
    $medals = ["🥇", "🥈", "🥉"];
    foreach ($topUsers as $idx => $topUser) {
        $medal = $medals[$idx] ?? "•";
        $userName = htmlspecialchars($topUser['username']);
        $totalPoints = (int)$topUser['total_points'];
        $driverPoints = (int)$topUser['driver_points'];
        $constructorBonusNote = ($topUser['constructor_points'] > 0) ? " ⭐ (incl. constructor bonus!)" : "";
        $podiumBonusNote = ($topUser['top3_bonus'] > 0) ? " 👑 (podium sweep!)" : "";
        
        $content .= "$medal <strong>P" . ($idx + 1) . ": " . $userName . "</strong> - $totalPoints Points$constructorBonusNote$podiumBonusNote\n";
        $content .= "    └─ Driver Accuracy: $driverPoints pts | Strategy Bonus Activated ✅\n\n";
    }
    
    // Engagement Section
    $content .= "💬 <strong>Community Challenge:</strong>\nThink you can beat the odds next time? The competition's heating up faster than a DRS zone! Show us what you've got! 🚀\n\n";
    
    $content .= "---\n";
    $content .= "<em>Data is beautiful. Predictions are educated guesses. Memes are eternal. See you next race! 🏎️</em>\n\n";
    
    $content .= "</div>";

    return $content;
}

