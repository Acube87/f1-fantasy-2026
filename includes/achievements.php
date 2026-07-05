<?php
/**
 * Achievements System Helper Functions
 * Handles checking, unlocking, and managing user achievements
 */

require_once __DIR__ . '/../config.php';

/**
 * Check and unlock all applicable achievements for a user
 * This should be called after significant events (prediction submitted, scores calculated, etc.)
 */
function checkAndUnlockAchievements($userId, $db = null) {
    if (!$db) {
        $db = getDB();
    }
    
    $newlyUnlocked = [];
    
    // Get all achievements the user doesn't have yet
    $stmt = $db->prepare("
        SELECT a.id, a.tier
        FROM achievements a
        LEFT JOIN user_achievements ua ON a.id = ua.achievement_id AND ua.user_id = ?
        WHERE ua.id IS NULL
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $lockedAchievements = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Check each locked achievement
    foreach ($lockedAchievements as $achievement) {
        $achievementId = $achievement['id'];
        
        if (checkAchievementCriteria($userId, $achievementId, $db)) {
            if (unlockAchievement($userId, $achievementId, $db)) {
                $newlyUnlocked[] = $achievementId;
            }
        }
    }
    
    return $newlyUnlocked;
}

/**
 * Check if a specific achievement's criteria is met
 */
function checkAchievementCriteria($userId, $achievementId, $db) {
    switch ($achievementId) {
        // COMMON
        case 'first_prediction': return checkFirstPrediction($userId, $db);
        case 'welcome_aboard': return checkProfileComplete($userId, $db);
        case 'first_points': return checkFirstPoints($userId, $db);
        case 'participation_5': return checkParticipation($userId, 5, $db);
        case 'streak_3': return checkScoreStreak($userId, 3, $db);
        
        // RARE
        case 'participation_10': return checkParticipation($userId, 10, $db);
        case 'podium_sweep_1': return checkPodiumSweeps($userId, 1, $db);
        case 'total_500': return checkTotalPoints($userId, 100, $db);
        case 'constructor_correct_5': return false; // Constructor tracking not implemented yet
        case 'perfectionist': return checkExactMatchesInRace($userId, 5, $db);
        case 'accuracy_20': return checkAccuracy($userId, 45, $db);
        
        // EPIC
        case 'big_score': return checkSingleRaceScore($userId, 150, false, $db);
        case 'podium_sweep_3': return checkPodiumSweeps($userId, 5, $db);
        case 'streak_10': return checkParticipationStreak($userId, 10, $db);
        case 'double_points_master': return checkDoublePointsScore($userId, 200, $db);
        case 'accuracy_30': return checkAccuracy($userId, 30, $db);
        case 'total_1000': return checkTotalPoints($userId, 1000, $db);
        case 'race_winner_3': return false; // Race win tracking not implemented yet
        
        // LEGENDARY
        case 'legendary_performance': return checkSingleRaceScore($userId, 150, false, $db);
        case 'podium_sweep_5': return checkPodiumSweeps($userId, 10, $db);
        case 'accuracy_40': return checkAccuracy($userId, 66, $db);
        case 'total_2500': return checkTotalPoints($userId, 2000, $db);
        
        // SPECIAL
        case 'first_race_winner': return false; // Race win tracking not implemented yet
        case 'constructor_sweep': return false; // Constructor tracking not implemented yet
        case 'perfect_weekend': return checkConsecutiveHighScores($userId, 3, 100, $db);
        case 'mega_race': return checkDoublePointsScore($userId, 200, $db);
        case 'silver_arrows': return checkMercedes12($userId, $db);
        case 'columbus': return checkWinAllContinents($userId, $db);
        case 'f1_hero': return checkAllRacesParticipation($userId, $db);
        
        default: return false;
    }
}

/**
 * Unlock an achievement for a user (auto-displayed by default)
 */
function unlockAchievement($userId, $achievementId, $db) {
    $stmt = $db->prepare("INSERT IGNORE INTO user_achievements (user_id, achievement_id, is_displayed) VALUES (?, ?, 1)");
    $stmt->bind_param("is", $userId, $achievementId);
    return $stmt->execute();
}

/**
 * Get all achievements unlocked by a user
 */
function getUserAchievements($userId, $db = null) {
    if (!$db) {
        $db = getDB();
    }
    
    $stmt = $db->prepare("
        SELECT a.*, ua.unlocked_at, ua.is_displayed
        FROM user_achievements ua
        JOIN achievements a ON ua.achievement_id = a.id
        WHERE ua.user_id = ?
        ORDER BY ua.unlocked_at DESC
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Get achievement stats
 */
function getAchievementStats($userId, $db = null) {
    if (!$db) {
        $db = getDB();
    }
    
    // Get total achievements
    $totalStmt = $db->query("SELECT COUNT(*) as total FROM achievements");
    $total = $totalStmt->fetch_assoc()['total'];
    
    // Get unlocked count
    $unlockedStmt = $db->prepare("SELECT COUNT(*) as unlocked FROM user_achievements WHERE user_id = ?");
    $unlockedStmt->bind_param("i", $userId);
    $unlockedStmt->execute();
    $unlocked = $unlockedStmt->get_result()->fetch_assoc()['unlocked'];
    
    // Get displayed badges (multiple)
    $displayedStmt = $db->prepare("
        SELECT a.* 
        FROM user_achievements ua
        JOIN achievements a ON ua.achievement_id = a.id
        WHERE ua.user_id = ? AND ua.is_displayed = 1
    ");
    $displayedStmt->bind_param("i", $userId);
    $displayedStmt->execute();
    $displayed = $displayedStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    return [
        'total' => $total,
        'unlocked' => $unlocked,
        'completion' => $total > 0 ? round(($unlocked / $total) * 100) : 0,
        'displayed' => $displayed // Now an array
    ];
}

/**
 * Toggle displayed badge status (allow multiple)
 */
function toggleDisplayedBadge($userId, $achievementId, $db = null) {
    if (!$db) {
        $db = getDB();
    }
    
    // Check current status
    $stmt = $db->prepare("SELECT is_displayed FROM user_achievements WHERE user_id = ? AND achievement_id = ?");
    $stmt->bind_param("is", $userId, $achievementId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if (!$result) return false; // Not unlocked
    
    $newStatus = $result['is_displayed'] ? 0 : 1;
    
    $updateStmt = $db->prepare("UPDATE user_achievements SET is_displayed = ? WHERE user_id = ? AND achievement_id = ?");
    $updateStmt->bind_param("iis", $newStatus, $userId, $achievementId);
    return $updateStmt->execute();
}

// ============================================================================
// CRITERIA FUNCTIONS
// ============================================================================

function checkFirstPrediction($userId, $db) {
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM predictions WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc()['count'] > 0;
}

function checkProfileComplete($userId, $db) {
    $stmt = $db->prepare("SELECT full_name, avatar_style FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    return !empty($user['full_name']) && !empty($user['avatar_style']);
}

function checkFirstPoints($userId, $db) {
    $stmt = $db->prepare("SELECT total_points FROM user_totals WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return ($result && $result['total_points'] > 0);
}

function checkParticipation($userId, $minRaces, $db) {
    $stmt = $db->prepare("SELECT COUNT(DISTINCT race_id) as count FROM predictions WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc()['count'] >= $minRaces;
}

function checkScoreStreak($userId, $minStreak, $db) {
    // This is simple: check last 3 active races where user participated
    // For robust streak, we need race dates.
    // Simplifying: Check if user has points in the last N races relative to TODAY
    // Or just count total races with points? No, streak means consecutive.
    // Let's implement simple check: Get last N races, check if user has valid score > 0 in all
    
    // Get last N completed races
    $stmt = $db->prepare("
        SELECT id FROM races 
        WHERE status = 'completed' AND results_fetched = 1 
        ORDER BY race_date DESC LIMIT ?
    ");
    $stmt->bind_param("i", $minStreak);
    $stmt->execute();
    $races = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    if (count($races) < $minStreak) return false;
    
    // Check if user has score > 0 in ALL these races
    $raceIds = array_column($races, 'id');
    $placeholders = implode(',', array_fill(0, count($raceIds), '?'));
    
    $sql = "SELECT COUNT(*) as count FROM scores WHERE user_id = ? AND total_points > 0 AND race_id IN ($placeholders)";
    
    $stmt = $db->prepare($sql);
    $params = array_merge([$userId], $raceIds);
    $types = str_repeat('i', count($params));
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    
    return $stmt->get_result()->fetch_assoc()['count'] == $minStreak;
}

function checkPodiumSweeps($userId, $minSweeps, $db) {
    // 10 points bonus was the old way? No, current way is top3_bonus
    // In config: define('POINTS_PODIUM_SWEEP', 10);
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM scores WHERE user_id = ? AND top3_bonus >= 10");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc()['count'] >= $minSweeps;
}

function checkTotalPoints($userId, $minPoints, $db) {
    $stmt = $db->prepare("SELECT total_points FROM user_totals WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    return ($res && $res['total_points'] >= $minPoints);
}

function checkExactMatchesInRace($userId, $minMatches, $db) {
    // Find MAX exact matches in any single race
    $stmt = $db->prepare("
        SELECT p.race_id, COUNT(*) as exact_count
        FROM predictions p
        JOIN race_results rr ON p.race_id = rr.race_id AND p.driver_id = rr.driver_id AND p.predicted_position = rr.position
        WHERE p.user_id = ?
        GROUP BY p.race_id
        ORDER BY exact_count DESC LIMIT 1
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    return ($res && $res['exact_count'] >= $minMatches);
}

function checkAccuracy($userId, $minPct, $db) {
    // Get total predictions and total exact matches
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN p.predicted_position = rr.position THEN 1 ELSE 0 END) as exact
        FROM predictions p
        JOIN race_results rr ON p.race_id = rr.race_id AND p.driver_id = rr.driver_id
        WHERE p.user_id = ?
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    
    if (!$res || $res['total'] == 0) return false;
    
    $accuracy = ($res['exact'] / $res['total']) * 100;
    return $accuracy >= $minPct;
}

function checkSingleRaceScore($userId, $minScore, $doubleOnly, $db) {
    $sql = "SELECT MAX(total_points) as max_score FROM scores WHERE user_id = ?";
    if ($doubleOnly) {
        $sql = "
            SELECT MAX(s.total_points) as max_score 
            FROM scores s
            JOIN races r ON s.race_id = r.id
            WHERE s.user_id = ?
              AND (r.country IN ('China', 'UK', 'United Kingdom', 'Singapore') OR r.race_name LIKE '%British%')
        ";
    }
    
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc()['max_score'] >= $minScore;
}

function checkParticipationStreak($userId, $minStreak, $db) {
    // Get last N races
    $stmt = $db->prepare("SELECT id FROM races WHERE status = 'completed' ORDER BY race_date DESC LIMIT ?");
    $stmt->bind_param("i", $minStreak);
    $stmt->execute();
    $races = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    if (count($races) < $minStreak) return false;
    
    // Check if user participated in ALL of them
    $count = 0;
    foreach ($races as $race) {
        $stmt2 = $db->prepare("SELECT 1 FROM predictions WHERE user_id = ? AND race_id = ? LIMIT 1");
        $stmt2->bind_param("ii", $userId, $race['id']);
        $stmt2->execute();
        if ($stmt2->get_result()->num_rows > 0) $count++;
    }
    
    return $count >= $minStreak;
}

function checkDoublePointsScore($userId, $score, $db) {
    return checkSingleRaceScore($userId, $score, true, $db);
}

function checkConsecutiveHighScores($userId, $consecutive, $minScore, $db) {
    // Get user scores ordered by date
    $stmt = $db->prepare("
        SELECT s.total_points 
        FROM scores s
        JOIN races r ON s.race_id = r.id
        WHERE s.user_id = ?
        ORDER BY r.race_date ASC
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $scores = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    $currentStreak = 0;
    foreach ($scores as $s) {
        if ($s['total_points'] >= $minScore) {
            $currentStreak++;
            if ($currentStreak >= $consecutive) return true;
        } else {
            $currentStreak = 0;
        }
    }
    return false;
}

/**
 * Check if user correctly predicted Mercedes 1-2 finish
 */
function checkMercedes12($userId, $db) {
    // Check if user has any race where they predicted:
    // Position 1 = Mercedes driver AND Position 2 = Mercedes driver
    // AND both were correct
    $stmt = $db->prepare("
        SELECT COUNT(*) as count
        FROM predictions p1
        JOIN predictions p2 ON p1.user_id = p2.user_id AND p1.race_id = p2.race_id
        JOIN race_results rr1 ON p1.race_id = rr1.race_id AND p1.driver_id = rr1.driver_id
        JOIN race_results rr2 ON p2.race_id = rr2.race_id AND p2.driver_id = rr2.driver_id
        WHERE p1.user_id = ?
        AND p1.predicted_position = 1 AND p2.predicted_position = 2
        AND rr1.position = 1 AND rr2.position = 2
        AND rr1.constructor_name = 'Mercedes' AND rr2.constructor_name = 'Mercedes'
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc()['count'] > 0;
}

/**
 * Check if user won at least one race in each continent
 * Continents based on typical F1 calendar: Europe, Asia, Americas, Middle East, Oceania
 */
function checkWinAllContinents($userId, $db) {
    // Map countries to continents
    $continentMap = [
        'Europe' => ['UK', 'Italy', 'Spain', 'Monaco', 'Belgium', 'Netherlands', 'Austria', 'Hungary', 'France'],
        'Asia' => ['China', 'Japan', 'Singapore', 'Malaysia', 'Thailand'],
        'Americas' => ['USA', 'Mexico', 'Brazil', 'Canada', 'Argentina'],
        'Middle East' => ['Bahrain', 'Saudi Arabia', 'UAE', 'Qatar'],
        'Oceania' => ['Australia']
    ];
    
    $continentsWon = [];
    
    foreach ($continentMap as $continent => $countries) {
        $placeholders = implode(',', array_fill(0, count($countries), '?'));
        
        $sql = "
            SELECT COUNT(*) as count
            FROM scores s
            JOIN races r ON s.race_id = r.id
            WHERE s.user_id = ?
            AND r.country IN ($placeholders)
            AND s.total_points = (
                SELECT MAX(total_points) 
                FROM scores 
                WHERE race_id = s.race_id
            )
        ";
        
        $stmt = $db->prepare($sql);
        $params = array_merge([$userId], $countries);
        $types = str_repeat('s', count($countries)) . 'i';
        $types = 'i' . str_repeat('s', count($countries)); // userId first, then countries
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        if ($result['count'] > 0) {
            $continentsWon[] = $continent;
        }
    }
    
    // Need to win in all 5 continents
    return count($continentsWon) >= 5;
}

/**
 * Check if user participated in all races in the current season
 */
function checkAllRacesParticipation($userId, $db) {
    // Get total races
    $totalStmt = $db->query("SELECT COUNT(*) as total FROM races");
    $total = $totalStmt->fetch_assoc()['total'];
    
    if ($total == 0) return false;
    
    // Get user's participation count
    $stmt = $db->prepare("SELECT COUNT(DISTINCT race_id) as count FROM predictions WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $userCount = $stmt->get_result()->fetch_assoc()['count'];
    
    // User must have participated in ALL races
    return $userCount >= $total;
}
?>
