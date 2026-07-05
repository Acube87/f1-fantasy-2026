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

if (!$targetUserId || !$raceId) {
    header('Location: leaderboard.php');
    exit;
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

// Fetch race
$stmt = $db->prepare("SELECT * FROM races WHERE id = ?");
$stmt->bind_param("i", $raceId);
$stmt->execute();
$race = $stmt->get_result()->fetch_assoc();

if (!$race) {
    header('Location: leaderboard.php');
    exit;
}

// Enforce deadline gate — predictions only visible AFTER deadline passes (server-side)
$deadline = getPredictionDeadline($race['race_date']);
$now = new DateTime('now', new DateTimeZone('UTC'));
$deadlinePassed = $now >= $deadline;

if (!$deadlinePassed) {
    // Show a friendly gate page instead of redirecting
    $gatedPage = true;
    $deadlineFormatted = $deadline->format('D, d M Y \a\t H:i \U\T\C');
} else {
    $gatedPage = false;
    $deadlineFormatted = $deadline->format('D, d M Y \a\t H:i \U\T\C');
}

// Fetch driver predictions for this user/race
$predictions = [];
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

    // Fetch constructor prediction
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
}

$isMe = ($currentUser['id'] === $targetUserId);
$displayName = htmlspecialchars($targetUser['username']);
$raceFlag = getRaceFlag($race['country'] ?? '');

// Team abbreviations mapping
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $displayName; ?>'s Predictions – <?php echo htmlspecialchars($race['country']); ?> · <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="css/gaming-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --f1-carbon: #0A0A0A;
            --f1-text: #FFFFFF;
        }
        .team-badge {
            width: 28px; height: 28px; border-radius: 6px;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.6rem; font-weight: 900; letter-spacing: -0.5px;
            flex-shrink: 0; box-shadow: 0 2px 8px rgba(0,0,0,0.3);
        }
        .badge-ferrari       { background: linear-gradient(135deg, #DC0000 0%, #E8002D 100%); color: white; }
        .badge-mercedes      { background: linear-gradient(135deg, #00D2BE 0%, #27F4D2 100%); color: #000; }
        .badge-red-bull      { background: linear-gradient(135deg, #3671C6 0%, #1E41FF 100%); color: white; }
        .badge-mclaren       { background: linear-gradient(135deg, #FF8000 0%, #FFA04D 100%); color: white; }
        .badge-aston-martin  { background: linear-gradient(135deg, #00665F 0%, #229971 100%); color: white; }
        .badge-alpine        { background: linear-gradient(135deg, #0090FF 0%, #2E9AFF 100%); color: white; }
        .badge-williams      { background: linear-gradient(135deg, #005AFF 0%, #4280FF 100%); color: white; }
        .badge-haas          { background: linear-gradient(135deg, #FFFFFF 0%, #B6BABD 100%); color: #000; border: 1px solid rgba(255,255,255,0.2); }
        .badge-rb,
        .badge-racing-bulls  { background: linear-gradient(135deg, #6692FF 0%, #1E41FF 100%); color: white; }
        .badge-sauber,
        .badge-kick-sauber   { background: linear-gradient(135deg, #00E701 0%, #52B256 100%); color: #000; }
        .badge-audi          { background: linear-gradient(135deg, #000000 0%, #FF1721 100%); color: white; }
        .badge-cadillac      { background: linear-gradient(135deg, #0C1C8C 0%, #C41E3A 100%); color: white; }

        .team-ferrari       { border-left: 3px solid #DC0000; }
        .team-mercedes      { border-left: 3px solid #00D2BE; }
        .team-red-bull      { border-left: 3px solid #3671C6; }
        .team-mclaren       { border-left: 3px solid #FF8000; }
        .team-aston-martin  { border-left: 3px solid #00665F; }
        .team-alpine        { border-left: 3px solid #0090FF; }
        .team-williams      { border-left: 3px solid #005AFF; }
        .team-haas          { border-left: 3px solid #B6BABD; }
        .team-rb,
        .team-racing-bulls  { border-left: 3px solid #6692FF; }
        .team-sauber,
        .team-kick-sauber   { border-left: 3px solid #00E701; }
        .team-audi          { border-left: 3px solid #FF1721; }
        .team-cadillac      { border-left: 3px solid #C41E3A; }

        .pred-row {
            display: flex; align-items: center; gap: 12px;
            padding: 8px 16px;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            background: rgba(255,255,255,0.02);
            transition: background 0.15s;
        }
        .pred-row:hover { background: rgba(255,255,255,0.05); }
        .pos-num {
            font-variant-numeric: tabular-nums; width: 28px;
            text-align: center; font-weight: 800; color: #64748b; font-size: 0.8rem;
        }
        .pos-medal-1 { color: #FBBF24; text-shadow: 0 0 8px rgba(251,191,36,0.5); }
        .pos-medal-2 { color: #CBD5E1; }
        .pos-medal-3 { color: #D97706; }

        .lock-banner {
            background: rgba(16, 185, 129, 0.08);
            border: 1px solid rgba(16, 185, 129, 0.3);
        }
    </style>
</head>
<body style="background:var(--f1-carbon);color:var(--f1-text);min-height:100vh;">

    <!-- Navbar -->
    <?php require_once __DIR__ . '/includes/nav.php'; ?>

    <!-- Main Content -->
    <main class="pt-24 pb-16 px-4 md:px-8 max-w-3xl mx-auto">

        <!-- Back link -->
        <a href="leaderboard.php" class="inline-flex items-center gap-2 text-gray-400 hover:text-white transition mb-6 text-sm">
            <i class="fas fa-arrow-left"></i> Back to Leaderboard
        </a>

        <!-- Race + User Header -->
        <div class="g-card p-6 mb-6">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div class="flex items-center gap-4">
                    <div class="text-4xl"><?php echo $raceFlag; ?></div>
                    <div>
                        <div class="text-xs text-gray-500 uppercase tracking-widest font-bold mb-1"><?php echo htmlspecialchars($race['circuit_name'] ?? ''); ?></div>
                        <h1 class="text-2xl font-black text-white uppercase tracking-wide"><?php echo htmlspecialchars($race['country']); ?> GP</h1>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <img src="<?php echo getAvatarUrl($targetUser['avatar_style'] ?? 'avataaars', $targetUser['username']); ?>" alt="Avatar" class="w-12 h-12 rounded-full border-2 border-orange-500/50">
                    <div>
                        <div class="text-xs text-gray-500 uppercase tracking-wide">Predictions by</div>
                        <div class="text-lg font-black text-orange-400"><?php echo $displayName; ?><?php if ($isMe) echo ' <span class="text-xs bg-orange-500 text-white px-1 rounded">YOU</span>'; ?></div>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($gatedPage): ?>
        <!-- GATE: Deadline not yet passed -->
        <div class="g-card p-10 text-center">
            <div class="w-20 h-20 rounded-full bg-yellow-500/20 flex items-center justify-center text-4xl mx-auto mb-6">
                🔒
            </div>
            <h2 class="text-2xl font-black text-white mb-3">Predictions Are Hidden Until Deadline</h2>
            <p class="text-gray-400 text-sm max-w-md mx-auto mb-6">
                To keep the competition fair, <?php echo $isMe ? 'your' : $displayName . "'s"; ?> predictions for this race will be publicly visible <strong class="text-white">after the prediction deadline</strong>.<br><br>
                You'll be able to view them from:<br>
                <span class="text-yellow-400 font-bold"><?php echo $deadlineFormatted; ?></span>
            </p>
            <a href="leaderboard.php" class="g-btn g-btn-blue px-6 py-2.5 inline-flex items-center gap-2 text-sm">
                <i class="fas fa-arrow-left"></i> Back to Leaderboard
            </a>
        </div>

        <?php elseif (empty($predictions)): ?>
        <!-- No predictions submitted -->
        <div class="g-card p-10 text-center">
            <div class="w-20 h-20 rounded-full bg-gray-500/20 flex items-center justify-center text-4xl mx-auto mb-6">
                <i class="fas fa-ghost text-gray-500 text-3xl"></i>
            </div>
            <h2 class="text-2xl font-black text-white mb-3">No Predictions Found</h2>
            <p class="text-gray-400 text-sm"><?php echo $displayName; ?> didn't submit a prediction for this race.</p>
        </div>

        <?php else: ?>

        <!-- Lock verified badge -->
        <div class="lock-banner rounded-xl px-5 py-3 mb-5 flex items-center gap-3">
            <i class="fas fa-lock text-green-400"></i>
            <div class="text-sm text-gray-300">
                <span class="text-green-400 font-bold">Predictions locked</span> before the deadline
                (<span class="font-mono text-xs text-gray-400"><?php echo $deadlineFormatted; ?></span>).
                This list is read-only and cannot be altered retroactively.
            </div>
        </div>

        <!-- Driver Prediction List -->
        <div class="g-card overflow-hidden mb-5">
            <div class="px-4 py-3 border-b border-white/10 bg-black/20 flex items-center gap-2">
                <i class="fas fa-list-ol text-orange-500 text-sm"></i>
                <h2 class="font-bold text-white text-sm uppercase tracking-wider">Driver Predictions</h2>
                <span class="ml-auto text-xs text-gray-500"><?php echo count($predictions); ?> drivers</span>
            </div>
            <div>
                <?php foreach ($predictions as $pred):
                    $pos      = (int)$pred['predicted_position'];
                    $team     = $pred['team'] ?? '';
                    $teamSlug = strtolower(str_replace(' ', '-', $team));
                    $abbr     = $teamAbbr[$team] ?? strtoupper(substr($team, 0, 3));
                    $posClass = match($pos) { 1 => 'pos-medal-1', 2 => 'pos-medal-2', 3 => 'pos-medal-3', default => '' };
                ?>
                <div class="pred-row team-<?php echo $teamSlug; ?>">
                    <div class="pos-num <?php echo $posClass; ?>">
                        <?php if ($pos === 1): ?>🥇<?php elseif ($pos === 2): ?>🥈<?php elseif ($pos === 3): ?>🥉<?php else: echo $pos; endif; ?>
                    </div>
                    <div class="team-badge badge-<?php echo $teamSlug; ?>"><?php echo $abbr; ?></div>
                    <div class="flex-1">
                        <div class="text-sm font-semibold text-white"><?php echo htmlspecialchars($pred['driver_name']); ?></div>
                        <div class="text-xs text-gray-500"><?php echo htmlspecialchars($team); ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if (!empty($constructorPreds)): ?>
        <!-- Constructor Prediction -->
        <div class="g-card overflow-hidden">
            <div class="px-4 py-3 border-b border-white/10 bg-black/20 flex items-center gap-2">
                <i class="fas fa-wrench text-blue-400 text-sm"></i>
                <h2 class="font-bold text-white text-sm uppercase tracking-wider">Top Constructor Prediction</h2>
            </div>
            <div class="p-4 space-y-2">
                <?php foreach ($constructorPreds as $cp): ?>
                <div class="flex items-center gap-3 py-2 px-3 bg-white/5 rounded-lg">
                    <span class="text-blue-400 font-black text-lg"><?php echo (int)$cp['predicted_position']; ?></span>
                    <span class="text-sm font-semibold text-white"><?php echo htmlspecialchars($cp['constructor_name']); ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php endif; // end !gated && has predictions ?>

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
