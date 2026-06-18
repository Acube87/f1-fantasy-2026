<?php
require_once __DIR__ . '/includes/maintenance-gate.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'includes/avatars.php';

$user = getCurrentUser();
if (!$user) {
    header('Location: index.php');
    exit;
}

$db = getDB();
$userId = $user['id'];

try {
    $checkCol = $db->query("SHOW COLUMNS FROM users LIKE 'avatar_style'");
    if ($checkCol->num_rows == 0) {
        $db->query("ALTER TABLE users ADD COLUMN avatar_style VARCHAR(100) DEFAULT 'avataaars' AFTER email");
    }
} catch (Exception $e) {}

$stats = getUserStats($userId);
$totalPoints = $stats['total_points'] ?? 0;
$racesParticipated = $stats['races_participated'] ?? 0;

if ($racesParticipated === 0) {
    $countStmt = $db->prepare("
        SELECT COUNT(DISTINCT race_id) as races 
        FROM predictions p
        JOIN races r ON p.race_id = r.id
        WHERE p.user_id = ? AND r.status = 'completed'
    ");
    $countStmt->bind_param("i", $userId);
    $countStmt->execute();
    $countResult = $countStmt->get_result()->fetch_assoc();
    $racesParticipated = (int)($countResult['races'] ?? 0);
}

$level = $racesParticipated;
$rank = $stats['rank'] ?? '-';
$rankSuffix = match($rank) {
    1 => 'st', 2 => 'nd', 3 => 'rd', default => 'th'
};
if (!is_numeric($rank)) $rankSuffix = '';

$accuracyStmt = $db->prepare("
    SELECT 
        COUNT(*) as total_predictions,
        SUM(CASE WHEN p.predicted_position = r.position THEN 1 ELSE 0 END) as exact_matches
    FROM predictions p
    LEFT JOIN race_results r ON p.race_id = r.race_id AND p.driver_id = r.driver_id
    WHERE p.user_id = ? AND r.position IS NOT NULL
");
$accuracyStmt->bind_param("i", $userId);
$accuracyStmt->execute();
$accuracyResult = $accuracyStmt->get_result()->fetch_assoc();
$totalPredictionsMade = $accuracyResult['total_predictions'] ?? 0;
$exactMatches = $accuracyResult['exact_matches'] ?? 0;
$accuracy = $totalPredictionsMade > 0 ? ($exactMatches / $totalPredictionsMade) * 100 : 0;

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
    $isNextDoublePoints = in_array($nextRace['country'], ['China', 'UK', 'Singapore']);
    
    if ($now < $deadline) {
        $predictionsOpen = true;
        $predictionStatus = 'OPEN';
        
        $interval = $now->diff($deadline);
        $totalDays = $interval->days;
        $hours = $interval->h;
        $minutes = $interval->i;
        
        if ($totalDays > 0) {
            $countdownText = $totalDays . 'd ' . $hours . 'h';
        } elseif ($hours > 0) {
            $countdownText = $hours . 'h ' . $minutes . 'm';
        } else {
            $countdownText = $minutes . 'm';
        }
        
        $maxDaysWindow = 7;
        $daysRemaining = $totalDays + ($hours / 24);
        $progressPercentage = min(100, max(0, (($maxDaysWindow - $daysRemaining) / $maxDaysWindow) * 100));
        $progressBarWidth = round($progressPercentage, 2);
    } else {
        $predictionStatus = 'CLOSED';
        $countdownText = 'Locked';
        $progressBarWidth = 0;
    }
}

$leaderboard = getLeaderboard(5);

$recentResults = [];
$stmt = $db->prepare("
    SELECT r.id as race_id, r.race_name, r.country, s.total_points, r.race_date 
    FROM scores s 
    JOIN races r ON s.race_id = r.id 
    WHERE s.user_id = ? 
    ORDER BY r.race_date DESC 
    LIMIT 3
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$recentResults = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$upcomingRaces = [];
$stmt = $db->prepare("
    SELECT id, race_name, country, circuit_name, race_date 
    FROM races 
    WHERE status = 'upcoming' AND race_date >= CURDATE() 
    ORDER BY race_date ASC 
    LIMIT 5
");
$stmt->execute();
$racesData = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$now = new DateTime('now', new DateTimeZone('UTC'));
foreach ($racesData as $race) {
    if ($nextRace && $race['id'] == $nextRace['id']) {
        $race['unlocked'] = true;
    } else {
        $raceDate = new DateTime($race['race_date'], new DateTimeZone('UTC'));
        $unlockDate = clone $raceDate;
        $unlockDate->modify('-7 days')->setTime(0, 0, 0);
        $race['unlocked'] = $now >= $unlockDate;
    }
    $upcomingRaces[] = $race;
}

// Pass points to nav
$_SESSION['nav_points'] = $totalPoints;

// Get user's latest predictions for "inventory"
$userPicks = [];
if ($nextRace) {
    $pickStmt = $db->prepare("
        SELECT p.driver_id, p.driver_name, p.predicted_position, d.team
        FROM predictions p
        LEFT JOIN drivers d ON p.driver_id = d.id
        WHERE p.user_id = ? AND p.race_id = ?
        ORDER BY p.predicted_position ASC
        LIMIT 5
    ");
    $pickStmt->bind_param("ii", $userId, $nextRace['id']);
    $pickStmt->execute();
    $userPicks = $pickStmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Total players count
$playersCount = 0;
$pc = $db->query("SELECT COUNT(*) as c FROM users");
if ($pc) $playersCount = (int)$pc->fetch_assoc()['c'];

// Completed races count
$completedRaces = 0;
$cr = $db->query("SELECT COUNT(*) as c FROM races WHERE status = 'completed'");
if ($cr) $completedRaces = (int)$cr->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — Paddock Picks</title>
    <link rel="stylesheet" href="css/gaming-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<?php require_once __DIR__ . '/includes/nav.php'; ?>

<div class="app-layout">

    <!-- ─── LEFT SIDEBAR ─── -->
    <div class="sidebar-scroll">

        <!-- Countdown Graph Card -->
        <div class="card graph-card">
            <div class="graph-card-bg"></div>
            <div class="card-body">
                <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:8px;">
                    <div>
                        <div class="graph-value" id="multiplier-value"><?php echo $predictionsOpen ? '2.54x' : '0.00x'; ?></div>
                        <div class="graph-value-label"><?php echo $predictionsOpen ? 'Current multiplier' : 'Predictions closed'; ?></div>
                    </div>
                    <?php if ($nextRace): ?>
                    <span class="badge <?php echo $predictionsOpen ? 'badge-green' : 'badge-gray'; ?>"><?php echo $predictionStatus; ?></span>
                    <?php endif; ?>
                </div>
                <div class="graph-canvas">
                    <svg class="graph-svg" viewBox="0 0 200 80" preserveAspectRatio="none">
                        <defs>
                            <linearGradient id="curve-grad" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stop-color="rgba(124,58,237,0.3)"/>
                                <stop offset="100%" stop-color="rgba(124,58,237,0)"/>
                            </linearGradient>
                        </defs>
                        <path d="M0,70 Q30,65 60,55 Q90,40 120,30 Q150,18 180,8 L200,4" 
                              stroke="var(--accent-purple)" stroke-width="2" fill="none" stroke-linecap="round"/>
                        <path d="M0,70 Q30,65 60,55 Q90,40 120,30 Q150,18 180,8 L200,4 L200,80 L0,80 Z" 
                              fill="url(#curve-grad)"/>
                    </svg>
                </div>
                <div class="graph-labels">
                    <span class="graph-label">1.00x</span>
                    <span class="graph-label">2.00x</span>
                    <span class="graph-label">3.00x</span>
                    <span class="graph-label">4.00x</span>
                </div>
            </div>
        </div>

        <!-- Recent Results Pills -->
        <div class="card">
            <div class="card-header">
                <span class="card-header-title">Recent Results</span>
                <span class="section-action">View all <i class="fas fa-chevron-right" style="font-size:9px;"></i></span>
            </div>
            <div class="card-body">
                <?php if (empty($recentResults)): ?>
                    <div style="text-align:center;padding:12px 0;color:var(--text-muted);font-size:12px;">No results yet</div>
                <?php else: ?>
                <div class="pill-row">
                    <?php foreach ($recentResults as $res): ?>
                    <a href="race-results.php?race_id=<?php echo $res['race_id']; ?>" style="text-decoration:none;">
                        <span class="pill pill-<?php echo $res['total_points'] > 15 ? 'purple' : 'orange'; ?>">
                            <?php echo htmlspecialchars($res['country']); ?> +<?php echo $res['total_points']; ?>
                        </span>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Last Win -->
        <div class="card">
            <div class="card-body">
                <div class="lastwin-card">
                    <div class="lastwin-avatar">
                        <img src="<?php echo getAvatarUrl($user['avatar_style'] ?? 'avataaars', $user['username']); ?>" alt="">
                    </div>
                    <div class="lastwin-info">
                        <div class="lastwin-label">Last Race</div>
                        <div class="lastwin-name"><?php echo !empty($recentResults) ? htmlspecialchars($recentResults[0]['country']) : '—'; ?></div>
                        <div class="lastwin-item"><?php echo !empty($recentResults) ? '+' . $recentResults[0]['total_points'] . ' pts' : 'No races yet'; ?></div>
                    </div>
                    <div class="lastwin-right">
                        <?php if ($nextRace): ?>
                        <div class="lastwin-timer"><?php echo $countdownText; ?></div>
                        <div class="lastwin-players">👥 <?php echo $playersCount; ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Driver Inventory -->
        <div class="card" style="flex:1;">
            <div class="card-header">
                <span class="card-header-title">Your Picks</span>
                <span class="section-action"><?php echo count($userPicks); ?> drivers</span>
            </div>
            <div class="card-body">
                <?php if (empty($userPicks)): ?>
                    <div style="text-align:center;padding:16px 0;color:var(--text-muted);font-size:12px;">
                        No picks yet for next race
                    </div>
                    <?php if ($nextRace): ?>
                    <a href="predict.php?race_id=<?php echo $nextRace['id']; ?>" class="btn btn-primary btn-sm" style="width:100%;">Make Predictions</a>
                    <?php endif; ?>
                <?php else: ?>
                <div style="display:flex;flex-direction:column;gap:6px;">
                    <?php foreach ($userPicks as $pick): ?>
                    <div class="item-card">
                        <div class="item-card-image" style="font-size:18px;">
                            <i class="fas fa-helmet-safety"></i>
                        </div>
                        <div class="item-card-name"><?php echo htmlspecialchars($pick['driver_name']); ?></div>
                        <div class="item-card-meta">
                            <span class="item-card-float">P<?php echo $pick['predicted_position']; ?></span>
                            <span class="item-card-rarity item-card-rarity-purple"></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Action Buttons -->
        <div style="display:flex;gap:8px;padding:4px 0;">
            <button class="btn btn-icon btn-blue" style="width:40px;height:40px;"><i class="fas fa-comment-dots"></i></button>
            <button class="btn btn-icon btn-primary" style="width:40px;height:40px;"><i class="fas fa-thumbs-up"></i></button>
        </div>

    </div>

    <!-- ─── CENTER CONTENT ─── -->
    <div style="display:flex;flex-direction:column;gap:12px;">

        <!-- Hero Race Display -->
        <div class="hero-display">
            <div class="hero-bg" style="background-image:url('<?php echo getRaceHeroImage($nextRace['country'] ?? ''); ?>');"></div>
            <div class="hero-overlay"></div>
            <div class="hero-content">
                <?php if ($nextRace): ?>
                <div class="hero-badge">
                    <i class="fas fa-flag-checkered"></i> ROUND <?php echo $nextRace['race_number']; ?>
                </div>
                <div class="hero-country">
                    <?php echo htmlspecialchars($nextRace['country']); ?>
                </div>
                <div class="hero-circuit">
                    <i class="fas fa-map-marker-alt" style="color:var(--accent-purple-light);font-size:12px;"></i>
                    <?php echo htmlspecialchars($nextRace['circuit_name']); ?>
                </div>
                <div class="hero-countdown-ring">
                    <svg viewBox="0 0 80 80">
                        <circle class="bg" cx="40" cy="40" r="34"/>
                        <circle class="progress" id="countdown-ring" cx="40" cy="40" r="34"
                                stroke-dasharray="213.6" stroke-dashoffset="<?php echo (1 - $progressBarWidth/100) * 213.6; ?>"/>
                    </svg>
                    <div class="hero-countdown-text" id="cd-center"><?php echo $countdownText; ?></div>
                </div>
                <?php if ($predictionsOpen): ?>
                <div class="hero-price">
                    <span class="hero-price-old"><?php echo $racesParticipated; ?> races</span>
                    <span class="hero-price-arrow"><i class="fas fa-arrow-right"></i></span>
                    <span class="hero-price-new">#<?php echo $rank; ?> rank</span>
                </div>
                <a href="predict.php?race_id=<?php echo $nextRace['id']; ?>" class="hero-receive">
                    <i class="fas fa-pencil-alt"></i> Make Prediction
                </a>
                <?php else: ?>
                <div class="hero-price">
                    <span class="hero-price-old">Predictions</span>
                    <span class="hero-price-arrow"><i class="fas fa-lock"></i></span>
                    <span class="hero-price-new" style="color:var(--accent-red);">Locked</span>
                </div>
                <?php endif; ?>
                <?php else: ?>
                <div style="padding:40px 0;color:var(--text-muted);">Season completed</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Stats Row (4 mini cards) -->
        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px;">
            <div class="card" style="padding:14px;text-align:center;">
                <div style="color:var(--accent-purple-light);font-size:20px;font-weight:800;">#<?php echo $rank; ?></div>
                <div style="font-size:10px;color:var(--text-muted);text-transform:uppercase;font-weight:600;">Rank</div>
            </div>
            <div class="card" style="padding:14px;text-align:center;">
                <div style="color:var(--accent-green);font-size:20px;font-weight:800;"><?php echo number_format($totalPoints); ?></div>
                <div style="font-size:10px;color:var(--text-muted);text-transform:uppercase;font-weight:600;">Points</div>
            </div>
            <div class="card" style="padding:14px;text-align:center;">
                <div style="color:var(--accent-blue-light);font-size:20px;font-weight:800;"><?php echo $racesParticipated; ?></div>
                <div style="font-size:10px;color:var(--text-muted);text-transform:uppercase;font-weight:600;">Races</div>
            </div>
            <div class="card" style="padding:14px;text-align:center;">
                <div style="color:var(--accent-orange);font-size:20px;font-weight:800;"><?php echo number_format($accuracy, 1); ?>%</div>
                <div style="font-size:10px;color:var(--text-muted);text-transform:uppercase;font-weight:600;">Accuracy</div>
            </div>
        </div>

        <!-- Promo Cards -->
        <div class="promo-row">
            <div class="promo-card promo-card-purple">
                <div class="promo-icon-3d"><i class="fas fa-star"></i></div>
                <div class="promo-card-text">Stay ahead of the grid. Make your picks before the deadline!</div>
                <div class="promo-card-arrow"><i class="fas fa-arrow-right"></i></div>
            </div>
            <div class="promo-card promo-card-blue">
                <div class="promo-icon-3d"><i class="fas fa-trophy"></i></div>
                <div class="promo-card-text">Double points in China, UK & Singapore. Plan your strategy!</div>
                <div class="promo-card-arrow"><i class="fas fa-arrow-right"></i></div>
            </div>
            <div class="promo-card promo-card-orange">
                <div class="promo-icon-3d"><i class="fas fa-bolt"></i></div>
                <div class="promo-card-text">Check the leaderboard and see how you stack up against rivals.</div>
                <div class="promo-card-arrow"><i class="fas fa-arrow-right"></i></div>
            </div>
        </div>

        <!-- Statistic Feed / Upcoming Races -->
        <div class="card">
            <div class="card-header">
                <span class="card-header-title"><i class="fas fa-calendar-alt"></i> Upcoming Races</span>
                <span class="section-action"><?php echo count($upcomingRaces); ?> events</span>
            </div>
            <div class="card-body" style="padding:8px 16px;">
                <?php if (empty($upcomingRaces)): ?>
                <div style="text-align:center;padding:16px;color:var(--text-muted);font-size:13px;">No upcoming races</div>
                <?php else: ?>
                <?php foreach ($upcomingRaces as $uRace): 
                    $rDeadline = getPredictionDeadline($uRace['race_date']);
                    $rNow = new DateTime('now', new DateTimeZone('UTC'));
                    $rIsOpen = $rNow < $rDeadline;
                    $flag = getRaceFlag($uRace['country']);
                ?>
                <a href="<?php echo $uRace['unlocked'] ? 'predict.php?race_id=' . $uRace['id'] : '#'; ?>" 
                   style="display:flex;align-items:center;gap:12px;padding:10px 0;text-decoration:none;border-bottom:1px solid var(--border-subtle);"
                   <?php if (!$uRace['unlocked']): ?> onclick="return false;"<?php endif; ?>>
                    <div style="width:32px;height:32px;border-radius:8px;background:var(--bg-surface);display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0;">
                        <?php echo $flag; ?>
                    </div>
                    <div style="flex:1;">
                        <div style="font-size:13px;font-weight:600;color:var(--text-primary);"><?php echo htmlspecialchars($uRace['country']); ?> GP</div>
                        <div style="font-size:11px;color:var(--text-muted);"><?php echo date('M d, Y', strtotime($uRace['race_date'])); ?></div>
                    </div>
                    <div style="text-align:right;">
                        <?php if ($uRace['unlocked'] && $rIsOpen): ?>
                            <span class="badge badge-green">Open</span>
                        <?php elseif ($uRace['unlocked'] && !$rIsOpen): ?>
                            <span class="badge badge-red">Closed</span>
                        <?php else: ?>
                            <span class="badge badge-gray">Locked</span>
                        <?php endif; ?>
                    </div>
                </a>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Scores Feed -->
        <div class="card">
            <div class="card-header">
                <span class="card-header-title"><i class="fas fa-chart-line"></i> Recent Scores</span>
                <span class="section-action"><?php echo $totalPredictionsMade; ?> predictions</span>
            </div>
            <div class="card-body" style="padding:4px 16px;">
                <?php if (empty($recentResults)): ?>
                <div style="text-align:center;padding:20px;color:var(--text-muted);font-size:13px;">
                    No race history yet. Start predicting!
                </div>
                <?php else: ?>
                <?php foreach ($recentResults as $res): ?>
                <div class="stat-row">
                    <div class="stat-avatar" style="background:var(--bg-elevated);display:flex;align-items:center;justify-content:center;color:var(--accent-purple-light);">
                        <i class="fas fa-flag" style="font-size:14px;"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-name"><?php echo htmlspecialchars($res['country']); ?> GP</div>
                        <div class="stat-meta">
                            <span><?php echo date('M d', strtotime($res['race_date'])); ?></span>
                            <span class="stat-icon-badge stat-icon-badge-green">+<?php echo $res['total_points']; ?> pts</span>
                        </div>
                    </div>
                    <div style="font-weight:700;font-size:16px;color:var(--accent-green);">+<?php echo $res['total_points']; ?></div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <!-- ─── RIGHT SIDEBAR ─── -->
    <div class="sidebar-scroll">

        <!-- Online Counter -->
        <div class="online-bar">
            <i class="fas fa-volume-mute" style="color:var(--text-muted);font-size:14px;"></i>
            <span class="online-count"><?php echo $playersCount; ?></span>
            <span style="color:var(--text-muted);font-size:12px;">online</span>
            <span style="margin-left:auto;font-size:12px;color:var(--text-muted);">👥 <?php echo $playersCount; ?></span>
        </div>

        <!-- Leaderboard Mini -->
        <div class="card">
            <div class="card-header">
                <span class="card-header-title"><i class="fas fa-trophy" style="color:var(--accent-orange);"></i> Top Strategists</span>
                <span class="section-action">Global</span>
            </div>
            <div class="card-body" style="padding:4px 12px;">
                <?php foreach ($leaderboard as $idx => $player):
                    $isMe = ($player['username'] === $user['username']);
                ?>
                <div class="chat-msg" style="padding:8px 0;<?php echo $isMe ? 'background:rgba(124,58,237,0.04);margin:0 -12px;padding:8px 12px;border-radius:6px;' : ''; ?>">
                    <div class="chat-avatar" style="background:var(--bg-elevated);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:<?php echo $idx === 0 ? 'var(--accent-orange)' : ($idx === 1 ? 'var(--text-secondary)' : ($idx === 2 ? '#cd7f32' : 'var(--text-muted)')); ?>">
                        <?php echo $idx + 1; ?>
                    </div>
                    <div class="chat-bubble">
                        <div class="chat-header">
                            <span class="chat-username"><?php echo htmlspecialchars($player['username']); ?></span>
                            <span class="chat-level">[<?php echo $player['races_participated'] ?? 0; ?>]</span>
                            <?php if ($isMe): ?><span class="badge badge-purple" style="font-size:9px;padding:1px 6px;">You</span><?php endif; ?>
                            <span class="chat-time"><?php echo $player['total_points']; ?> pts</span>
                        </div>
                        <div class="chat-text">Level <?php echo $player['races_participated'] ?? 0; ?> strategist</div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div style="padding:8px 16px;border-top:1px solid var(--border-subtle);">
                <a href="leaderboard.php" class="btn btn-outline btn-sm" style="width:100%;">View Full Standings</a>
            </div>
        </div>

        <!-- Activity Feed -->
        <div class="card" style="flex:1;">
            <div class="card-header">
                <span class="card-header-title"><i class="fas fa-comment-dots"></i> Activity</span>
                <span class="section-action">Live</span>
            </div>
            <div class="card-body" style="padding:4px 12px;">
                <div class="chat-msg">
                    <div class="chat-avatar" style="display:flex;align-items:center;justify-content:center;font-size:14px;color:var(--accent-green);background:rgba(34,197,94,0.1);">
                        <i class="fas fa-robot"></i>
                    </div>
                    <div class="chat-bubble">
                        <div class="chat-header">
                            <span class="chat-username" style="color:var(--accent-green);">System</span>
                            <span class="chat-time">now</span>
                        </div>
                        <div class="chat-text chat-text-system">Season 2026 is live — <?php echo $completedRaces; ?> races completed</div>
                    </div>
                </div>
                <div class="chat-msg">
                    <div class="chat-avatar" style="display:flex;align-items:center;justify-content:center;font-size:14px;color:var(--accent-purple-light);background:rgba(124,58,237,0.1);">
                        <i class="fas fa-trophy"></i>
                    </div>
                    <div class="chat-bubble">
                        <div class="chat-header">
                            <span class="chat-username">Leaderboard</span>
                            <span class="chat-time">1h ago</span>
                        </div>
                        <div class="chat-text"><?php echo $playersCount; ?> players competing for the top spot</div>
                    </div>
                </div>
                <?php if ($nextRace): ?>
                <div class="chat-msg">
                    <div class="chat-avatar" style="display:flex;align-items:center;justify-content:center;font-size:14px;color:var(--accent-blue-light);background:rgba(79,124,255,0.1);">
                        <i class="fas fa-flag-checkered"></i>
                    </div>
                    <div class="chat-bubble">
                        <div class="chat-header">
                            <span class="chat-username">Next Race</span>
                            <span class="chat-time"><?php echo $countdownText; ?></span>
                        </div>
                        <div class="chat-text"><?php echo htmlspecialchars($nextRace['country']); ?> GP — <?php echo htmlspecialchars($nextRace['circuit_name']); ?></div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <!-- Chat Input -->
            <div style="padding:10px 16px;">
                <div class="chat-input-row" style="border-top:none;margin-top:0;padding-top:0;">
                    <input type="text" class="chat-input" placeholder="Write a message..." readonly>
                    <button class="chat-send"><i class="fas fa-paper-plane"></i></button>
                </div>
            </div>
        </div>

    </div>

</div>

<script>
// Live countdown
(function() {
    var deadline = <?php echo $deadline ? $deadline->getTimestamp() * 1000 : 0; ?>;
    var ring = document.getElementById('countdown-ring');
    var cd = document.getElementById('cd-center');
    var maxWindow = 7 * 24 * 60 * 60 * 1000;
    var circumference = 213.6;
    
    if (!deadline) return;
    
    function tick() {
        var now = Date.now();
        var left = deadline - now;
        if (left <= 0) {
            if (ring) ring.style.strokeDashoffset = '0';
            if (cd) cd.textContent = 'Locked';
            return;
        }
        var d = Math.floor(left / 86400000);
        var h = Math.floor((left % 86400000) / 3600000);
        var m = Math.floor((left % 3600000) / 60000);
        if (cd) cd.textContent = d > 0 ? d+'d '+h+'h' : h > 0 ? h+'h '+m+'m' : m > 0 ? m+'m' : '';
        var p = Math.min(Math.max(((maxWindow - left) / maxWindow), 0), 1);
        if (ring) ring.style.strokeDashoffset = (circumference * (1 - p)).toFixed(1);
    }
    tick();
    setInterval(tick, 1000);
})();
</script>

<script src="app.js"></script>
</body>
</html>
