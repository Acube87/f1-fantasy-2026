<?php
require_once 'includes/auth.php';
require_once 'includes/maintenance-gate.php';
require_once 'includes/functions.php';
require_once 'includes/avatars.php';

// Must be logged in
$currentUser = getCurrentUser();
if (!$currentUser) {
    header('Location: login.php');
    exit;
}

$db = getDB();

// Validate inputs
$targetUserId = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$raceId       = isset($_GET['race_id'])  ? intval($_GET['race_id'])  : 0;

if (!$raceId) {
    header('Location: leaderboard.php');
    exit;
}

// Fetch race
$stmt = $db->prepare("SELECT * FROM races WHERE id = ?");
$stmt->bind_param("i", $raceId);
$stmt->execute();
$race = $stmt->get_result()->fetch_assoc();

if (!$race) {
    header('Location: leaderboard.php');
    exit;
}

// If no user_id specified, fall back to current user
if (!$targetUserId) {
    $targetUserId = $currentUser['id'];
}

// Fetch target user
$stmt = $db->prepare("SELECT id, username, avatar_style FROM users WHERE id = ?");
$stmt->bind_param("i", $targetUserId);
$stmt->execute();
$targetUser = $stmt->get_result()->fetch_assoc();

if (!$targetUser) {
    header('Location: leaderboard.php');
    exit;
}

// Enforce deadline gate
$deadline = getPredictionDeadline($race['race_date']);
$now = new DateTime('now', new DateTimeZone('UTC'));
$deadlinePassed = $now >= $deadline;

if (!$deadlinePassed) {
    $gatedPage = true;
    $deadlineFormatted = $deadline->format('D, d M Y \a\t H:i \U\T\C');
} else {
    $gatedPage = false;
    $deadlineFormatted = $deadline->format('D, d M Y \a\t H:i \U\T\C');
}

// Fetch ALL users who made predictions for this race (for race leaderboard + nav)
$allRaceUsers = [];
if (!$gatedPage) {
    $stmt = $db->query("
        SELECT DISTINCT p.user_id, u.username, u.avatar_style,
            COALESCE(s.total_points, 0) as total_points,
            COALESCE(s.driver_points, 0) as driver_points,
            COALESCE(s.constructor_points, 0) as constructor_points,
            COALESCE(s.top3_bonus, 0) as top3_bonus
        FROM predictions p
        JOIN users u ON p.user_id = u.id
        LEFT JOIN scores s ON s.user_id = p.user_id AND s.race_id = p.race_id
        WHERE p.race_id = $raceId
        ORDER BY total_points DESC, u.username ASC
    ");
    if ($stmt) {
        $allRaceUsers = $stmt->fetch_all(MYSQLI_ASSOC);
    }

    // Build user nav (prev/next)
    $userIds = array_column($allRaceUsers, 'user_id');
    $currentIdx = array_search($targetUserId, $userIds);
    $prevUserId = ($currentIdx !== false && $currentIdx > 0) ? $userIds[$currentIdx - 1] : null;
    $nextUserId = ($currentIdx !== false && $currentIdx < count($userIds) - 1) ? $userIds[$currentIdx + 1] : null;
}

// Fetch target user's driver predictions
$predictions = [];
$constructorPreds = [];
$actualResults = [];
$scoreRecord = null;

if (!$gatedPage) {
    $stmt = $db->prepare("
        SELECT p.predicted_position, p.driver_name, d.team, d.id as driver_id
        FROM predictions p
        LEFT JOIN drivers d ON p.driver_id = d.id
        WHERE p.user_id = ? AND p.race_id = ?
        ORDER BY p.predicted_position ASC
    ");
    $stmt->bind_param("ii", $targetUserId, $raceId);
    $stmt->execute();
    $predictions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $stmt = $db->prepare("
        SELECT constructor_name, predicted_position
        FROM constructor_predictions
        WHERE user_id = ? AND race_id = ?
        ORDER BY predicted_position ASC
        LIMIT 5
    ");
    $stmt->bind_param("ii", $targetUserId, $raceId);
    $stmt->execute();
    $constructorPreds = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $stmt = $db->prepare("
        SELECT driver_id, driver_name, position, constructor_name, points, fastest_lap
        FROM race_results
        WHERE race_id = ?
        ORDER BY position ASC
    ");
    $stmt->bind_param("i", $raceId);
    $stmt->execute();
    $actualRows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    foreach ($actualRows as $r) {
        $actualResults[$r['driver_id']] = $r;
        $actualResults[$r['driver_name']] = $r;
    }

    $stmt = $db->prepare("
        SELECT total_points, driver_points, constructor_points, top3_bonus, constructor_top3_bonus
        FROM scores
        WHERE user_id = ? AND race_id = ?
    ");
    $stmt->bind_param("ii", $targetUserId, $raceId);
    $stmt->execute();
    $scoreRecord = $stmt->get_result()->fetch_assoc();
}

$isMe = ($currentUser['id'] === $targetUserId);
$displayName = htmlspecialchars($targetUser['username']);
$raceFlag = getRaceFlag($race['country'] ?? '');
$raceHero = getRaceHeroImage($race['country'] ?? '');
$isDoublePoints = isDoublePointsRace($race);
$raceDate = new DateTime($race['race_date']);
$raceDateFormatted = $raceDate->format('j F Y');

$teamAbbr = [
    'Ferrari'       => 'FER',
    'Mercedes'      => 'MER',
    'Red Bull'      => 'RBR',
    'McLaren'       => 'MCL',
    'Aston Martin'  => 'AMR',
    'Alpine'        => 'ALP',
    'Williams'      => 'WIL',
    'Haas'          => 'HAA',
    'RB'            => 'RB',
    'Racing Bulls'  => 'RB',
    'Sauber'        => 'SAU',
    'Kick Sauber'   => 'SAU',
    'Audi'          => 'AUD',
    'Cadillac'      => 'CAD',
];

$F1_POINTS = [25,18,15,12,10,8,6,4,2,1];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Race Results – <?php echo htmlspecialchars($race['country']); ?> GP · <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="css/gaming-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --nfs-pink: #ff0077;
            --nfs-cyan: #00e5ff;
            --nfs-orange: #ff6a00;
            --nfs-green: #00ff88;
            --nfs-purple: #8b5cf6;
        }
        body {
            font-family: 'Inter', sans-serif;
        }
        .hero-card {
            position: relative;
            border-radius: 16px;
            overflow: hidden;
            min-height: 220px;
            display: flex;
            align-items: center;
        }
        .hero-card .hero-bg {
            position: absolute;
            inset: 0;
            background-size: cover;
            background-position: center center;
        }
        .hero-card .hero-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(10,10,10,0.92) 0%, rgba(10,10,10,0.6) 50%, rgba(10,10,10,0.3) 100%);
        }
        .hero-card .hero-carbon {
            position: absolute;
            inset: 0;
            background: repeating-linear-gradient(
                45deg,
                transparent,
                transparent 20px,
                rgba(255,255,255,0.02) 20px,
                rgba(255,255,255,0.02) 40px
            );
        }
        .hero-content {
            position: relative;
            z-index: 2;
            padding: 40px 32px;
        }

        .race-user-card {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 12px;
            transition: all 0.2s;
        }
        .race-user-card:hover {
            background: rgba(255,255,255,0.06);
        }
        .race-user-card.active {
            border-color: var(--nfs-pink);
            box-shadow: 0 0 20px rgba(255,0,119,0.15);
        }

        .pos-num {
            font-variant-numeric: tabular-nums;
            width: 28px;
            text-align: center;
            font-weight: 800;
            color: #64748b;
            font-size: 0.8rem;
        }
        .pos-medal-1 { color: #FBBF24; text-shadow: 0 0 8px rgba(251,191,36,0.5); }
        .pos-medal-2 { color: #CBD5E1; }
        .pos-medal-3 { color: #D97706; }

        .pred-row {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px 16px;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            background: rgba(255,255,255,0.02);
            transition: background 0.15s;
        }
        .pred-row:hover { background: rgba(255,255,255,0.05); }

        .double-points-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: linear-gradient(135deg, rgba(255,0,119,0.2), rgba(255,106,0,0.2));
            border: 1px solid rgba(255,0,119,0.4);
            border-radius: 20px;
            padding: 4px 14px;
            font-size: 0.7rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #ff0077;
            animation: pulse-glow 2s ease-in-out infinite;
        }
        @keyframes pulse-glow {
            0%, 100% { box-shadow: 0 0 8px rgba(255,0,119,0.2); }
            50% { box-shadow: 0 0 20px rgba(255,0,119,0.5); }
        }

        .rank-num {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 1.3rem;
            letter-spacing: 0.02em;
        }

        .user-nav-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border-radius: 8px;
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.08);
            color: #94a3b8;
            font-size: 0.85rem;
            font-weight: 600;
            transition: all 0.2s;
        }
        .user-nav-btn:hover {
            background: rgba(255,255,255,0.08);
            color: #fff;
        }
        .user-nav-btn:disabled {
            opacity: 0.3;
            cursor: not-allowed;
        }

        .section-header {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 1.4rem;
            letter-spacing: 0.04em;
        }

        /* ── Prediction Row Redesign ───────────────────────── */
        @keyframes slideInRow {
            from { opacity: 0; transform: translateX(-14px); }
            to   { opacity: 1; transform: translateX(0); }
        }
        @keyframes exactPulse {
            0%,100% { border-color: rgba(0,255,136,0.4); box-shadow: 0 0 0 0 rgba(0,255,136,0); }
            50%     { border-color: rgba(0,255,136,0.8); box-shadow: 0 0 18px rgba(0,255,136,0.18); }
        }
        @keyframes ptsPop {
            0%   { transform: scale(0.4); opacity: 0; }
            65%  { transform: scale(1.2); }
            100% { transform: scale(1);   opacity: 1; }
        }
        @keyframes shimmerBg {
            0%   { background-position: -200% center; }
            100% { background-position:  200% center; }
        }

        .drow {
            display: grid;
            grid-template-columns: 1fr 30px 90px 64px;
            align-items: center;
            border-bottom: 1px solid rgba(255,255,255,0.04);
            border-left: 3px solid transparent;
            animation: slideInRow 0.4s ease both;
            transition: background 0.15s;
        }
        .drow:last-child { border-bottom: none; }
        .drow:hover { background: rgba(255,255,255,0.025); }
        .drow.exact {
            background: rgba(0,255,136,0.05);
            border-left-color: #00ff88;
            animation: slideInRow 0.4s ease both, exactPulse 2.8s ease-in-out 0.5s infinite;
        }
        .drow.miss   { border-left-color: rgba(239,68,68,0.25); }
        .drow.pending{ opacity: 0.45; border-left-color: rgba(100,116,139,0.2); }

        .dcell-pred {
            display: flex; align-items: center; gap: 10px;
            padding: 11px 10px 11px 14px;
        }
        .dcell-match {
            display: flex; align-items: center; justify-content: center;
        }
        .dcell-actual {
            display: flex; align-items: center; gap: 8px;
            padding: 11px 6px;
        }
        .dcell-pts {
            padding: 11px 14px 11px 4px;
            text-align: right;
        }

        .ppip {
            min-width: 34px; height: 34px; border-radius: 7px; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center;
            font-family: 'Bebas Neue', sans-serif; font-size: 0.85rem; letter-spacing: 0.02em;
        }
        .ppip.rank1  { background: rgba(255,215,0,0.12); color: #ffd700; border: 1px solid rgba(255,215,0,0.3); }
        .ppip.rank2  { background: rgba(200,200,210,0.1); color: #c8c8d2; border: 1px solid rgba(200,200,210,0.25); }
        .ppip.rank3  { background: rgba(205,127,50,0.12); color: #e09050; border: 1px solid rgba(205,127,50,0.28); }
        .ppip.top10  { background: rgba(255,106,0,0.08); color: #ff8040; border: 1px solid rgba(255,106,0,0.18); }
        .ppip.other  { background: rgba(255,255,255,0.04); color: #64748b; border: 1px solid rgba(255,255,255,0.08); }
        .ppip.aexact { background: rgba(0,255,136,0.14); color: #00ff88; border: 1px solid rgba(0,255,136,0.35); }

        .dname { font-size: 0.8rem; font-weight: 700; color: #e2e8f0; line-height: 1.2; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .dteam { font-size: 0.58rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: #475569; margin-top: 2px; }

        .match-exact { font-size: 17px; color: #00ff88; filter: drop-shadow(0 0 5px rgba(0,255,136,0.7)); }
        .match-miss  { font-size: 13px; color: #3f4f60; }
        .match-na    { font-size: 13px; color: #2a3542; }

        .apos-badge {
            font-family: 'Bebas Neue', sans-serif; font-size: 0.78rem;
            color: #64748b; white-space: nowrap;
        }
        .apos-diff {
            font-size: 0.58rem; font-weight: 700; color: #475569;
            letter-spacing: 0.02em;
        }

        .pts-num {
            font-family: 'Bebas Neue', sans-serif; font-size: 1.55rem; letter-spacing: 0.02em;
            color: #00ff88; line-height: 1;
            animation: ptsPop 0.45s cubic-bezier(0.175,0.885,0.32,1.275) both;
        }
        .pts-tag { font-size: 0.44rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.1em; color: #00cc70; }
        .pts-zero { font-size: 0.9rem; font-weight: 700; color: #263040; }

        /* Score breakdown cards */
        .scard {
            border-radius: 12px; text-align: center; padding: 14px 8px;
            border: 1px solid rgba(255,255,255,0.06); position: relative; overflow: hidden;
            background: rgba(255,255,255,0.02);
        }
        .scard::after {
            content: ''; position: absolute; top: 0; left: 0; right: 0; height: 2px;
            border-radius: 99px;
        }
        .scard.stotal::after  { background: linear-gradient(90deg, #00ff88, #00e5ff); }
        .scard.sdriver::after { background: #3b82f6; }
        .scard.spodium::after { background: #a855f7; }
        .scard.scons::after   { background: #f97316; }
        .scard-num {
            font-family: 'Bebas Neue', sans-serif; font-size: 2rem; line-height: 1;
            animation: ptsPop 0.4s cubic-bezier(0.175,0.885,0.32,1.275) 0.1s both;
        }
        .scard-lbl {
            font-size: 0.52rem; font-weight: 700; text-transform: uppercase;
            letter-spacing: 0.08em; color: #475569; margin-top: 4px;
        }
    </style>
</head>
<body style="background:var(--f1-carbon);color:var(--f1-text);min-height:100vh;">

    <?php require_once __DIR__ . '/includes/nav.php'; ?>

    <main class="pt-20 pb-16 px-4 md:px-8 max-w-5xl mx-auto">

        <!-- Back link -->
        <a href="leaderboard.php" class="inline-flex items-center gap-2 text-gray-500 hover:text-white transition mb-5 text-sm">
            <i class="fas fa-arrow-left"></i> Back to Leaderboard
        </a>

        <!-- Hero Banner -->
        <div class="hero-card mb-6">
            <?php if ($raceHero): ?>
            <div class="hero-bg" style="background-image:url('<?php echo $raceHero; ?>')"></div>
            <?php endif; ?>
            <div class="hero-overlay"></div>
            <div class="hero-carbon"></div>
            <div class="hero-content w-full">
                <div class="flex items-start justify-between flex-wrap gap-4">
                    <div>
                        <div class="flex items-center gap-3 mb-2">
                            <span class="text-5xl"><?php echo $raceFlag; ?></span>
                            <div>
                                <div class="text-xs text-gray-500 uppercase tracking-widest font-bold"><?php echo htmlspecialchars($race['circuit_name'] ?? ''); ?></div>
                                <h1 class="text-3xl md:text-4xl font-black text-white uppercase tracking-wide" style="font-family:'Bebas Neue',sans-serif;letter-spacing:0.04em">
                                    <?php echo htmlspecialchars($race['country']); ?> GP
                                </h1>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 mt-1">
                            <span class="text-xs text-gray-400"><?php echo $raceDateFormatted; ?></span>
                            <?php if ($isDoublePoints): ?>
                            <span class="double-points-badge">
                                <i class="fas fa-bolt"></i> 2x Points
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if (!$gatedPage && count($allRaceUsers) > 1): ?>
                    <div class="flex items-center gap-2">
                        <?php if ($prevUserId): ?>
                        <a href="view-predictions.php?user_id=<?php echo $prevUserId; ?>&race_id=<?php echo $raceId; ?>" class="user-nav-btn">
                            <i class="fas fa-chevron-left"></i> Prev
                        </a>
                        <?php endif; ?>
                        <span class="text-xs text-gray-500 px-2">
                            <?php echo $currentIdx + 1; ?>/<?php echo count($allRaceUsers); ?>
                        </span>
                        <?php if ($nextUserId): ?>
                        <a href="view-predictions.php?user_id=<?php echo $nextUserId; ?>&race_id=<?php echo $raceId; ?>" class="user-nav-btn">
                            Next <i class="fas fa-chevron-right"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if ($gatedPage): ?>
        <!-- GATE -->
        <div class="g-card p-10 text-center">
            <div class="w-20 h-20 rounded-full bg-yellow-500/20 flex items-center justify-center text-4xl mx-auto mb-6">
                🔒
            </div>
            <h2 class="text-2xl font-black text-white mb-3">Predictions Hidden Until Deadline</h2>
            <p class="text-gray-400 text-sm max-w-md mx-auto mb-6">
                Predictions for this race will be visible <strong class="text-white">after the prediction deadline</strong>.<br><br>
                <span class="text-yellow-400 font-bold"><?php echo $deadlineFormatted; ?></span>
            </p>
            <a href="leaderboard.php" class="g-btn g-btn-blue px-6 py-2.5 inline-flex items-center gap-2 text-sm">
                <i class="fas fa-arrow-left"></i> Back to Leaderboard
            </a>
        </div>

        <?php elseif (!$gatedPage && count($allRaceUsers) === 0): ?>
        <!-- No predictions at all -->
        <div class="g-card p-10 text-center">
            <div class="w-20 h-20 rounded-full bg-gray-500/20 flex items-center justify-center text-4xl mx-auto mb-6">
                <i class="fas fa-ghost text-gray-500 text-3xl"></i>
            </div>
            <h2 class="text-2xl font-black text-white mb-3">No Predictions Yet</h2>
            <p class="text-gray-400 text-sm">No one has submitted predictions for this race.</p>
        </div>

        <?php else: ?>

        <!-- Race Leaderboard (All Users) -->
        <div class="g-card overflow-hidden mb-6">
            <div class="px-5 py-3 border-b border-white/10 bg-black/20 flex items-center gap-2">
                <i class="fas fa-trophy text-amber-400 text-sm"></i>
                <h2 class="section-header text-white">Race Leaderboard</h2>
                <?php if ($isDoublePoints): ?>
                <span class="ml-auto double-points-badge text-[0.6rem]">
                    <i class="fas fa-bolt"></i> 2x Points
                </span>
                <?php endif; ?>
            </div>
            <div>
                <?php foreach ($allRaceUsers as $idx => $ru):
                    $isActive = (int)$ru['user_id'] === $targetUserId;
                    $rank = $idx + 1;
                    $medal = $rank === 1 ? '🥇' : ($rank === 2 ? '🥈' : ($rank === 3 ? '🥉' : ''));
                ?>
                <a href="view-predictions.php?user_id=<?php echo $ru['user_id']; ?>&race_id=<?php echo $raceId; ?>"
                   class="race-user-card <?php echo $isActive ? 'active' : ''; ?> block px-5 py-3 <?php echo $idx > 0 ? 'mt-1' : ''; ?>">
                    <div class="flex items-center gap-4">
                        <div class="rank-num <?php echo $rank <= 3 ? '' : 'text-gray-600'; ?>" style="min-width:36px;text-align:center">
                            <?php echo $medal ?: '#' . $rank; ?>
                        </div>
                        <img src="<?php echo getAvatarUrl($ru['avatar_style'] ?? 'avataaars', $ru['username']); ?>"
                             alt="" class="w-8 h-8 rounded-full border <?php echo $isActive ? 'border-[#ff0077]' : 'border-white/10'; ?>">
                        <div class="flex-1">
                            <div class="font-semibold text-sm <?php echo $isActive ? 'text-white' : 'text-gray-300'; ?>">
                                <?php echo htmlspecialchars($ru['username']); ?>
                                <?php if ($isActive): ?><span class="text-xs text-[#ff0077] ml-1">(viewing)</span><?php endif; ?>
                            </div>
                            <div class="flex items-center gap-3 text-xs text-gray-500 mt-0.5">
                                <span>Driver: <strong class="text-blue-400"><?php echo $ru['driver_points']; ?></strong></span>
                                <span>Constructor: <strong class="text-orange-400"><?php echo $ru['constructor_points']; ?></strong></span>
                                <span>Podium: <strong class="text-purple-400"><?php echo $ru['top3_bonus']; ?></strong></span>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-xl font-black <?php echo $rank === 1 ? 'text-amber-400' : ($rank === 2 ? 'text-gray-300' : ($rank === 3 ? 'text-orange-400' : 'text-gray-400')); ?>">
                                <?php echo $ru['total_points']; ?>
                            </div>
                            <div class="text-[0.55rem] text-gray-600 uppercase tracking-wider font-semibold">Total</div>
                        </div>
                        <?php if (!$isActive): ?>
                        <i class="fas fa-chevron-right text-gray-600 text-sm"></i>
                        <?php endif; ?>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Current User Predictions vs Actual -->
        <?php if (empty($predictions)): ?>
        <div class="g-card p-8 text-center">
            <div class="w-16 h-16 rounded-full bg-gray-500/20 flex items-center justify-center text-3xl mx-auto mb-4">
                <i class="fas fa-ghost text-gray-500"></i>
            </div>
            <h2 class="text-xl font-black text-white mb-2">
                <?php echo $displayName; ?> didn't predict this race
            </h2>
            <p class="text-gray-500 text-sm">No predictions submitted.</p>
        </div>

        <?php else: ?>

        <!-- User header -->
        <div class="g-card px-5 py-4 mb-5">
            <div class="flex items-center justify-between flex-wrap gap-3">
                <div class="flex items-center gap-3">
                    <img src="<?php echo getAvatarUrl($targetUser['avatar_style'] ?? 'avataaars', $targetUser['username']); ?>"
                         alt="Avatar" class="w-10 h-10 rounded-full border-2 border-orange-500/50">
                    <div>
                        <div class="text-xs text-gray-500 uppercase tracking-wide">Predictions by</div>
                        <div class="text-lg font-black text-orange-400">
                            <?php echo $displayName; ?>
                            <?php if ($isMe): ?><span class="text-xs bg-orange-500/20 text-orange-400 px-2 rounded ml-1">YOU</span><?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php if ($scoreRecord): ?>
                <div class="flex items-center gap-4">
                    <div class="text-right">
                        <div class="text-2xl font-black text-emerald-400"><?php echo $scoreRecord['total_points']; ?></div>
                        <div class="text-[0.55rem] text-gray-500 uppercase tracking-widest font-semibold">Total</div>
                    </div>
                    <?php if ($isDoublePoints): ?>
                    <div class="double-points-badge">
                        <i class="fas fa-bolt"></i> ×2
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Score breakdown -->
        <?php if ($scoreRecord): ?>
        <div class="grid grid-cols-4 gap-3 mb-5">
            <div class="scard stotal">
                <div class="scard-num" style="color:#00ff88">
                    <?php if ($isDoublePoints): ?><span style="font-size:0.8rem;color:#ff0077;font-family:'Bebas Neue',sans-serif;">2×</span><?php endif; ?>
                    <?php echo $scoreRecord['total_points']; ?>
                </div>
                <div class="scard-lbl">Total Pts</div>
            </div>
            <div class="scard sdriver">
                <div class="scard-num" style="color:#3b82f6"><?php echo $scoreRecord['driver_points']; ?></div>
                <div class="scard-lbl">Driver</div>
            </div>
            <div class="scard spodium">
                <div class="scard-num" style="color:#a855f7"><?php echo $scoreRecord['top3_bonus']; ?></div>
                <div class="scard-lbl">Podium</div>
            </div>
            <div class="scard scons">
                <div class="scard-num" style="color:#f97316"><?php echo $scoreRecord['constructor_points']; ?></div>
                <div class="scard-lbl">Constructor</div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Predictions vs Actual Table -->
        <div class="g-card overflow-hidden mb-5">
            <div class="px-4 py-3 border-b border-white/10 bg-black/20 flex items-center gap-2">
                <i class="fas fa-list-ol text-orange-500 text-sm"></i>
                <h2 class="font-bold text-white text-sm uppercase tracking-wider">Driver Predictions</h2>
                <?php
                $exactCount = 0;
                foreach ($predictions as $p) {
                    $did = $p['driver_id'];
                    $act = $actualResults[$did] ?? $actualResults[$p['driver_name']] ?? null;
                    if ($act && (int)$act['position'] === (int)$p['predicted_position']) $exactCount++;
                }
                ?>
                <span class="ml-auto text-xs text-gray-500"><?php echo count($predictions); ?> drivers</span>
                <?php if ($exactCount > 0): ?>
                <span style="font-size:0.6rem;font-weight:800;background:rgba(0,255,136,0.12);color:#00ff88;border:1px solid rgba(0,255,136,0.25);padding:2px 8px;border-radius:999px;text-transform:uppercase;letter-spacing:0.06em;">
                    <?php echo $exactCount; ?> exact ✓
                </span>
                <?php endif; ?>
            </div>

            <!-- Column headers -->
            <div style="display:grid;grid-template-columns:1fr 30px 90px 64px;padding:5px 14px;border-bottom:1px solid rgba(255,255,255,0.05);background:rgba(0,0,0,0.25);">
                <div style="font-size:0.52rem;font-weight:800;text-transform:uppercase;letter-spacing:0.1em;color:#334155;">Your Pick</div>
                <div></div>
                <div style="font-size:0.52rem;font-weight:800;text-transform:uppercase;letter-spacing:0.1em;color:#334155;text-align:center;">Actual</div>
                <div style="font-size:0.52rem;font-weight:800;text-transform:uppercase;letter-spacing:0.1em;color:#334155;text-align:right;padding-right:14px;">Pts</div>
            </div>

            <div>
            <?php foreach ($predictions as $i => $pred):
                $predPos  = (int)$pred['predicted_position'];
                $team     = $pred['team'] ?? '';
                $abbr     = $teamAbbr[$team] ?? strtoupper(substr($team, 0, 3));
                $driverId = $pred['driver_id'];

                $actual    = $actualResults[$driverId] ?? $actualResults[$pred['driver_name']] ?? null;
                $actualPos = $actual ? (int)$actual['position'] : null;
                $isExact   = $actualPos !== null && $actualPos === $predPos;

                // Fixed: award +3 for ANY exact match; F1 base points only for top 10
                $ptsEarned = 0;
                if ($isExact) {
                    $ptsEarned = 3;
                    if ($predPos <= 10) {
                        $ptsEarned += $F1_POINTS[$predPos - 1];
                    }
                }

                $rowClass = $isExact ? 'exact' : ($actualPos !== null ? 'miss' : 'pending');
                $delay    = number_format($i * 0.045, 3);

                $predPos === 1  ? $pipClass = 'rank1'  :
                ($predPos === 2 ? $pipClass = 'rank2'  :
                ($predPos === 3 ? $pipClass = 'rank3'  :
                ($predPos <= 10 ? $pipClass = 'top10'  :
                                  $pipClass = 'other')));

                if ($isExact) {
                    $aPipClass = 'aexact';
                } elseif ($actualPos === 1)  { $aPipClass = 'rank1'; }
                elseif ($actualPos === 2)    { $aPipClass = 'rank2'; }
                elseif ($actualPos === 3)    { $aPipClass = 'rank3'; }
                elseif ($actualPos !== null && $actualPos <= 10) { $aPipClass = 'top10'; }
                else                         { $aPipClass = 'other'; }

                $diff = $actualPos !== null ? $actualPos - $predPos : null;
                if ($diff === 0)       $diffTxt = '';
                elseif ($diff !== null && $diff > 0)  $diffTxt = '▼' . abs($diff);
                elseif ($diff !== null)               $diffTxt = '▲' . abs($diff);
                else                   $diffTxt = '';
            ?>
            <div class="drow <?php echo $rowClass; ?>" style="animation-delay:<?php echo $delay; ?>s">

                <!-- Your pick -->
                <div class="dcell-pred">
                    <div class="ppip <?php echo $pipClass; ?>">P<?php echo $predPos; ?></div>
                    <div style="min-width:0">
                        <div class="dname"><?php echo htmlspecialchars($pred['driver_name']); ?></div>
                        <div class="dteam"><?php echo $abbr; ?></div>
                    </div>
                </div>

                <!-- Match indicator -->
                <div class="dcell-match">
                    <?php if ($actualPos === null): ?>
                        <span class="match-na"><i class="fas fa-minus"></i></span>
                    <?php elseif ($isExact): ?>
                        <span class="match-exact">✓</span>
                    <?php else: ?>
                        <span class="match-miss"><i class="fas fa-times"></i></span>
                    <?php endif; ?>
                </div>

                <!-- Actual result -->
                <div class="dcell-actual">
                    <?php if ($actualPos !== null): ?>
                        <div class="ppip <?php echo $aPipClass; ?>">P<?php echo $actualPos; ?></div>
                        <?php if ($diffTxt): ?>
                        <div class="apos-diff" style="color:<?php echo ($diff > 0 ? '#ef4444' : '#f97316'); ?>"><?php echo $diffTxt; ?></div>
                        <?php endif; ?>
                    <?php else: ?>
                        <span style="font-size:0.65rem;color:#334155;">TBC</span>
                    <?php endif; ?>
                </div>

                <!-- Points -->
                <div class="dcell-pts">
                    <?php if ($ptsEarned > 0): ?>
                        <div class="pts-num" style="animation-delay:<?php echo number_format($delay + 0.12, 3); ?>s">+<?php echo $ptsEarned; ?></div>
                        <?php if ($isExact): ?><div class="pts-tag">exact</div><?php endif; ?>
                    <?php elseif ($actualPos !== null): ?>
                        <div class="pts-zero">0</div>
                    <?php else: ?>
                        <div class="pts-zero">–</div>
                    <?php endif; ?>
                </div>

            </div>
            <?php endforeach; ?>
            </div>
        </div>

        <!-- Constructor Predictions -->
        <?php if (!empty($constructorPreds)): ?>
        <div class="g-card overflow-hidden mb-5">
            <div class="px-4 py-3 border-b border-white/10 bg-black/20 flex items-center gap-2">
                <i class="fas fa-wrench text-blue-400 text-sm"></i>
                <h2 class="font-bold text-white text-sm uppercase tracking-wider">Constructor Predictions</h2>
            </div>
            <div class="p-4 space-y-2">
                <?php
                $actualConstructors = [];
                $stmt = $db->prepare("
                    SELECT rr.constructor_name, SUM(rr.points) as total_pts
                    FROM race_results rr
                    WHERE rr.race_id = ?
                    GROUP BY rr.constructor_name
                    ORDER BY total_pts DESC
                ");
                $stmt->bind_param("i", $raceId);
                $stmt->execute();
                $actualConsRows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                foreach ($actualConsRows as $i => $cr) {
                    $actualConstructors[$cr['constructor_name']] = $i + 1;
                }
                ?>
                <?php foreach ($constructorPreds as $cp):
                    $conName = $cp['constructor_name'];
                    $conPredPos = (int)$cp['predicted_position'];
                    $conActualPos = $actualConstructors[$conName] ?? null;
                    $conCorrect = $conActualPos !== null && $conActualPos === $conPredPos;
                ?>
                <div class="flex items-center gap-3 py-2 px-3 bg-white/5 rounded-lg">
                    <span class="text-blue-400 font-black text-lg"><?php echo $conPredPos; ?></span>
                    <span class="text-sm font-semibold text-white flex-1"><?php echo htmlspecialchars($conName); ?></span>
                    <?php if ($conActualPos !== null): ?>
                        <span class="text-xs text-gray-500">Actual: #<?php echo $conActualPos; ?></span>
                        <?php if ($conCorrect): ?>
                            <span class="text-emerald-400 text-sm">✅</span>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php endif; // end predictions check ?>
        <?php endif; // end gated check ?>

    </main>

    <footer class="mt-12 border-t border-white/10 py-6 text-center">
        <p class="text-gray-500 text-sm mb-2">&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</p>
        <p class="text-gray-600 text-xs">
            Powered by <a href="https://www.scanerrific.com" target="_blank" class="text-orange-500 hover:text-orange-400 font-semibold transition">Scanerrific</a>
        </p>
    </footer>

<script src="app.js"></script>
</body>
</html>
