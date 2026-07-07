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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Playfair+Display:ital,wght@1,400;1,500&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --canvas:#F5F3EF;--bg:#F5F3EF;--bg2:#FAF9F7;--surface:#FFF;--card:#FFF;--card2:#FAF9F7;--surface-muted:#FAF9F7;
            --border:#E8E5E0;--border-light:#F0EDE8;
            --text:#1A1A1A;--text2:#6B6864;--text3:#A09C96;
            --accent:#C41E3A;--accent-soft:#F5E6E9;
            --live:#2D6A4F;--gold:#C9A96E;--silver:#A8A5A0;--bronze:#B08050;
        }
        body {
            font-family: 'Inter', sans-serif; background: var(--canvas); color: var(--text);
            min-height: 100vh;
        }
        .hero-bg {
            position: absolute; inset: 0;
            background-size: cover; background-position: center center;
        }
        .hero-overlay {
            position: absolute; inset: 0;
        }
        .race-user-card {
            background: var(--surface);
            border: 1px solid var(--border);
            transition: all 0.2s;
        }
        .race-user-card:hover {
            background: var(--surface-muted);
        }
        .race-user-card.active {
            border-color: var(--accent);
        }

        .pos-num {
            font-variant-numeric: tabular-nums;
            width: 28px; text-align: center;
            font-weight: 800; color: var(--text2); font-size: 0.8rem;
        }
        .pos-medal-1 { color: var(--gold); }
        .pos-medal-2 { color: var(--silver); }
        .pos-medal-3 { color: var(--bronze); }

        .pred-row {
            display: flex; align-items: center; gap: 12px;
            padding: 8px 16px;
            border-bottom: 1px solid var(--border);
            background: var(--surface);
            transition: background 0.15s;
        }
        .pred-row:hover { background: var(--surface-muted); }

        .double-points-badge {
            display: inline-flex; align-items: center; gap: 6px;
            background: var(--accent-soft);
            border: 1px solid var(--accent);
            padding: 4px 14px;
            font-size: 0.7rem; font-weight: 800;
            text-transform: uppercase; letter-spacing: 0.05em;
            color: var(--accent);
        }

        .rank-num {
            font-family: 'Playfair Display', serif; font-style: italic;
            font-size: 1.3rem; letter-spacing: 0.02em;
        }

        .user-nav-btn {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 8px 16px;
            background: var(--surface);
            border: 1px solid var(--border);
            color: var(--text2);
            font-size: 0.85rem; font-weight: 600;
            transition: all 0.2s;
        }
        .user-nav-btn:hover {
            background: var(--surface-muted);
            color: var(--text);
        }
        .user-nav-btn:disabled {
            opacity: 0.3; cursor: not-allowed;
        }

        .section-header {
            font-family: 'Playfair Display', serif; font-style: italic;
            font-size: 1.4rem; letter-spacing: 0.04em; color: var(--text);
        }

        /* ── Prediction Row Redesign ───────────────────────── */
        @keyframes slideInRow {
            from { opacity: 0; transform: translateX(-14px); }
            to   { opacity: 1; transform: translateX(0); }
        }
        @keyframes exactPulse {
            0%,100% { border-color: rgba(45,106,79,0.4); box-shadow: 0 0 0 0 rgba(45,106,79,0); }
            50%     { border-color: rgba(45,106,79,0.8); box-shadow: 0 0 18px rgba(45,106,79,0.18); }
        }
        @keyframes ptsPop {
            0%   { transform: scale(0.4); opacity: 0; }
            65%  { transform: scale(1.2); }
            100% { transform: scale(1);   opacity: 1; }
        }

        .drow {
            display: grid;
            grid-template-columns: 1fr 30px 90px 64px;
            align-items: center;
            border-bottom: 1px solid var(--border-light);
            border-left: 3px solid transparent;
            animation: slideInRow 0.4s ease both;
            transition: background 0.15s;
            background: var(--surface);
        }
        .drow:last-child { border-bottom: none; }
        .drow:hover { background: var(--surface-muted); }
        .drow.exact {
            background: rgba(45,106,79,0.04);
            border-left-color: var(--live);
            animation: slideInRow 0.4s ease both, exactPulse 2.8s ease-in-out 0.5s infinite;
        }
        .drow.miss   { border-left-color: rgba(196,30,58,0.25); }
        .drow.pending{ opacity: 0.45; border-left-color: var(--text3); }

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
            min-width: 34px; height: 34px; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center;
            font-family: 'JetBrains Mono', monospace; font-size: 0.85rem;
            font-variant-numeric: tabular-nums;
        }
        .ppip.rank1  { background: rgba(201,169,110,0.12); color: var(--gold); border: 1px solid rgba(201,169,110,0.3); }
        .ppip.rank2  { background: rgba(168,165,160,0.1); color: var(--silver); border: 1px solid rgba(168,165,160,0.25); }
        .ppip.rank3  { background: rgba(176,128,80,0.12); color: var(--bronze); border: 1px solid rgba(176,128,80,0.28); }
        .ppip.top10  { background: rgba(196,30,58,0.06); color: var(--accent); border: 1px solid rgba(196,30,58,0.15); }
        .ppip.other  { background: var(--surface-muted); color: var(--text2); border: 1px solid var(--border); }
        .ppip.aexact { background: rgba(45,106,79,0.1); color: var(--live); border: 1px solid rgba(45,106,79,0.25); }

        .dname { font-size: 0.8rem; font-weight: 700; color: var(--text); line-height: 1.2; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .dteam { font-size: 0.58rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: var(--text2); margin-top: 2px; }

        .match-exact { font-size: 17px; color: var(--live); }
        .match-miss  { font-size: 13px; color: var(--text3); }
        .match-na    { font-size: 13px; color: var(--text3); }

        .apos-badge {
            font-family: 'JetBrains Mono', monospace; font-size: 0.78rem;
            color: var(--text2); white-space: nowrap;
        }
        .apos-diff {
            font-size: 0.58rem; font-weight: 700; color: var(--text2);
            letter-spacing: 0.02em;
        }

        .pts-num {
            font-family: 'JetBrains Mono', monospace; font-size: 1.55rem;
            font-variant-numeric: tabular-nums;
            color: var(--live); line-height: 1;
            animation: ptsPop 0.45s cubic-bezier(0.175,0.885,0.32,1.275) both;
        }
        .pts-tag { font-size: 0.44rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.1em; color: var(--live); }
        .pts-zero { font-size: 0.9rem; font-weight: 700; color: var(--text3); }

        /* Score breakdown cards */
        .scard {
            text-align: center; padding: 14px 8px;
            border: 1px solid var(--border); position: relative; overflow: hidden;
            background: var(--surface);
        }
        .scard::after {
            content: ''; position: absolute; top: 0; left: 0; right: 0; height: 2px;
        }
        .scard.stotal::after  { background: var(--live); }
        .scard.sdriver::after { background: var(--accent); }
        .scard.spodium::after { background: var(--gold); }
        .scard.scons::after   { background: var(--text2); }
        .scard-num {
            font-family: 'JetBrains Mono', monospace; font-size: 2rem; line-height: 1;
            font-variant-numeric: tabular-nums;
            animation: ptsPop 0.4s cubic-bezier(0.175,0.885,0.32,1.275) 0.1s both;
        }
        .scard-lbl {
            font-size: 0.52rem; font-weight: 700; text-transform: uppercase;
            letter-spacing: 0.08em; color: var(--text2); margin-top: 4px;
        }

        /* g-card (from old gaming-style.css) */
        .g-card { background: var(--surface); border: 1px solid var(--border); }

        /* Override Tailwind dark-theme colors for editorial theme */
        .text-white { color: var(--text); }
        .text-gray-300 { color: var(--text); }
        .text-gray-400 { color: var(--text2); }
        .text-gray-500 { color: var(--text2); }
        .text-gray-600 { color: var(--text3); }
        .text-amber-400 { color: var(--gold); }
        .text-emerald-400 { color: var(--live); }
        .text-orange-400 { color: var(--accent); }
        .text-orange-500 { color: var(--accent); }
        .text-blue-400 { color: var(--text2); }
        .text-purple-400 { color: var(--text2); }
        .text-yellow-400 { color: var(--gold); }
        .bg-black\/20 { background: var(--surface-muted); }
        .border-white\/10 { border-color: var(--border); }
        .border-white\/5 { border-color: var(--border-light); }
        .hover\:text-orange-400:hover { color: var(--accent); }
        .hover\:text-orange-300:hover { color: var(--accent); }
        .hover\:text-white:hover { color: var(--text); }
        .hover\:border-blue-500\/30:hover { border-color: var(--accent); }
        .hover\:bg-blue-500\/5:hover { background: var(--accent-soft); }

        /* Remove all border-radius */
        .g-card, .rounded-lg, .rounded-full { border-radius: 0 !important; }

        .hero{position:relative;height:220px;overflow:hidden}
        .hero-bg{position:absolute;inset:0;background-size:cover;background-position:center}
        .hero-overlay{position:absolute;inset:0;background:linear-gradient(to bottom,rgba(0,0,0,0.1) 0%,rgba(0,0,0,0.5) 100%)}
        .race-info{background:var(--surface);border:1px solid var(--border);padding:16px 20px;display:flex;align-items:center;justify-content:space-between;gap:16px}
        .race-info-left{flex:1;min-width:0}
        .race-info-right{flex-shrink:0;display:flex;align-items:center;gap:12px}
        .race-title{font-family:'Playfair Display',serif;font-style:italic;font-weight:400;font-size:22px;color:var(--text);line-height:1.2;margin-bottom:2px}
        .race-meta{font-size:11px;text-transform:uppercase;letter-spacing:0.08em;color:var(--text2)}
        .race-flag{font-size:32px;line-height:1;flex-shrink:0}
    </style>
</head>
<body>

    <?php require_once __DIR__ . '/includes/nav.php'; ?>

    <main class="pt-20 pb-16 px-4 md:px-8 max-w-5xl mx-auto">

        <!-- Back link -->
        <a href="leaderboard.php" class="inline-flex items-center gap-2 transition mb-5 text-sm" style="color:var(--text2)">
            <i class="fas fa-arrow-left"></i> Back to Leaderboard
        </a>

        <!-- Hero Banner (just image) -->
        <div class="hero" style="height:220px;margin-bottom:0">
            <?php if ($raceHero): ?>
            <div class="hero-bg" style="background-image:url('<?php echo $raceHero; ?>')"></div>
            <?php endif; ?>
            <div class="hero-overlay"></div>
        </div>

        <!-- Race Info (below hero) -->
        <div class="race-info" style="margin-bottom:20px">
            <div class="race-info-left">
                <div class="flex items-center gap-3">
                    <span class="race-flag"><?php echo $raceFlag; ?></span>
                    <div>
                        <div class="race-title" style="font-size:22px"><?php echo htmlspecialchars($race['country']); ?> GP</div>
                        <div class="race-meta">
                            <?php echo htmlspecialchars($race['circuit_name'] ?? ''); ?> · <?php echo $raceDateFormatted; ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="race-info-right">
                <?php if ($isDoublePoints): ?>
                <span class="badge badge-accent" style="font-size:10px"><i class="fas fa-bolt"></i> 2x Points</span>
                <?php endif; ?>
                <?php if (!$gatedPage && count($allRaceUsers) > 1): ?>
                <div class="flex items-center gap-1">
                    <?php if ($prevUserId): ?>
                    <a href="view-predictions.php?user_id=<?php echo $prevUserId; ?>&race_id=<?php echo $raceId; ?>" class="user-nav-btn" style="padding:4px 10px;font-size:12px">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                    <?php endif; ?>
                    <span class="mono" style="font-size:11px;color:var(--text2);padding:0 4px"><?php echo $currentIdx + 1; ?>/<?php echo count($allRaceUsers); ?></span>
                    <?php if ($nextUserId): ?>
                    <a href="view-predictions.php?user_id=<?php echo $nextUserId; ?>&race_id=<?php echo $raceId; ?>" class="user-nav-btn" style="padding:4px 10px;font-size:12px">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($gatedPage): ?>
        <!-- GATE -->
        <div class="g-card p-10 text-center">
            <div class="w-20 h-20 flex items-center justify-center text-4xl mx-auto mb-6" style="color:var(--gold)">
                🔒
            </div>
            <h2 class="text-2xl font-black mb-3">Predictions Hidden Until Deadline</h2>
            <p class="text-sm max-w-md mx-auto mb-6" style="color:var(--text2)">
                Predictions for this race will be visible <strong>after the prediction deadline</strong>.<br><br>
                <span class="font-bold" style="color:var(--gold)"><?php echo $deadlineFormatted; ?></span>
            </p>
            <a href="leaderboard.php" class="btn-outline inline-flex items-center gap-2 text-sm px-6 py-2.5">
                <i class="fas fa-arrow-left"></i> Back to Leaderboard
            </a>
        </div>

        <?php elseif (!$gatedPage && count($allRaceUsers) === 0): ?>
        <!-- No predictions at all -->
        <div class="g-card p-10 text-center">
            <div class="w-20 h-20 flex items-center justify-center text-4xl mx-auto mb-6" style="color:var(--text3)">
                <i class="fas fa-ghost text-3xl"></i>
            </div>
            <h2 class="text-2xl font-black mb-3">No Predictions Yet</h2>
            <p class="text-sm" style="color:var(--text2)">No one has submitted predictions for this race.</p>
        </div>

        <?php else: ?>

        <!-- Race Leaderboard (All Users) -->
        <div class="g-card overflow-hidden mb-6">
            <div class="px-5 py-3 border-b border-[var(--border)] bg-[var(--surface-muted)] flex items-center gap-2">
                <i class="fas fa-trophy text-sm" style="color:var(--gold)"></i>
                <h2 class="section-header">Race Leaderboard</h2>
                <?php if ($isDoublePoints): ?>
                <span class="ml-auto double-points-badge" style="font-size:0.6rem">
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
                             alt="" class="w-8 h-8 border <?php echo $isActive ? 'border-[var(--accent)]' : 'border-[var(--border)]'; ?>">
                        <div class="flex-1">
                            <div class="font-semibold text-sm <?php echo $isActive ? '' : ''; ?>">
                                <?php echo htmlspecialchars($ru['username']); ?>
                                <?php if ($isActive): ?><span class="text-xs ml-1" style="color:var(--accent)">(viewing)</span><?php endif; ?>
                            </div>
                            <div class="flex items-center gap-3 mt-0.5" style="color:var(--text2);font-size:0.75rem">
                                <span>Driver: <strong style="color:var(--accent)"><?php echo $ru['driver_points']; ?></strong></span>
                                <span>Constructor: <strong style="color:var(--accent)"><?php echo $ru['constructor_points']; ?></strong></span>
                                <span>Podium: <strong style="color:var(--gold)"><?php echo $ru['top3_bonus']; ?></strong></span>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-xl font-black" style="color:<?php echo $rank === 1 ? 'var(--gold)' : ($rank === 2 ? 'var(--silver)' : ($rank === 3 ? 'var(--bronze)' : 'var(--text2)')); ?>">
                                <?php echo $ru['total_points']; ?>
                            </div>
                            <div class="text-[0.55rem] uppercase tracking-wider font-semibold" style="color:var(--text3)">Total</div>
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
            <div class="w-16 h-16 flex items-center justify-center text-3xl mx-auto mb-4" style="color:var(--text3)">
                <i class="fas fa-ghost"></i>
            </div>
            <h2 class="text-xl font-black mb-2">
                <?php echo $displayName; ?> didn't predict this race
            </h2>
            <p class="text-sm" style="color:var(--text2)">No predictions submitted.</p>
        </div>

        <?php else: ?>

        <!-- User header -->
        <div class="g-card px-5 py-4 mb-5">
            <div class="flex items-center justify-between flex-wrap gap-3">
                <div class="flex items-center gap-3">
                    <img src="<?php echo getAvatarUrl($targetUser['avatar_style'] ?? 'avataaars', $targetUser['username']); ?>"
                         alt="Avatar" class="w-10 h-10 border-2" style="border-color:var(--accent)">
                    <div>
                        <div class="text-xs uppercase tracking-wide" style="color:var(--text2)">Predictions by</div>
                        <div class="text-lg font-black" style="color:var(--accent)">
                            <?php echo $displayName; ?>
                            <?php if ($isMe): ?><span class="text-xs px-2 ml-1" style="color:var(--accent);background:var(--accent-soft)">YOU</span><?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php if ($scoreRecord): ?>
                <div class="flex items-center gap-4">
                    <div class="text-right">
                        <div class="text-2xl font-black" style="color:var(--live)"><?php echo $scoreRecord['total_points']; ?></div>
                        <div class="text-[0.55rem] uppercase tracking-widest font-semibold" style="color:var(--text3)">Total</div>
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
                <div class="scard-num" style="color:var(--live)">
                    <?php if ($isDoublePoints): ?><span style="font-size:0.8rem;color:var(--accent)">2×</span><?php endif; ?>
                    <?php echo $scoreRecord['total_points']; ?>
                </div>
                <div class="scard-lbl">Total Pts</div>
            </div>
            <div class="scard sdriver">
                <div class="scard-num" style="color:var(--accent)"><?php echo $scoreRecord['driver_points']; ?></div>
                <div class="scard-lbl">Driver</div>
            </div>
            <div class="scard spodium">
                <div class="scard-num" style="color:var(--gold)"><?php echo $scoreRecord['top3_bonus']; ?></div>
                <div class="scard-lbl">Podium</div>
            </div>
            <div class="scard scons">
                <div class="scard-num" style="color:var(--text2)"><?php echo $scoreRecord['constructor_points']; ?></div>
                <div class="scard-lbl">Constructor</div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Predictions vs Actual Table -->
        <div class="g-card overflow-hidden mb-5">
            <div class="px-4 py-3 border-b border-[var(--border)] bg-[var(--surface-muted)] flex items-center gap-2">
                <i class="fas fa-list-ol text-sm" style="color:var(--accent)"></i>
                <h2 class="font-bold text-sm uppercase tracking-wider">Driver Predictions</h2>
                <?php
                $exactCount = 0;
                foreach ($predictions as $p) {
                    $did = $p['driver_id'];
                    $act = $actualResults[$did] ?? $actualResults[$p['driver_name']] ?? null;
                    if ($act && (int)$act['position'] === (int)$p['predicted_position']) $exactCount++;
                }
                ?>
                <span class="ml-auto text-xs" style="color:var(--text2)"><?php echo count($predictions); ?> drivers</span>
                <?php if ($exactCount > 0): ?>
                <span style="font-size:0.6rem;font-weight:800;background:rgba(45,106,79,0.1);color:var(--live);border:1px solid rgba(45,106,79,0.2);padding:2px 8px;text-transform:uppercase;letter-spacing:0.06em;">
                    <?php echo $exactCount; ?> exact ✓
                </span>
                <?php endif; ?>
            </div>

            <!-- Column headers -->
            <div style="display:grid;grid-template-columns:1fr 30px 90px 64px;padding:5px 14px;border-bottom:1px solid var(--border);background:var(--surface-muted);">
                <div style="font-size:0.52rem;font-weight:800;text-transform:uppercase;letter-spacing:0.1em;color:var(--text2);">Your Pick</div>
                <div></div>
                <div style="font-size:0.52rem;font-weight:800;text-transform:uppercase;letter-spacing:0.1em;color:var(--text2);text-align:center;">Actual</div>
                <div style="font-size:0.52rem;font-weight:800;text-transform:uppercase;letter-spacing:0.1em;color:var(--text2);text-align:right;padding-right:14px;">Pts</div>
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
                        <div class="apos-diff" style="color:<?php echo ($diff > 0 ? 'var(--accent)' : 'var(--live)'); ?>"><?php echo $diffTxt; ?></div>
                        <?php endif; ?>
                    <?php else: ?>
                        <span style="font-size:0.65rem;color:var(--text3);">TBC</span>
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
            <div class="px-4 py-3 border-b border-[var(--border)] bg-[var(--surface-muted)] flex items-center gap-2">
                <i class="fas fa-wrench text-sm" style="color:var(--accent)"></i>
                <h2 class="font-bold text-sm uppercase tracking-wider">Constructor Predictions</h2>
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
                <div class="flex items-center gap-3 py-2 px-3" style="background:var(--surface-muted);border:1px solid var(--border-light)">
                    <span class="font-black text-lg" style="color:var(--accent)"><?php echo $conPredPos; ?></span>
                    <span class="text-sm font-semibold flex-1"><?php echo htmlspecialchars($conName); ?></span>
                    <?php if ($conActualPos !== null): ?>
                        <span class="text-xs" style="color:var(--text2)">Actual: #<?php echo $conActualPos; ?></span>
                        <?php if ($conCorrect): ?>
                            <span class="text-sm" style="color:var(--live)">✅</span>
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

    <footer class="mt-12 py-6 text-center" style="border-top:1px solid var(--border);">
        <p class="text-sm mb-2" style="color:var(--text2)">&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</p>
        <p class="text-xs" style="color:var(--text3)">
            Powered by <a href="https://www.scanerrific.com" target="_blank" style="color:var(--accent);font-weight:600">Scanerrific</a>
        </p>
    </footer>

<script src="app.js"></script>
</body>
</html>
