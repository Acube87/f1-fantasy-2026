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

// ─── DATA GATHERING ────────────────────────────────────────────────────────

// Latest completed race (China)
$lastRaceStmt = $db->query("SELECT * FROM races WHERE status = 'completed' ORDER BY race_date DESC LIMIT 1");
$lastRace = $lastRaceStmt ? $lastRaceStmt->fetch_assoc() : null;

// Next upcoming race (Japan)
$nextRace = getNextRace();

// China GP - top 3 scorers
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

// China GP - actual race winner (P1 driver)
$raceWinner = null;
if ($lastRace) {
    $winStmt = $db->prepare("SELECT driver_name, constructor_name FROM race_results WHERE race_id = ? AND position = 1 LIMIT 1");
    $winStmt->bind_param("i", $lastRace['id']);
    $winStmt->execute();
    $raceWinner = $winStmt->get_result()->fetch_assoc();
}

// China GP - full top 5 results
$top5Results = [];
if ($lastRace) {
    $resStmt = $db->prepare("SELECT driver_name, constructor_name, position FROM race_results WHERE race_id = ? AND position <= 5 ORDER BY position ASC");
    $resStmt->bind_param("i", $lastRace['id']);
    $resStmt->execute();
    $top5Results = $resStmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Next race - who has submitted predictions
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

// Overall leaderboard snapshot (top 5)
$leaderboard = getLeaderboard(5);

// Stats: who scored highest in China, who got podium sweep, etc.
$podiumSweepUsers = [];
if ($lastRace) {
    $psStmt = $db->prepare("SELECT u.username FROM scores s JOIN users u ON s.user_id = u.id WHERE s.race_id = ? AND s.top3_bonus > 0");
    $psStmt->bind_param("i", $lastRace['id']);
    $psStmt->execute();
    $podiumSweepUsers = $psStmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

$constructorBonusUsers = [];
if ($lastRace) {
    $cbStmt = $db->prepare("SELECT u.username FROM scores s JOIN users u ON s.user_id = u.id WHERE s.race_id = ? AND s.constructor_points > 0");
    $cbStmt->bind_param("i", $lastRace['id']);
    $cbStmt->execute();
    $constructorBonusUsers = $cbStmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Total predictions submitted for China
$chinaPredCount = 0;
if ($lastRace) {
    $cpStmt = $db->prepare("SELECT COUNT(DISTINCT user_id) as c FROM predictions WHERE race_id = ?");
    $cpStmt->bind_param("i", $lastRace['id']);
    $cpStmt->execute();
    $chinaPredCount = $cpStmt->get_result()->fetch_assoc()['c'] ?? 0;
}

$isDoublePoints = $lastRace && in_array($lastRace['country'], ['China', 'UK', 'Singapore']);

// Season leaderboard - total races completed
$completedRacesCount = $db->query("SELECT COUNT(*) as c FROM races WHERE status = 'completed'")->fetch_assoc()['c'] ?? 0;
$totalRacesCount = $db->query("SELECT COUNT(*) as c FROM races")->fetch_assoc()['c'] ?? 0;

// My rank & points
$myStats = getUserStats($userId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Race Control — <?php echo SITE_NAME; ?></title>
    <meta name="description" content="Live F1 Fantasy telemetry — season standings, prediction tracker, and post-race analysis for Paddock Picks.">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="css/gaming-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;900&display=swap" rel="stylesheet">
    <style>
        /* ── Live Feed Cards ─────────────────────────────── */
        .feed-card {
            background: rgba(10, 15, 28, 0.7);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.07);
            border-radius: 1.5rem;
            transition: border-color 0.3s;
        }
        .feed-card:hover { border-color: rgba(255,255,255,0.14); }

        /* ── Ticker/Live Badge ───────────────────────────── */
        .live-dot {
            width: 8px; height: 8px;
            background: #f97316;
            border-radius: 50%;
            animation: livepulse 1.4s ease-in-out infinite;
            flex-shrink: 0;
        }
        @keyframes livepulse {
            0%, 100% { opacity: 1; transform: scale(1); box-shadow: 0 0 0 0 rgba(249,115,22,0.7); }
            50% { opacity: 0.7; transform: scale(0.85); box-shadow: 0 0 0 6px rgba(249,115,22,0); }
        }

        /* ── Podium Medals ───────────────────────────────── */
        .medal-1 { background: linear-gradient(135deg, #F59E0B, #FBBF24); color: #1a1200; }
        .medal-2 { background: linear-gradient(135deg, #94A3B8, #CBD5E1); color: #0f172a; }
        .medal-3 { background: linear-gradient(135deg, #92400E, #B45309); color: white; }

        /* ── User Chip ───────────────────────────────────── */
        .user-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.03em;
            border: 1px solid;
        }
        .chip-green { background: rgba(34,197,94,0.1); border-color: rgba(34,197,94,0.3); color: #4ade80; }
        .chip-red   { background: rgba(239,68,68,0.08); border-color: rgba(239,68,68,0.2); color: #f87171; text-decoration: line-through; opacity: 0.6; }

        /* ── Progress bar ────────────────────────────────── */
        .sub-bar-track { background: rgba(255,255,255,0.06); border-radius: 999px; height: 6px; overflow: hidden; }
        .sub-bar-fill  { height: 100%; border-radius: 999px; background: linear-gradient(90deg, #22c55e, #16a34a); transition: width 1s ease; }

        /* ── Scan line shimmer on stat cards ──────────────── */
        @keyframes scanline {
            0%   { transform: translateY(-100%); }
            100% { transform: translateY(400%); }
        }
        .scanline::after {
            content: '';
            position: absolute;
            left: 0; right: 0; height: 30%;
            background: linear-gradient(to bottom, transparent, rgba(255,255,255,0.03), transparent);
            animation: scanline 3s linear infinite;
            pointer-events: none;
        }

        /* ── Timeline dot ─────────────────────────────────── */
        .tl-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; margin-top: 4px; }

        /* ── Double points badge ──────────────────────────── */
        .dp-badge {
            background: linear-gradient(135deg, rgba(168,85,247,0.25), rgba(99,102,241,0.25));
            border: 1px solid rgba(168,85,247,0.4);
            color: #c084fc;
        }

        /* ── Glowing orange underline headers ─────────────── */
        .section-label {
            font-size: 10px; font-weight: 900;
            letter-spacing: 0.25em;
            text-transform: uppercase;
            color: #f97316;
        }

        /* ── Next race card glow ──────────────────────────── */
        .next-race-glow {
            box-shadow: 0 0 60px rgba(249,115,22,0.06) inset,
                        0 0 0 1px rgba(249,115,22,0.15);
        }
    </style>
</head>
<body class="gaming-theme text-gray-200" style="font-family: 'Inter', sans-serif;">

<!-- ── NAV ──────────────────────────────────────────────────────────────── -->
<nav class="g-nav fixed w-full z-50 px-6 py-4 flex justify-between items-center bg-slate-900/80 backdrop-blur-md border-b border-white/5">
    <div class="flex items-center gap-6">
        <a href="dashboard.php" class="flex items-center gap-3 hover:opacity-80 transition group">
            <div class="w-10 h-10 bg-gradient-to-br from-red-600 to-orange-500 rounded-xl flex items-center justify-center shadow-lg shadow-orange-500/20 group-hover:scale-105 transition-transform">
                <i class="fas fa-flag-checkered text-white text-lg"></i>
            </div>
            <span class="font-black text-xl tracking-wide text-white uppercase italic hidden sm:block">PADDOCK PICKS</span>
        </a>
        <div class="hidden lg:flex items-center gap-6 text-xs uppercase tracking-[0.15em] font-black">
            <a href="dashboard.php" class="text-gray-400 hover:text-white transition">Dashboard</a>
            <a href="updates.php" class="text-orange-500 border-b-2 border-orange-500 pb-0.5">Race Updates</a>
            <a href="leaderboard.php" class="text-gray-400 hover:text-white transition">Leaderboard</a>
            <a href="achievements.php" class="text-gray-400 hover:text-white transition">Achievements</a>
        </div>
    </div>
    <div class="flex items-center gap-4">
        <!-- Live badge -->
        <div class="hidden md:flex items-center gap-2 bg-orange-500/10 border border-orange-500/20 rounded-full px-3 py-1.5">
            <div class="live-dot"></div>
            <span class="text-[10px] font-black text-orange-400 uppercase tracking-widest">Live Telemetry</span>
        </div>
        <a href="profile.php" class="w-9 h-9 rounded-full bg-slate-700 border-2 border-white/10 overflow-hidden hover:border-orange-500 transition shadow">
            <img src="<?php echo getAvatarUrl($user['avatar_style'] ?? 'avataaars', $user['username']); ?>" alt="Avatar" class="w-full h-full object-cover">
        </a>
        <a href="logout.php" class="text-gray-500 hover:text-white transition text-sm" title="Sign Out"><i class="fas fa-sign-out-alt"></i></a>
    </div>
</nav>

<!-- ── HERO ─────────────────────────────────────────────────────────────── -->
<div class="pt-24 px-4 md:px-8 max-w-7xl mx-auto">
    <div class="py-10 flex flex-col md:flex-row items-start md:items-end justify-between gap-6">
        <div>
            <div class="flex items-center gap-3 mb-3">
                <div class="live-dot"></div>
                <span class="section-label">Paddock Dispatch — Season Underway</span>
            </div>
            <h1 class="text-4xl md:text-6xl font-black text-white italic uppercase leading-tight">
                RACE CONTROL<br>
                <span class="g-text-gradient">TELEMETRY</span>
            </h1>
            <p class="text-gray-400 mt-3 text-sm max-w-xl">
                Live standings, prediction tracker, post-race analysis and everything in between. 
                <strong class="text-white"><?php echo $completedRacesCount; ?>/<?php echo $totalRacesCount; ?> rounds complete.</strong>
            </p>
        </div>
        <div class="flex items-center gap-3">
            <?php if ($nextRace): ?>
            <a href="predict.php?race_id=<?php echo $nextRace['id']; ?>" 
               class="px-6 py-3 bg-gradient-to-r from-orange-600 to-orange-500 hover:from-orange-500 hover:to-orange-400 text-white font-black text-sm rounded-xl shadow-lg shadow-orange-500/20 transition transform hover:scale-105 flex items-center gap-2">
                <i class="fas fa-pencil-alt"></i> My <?php echo htmlspecialchars($nextRace['country']); ?> Prediction
            </a>
            <?php endif; ?>
            <a href="leaderboard.php" class="px-6 py-3 bg-white/5 hover:bg-white/10 border border-white/10 text-white font-bold text-sm rounded-xl transition flex items-center gap-2">
                <i class="fas fa-trophy text-yellow-400"></i> Standings
            </a>
        </div>
    </div>
</div>

<!-- ── MAIN GRID ─────────────────────────────────────────────────────────── -->
<main class="pb-20 px-4 md:px-8 max-w-7xl mx-auto">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- ═══════════════════════════════════════════════════════ -->
        <!-- LEFT + CENTRE (col-span-2) -->
        <!-- ═══════════════════════════════════════════════════════ -->
        <div class="lg:col-span-2 space-y-6">

            <!-- ── PADDOCK DISPATCH: SESSION UPDATE ──────────────── -->
            <div class="feed-card p-0 overflow-hidden border border-orange-500/20">
                <!-- Header banner -->
                <div class="bg-gradient-to-r from-orange-600/20 via-orange-500/10 to-transparent px-8 py-5 border-b border-orange-500/15 flex items-center justify-between gap-4 flex-wrap">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 bg-orange-500/20 border border-orange-500/30 rounded-xl flex items-center justify-center text-orange-400 text-lg flex-shrink-0">
                            <i class="fas fa-broadcast-tower"></i>
                        </div>
                        <div>
                            <div class="text-[10px] font-black text-orange-400 uppercase tracking-widest mb-0.5">Paddock Dispatch — Official Bulletin</div>
                            <h2 class="text-lg font-black text-white italic uppercase">App Update &amp; Score Corrections</h2>
                        </div>
                    </div>
                    <div class="text-[10px] font-bold text-gray-500 uppercase tracking-widest flex-shrink-0">
                        <?php echo date('d M Y'); ?> · v2.1
                    </div>
                </div>

                <div class="p-8 space-y-6">

                    <!-- Score correction notice -->
                    <div class="bg-green-500/8 border border-green-500/20 rounded-2xl p-5">
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 bg-green-500/20 rounded-lg flex items-center justify-center text-green-400 flex-shrink-0 mt-0.5">
                                <i class="fas fa-check-circle text-sm"></i>
                            </div>
                            <div>
                                <h3 class="font-black text-white text-sm uppercase italic mb-1">🏆 Scores Retroactively Corrected — Australia &amp; China</h3>
                                <p class="text-gray-300 text-sm leading-relaxed">
                                    We identified and fixed a bug where the <strong class="text-green-400">Constructor Bonus (+5 pts)</strong> was not being awarded to anyone. 
                                    If you picked the correct winning constructor and had the right driver in the exact position, those 5 points were silently missing from your total.
                                </p>
                                <p class="text-gray-400 text-sm leading-relaxed mt-2">
                                    <strong class="text-white">Both the Australian GP and Chinese GP scores have been recalculated.</strong> 
                                    If you earned the constructor bonus, it's now reflected in your total and on the leaderboard — including the 2× double points multiplier for China. 
                                    Check your race results to see your updated breakdown. 
                                </p>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    <a href="race-results.php?race_id=1" class="text-[10px] font-black text-green-400 bg-green-500/10 border border-green-500/20 px-3 py-1.5 rounded-lg hover:bg-green-500/20 transition">
                                        <i class="fas fa-flag mr-1"></i>Australia Results →
                                    </a>
                                    <a href="race-results.php?race_id=2" class="text-[10px] font-black text-green-400 bg-green-500/10 border border-green-500/20 px-3 py-1.5 rounded-lg hover:bg-green-500/20 transition">
                                        <i class="fas fa-flag mr-1"></i>China Results →
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- What else changed -->
                    <div>
                        <div class="text-[10px] font-black text-gray-500 uppercase tracking-widest mb-3">Also Deployed This Session</div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div class="flex items-start gap-3 p-4 bg-white/3 border border-white/5 rounded-xl">
                                <div class="w-7 h-7 bg-purple-500/20 rounded-lg flex items-center justify-center text-purple-400 flex-shrink-0"><i class="fas fa-tools text-xs"></i></div>
                                <div>
                                    <div class="text-xs font-black text-white mb-0.5">⚡ Double Points Applied</div>
                                    <div class="text-[10px] text-gray-400 leading-relaxed">China GP 2× multiplier is now correctly calculated and shown on all score cards.</div>
                                </div>
                            </div>
                            <div class="flex items-start gap-3 p-4 bg-white/3 border border-white/5 rounded-xl">
                                <div class="w-7 h-7 bg-blue-500/20 rounded-lg flex items-center justify-center text-blue-400 flex-shrink-0"><i class="fas fa-satellite-dish text-xs"></i></div>
                                <div>
                                    <div class="text-xs font-black text-white mb-0.5">📡 Race Updates Redesign</div>
                                    <div class="text-[10px] text-gray-400 leading-relaxed">This page has been completely overhauled with live telemetry, prediction tracker, and season progress — all live from the DB.</div>
                                </div>
                            </div>
                            <div class="flex items-start gap-3 p-4 bg-white/3 border border-white/5 rounded-xl">
                                <div class="w-7 h-7 bg-orange-500/20 rounded-lg flex items-center justify-center text-orange-400 flex-shrink-0"><i class="fas fa-tachometer-alt text-xs"></i></div>
                                <div>
                                    <div class="text-xs font-black text-white mb-0.5">🏠 Dashboard Updated</div>
                                    <div class="text-[10px] text-gray-400 leading-relaxed">Dashboard now auto-advances to the next race the moment results are deployed — Japan GP hero image, timer and flag auto-loaded.</div>
                                </div>
                            </div>
                            <div class="flex items-start gap-3 p-4 bg-white/3 border border-white/5 rounded-xl">
                                <div class="w-7 h-7 bg-green-500/20 rounded-lg flex items-center justify-center text-green-400 flex-shrink-0"><i class="fas fa-lock text-xs"></i></div>
                                <div>
                                    <div class="text-xs font-black text-white mb-0.5">🔒 Maintenance Mode</div>
                                    <div class="text-[10px] text-gray-400 leading-relaxed">App now locks correctly during post-race score processing so standings aren't seen mid-calculation.</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sign-off -->
                    <div class="flex items-center gap-3 pt-2 border-t border-white/5">
                        <div class="w-8 h-8 rounded-full bg-orange-500/20 flex items-center justify-center text-orange-400 flex-shrink-0"><i class="fas fa-user-tie text-xs"></i></div>
                        <div>
                            <div class="text-xs text-gray-400 italic">"Scores are live and correct. Japan is next — get your grids locked in. 🏁"</div>
                            <div class="text-[10px] font-black text-white uppercase mt-0.5">Aurimas <span class="text-orange-500 font-light">— Race Controller</span></div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- ── POST-RACE DEBRIEF: CHINA GP ───────────────────── -->
            <?php if ($lastRace): ?>
            <div class="feed-card p-0 overflow-hidden">
                <!-- Banner -->
                <div class="relative bg-gradient-to-r from-slate-900 via-red-950/30 to-slate-900 px-8 py-5 border-b border-white/5 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="flex items-center gap-4">
                        <div class="text-4xl"><?php echo getRaceFlag($lastRace['country']); ?></div>
                        <div>
                            <div class="section-label mb-1">Post-Race Debrief</div>
                            <h2 class="text-xl font-black text-white italic uppercase"><?php echo htmlspecialchars($lastRace['race_name']); ?></h2>
                            <div class="text-xs text-gray-500 mt-0.5">
                                <?php echo date('d M Y', strtotime($lastRace['race_date'])); ?> · Round <?php echo str_pad($lastRace['id'], 2, '0', STR_PAD_LEFT); ?>
                            </div>
                        </div>
                    </div>
                    <?php if ($isDoublePoints): ?>
                    <div class="dp-badge px-4 py-2 rounded-xl text-center flex-shrink-0">
                        <div class="text-xs font-black uppercase tracking-widest">⚡ Double Points</div>
                        <div class="text-[10px] text-purple-300/70 mt-0.5">2× Multiplier Applied</div>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="p-8 grid grid-cols-1 md:grid-cols-2 gap-8">

                    <!-- Race Top 5 -->
                    <div>
                        <div class="section-label mb-4"><i class="fas fa-flag-checkered mr-1"></i>Official Top 5</div>
                        <div class="space-y-2">
                            <?php
                            $posIcons  = ['🥇','🥈','🥉','4️⃣','5️⃣'];
                            $posBorder = ['border-yellow-400/40 bg-yellow-400/5','border-gray-300/30 bg-white/3','border-amber-600/30 bg-amber-900/5','border-white/5','border-white/5'];
                            foreach ($top5Results as $i => $r):
                            ?>
                            <div class="flex items-center gap-3 p-3 rounded-xl border <?php echo $posBorder[$i]; ?>">
                                <span class="text-xl w-7 text-center flex-shrink-0"><?php echo $posIcons[$i]; ?></span>
                                <div class="flex-1 min-w-0">
                                    <div class="text-sm font-bold text-white truncate"><?php echo htmlspecialchars($r['driver_name']); ?></div>
                                    <div class="text-[10px] text-gray-500 uppercase tracking-wide"><?php echo htmlspecialchars($r['constructor_name'] ?? '—'); ?></div>
                                </div>
                                <div class="text-xs font-black text-gray-400">P<?php echo $r['position']; ?></div>
                            </div>
                            <?php endforeach; ?>
                            <?php if (empty($top5Results)): ?>
                            <p class="text-gray-500 text-sm">Results not yet available.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Paddock Picks Scorers Podium -->
                    <div>
                        <div class="section-label mb-4"><i class="fas fa-users mr-1"></i>Prediction Podium</div>
                        <div class="space-y-2">
                            <?php
                            $medalClasses = ['medal-1','medal-2','medal-3'];
                            $medalsText   = ['🥇 P1','🥈 P2','🥉 P3'];
                            foreach ($topScorers as $i => $ts):
                            ?>
                            <div class="flex items-center gap-3 p-3 rounded-xl bg-white/3 border border-white/5 hover:border-white/10 transition">
                                <div class="w-9 h-9 rounded-lg <?php echo $medalClasses[$i]; ?> flex items-center justify-center font-black text-xs flex-shrink-0">
                                    <?php echo ($i+1); ?>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="text-sm font-bold text-white">@<?php echo htmlspecialchars($ts['username']); ?></div>
                                    <div class="flex items-center gap-2 mt-0.5">
                                        <?php if ($ts['top3_bonus'] > 0): ?>
                                        <span class="text-[9px] font-black text-orange-400 bg-orange-500/10 px-1.5 py-0.5 rounded">👑 PODIUM SWEEP</span>
                                        <?php endif; ?>
                                        <?php if ($ts['constructor_points'] > 0): ?>
                                        <span class="text-[9px] font-black text-purple-400 bg-purple-500/10 px-1.5 py-0.5 rounded">⭐ CONSTRUCTOR</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="text-right flex-shrink-0">
                                    <div class="text-lg font-black text-white"><?php echo $ts['total_points']; ?></div>
                                    <div class="text-[9px] text-gray-500 uppercase">pts</div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <?php if (empty($topScorers)): ?>
                            <p class="text-gray-500 text-sm">Scores being calculated…</p>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>

                <!-- Race Trivia Strip -->
                <div class="px-8 pb-8">
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        <div class="bg-white/3 border border-white/5 rounded-xl p-3 text-center scanline relative overflow-hidden">
                            <div class="text-2xl font-black text-orange-400"><?php echo $chinaPredCount; ?></div>
                            <div class="text-[9px] text-gray-500 uppercase font-bold mt-1">Predictions Made</div>
                        </div>
                        <div class="bg-white/3 border border-white/5 rounded-xl p-3 text-center scanline relative overflow-hidden">
                            <div class="text-2xl font-black text-purple-400"><?php echo count($podiumSweepUsers); ?></div>
                            <div class="text-[9px] text-gray-500 uppercase font-bold mt-1">Podium Sweeps</div>
                        </div>
                        <div class="bg-white/3 border border-white/5 rounded-xl p-3 text-center scanline relative overflow-hidden">
                            <div class="text-2xl font-black text-blue-400"><?php echo count($constructorBonusUsers); ?></div>
                            <div class="text-[9px] text-gray-500 uppercase font-bold mt-1">Constructor Bonus</div>
                        </div>
                        <div class="bg-white/3 border border-white/5 rounded-xl p-3 text-center scanline relative overflow-hidden">
                            <div class="text-2xl font-black text-green-400">2×</div>
                            <div class="text-[9px] text-gray-500 uppercase font-bold mt-1">Points Multiplier</div>
                        </div>
                    </div>
                </div>

                <!-- Notable Achievements Row -->
                <?php if (!empty($podiumSweepUsers) || !empty($constructorBonusUsers)): ?>
                <div class="border-t border-white/5 px-8 py-5 flex flex-wrap gap-4">
                    <?php if (!empty($podiumSweepUsers)): ?>
                    <div>
                        <div class="text-[9px] text-gray-600 font-black uppercase tracking-widest mb-2">👑 Podium Sweep Bonus</div>
                        <div class="flex flex-wrap gap-1.5">
                            <?php foreach ($podiumSweepUsers as $pu): ?>
                            <span class="user-chip chip-green">@<?php echo htmlspecialchars($pu['username']); ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($constructorBonusUsers)): ?>
                    <div>
                        <div class="text-[9px] text-gray-600 font-black uppercase tracking-widest mb-2">⭐ Constructor Bonus</div>
                        <div class="flex flex-wrap gap-1.5">
                            <?php foreach ($constructorBonusUsers as $cu): ?>
                            <span class="user-chip" style="background:rgba(168,85,247,0.1);border-color:rgba(168,85,247,0.3);color:#c084fc;">@<?php echo htmlspecialchars($cu['username']); ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- ── NEXT RACE: JAPAN GP INTEL ─────────────────────── -->
            <?php if ($nextRace): ?>
            <div class="feed-card next-race-glow p-0 overflow-hidden">
                <div class="bg-gradient-to-r from-slate-900 via-orange-950/20 to-slate-900 px-8 py-5 border-b border-orange-500/10">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div class="text-4xl"><?php echo getRaceFlag($nextRace['country']); ?></div>
                            <div>
                                <div class="section-label mb-1 text-orange-400"><i class="fas fa-chevron-right mr-1"></i>Up Next</div>
                                <h2 class="text-xl font-black text-white italic uppercase"><?php echo htmlspecialchars($nextRace['race_name']); ?></h2>
                                <div class="text-xs text-gray-500 mt-0.5"><?php echo date('d M Y', strtotime($nextRace['race_date'])); ?> · Round <?php echo str_pad($nextRace['id'], 2, '0', STR_PAD_LEFT); ?></div>
                            </div>
                        </div>
                        <a href="predict.php?race_id=<?php echo $nextRace['id']; ?>" 
                           class="px-5 py-2.5 bg-orange-500 hover:bg-orange-400 text-white font-black text-xs rounded-xl transition transform hover:scale-105 shadow-lg shadow-orange-500/20 whitespace-nowrap">
                            <i class="fas fa-lock-open mr-1"></i> Lock In Grid
                        </a>
                    </div>
                </div>

                <div class="p-8 space-y-5">
                    <!-- Key facts -->
                    <div class="grid grid-cols-3 gap-3">
                        <div class="bg-white/3 border border-white/5 rounded-xl p-4 text-center">
                            <div class="text-xl font-black text-white">🏯</div>
                            <div class="text-[10px] text-gray-400 font-bold mt-2">Suzuka Circuit</div>
                            <div class="text-[9px] text-gray-600 uppercase mt-0.5">5.807 km</div>
                        </div>
                        <div class="bg-white/3 border border-white/5 rounded-xl p-4 text-center">
                            <div class="text-xl font-black text-white">53</div>
                            <div class="text-[10px] text-gray-400 font-bold mt-2">Race Laps</div>
                            <div class="text-[9px] text-gray-600 uppercase mt-0.5">Standard Points</div>
                        </div>
                        <div class="bg-white/3 border border-white/5 rounded-xl p-4 text-center">
                            <div class="text-xl font-black text-white">📅</div>
                            <div class="text-[10px] text-gray-400 font-bold mt-2"><?php echo date('d M', strtotime($nextRace['race_date'])); ?></div>
                            <div class="text-[9px] text-gray-600 uppercase mt-0.5">Race Day</div>
                        </div>
                    </div>

                    <!-- Intel Bulletin -->
                    <div class="bg-orange-500/5 border border-orange-500/15 rounded-xl p-5">
                        <div class="section-label text-orange-400 mb-3"><i class="fas fa-satellite-dish mr-1"></i>Race Intelligence</div>
                        <div class="space-y-3 text-sm text-gray-300 leading-relaxed">
                            <div class="flex gap-3">
                                <div class="tl-dot bg-orange-500 mt-1"></div>
                                <p>Suzuka is a <strong class="text-white">driver's circuit</strong> — high-speed technical corners that reward mechanical grip and aero efficiency. Expect the <strong class="text-white">aerodynamic package</strong> results from China to be a strong predictor.</p>
                            </div>
                            <div class="flex gap-3">
                                <div class="tl-dot bg-blue-400 mt-1"></div>
                                <p>The famous <strong class="text-white">130R corner</strong> and the notorious Spoon Curve will separate true pace from bravado this weekend.</p>
                            </div>
                            <div class="flex gap-3">
                                <div class="tl-dot bg-green-400 mt-1"></div>
                                <p>Predictions are <strong class="text-white">open now</strong>. Don't get caught off the grid — submit before Saturday qualifying!</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- ── SEASON TIMELINE ────────────────────────────────── -->
            <div class="feed-card p-8">
                <div class="section-label mb-6"><i class="fas fa-calendar-alt mr-1"></i>Season Progress</div>
                <div class="flex items-center gap-3 mb-6">
                    <div class="flex-1 bg-white/5 rounded-full h-3 overflow-hidden">
                        <div class="h-full bg-gradient-to-r from-orange-600 to-orange-400 rounded-full transition-all duration-1000"
                             style="width: <?php echo $totalRacesCount > 0 ? round(($completedRacesCount / $totalRacesCount) * 100) : 0; ?>%"></div>
                    </div>
                    <span class="text-xs font-black text-white whitespace-nowrap"><?php echo $completedRacesCount; ?> / <?php echo $totalRacesCount; ?> Rounds</span>
                </div>
                <?php
                $allRaces = $db->query("SELECT id, race_name, country, race_date, status FROM races ORDER BY race_date ASC LIMIT 8")->fetch_all(MYSQLI_ASSOC);
                ?>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                    <?php foreach ($allRaces as $r):
                        $isDone = $r['status'] === 'completed';
                        $isNext = $nextRace && $r['id'] === $nextRace['id'];
                        $flag   = getRaceFlag($r['country']);
                    ?>
                    <div class="p-3 rounded-xl border text-center transition <?php
                        echo $isDone ? 'bg-green-500/5 border-green-500/20' : ($isNext ? 'bg-orange-500/10 border-orange-500/30' : 'bg-white/2 border-white/5')
                    ?>">
                        <div class="text-xl mb-1"><?php echo $flag; ?></div>
                        <div class="text-[10px] font-black text-white truncate"><?php echo htmlspecialchars($r['country']); ?></div>
                        <div class="text-[9px] font-bold mt-1 <?php echo $isDone ? 'text-green-400' : ($isNext ? 'text-orange-400' : 'text-gray-600'); ?>">
                            <?php echo $isDone ? '✅ Done' : ($isNext ? '🔜 Next' : date('d M', strtotime($r['race_date']))); ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

        </div><!-- /col-span-2 -->

        <!-- ═══════════════════════════════════════════════════════ -->
        <!-- RIGHT COLUMN -->
        <!-- ═══════════════════════════════════════════════════════ -->
        <div class="space-y-6">

            <!-- ── MY STATS ──────────────────────────────────────── -->
            <div class="feed-card p-6">
                <div class="section-label mb-4"><i class="fas fa-user-astronaut mr-1"></i>Your Season</div>
                <div class="flex items-center gap-4 mb-5 p-4 bg-white/3 rounded-xl border border-white/5">
                    <img src="<?php echo getAvatarUrl($user['avatar_style'] ?? 'avataaars', $user['username']); ?>" 
                         alt="" class="w-12 h-12 rounded-full object-cover border-2 border-orange-500/40">
                    <div>
                        <div class="font-black text-white">@<?php echo htmlspecialchars($user['username']); ?></div>
                        <div class="text-xs text-gray-500 font-bold uppercase tracking-wide">Race Engineer</div>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div class="bg-black/30 rounded-xl p-4 text-center scanline relative overflow-hidden">
                        <div class="text-3xl font-black text-orange-400"><?php echo number_format($myStats['total_points']); ?></div>
                        <div class="text-[9px] text-gray-500 uppercase font-bold mt-1">Total Points</div>
                    </div>
                    <div class="bg-black/30 rounded-xl p-4 text-center scanline relative overflow-hidden">
                        <div class="text-3xl font-black text-white">P<?php echo $myStats['rank']; ?></div>
                        <div class="text-[9px] text-gray-500 uppercase font-bold mt-1">Global Rank</div>
                    </div>
                </div>
                <?php if ($nextRace): ?>
                <a href="predict.php?race_id=<?php echo $nextRace['id']; ?>" 
                   class="mt-4 flex items-center justify-center gap-2 w-full py-3 bg-orange-500 hover:bg-orange-400 text-white font-black text-xs rounded-xl transition transform hover:scale-105">
                    <i class="fas fa-pencil-alt"></i> Submit <?php echo htmlspecialchars($nextRace['country']); ?> Prediction
                </a>
                <?php endif; ?>
            </div>

            <!-- ── PREDICTION TRACKER: NEXT RACE ────────────────── -->
            <?php if ($nextRace): ?>
            <div class="feed-card p-6 border-t-4 border-t-green-500">
                <div class="flex items-center justify-between mb-1">
                    <div class="section-label"><i class="fas fa-satellite mr-1"></i>Live Submission Tracker</div>
                    <div class="flex items-center gap-1.5">
                        <div class="live-dot"></div>
                        <span class="text-[9px] font-black text-orange-400 uppercase">Live</span>
                    </div>
                </div>
                <p class="text-xs text-gray-500 mb-5"><?php echo htmlspecialchars($nextRace['race_name']); ?> Predictions</p>

                <!-- Progress bar -->
                <div class="mb-5">
                    <div class="flex justify-between text-[10px] font-black text-gray-400 mb-2">
                        <span><?php echo count($submitted); ?> locked in</span>
                        <span><?php echo $submissionPct; ?>%</span>
                    </div>
                    <div class="sub-bar-track">
                        <div class="sub-bar-fill" style="width: <?php echo $submissionPct; ?>%"></div>
                    </div>
                    <div class="text-[9px] text-gray-600 mt-1.5"><?php echo count($missing); ?> still pending out of <?php echo $totalUsers; ?> engineers</div>
                </div>

                <!-- Submitted -->
                <?php if (!empty($submitted)): ?>
                <div class="mb-4">
                    <div class="text-[9px] font-black text-green-400 uppercase tracking-widest mb-2">✅ Ready on Grid (<?php echo count($submitted); ?>)</div>
                    <div class="flex flex-wrap gap-1.5">
                        <?php foreach ($submitted as $s): ?>
                        <span class="user-chip chip-green">
                            @<?php echo htmlspecialchars($s['username']); ?>
                        </span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Missing -->
                <?php if (!empty($missing)): ?>
                <div>
                    <div class="text-[9px] font-black text-red-400 uppercase tracking-widest mb-2 flex items-center gap-1.5">
                        <span class="animate-pulse">●</span> Still in the Pits (<?php echo count($missing); ?>)
                    </div>
                    <div class="flex flex-wrap gap-1.5">
                        <?php foreach ($missing as $m): ?>
                        <span class="user-chip chip-red">
                            @<?php echo htmlspecialchars($m['username']); ?>
                        </span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- ── CHAMPIONSHIP LEADERBOARD ──────────────────────── -->
            <div class="feed-card p-6">
                <div class="section-label mb-4"><i class="fas fa-trophy mr-1 text-yellow-500"></i>Championship Standings</div>
                <div class="space-y-2">
                    <?php foreach ($leaderboard as $i => $l):
                        $isMe = $l['id'] == $userId;
                        $rankBg = match($i) {
                            0 => 'bg-yellow-400/10 border-yellow-400/30',
                            1 => 'bg-gray-300/5 border-gray-300/15',
                            2 => 'bg-amber-700/10 border-amber-700/30',
                            default => 'bg-white/2 border-white/5'
                        };
                    ?>
                    <div class="flex items-center gap-3 p-3 rounded-xl border <?php echo $rankBg; ?> <?php echo $isMe ? 'ring-1 ring-orange-500/50' : ''; ?>">
                        <div class="w-7 h-7 rounded-lg <?php echo ['medal-1','medal-2','medal-3','bg-white/10 text-gray-400','bg-white/10 text-gray-400'][$i]; ?> flex items-center justify-center font-black text-xs flex-shrink-0">
                            <?php echo ($i+1); ?>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="text-sm font-bold text-white truncate">
                                @<?php echo htmlspecialchars($l['username']); ?>
                                <?php if ($isMe): ?><span class="text-[9px] text-orange-400 font-black ml-1">YOU</span><?php endif; ?>
                            </div>
                            <div class="text-[9px] text-gray-500"><?php echo $l['races_participated'] ?? 0; ?> races</div>
                        </div>
                        <div class="text-sm font-black text-white flex-shrink-0"><?php echo number_format($l['total_points'] ?? 0); ?> <span class="text-[9px] text-gray-500 font-normal">pts</span></div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <a href="leaderboard.php" class="mt-4 flex items-center justify-center gap-2 text-xs font-black text-gray-400 hover:text-white transition py-2 border border-white/5 rounded-xl hover:border-white/15 hover:bg-white/3">
                    Full Standings <i class="fas fa-chevron-right text-[10px]"></i>
                </a>
            </div>

            <!-- ── QUICK LINKS ─────────────────────────────────────── -->
            <div class="feed-card p-6">
                <div class="section-label mb-4"><i class="fas fa-bolt mr-1"></i>Paddock Quick Access</div>
                <div class="space-y-2">
                    <a href="achievements.php" class="flex items-center gap-3 p-3 rounded-xl bg-white/3 border border-white/5 hover:border-purple-500/30 hover:bg-purple-500/5 transition group">
                        <div class="w-8 h-8 bg-purple-500/20 rounded-lg flex items-center justify-center text-purple-400 group-hover:scale-110 transition"><i class="fas fa-medal text-sm"></i></div>
                        <div>
                            <div class="text-sm font-bold text-white">Achievements</div>
                            <div class="text-[9px] text-gray-500">Unlock badges & trophies</div>
                        </div>
                        <i class="fas fa-chevron-right text-gray-600 text-xs ml-auto"></i>
                    </a>
                    <?php if ($lastRace): ?>
                    <a href="race-results.php?race_id=<?php echo $lastRace['id']; ?>" class="flex items-center gap-3 p-3 rounded-xl bg-white/3 border border-white/5 hover:border-green-500/30 hover:bg-green-500/5 transition group">
                        <div class="w-8 h-8 bg-green-500/20 rounded-lg flex items-center justify-center text-green-400 group-hover:scale-110 transition"><i class="fas fa-chart-bar text-sm"></i></div>
                        <div>
                            <div class="text-sm font-bold text-white">My China Results</div>
                            <div class="text-[9px] text-gray-500">View your prediction vs reality</div>
                        </div>
                        <i class="fas fa-chevron-right text-gray-600 text-xs ml-auto"></i>
                    </a>
                    <?php endif; ?>
                    <a href="points-system.php" class="flex items-center gap-3 p-3 rounded-xl bg-white/3 border border-white/5 hover:border-blue-500/30 hover:bg-blue-500/5 transition group">
                        <div class="w-8 h-8 bg-blue-500/20 rounded-lg flex items-center justify-center text-blue-400 group-hover:scale-110 transition"><i class="fas fa-calculator text-sm"></i></div>
                        <div>
                            <div class="text-sm font-bold text-white">Points System</div>
                            <div class="text-[9px] text-gray-500">Scoring rules explained</div>
                        </div>
                        <i class="fas fa-chevron-right text-gray-600 text-xs ml-auto"></i>
                    </a>
                </div>
            </div>

        </div><!-- /right column -->
    </div><!-- /grid -->

    <!-- ── SIGN-OFF ────────────────────────────────────────────────────── -->
    <div class="mt-10 flex flex-col md:flex-row items-center gap-8 p-8 rounded-[2rem] bg-slate-900/40 border border-white/5 border-b-4 border-b-orange-500">
        <div class="w-16 h-16 rounded-2xl bg-orange-500/20 flex items-center justify-center text-orange-500 text-2xl shadow-2xl border border-orange-500/30 flex-shrink-0">
            <i class="fas fa-user-tie"></i>
        </div>
        <div>
            <p class="text-gray-400 italic leading-relaxed text-sm">
                "Races are fully underway — <strong class="text-white">Japan is next on the calendar.</strong> 
                Suzuka separates the tacticians from the gamblers. Get your predictions locked in early, 
                study the China form guide, and don't sleep on the constructor bonus. See you on the grid. 🏁"
            </p>
            <div class="mt-3 font-black italic text-white uppercase text-sm leading-none">
                Aurimas <span class="text-orange-500 font-light ml-2">Race Controller</span>
            </div>
            <div class="text-[9px] text-gray-600 font-black uppercase tracking-[0.4em] mt-1">Scanerrific Paddock Picks · Competition Bureau</div>
        </div>
    </div>

</main>

<footer class="mt-10 border-t border-white/5 py-8 text-center">
    <p class="text-gray-600 text-xs font-bold uppercase tracking-widest">© <?php echo date('Y'); ?> Paddock Picks · Powered by <a href="https://www.scanerrific.com" target="_blank" class="text-orange-500 hover:text-orange-400 transition">Scanerrific</a></p>
</footer>

</body>
</html>
