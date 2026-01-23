<?php
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();
$user = getCurrentUser();

$raceId = $_GET['race_id'] ?? null;
$db = getDB();

if (!$raceId) {
    header('Location: index.php');
    exit;
}

// Get race info
$stmt = $db->prepare("SELECT * FROM races WHERE id = ?");
$stmt->bind_param("i", $raceId);
$stmt->execute();
$race = $stmt->get_result()->fetch_assoc();

if (!$race) {
    header('Location: index.php');
    exit;
}

// Get race results
$resultsStmt = $db->prepare("SELECT * FROM race_results WHERE race_id = ? ORDER BY position");
$resultsStmt->bind_param("i", $raceId);
$resultsStmt->execute();
$results = $resultsStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get user's predictions
$userPredictions = getUserPredictions($user['id'], $raceId);
$predictionMap = [];
foreach ($userPredictions as $pred) {
    $predictionMap[$pred['driver_id']] = $pred['predicted_position'];
}

// Get user's score for this race
$scoreStmt = $db->prepare("SELECT * FROM scores WHERE user_id = ? AND race_id = ?");
$scoreStmt->bind_param("ii", $user['id'], $raceId);
$scoreStmt->execute();
$score = $scoreStmt->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Race Results - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header>
        <nav>
            <div class="container">
                <h1>🏎️ <?php echo SITE_NAME; ?></h1>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="leaderboard.php">Leaderboard</a></li>
                    <li><a href="predict.php">Make Predictions</a></li>
                    <li><a href="logout.php">Logout (<?php echo htmlspecialchars($user['username']); ?>)</a></li>
                </ul>
            </div>
        </nav>
    </header>

    <main class="container">
        <h2><?php echo htmlspecialchars($race['race_name']); ?> - Results</h2>
        <p class="race-info"><?php echo htmlspecialchars($race['circuit_name']); ?> - <?php echo date('F j, Y', strtotime($race['race_date'])); ?></p>
        
        <?php if ($score): ?>
            <div class="score-summary">
                <h3>Your Score for This Race</h3>
                <div class="stats-grid">
                    <div class="stat-card">
                        <h4>Driver Points</h4>
                        <p class="stat-value"><?php echo $score['driver_points']; ?></p>
                    </div>
                    <div class="stat-card">
                        <h4>Constructor Points</h4>
                        <p class="stat-value"><?php echo $score['constructor_points']; ?></p>
                    </div>
                    <div class="stat-card">
                        <h4>Top 3 Bonus</h4>
                        <p class="stat-value"><?php echo $score['top3_bonus'] + $score['constructor_top3_bonus']; ?></p>
                    </div>
                    <div class="stat-card">
                        <h4>Total Points</h4>
                        <p class="stat-value large"><?php echo $score['total_points']; ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if (empty($results)): ?>
            <div class="alert alert-warning">Results not available yet. Check back after the race!</div>
        <?php else: ?>
            <table class="scores-table">
                <thead>
                    <tr>
                        <th>Position</th>
                        <th>Driver</th>
                        <th>Constructor</th>
                        <th>Your Prediction</th>
                        <th>Points</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $result): 
                        $predictedPos = $predictionMap[$result['driver_id']] ?? null;
                        $pointsEarned = 0;
                        $matchClass = '';
                        
                        if ($predictedPos !== null) {
                            $diff = abs($predictedPos - $result['position']);
                            if ($diff == 0) {
                                $pointsEarned = POINTS_EXACT_POSITION;
                                $matchClass = 'correct';
                            } elseif ($diff == 1) {
                                $pointsEarned = POINTS_OFF_BY_ONE;
                                $matchClass = 'close';
                            }
                        }
                    ?>
                        <tr class="<?php echo $matchClass; ?>">
                            <td><strong><?php echo $result['position']; ?></strong></td>
                            <td><?php echo htmlspecialchars($result['driver_name']); ?></td>
                            <td><?php echo htmlspecialchars($result['constructor_name']); ?></td>
                            <td>
                                <?php if ($predictedPos !== null): ?>
                                    <?php echo $predictedPos; ?>
                                    <?php if ($matchClass === 'correct'): ?>
                                        <span class="badge">✓</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span style="color: #999;">No prediction</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $pointsEarned > 0 ? '+' . $pointsEarned : '-'; ?></td>
                            <td><?php echo htmlspecialchars($result['status']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </main>

    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>

