<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../includes/maintenance-gate.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/avatars.php';
require_once __DIR__ . '/../includes/csrf.php';
if (file_exists(__DIR__ . '/../includes/achievements.php')) {
    require_once __DIR__ . '/../includes/achievements.php';
}

$user = getCurrentUser();
$type = $_GET['type'] ?? 'dashboard';
$db = getDB();

function getUserData($user) {
    if (!$user) return null;
    return [
        'id' => $user['id'],
        'username' => $user['username'],
        'email' => $user['email'],
        'full_name' => $user['full_name'] ?? '',
        'avatar_style' => $user['avatar_style'] ?? 'avataaars',
        'is_admin' => !empty($user['is_admin']),
    ];
}

switch ($type) {

    case 'dashboard':
        if (!$user) {
            echo json_encode(['error' => 'not_authenticated']);
            exit;
        }
        // Capture any PHP warnings/errors
        set_error_handler(function($severity, $message, $file, $line) {
            throw new ErrorException($message, 0, $severity, $file, $line);
        });
        try {
        $userId = $user['id'];
        $stats = getUserStats($userId);
        
        // Next race
        $nextRace = getNextRace();
        $deadline = null;
        $predictionsOpen = false;
        $predictionStatus = 'CLOSED';
        $countdownText = '';
        $progressBarWidth = 100;
        $isNextDoublePoints = false;
        
        if ($nextRace) {
            $deadline = getPredictionDeadline($nextRace['race_date']);
            $now = new DateTime('now', new DateTimeZone('UTC'));
            $isNextDoublePoints = isDoublePointsRace($nextRace);
            
            if ($now->getTimestamp() < $deadline->getTimestamp()) {
                $predictionsOpen = true;
                $interval = $now->diff($deadline);
                $totalDays = $interval->days;
                $hours = $interval->h;
                $minutes = $interval->i;
                $countdownText = $totalDays > 0 ? "{$totalDays}d {$hours}h" : ($hours > 0 ? "{$hours}h {$minutes}m" : "{$minutes}m");
                $maxDaysWindow = 7;
                $daysRemaining = $totalDays + ($hours / 24);
                $progressBarWidth = round(min(100, max(0, (($maxDaysWindow - $daysRemaining) / $maxDaysWindow) * 100)), 2);
            } else {
                $countdownText = 'Locked';
                $progressBarWidth = 100;
            }
        }
        
        // Accuracy
        $accuracyStmt = $db->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN p.predicted_position = r.position THEN 1 ELSE 0 END) as exact FROM predictions p LEFT JOIN race_results r ON p.race_id = r.race_id AND p.driver_id = r.driver_id WHERE p.user_id = ? AND r.position IS NOT NULL");
        $accuracyStmt->bind_param("i", $userId);
        $accuracyStmt->execute();
        $acc = $accuracyStmt->get_result()->fetch_assoc();
        $accuracy = ($acc['total'] ?? 0) > 0 ? round((($acc['exact'] ?? 0) / $acc['total']) * 100, 1) : 0;
        
        // Total races in season
        $totalRaces = 0;
        $trQ = $db->query("SELECT COUNT(*) as c FROM races");
        if ($trQ) $totalRaces = (int)$trQ->fetch_assoc()['c'];

        // Recent results (user's own scores)
        $stmt = $db->prepare("SELECT r.id as race_id, r.race_name, r.country, r.race_number, s.total_points, r.race_date FROM scores s JOIN races r ON s.race_id = r.id WHERE s.user_id = ? ORDER BY r.race_date DESC LIMIT 3");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $recentResults = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Race podiums (top 3 users per completed race)
        $racePodiums = [];
        $compRaces = $db->query("SELECT id, race_name, country, race_date FROM races WHERE status = 'completed' ORDER BY race_date DESC");
        if ($compRaces) {
            while ($race = $compRaces->fetch_assoc()) {
                $pS = $db->prepare("SELECT u.id, u.username, u.avatar_style, s.total_points FROM scores s JOIN users u ON s.user_id = u.id WHERE s.race_id = ? ORDER BY s.total_points DESC LIMIT 3");
                $rid = (int)$race['id'];
                $pS->bind_param("i", $rid);
                $pS->execute();
                $podium = $pS->get_result()->fetch_all(MYSQLI_ASSOC);
                if (count($podium) > 0) {
                    $race['flag'] = getRaceFlag($race['country']);
                    $race['podium'] = $podium;
                    $racePodiums[] = $race;
                }
            }
        }
        
        // Upcoming races
        $upcomingRaces = [];
        $stmt = $db->prepare("SELECT id, race_name, country, circuit_name, race_date FROM races WHERE status = 'upcoming' AND race_date >= CURDATE() ORDER BY race_date ASC LIMIT 5");
        $stmt->execute();
        $racesData = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $now = new DateTime('now', new DateTimeZone('UTC'));
        foreach ($racesData as $race) {
            if ($nextRace && $race['id'] == $nextRace['id']) {
                $race['unlocked'] = true;
            } else {
                $raceDate = new DateTime($race['race_date'], new DateTimeZone('UTC'));
                $unlockDate = (clone $raceDate)->modify('-7 days')->setTime(0, 0, 0);
                $race['unlocked'] = $now >= $unlockDate;
            }
            $rDeadline = getPredictionDeadline($race['race_date']);
            $race['is_open'] = $now < $rDeadline;
            $race['flag'] = getRaceFlag($race['country']);
            $upcomingRaces[] = $race;
        }
        
        // Last completed race + user score
        $lastRaceInfo = null;
        $lrStmt = $db->query("SELECT id, race_name, country, circuit_name, race_date, race_number FROM races WHERE status = 'completed' ORDER BY race_date DESC LIMIT 1");
        if ($lrStmt && $lr = $lrStmt->fetch_assoc()) {
            $lastRaceInfo = $lr;
            $lastRaceInfo['flag'] = getRaceFlag($lr['country']);
            $lastRaceInfo['hero'] = getRaceHeroImage($lr['country']);
            $myScoreStmt = $db->prepare("SELECT total_points, driver_points, top3_bonus, constructor_points FROM scores WHERE user_id = ? AND race_id = ?");
            $myScoreStmt->bind_param("ii", $userId, $lr['id']);
            $myScoreStmt->execute();
            $lastRaceInfo['myScore'] = $myScoreStmt->get_result()->fetch_assoc();
        }
        
        // Most picked race winner (P1) for last completed race
        $mostPickedWinner = null;
        if ($lastRaceInfo) {
            $mpStmt = $db->prepare("SELECT p.driver_name, COUNT(*) as cnt FROM predictions p WHERE p.race_id = ? AND p.predicted_position = 1 GROUP BY p.driver_id, p.driver_name ORDER BY cnt DESC LIMIT 1");
            $rid = (int)$lastRaceInfo['id'];
            $mpStmt->bind_param("i", $rid);
            $mpStmt->execute();
            $mpRes = $mpStmt->get_result()->fetch_assoc();
            if ($mpRes) {
                $tpStmt = $db->prepare("SELECT COUNT(DISTINCT user_id) as total FROM predictions WHERE race_id = ?");
                $tpStmt->bind_param("i", $rid);
                $tpStmt->execute();
                $totalPred = (int)$tpStmt->get_result()->fetch_assoc()['total'];
                $mostPickedWinner = ['driver_name' => $mpRes['driver_name'], 'count' => (int)$mpRes['cnt'], 'total' => $totalPred];
            }
        }

        // Most picked race winner (P1) for next race
        $mostPickedNextWinner = null;
        if ($nextRace) {
            $mnStmt = $db->prepare("SELECT p.driver_name, COUNT(*) as cnt FROM predictions p WHERE p.race_id = ? AND p.predicted_position = 1 GROUP BY p.driver_id, p.driver_name ORDER BY cnt DESC LIMIT 1");
            $nrid = (int)$nextRace['id'];
            $mnStmt->bind_param("i", $nrid);
            $mnStmt->execute();
            $mnRes = $mnStmt->get_result()->fetch_assoc();
            if ($mnRes) {
                $tnStmt = $db->prepare("SELECT COUNT(DISTINCT user_id) as total FROM predictions WHERE race_id = ?");
                $tnStmt->bind_param("i", $nrid);
                $tnStmt->execute();
                $totalNPred = (int)$tnStmt->get_result()->fetch_assoc()['total'];
                $mostPickedNextWinner = ['driver_name' => $mnRes['driver_name'], 'count' => (int)$mnRes['cnt'], 'total' => $totalNPred];
            }
        }

        // Leaderboard full
        $leaderboard = getLeaderboard(200);
        
        // User picks for next race
        $userPicks = [];
        if ($nextRace) {
            $pStmt = $db->prepare("SELECT p.driver_id, p.driver_name, p.predicted_position, d.team FROM predictions p LEFT JOIN drivers d ON p.driver_id = d.id WHERE p.user_id = ? AND p.race_id = ? ORDER BY p.predicted_position ASC LIMIT 5");
            $pStmt->bind_param("ii", $userId, $nextRace['id']);
            $pStmt->execute();
            $userPicks = $pStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }
        
        // Players count
        $pc = $db->query("SELECT COUNT(*) as c FROM users");
        $playersCount = $pc ? (int)$pc->fetch_assoc()['c'] : 0;
        
        // User achievements
        $userAchievements = [];
        if (function_exists('getUserAchievements')) {
            try {
                $userAchievements = getUserAchievements($userId, $db);
            } catch (Exception $e) {}
        }
        
        echo json_encode([
            'auth' => getUserData($user),
            'stats' => $stats,
            'accuracy' => $accuracy,
            'nextRace' => $nextRace ? [
                'id' => $nextRace['id'],
                'race_name' => $nextRace['race_name'],
                'country' => $nextRace['country'],
                'circuit_name' => $nextRace['circuit_name'],
                'circuit_svg' => getCircuitSvg($nextRace['circuit_name'], $nextRace['country']),
                'race_date' => $nextRace['race_date'],
                'race_number' => $nextRace['race_number'],
                'flag' => getRaceFlag($nextRace['country']),
                'hero' => getRaceHeroImage($nextRace['country']),
            ] : null,
            'predictionStatus' => $predictionStatus,
            'predictionsOpen' => $predictionsOpen,
            'countdownText' => $countdownText,
            'progressBarWidth' => $progressBarWidth,
            'isDoublePoints' => $isNextDoublePoints,
            'deadline' => $deadline ? $deadline->getTimestamp() * 1000 : 0,
            'lastRace' => $lastRaceInfo,
            'recentResults' => $recentResults,
            'racePodiums' => $racePodiums,
            'upcomingRaces' => $upcomingRaces,
            'leaderboard' => $leaderboard,
            'userPicks' => $userPicks,
            'playersCount' => $playersCount,
            'totalPredictions' => (int)($acc['total'] ?? 0),
            'totalRaces' => $totalRaces,
            'mostPickedWinner' => $mostPickedWinner,
            'mostPickedNextWinner' => $mostPickedNextWinner,
            'userAchievements' => $userAchievements,
        ]);
        } catch (Throwable $e) {
            $err = ['error' => 'dashboard_error', 'message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()];
            error_log('DASHBOARD ERROR: ' . json_encode($err));
            echo json_encode($err);
        }
        break;

    case 'leaderboard':
        $all = getLeaderboard(100);
        echo json_encode(['auth' => getUserData($user), 'leaderboard' => $all]);
        break;

    case 'news':
        if (!$user) {
            echo json_encode(['error' => 'not_authenticated']);
            exit;
        }
        $posts = [];
        $stmt = $db->prepare("
            SELECT p.id, p.race_id, p.title, p.content, p.created_at, r.race_name, r.country, u.username, p.is_manual,
                   (SELECT COUNT(*) FROM post_likes WHERE post_id = p.id) as like_count,
                   (SELECT COUNT(*) FROM post_comments WHERE post_id = p.id) as comment_count,
                   (SELECT COUNT(*) FROM post_likes WHERE post_id = p.id AND user_id = ?) as user_liked
            FROM posts p
            LEFT JOIN races r ON p.race_id = r.id
            LEFT JOIN users u ON p.author_id = u.id
            ORDER BY p.created_at DESC
        ");
        $stmt->bind_param("i", $user['id']);
        $stmt->execute();
        $posts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        echo json_encode([
            'auth' => getUserData($user),
            'posts' => $posts,
        ]);
        break;

    case 'admin':
        if (!$user || empty($user['is_admin'])) {
            echo json_encode(['error' => 'not_authorized']);
            exit;
        }
        $races = $db->query("SELECT id, race_name, country, race_date, status FROM races ORDER BY race_date ASC")->fetch_all(MYSQLI_ASSOC);
        $drivers = $db->query("SELECT id, driver_name, team FROM drivers ORDER BY team, driver_name")->fetch_all(MYSQLI_ASSOC);
        echo json_encode([
            'auth' => getUserData($user),
            'races' => $races,
            'drivers' => $drivers,
        ]);
        break;

    case 'user':
        echo json_encode(['auth' => getUserData($user), 'csrf_token' => getCSRFToken()]);
        break;

    case 'predict':
        if (!$user) {
            echo json_encode(['error' => 'not_authenticated']);
            exit;
        }
        set_error_handler(function($severity, $message, $file, $line) {
            throw new ErrorException($message, 0, $severity, $file, $line);
        });
        try {
        $userId = $user['id'];
        $raceId = isset($_GET['race_id']) ? (int)$_GET['race_id'] : null;

        if (!$raceId) {
            $nextRace = getNextRace();
            if ($nextRace) {
                $raceId = $nextRace['id'];
            } else {
                echo json_encode(['error' => 'no_race_found']);
                exit;
            }
        }

        // Get race info
        $stmt = $db->prepare("SELECT * FROM races WHERE id = ?");
        $stmt->bind_param("i", $raceId);
        $stmt->execute();
        $race = $stmt->get_result()->fetch_assoc();

        if (!$race) {
            echo json_encode(['error' => 'race_not_found']);
            exit;
        }

        // Calculate deadline
        $deadline = getPredictionDeadline($race['race_date']);
        $now = new DateTime('now', new DateTimeZone('UTC'));
        $predictionsOpen = $now->getTimestamp() < $deadline->getTimestamp();

        // Countdown text and progress bar
        $countdownText = '';
        $progressBarWidth = 100;
        if ($predictionsOpen) {
            $interval = $now->diff($deadline);
            $totalDays = $interval->days;
            $hours = $interval->h;
            $minutes = $interval->i;
            $countdownText = $totalDays > 0 ? $totalDays . ' day' . ($totalDays > 1 ? 's' : '') . ' left' : ($hours > 0 ? $hours . ' hour' . ($hours > 1 ? 's' : '') . ' left' : $minutes . ' min left');
            $maxDaysBeforeDeadline = 7;
            $daysRemaining = $totalDays + ($hours / 24);
            $progressPercentage = max(0, min(100, (($maxDaysBeforeDeadline - $daysRemaining) / $maxDaysBeforeDeadline) * 100));
            $progressBarWidth = round($progressPercentage, 2);
        } else {
            $countdownText = 'Closed';
            $progressBarWidth = 100;
        }

        // Get all drivers
        $stmt = $db->prepare("SELECT id, driver_name, team FROM drivers ORDER BY team, driver_name");
        $stmt->execute();
        $drivers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Get existing predictions for this race
        $predictions = [];
        $stmt = $db->prepare("SELECT driver_id, predicted_position FROM predictions WHERE race_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $raceId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $predictions[$row['driver_id']] = (int)$row['predicted_position'];
        }
        $hasPrediction = !empty($predictions);

        // Load previous race's predictions
        $prevPredictions = [];
        $prevRaceStmt = $db->prepare("SELECT id FROM races WHERE race_date < (SELECT race_date FROM races WHERE id = ?) ORDER BY race_date DESC LIMIT 1");
        $prevRaceStmt->bind_param("i", $raceId);
        $prevRaceStmt->execute();
        $prevRace = $prevRaceStmt->get_result()->fetch_assoc();
        if ($prevRace) {
            $prevPredStmt = $db->prepare("SELECT driver_id, predicted_position FROM predictions WHERE race_id = ? AND user_id = ? ORDER BY predicted_position ASC");
            $prevPredStmt->bind_param("ii", $prevRace['id'], $userId);
            $prevPredStmt->execute();
            $prevRes = $prevPredStmt->get_result();
            while ($row = $prevRes->fetch_assoc()) {
                $prevPredictions[$row['driver_id']] = (int)$row['predicted_position'];
            }
        }

        // Get constructors
        $constructors = [];
        $stmt = $db->prepare("SELECT id, name FROM constructors ORDER BY name");
        $stmt->execute();
        $constructors = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Team abbreviations and colors for constructors
        $teamColors = [
            'Ferrari' => ['color' => '#DC0000', 'badge' => 'badge-ferrari'],
            'Mercedes' => ['color' => '#00D2BE', 'badge' => 'badge-mercedes'],
            'Red Bull' => ['color' => '#3671C6', 'badge' => 'badge-red-bull'],
            'McLaren' => ['color' => '#FF8000', 'badge' => 'badge-mclaren'],
            'Aston Martin' => ['color' => '#00665F', 'badge' => 'badge-aston-martin'],
            'Alpine' => ['color' => '#0090FF', 'badge' => 'badge-alpine'],
            'Williams' => ['color' => '#005AFF', 'badge' => 'badge-williams'],
            'Haas' => ['color' => '#B6BABD', 'badge' => 'badge-haas'],
            'RB' => ['color' => '#6692FF', 'badge' => 'badge-rb'],
            'Racing Bulls' => ['color' => '#6692FF', 'badge' => 'badge-racing-bulls'],
            'Sauber' => ['color' => '#00E701', 'badge' => 'badge-sauber'],
            'Kick Sauber' => ['color' => '#00E701', 'badge' => 'badge-kick-sauber'],
            'Audi' => ['color' => '#FF1721', 'badge' => 'badge-audi'],
            'Cadillac' => ['color' => '#C41E3A', 'badge' => 'badge-cadillac'],
        ];

        $constructorsWithColors = [];
        foreach ($constructors as $c) {
            $name = $c['name'];
            $constructorsWithColors[] = [
                'id' => $c['id'],
                'name' => $name,
                'color' => $teamColors[$name]['color'] ?? '#64748b',
                'badge' => $teamColors[$name]['badge'] ?? 'badge-default',
            ];
        }

        echo json_encode([
            'auth' => getUserData($user),
            'nextRace' => [
                'id' => $race['id'],
                'race_name' => $race['race_name'],
                'country' => $race['country'],
                'circuit_name' => $race['circuit_name'],
                'race_date' => $race['race_date'],
                'flag' => getRaceFlag($race['country']),
                'hero' => getRaceHeroImage($race['country']),
            ],
            'drivers' => $drivers,
            'existingPredictions' => $predictions,
            'predictionsOpen' => $predictionsOpen,
            'countdownText' => $countdownText,
            'progressBarWidth' => $progressBarWidth,
            'deadline' => $deadline->getTimestamp(),
            'hasPrediction' => $hasPrediction,
            'prevPredictions' => $prevPredictions,
            'constructors' => $constructorsWithColors,
        ]);
        } catch (Throwable $e) {
            $err = ['error' => 'predict_error', 'message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()];
            error_log('PREDICT ERROR: ' . json_encode($err));
            echo json_encode($err);
        }
        break;

    case 'results':
        if (!$user) {
            echo json_encode(['error' => 'not_authenticated']);
            exit;
        }
        $userId = $user['id'];
        $raceId = isset($_GET['race_id']) ? (int)$_GET['race_id'] : 0;

        if (!$raceId) {
            // Return list of all races so user can pick one
            $allRaces = $db->query("SELECT id, race_name, country, circuit_name, race_date, status FROM races ORDER BY status ASC, race_date DESC");
            $raceList = [];
            if ($allRaces) {
                while ($r = $allRaces->fetch_assoc()) {
                    $raceList[] = [
                        'id' => $r['id'],
                        'race_name' => $r['race_name'],
                        'country' => $r['country'],
                        'circuit_name' => $r['circuit_name'],
                        'race_date' => $r['race_date'],
                        'status' => $r['status'],
                        'flag' => getRaceFlag($r['country']),
                        'hero' => getRaceHeroImage($r['country']),
                    ];
                }
            }
            echo json_encode([
                'auth' => getUserData($user),
                'race' => null,
                'raceList' => $raceList,
                'hasResults' => false,
                'predictions' => [],
                'scoreRecord' => null,
                'exactMatches' => 0,
                'raceLeaderboard' => [],
                'actualResults' => [],
            ]);
            exit;
        }

        // Get race details
        $stmt = $db->prepare("SELECT * FROM races WHERE id = ?");
        $stmt->bind_param("i", $raceId);
        $stmt->execute();
        $race = $stmt->get_result()->fetch_assoc();

        if (!$race) {
            echo json_encode(['error' => 'race_not_found']);
            exit;
        }

        $hasResults = $race['status'] === 'completed';

        // Get user's predictions for this race
        $stmt = $db->prepare("
            SELECT p.*, d.driver_name, d.team
            FROM predictions p
            LEFT JOIN drivers d ON p.driver_id = d.id
            WHERE p.user_id = ? AND p.race_id = ?
            ORDER BY p.predicted_position ASC
        ");
        $stmt->bind_param("ii", $userId, $raceId);
        $stmt->execute();
        $predictions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Get actual race results
        $stmt = $db->prepare("
            SELECT * FROM race_results 
            WHERE race_id = ? 
            ORDER BY position ASC
        ");
        $stmt->bind_param("i", $raceId);
        $stmt->execute();
        $actualResults = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Build actual position lookup
        $actualPositions = [];
        foreach ($actualResults as $result) {
            $actualPositions[$result['driver_id']] = $result['position'];
        }

        $f1Points = [1 => 25, 2 => 18, 3 => 15, 4 => 12, 5 => 10, 6 => 8, 7 => 6, 8 => 4, 9 => 2, 10 => 1];

        $totalPoints = 0;
        $exactMatches = 0;

        foreach ($predictions as &$pred) {
            $pred['actual_position'] = $actualPositions[$pred['driver_id']] ?? null;
            $pred['points_earned'] = 0;
            $pred['is_exact'] = false;

            if ($pred['actual_position'] !== null && (int)$pred['predicted_position'] === (int)$pred['actual_position']) {
                $pred['is_exact'] = true;
                $exactMatches++;
                $basePts = $f1Points[$pred['actual_position']] ?? 0;
                $pred['points_earned'] = $basePts + 3;
                $totalPoints += $pred['points_earned'];
            }
        }
        unset($pred);

        // Get score record
        $stmt = $db->prepare("SELECT * FROM scores WHERE user_id = ? AND race_id = ?");
        $stmt->bind_param("ii", $userId, $raceId);
        $stmt->execute();
        $scoreRecord = $stmt->get_result()->fetch_assoc();

        // Get race leaderboard with achievements
        $raceLeaderboard = [];
        $lbStmt = $db->prepare("
            SELECT s.user_id, s.total_points, s.driver_points, s.top3_bonus, s.constructor_points,
                   u.username, u.full_name, u.avatar_style
            FROM scores s
            JOIN users u ON s.user_id = u.id
            WHERE s.race_id = ?
            ORDER BY s.total_points DESC
        ");
        $lbStmt->bind_param("i", $raceId);
        $lbStmt->execute();
        $lbRaw = $lbStmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Attach achievements for each player
        foreach ($lbRaw as &$entry) {
            $entry['achievements'] = [];
            if (function_exists('getUserAchievements')) {
                try {
                    $ach = getUserAchievements($entry['user_id'], $db);
                    if (!empty($ach)) {
                        $entry['achievements'] = array_slice($ach, 0, 3);
                    }
                } catch (Exception $e) {}
            }
        }
        unset($entry);
        $raceLeaderboard = $lbRaw;

        echo json_encode([
            'auth' => getUserData($user),
            'race' => [
                'id' => $race['id'],
                'race_name' => $race['race_name'],
                'country' => $race['country'],
                'circuit_name' => $race['circuit_name'],
                'race_date' => $race['race_date'],
                'flag' => getRaceFlag($race['country']),
                'hero' => getRaceHeroImage($race['country']),
            ],
            'hasResults' => $hasResults,
            'predictions' => $predictions,
            'scoreRecord' => $scoreRecord ? [
                'driver_points' => (int)($scoreRecord['driver_points'] ?? 0),
                'top3_bonus' => (int)($scoreRecord['top3_bonus'] ?? 0),
                'constructor_points' => (int)($scoreRecord['constructor_points'] ?? 0),
                'total_points' => (int)($scoreRecord['total_points'] ?? 0),
            ] : null,
            'exactMatches' => $exactMatches,
            'raceLeaderboard' => $raceLeaderboard,
            'actualResults' => $actualResults,
        ]);
        break;

    case 'profile':
        if (!$user) {
            echo json_encode(['error' => 'not_authenticated']);
            exit;
        }
        $userId = $user['id'];

        $stats = getUserStats($userId);

        // Accuracy stats
        $stmt = $db->prepare("
            SELECT 
                COUNT(DISTINCT p.race_id) as total_predictions,
                AVG(ABS(p.predicted_position - r.position)) as avg_position_error,
                SUM(CASE WHEN p.predicted_position = r.position THEN 1 ELSE 0 END) as exact_matches,
                MIN(ABS(p.predicted_position - r.position)) as best_prediction_error
            FROM predictions p
            LEFT JOIN race_results r ON p.race_id = r.race_id AND p.driver_id = r.driver_id
            WHERE p.user_id = ? AND r.position IS NOT NULL
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $accuracyStats = $stmt->get_result()->fetch_assoc();

        $avgError = round((float)($accuracyStats['avg_position_error'] ?? 0), 1);
        $exactMatches = (int)($accuracyStats['exact_matches'] ?? 0);

        // Total driver predictions count
        $countStmt = $db->prepare("
            SELECT COUNT(*) as total_driver_predictions
            FROM predictions p
            LEFT JOIN race_results r ON p.race_id = r.race_id AND p.driver_id = r.driver_id
            WHERE p.user_id = ? AND r.position IS NOT NULL
        ");
        $countStmt->bind_param("i", $userId);
        $countStmt->execute();
        $countResult = $countStmt->get_result()->fetch_assoc();
        $totalDriverPredictions = (int)($countResult['total_driver_predictions'] ?? 0);

        $accuracy = $totalDriverPredictions > 0 ? round(($exactMatches / $totalDriverPredictions) * 100, 1) : 0;

        // Best race
        $bestRace = null;
        $stmt = $db->prepare("
            SELECT r.country, r.race_name, s.total_points, r.race_date
            FROM scores s
            JOIN races r ON s.race_id = r.id
            WHERE s.user_id = ?
            ORDER BY s.total_points DESC
            LIMIT 1
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $bestRace = $stmt->get_result()->fetch_assoc();

        // Recent races (full history)
        $stmt = $db->prepare("
            SELECT r.country, r.race_date, s.total_points
            FROM scores s
            JOIN races r ON s.race_id = r.id
            WHERE s.user_id = ?
            ORDER BY r.race_date DESC
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $recentRaces = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Current avatar style
        $checkStmt = $db->prepare("SELECT avatar_style FROM users WHERE id = ?");
        $checkStmt->bind_param("i", $userId);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result()->fetch_assoc();
        $currentAvatarStyle = $checkResult['avatar_style'] ?? $user['avatar_style'] ?? 'avataaars';

        // All avatars
        $allAvatars = getAllAvatars();

        // User achievements
        $userAchievements = [];
        if (function_exists('getUserAchievements')) {
            try {
                $userAchievements = getUserAchievements($userId, $db);
            } catch (Exception $e) {
                $userAchievements = [];
            }
        }

        echo json_encode([
            'auth' => getUserData($user),
            'stats' => $stats,
            'accuracy' => $accuracy,
            'exactMatches' => $exactMatches,
            'avgPositionError' => $avgError,
            'totalDriverPredictions' => $totalDriverPredictions,
            'bestRace' => $bestRace ? [
                'country' => $bestRace['country'],
                'race_name' => $bestRace['race_name'],
                'total_points' => (int)$bestRace['total_points'],
                'race_date' => $bestRace['race_date'],
            ] : null,
            'recentRaces' => $recentRaces,
            'currentAvatarStyle' => $currentAvatarStyle,
            'allAvatars' => $allAvatars,
            'userAchievements' => $userAchievements,
        ]);
        break;

    case 'updates':
        if (!$user) {
            echo json_encode(['error' => 'not_authenticated']);
            exit;
        }

        // Last completed race
        $lastRaceStmt = $db->query("SELECT * FROM races WHERE status = 'completed' ORDER BY race_date DESC LIMIT 1");
        $lastRace = $lastRaceStmt ? $lastRaceStmt->fetch_assoc() : null;

        // Next race
        $nextRace = getNextRace();

        // Top scorers for last race
        $topScorers = [];
        if ($lastRace) {
            $topStmt = $db->prepare("
                SELECT u.username, u.avatar_style, s.total_points, s.driver_points, s.top3_bonus, s.constructor_points
                FROM scores s
                JOIN users u ON s.user_id = u.id
                WHERE s.race_id = ?
                ORDER BY s.total_points DESC
                LIMIT 3
            ");
            $topStmt->bind_param("i", $lastRace['id']);
            $topStmt->execute();
            $topScorers = $topStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }

        // Race winner
        $raceWinner = null;
        if ($lastRace) {
            $winStmt = $db->prepare("SELECT driver_name, constructor_name FROM race_results WHERE race_id = ? AND position = 1 LIMIT 1");
            $winStmt->bind_param("i", $lastRace['id']);
            $winStmt->execute();
            $raceWinner = $winStmt->get_result()->fetch_assoc();
        }

        // Top 5 results
        $top5Results = [];
        if ($lastRace) {
            $resStmt = $db->prepare("SELECT driver_name, constructor_name, position FROM race_results WHERE race_id = ? AND position <= 5 ORDER BY position ASC");
            $resStmt->bind_param("i", $lastRace['id']);
            $resStmt->execute();
            $top5Results = $resStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }

        // Submitted and missing for next race
        $submitted = [];
        $missing = [];
        $nextRaceId = null;
        if ($nextRace) {
            $nextRaceId = $nextRace['id'];
            $subStmt = $db->prepare("SELECT DISTINCT u.id, u.username, u.avatar_style FROM users u JOIN predictions p ON u.id = p.user_id WHERE p.race_id = ? ORDER BY u.username ASC");
            $subStmt->bind_param("i", $nextRaceId);
            $subStmt->execute();
            $submitted = $subStmt->get_result()->fetch_all(MYSQLI_ASSOC);

            $misStmt = $db->prepare("SELECT id, username, avatar_style FROM users WHERE id NOT IN (SELECT DISTINCT user_id FROM predictions WHERE race_id = ?) ORDER BY username ASC");
            $misStmt->bind_param("i", $nextRaceId);
            $misStmt->execute();
            $missing = $misStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }

        $totalUsers = count($submitted) + count($missing);
        $submissionPct = $totalUsers > 0 ? round((count($submitted) / $totalUsers) * 100) : 0;

        // Podium sweep users
        $podiumSweepUsers = [];
        if ($lastRace) {
            $psStmt = $db->prepare("SELECT u.username FROM scores s JOIN users u ON s.user_id = u.id WHERE s.race_id = ? AND s.top3_bonus > 0");
            $psStmt->bind_param("i", $lastRace['id']);
            $psStmt->execute();
            $result = $psStmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $podiumSweepUsers[] = $row['username'];
            }
        }

        // Constructor bonus users
        $constructorBonusUsers = [];
        if ($lastRace) {
            $cbStmt = $db->prepare("SELECT u.username FROM scores s JOIN users u ON s.user_id = u.id WHERE s.race_id = ? AND s.constructor_points > 0");
            $cbStmt->bind_param("i", $lastRace['id']);
            $cbStmt->execute();
            $result = $cbStmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $constructorBonusUsers[] = $row['username'];
            }
        }

        // Leaderboard top 5
        $leaderboard = getLeaderboard(5);

        // All constructors with colors
        $teamColors = [
            'Ferrari' => ['color' => '#DC0000', 'badge' => 'badge-ferrari'],
            'Mercedes' => ['color' => '#00D2BE', 'badge' => 'badge-mercedes'],
            'Red Bull' => ['color' => '#3671C6', 'badge' => 'badge-red-bull'],
            'McLaren' => ['color' => '#FF8000', 'badge' => 'badge-mclaren'],
            'Aston Martin' => ['color' => '#00665F', 'badge' => 'badge-aston-martin'],
            'Alpine' => ['color' => '#0090FF', 'badge' => 'badge-alpine'],
            'Williams' => ['color' => '#005AFF', 'badge' => 'badge-williams'],
            'Haas' => ['color' => '#B6BABD', 'badge' => 'badge-haas'],
            'RB' => ['color' => '#6692FF', 'badge' => 'badge-rb'],
            'Racing Bulls' => ['color' => '#6692FF', 'badge' => 'badge-racing-bulls'],
            'Sauber' => ['color' => '#00E701', 'badge' => 'badge-sauber'],
            'Kick Sauber' => ['color' => '#00E701', 'badge' => 'badge-kick-sauber'],
            'Audi' => ['color' => '#FF1721', 'badge' => 'badge-audi'],
            'Cadillac' => ['color' => '#C41E3A', 'badge' => 'badge-cadillac'],
        ];

        $constructors = [];
        $conStmt = $db->query("SELECT id, name FROM constructors ORDER BY name");
        if ($conStmt) {
            while ($row = $conStmt->fetch_assoc()) {
                $name = $row['name'];
                $constructors[] = [
                    'id' => $row['id'],
                    'name' => $name,
                    'color' => $teamColors[$name]['color'] ?? '#64748b',
                    'badge' => $teamColors[$name]['badge'] ?? 'badge-default',
                ];
            }
        }

        echo json_encode([
            'auth' => getUserData($user),
            'lastRace' => $lastRace ? [
                'id' => $lastRace['id'],
                'race_name' => $lastRace['race_name'],
                'country' => $lastRace['country'],
                'race_date' => $lastRace['race_date'],
                'flag' => getRaceFlag($lastRace['country']),
            ] : null,
            'nextRace' => $nextRace ? [
                'id' => $nextRace['id'],
                'race_name' => $nextRace['race_name'],
                'country' => $nextRace['country'],
                'circuit_name' => $nextRace['circuit_name'],
                'race_date' => $nextRace['race_date'],
                'flag' => getRaceFlag($nextRace['country']),
                'deadline' => getPredictionDeadline($nextRace['race_date'])->getTimestamp() * 1000,
            ] : null,
            'topScorers' => $topScorers,
            'raceWinner' => $raceWinner,
            'top5Results' => $top5Results,
            'submitted' => $submitted,
            'missing' => $missing,
            'submissionPct' => $submissionPct,
            'podiumSweepUsers' => $podiumSweepUsers,
            'constructorBonusUsers' => $constructorBonusUsers,
            'leaderboard' => $leaderboard,
            'constructors' => $constructors,
        ]);
        break;

    case 'save_predictions':
        if (!$user) {
            echo json_encode(['error' => 'not_authenticated']);
            exit;
        }
        $userId = $user['id'];

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'POST required']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
            exit;
        }

        $raceId = (int)($input['race_id'] ?? 0);
        $predictionsInput = $input['predictions'] ?? [];
        $constructorPredictions = $input['constructor_predictions'] ?? [];

        if (!$raceId) {
            echo json_encode(['success' => false, 'message' => 'race_id required']);
            exit;
        }

        // Check if race exists and predictions are still open
        $stmt = $db->prepare("SELECT * FROM races WHERE id = ?");
        $stmt->bind_param("i", $raceId);
        $stmt->execute();
        $race = $stmt->get_result()->fetch_assoc();

        if (!$race) {
            echo json_encode(['success' => false, 'message' => 'Race not found']);
            exit;
        }

        $deadline = getPredictionDeadline($race['race_date']);
        $now = new DateTime('now', new DateTimeZone('UTC'));
        if ($now->getTimestamp() >= $deadline->getTimestamp()) {
            echo json_encode(['success' => false, 'message' => 'Predictions are closed for this race']);
            exit;
        }

        // Validate: check for duplicate driver selections
        $driverIds = array_column($predictionsInput, 'driver_id');
        if (count($driverIds) !== count(array_unique($driverIds))) {
            echo json_encode(['success' => false, 'message' => 'Duplicate driver selections are not allowed.']);
            exit;
        }

        try {
            $db->begin_transaction();

            // Clear existing predictions
            $stmt = $db->prepare("DELETE FROM predictions WHERE race_id = ? AND user_id = ?");
            $stmt->bind_param("ii", $raceId, $userId);
            $stmt->execute();

            $stmt = $db->prepare("DELETE FROM constructor_predictions WHERE race_id = ? AND user_id = ?");
            $stmt->bind_param("ii", $raceId, $userId);
            $stmt->execute();

            // Insert driver predictions
            $stmt = $db->prepare("INSERT INTO predictions (race_id, user_id, driver_id, driver_name, predicted_position) VALUES (?, ?, ?, ?, ?)");
            foreach ($predictionsInput as $pred) {
                $stmt->bind_param("iissi", $raceId, $userId, $pred['driver_id'], $pred['driver_name'], $pred['predicted_position']);
                $stmt->execute();
            }

            // Insert constructor predictions
            if (!empty($constructorPredictions)) {
                $stmt = $db->prepare("INSERT INTO constructor_predictions (race_id, user_id, constructor_id, constructor_name, predicted_position) VALUES (?, ?, ?, ?, ?)");
                foreach ($constructorPredictions as $pred) {
                    $cid = (string)($pred['constructor_id'] ?? '');
                    $cname = (string)($pred['constructor_name'] ?? '');
                    $cpos = (int)($pred['predicted_position'] ?? 0);
                    $stmt->bind_param("isssi", $raceId, $userId, $cid, $cname, $cpos);
                    $stmt->execute();
                }
            }

            $db->commit();
            echo json_encode(['success' => true, 'message' => 'Predictions saved']);
        } catch (Exception $e) {
            $db->rollback();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'update_profile':
        if (!$user) {
            echo json_encode(['error' => 'not_authenticated']);
            exit;
        }
        $userId = $user['id'];

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'POST required']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || !isset($input['action'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid input or missing action']);
            exit;
        }

        $action = $input['action'];

        switch ($action) {
            case 'update_name':
                $fullName = trim($input['full_name'] ?? '');
                $stmt = $db->prepare("UPDATE users SET full_name = ? WHERE id = ?");
                $stmt->bind_param("si", $fullName, $userId);
                $stmt->execute();
                echo json_encode(['success' => true, 'message' => 'Name updated']);
                break;

            case 'update_username':
                $newUsername = trim($input['username'] ?? '');
                if (strlen($newUsername) < 3) {
                    echo json_encode(['success' => false, 'message' => 'Username must be at least 3 characters']);
                    break;
                }
                $stmt = $db->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
                $stmt->bind_param("si", $newUsername, $userId);
                $stmt->execute();
                if ($stmt->get_result()->fetch_assoc()) {
                    echo json_encode(['success' => false, 'message' => 'Username already taken']);
                    break;
                }
                $stmt = $db->prepare("UPDATE users SET username = ? WHERE id = ?");
                $stmt->bind_param("si", $newUsername, $userId);
                $stmt->execute();
                $_SESSION['user']['username'] = $newUsername;
                echo json_encode(['success' => true, 'message' => 'Username updated']);
                break;

            case 'update_password':
                $currentPassword = $input['current_password'] ?? '';
                $newPassword = $input['new_password'] ?? '';
                $confirmPassword = $input['confirm_password'] ?? '';

                $stmt = $db->prepare("SELECT password_hash FROM users WHERE id = ?");
                $stmt->bind_param("i", $userId);
                $stmt->execute();
                $result = $stmt->get_result()->fetch_assoc();

                if (!password_verify($currentPassword, $result['password_hash'])) {
                    echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
                    break;
                }
                if (strlen($newPassword) < 6) {
                    echo json_encode(['success' => false, 'message' => 'New password must be at least 6 characters']);
                    break;
                }
                if ($newPassword !== $confirmPassword) {
                    echo json_encode(['success' => false, 'message' => 'Passwords do not match']);
                    break;
                }
                $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                $stmt->bind_param("si", $newPasswordHash, $userId);
                $stmt->execute();
                echo json_encode(['success' => true, 'message' => 'Password updated']);
                break;

            case 'update_avatar':
                $avatarStyle = $input['avatar_style'] ?? '';
                if (!defined('AVATAR_STYLES')) {
                    require_once __DIR__ . '/../includes/avatars.php';
                }
                $validStyles = array_keys(AVATAR_STYLES);
                if (!in_array($avatarStyle, $validStyles)) {
                    echo json_encode(['success' => false, 'message' => 'Invalid avatar style']);
                    break;
                }
                $stmt = $db->prepare("UPDATE users SET avatar_style = ? WHERE id = ?");
                $stmt->bind_param("si", $avatarStyle, $userId);
                $stmt->execute();
                $_SESSION['user']['avatar_style'] = $avatarStyle;
                echo json_encode(['success' => true, 'message' => 'Avatar updated']);
                break;

            default:
                echo json_encode(['success' => false, 'message' => 'Unknown action']);
        }
        break;

    default:
        echo json_encode(['error' => 'unknown_type']);
}
