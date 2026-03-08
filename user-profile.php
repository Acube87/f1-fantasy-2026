<?php
require_once __DIR__ . '/includes/maintenance-gate.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'includes/avatars.php';

$user = getCurrentUser();
if (!$user) {
    header('Location: login.php');
    exit;
}

$profileUserId = $_GET['user_id'] ?? null;
if (!$profileUserId || !is_numeric($profileUserId)) {
    header('Location: leaderboard.php');
    exit;
}

// If viewing own profile, redirect to dashboard
if ($profileUserId == $user['id']) {
    header('Location: dashboard.php');
    exit;
}

$db = getDB();

// Get profile user data
$stmt = $db->prepare("SELECT id, username, email, full_name, avatar_style, created_at FROM users WHERE id = ?");
$stmt->bind_param("i", $profileUserId);
$stmt->execute();
$profileUser = $stmt->get_result()->fetch_assoc();

if (!$profileUser) {
    header('Location: leaderboard.php');
    exit;
}

// Get profile user's stats
$stats = getUserStats($profileUserId);
$totalPoints = $stats['total_points'] ?? 0;
$racesParticipated = $stats['races_participated'] ?? 0;
$rank = $stats['rank'] ?? '-';

// Get Recent Results (Last 5 races for profile user)
$recentResults = [];
$stmt = $db->prepare("
    SELECT r.id as race_id, r.race_name, r.country, r.status, s.total_points, r.race_date 
    FROM scores s 
    JOIN races r ON s.race_id = r.id 
    WHERE s.user_id = ? 
    ORDER BY r.race_date DESC 
    LIMIT 5
");
$stmt->bind_param("i", $profileUserId);
$stmt->execute();
$recentResults = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Calculate Prediction Accuracy for profile user
$accuracyStmt = $db->prepare("
    SELECT 
        COUNT(*) as total_predictions,
        SUM(CASE WHEN p.predicted_position = res.position THEN 1 ELSE 0 END) as exact_matches
    FROM predictions p
    LEFT JOIN race_results res ON p.race_id = res.race_id AND p.driver_id = res.driver_id
    WHERE p.user_id = ? AND res.position IS NOT NULL
");
$accuracyStmt->bind_param("i", $profileUserId);
$accuracyStmt->execute();
$accuracyResult = $accuracyStmt->get_result()->fetch_assoc();
$totalPredictionsMade = $accuracyResult['total_predictions'] ?? 0;
$exactMatches = $accuracyResult['exact_matches'] ?? 0;
$accuracy = $totalPredictionsMade > 0 ? ($exactMatches / $totalPredictionsMade) * 100 : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($profileUser['username']); ?>'s Profile - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="css/gaming-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="gaming-theme text-gray-200">

    <!-- Navbar -->
    <nav class="g-nav fixed w-full z-50 px-6 py-4 flex justify-between items-center">
        <div class="flex items-center gap-8">
            <a href="index.php" class="flex items-center gap-4 hover:opacity-80 transition group">
                <div class="w-10 h-10 bg-gradient-to-br from-red-600 to-orange-500 rounded-xl flex items-center justify-center shadow-lg shadow-orange-500/20 group-hover:scale-105 transition-transform duration-300">
                    <i class="fas fa-flag-checkered text-white text-lg"></i>
                </div>
                <span class="font-bold text-xl tracking-wide text-white hidden sm:block group-hover:text-orange-400 transition-colors">PADDOCK PICKS</span>
            </a>
            
            <div class="hidden md:flex items-center gap-6">
                <a href="dashboard.php" class="text-gray-300 hover:text-white font-bold text-sm uppercase tracking-wide transition flex items-center gap-2">
                    <i class="fas fa-home text-orange-500/80"></i> Dashboard
                </a>
                <a href="updates.php" class="text-gray-300 hover:text-white font-bold text-sm uppercase tracking-wide transition flex items-center gap-2 relative">
                    <i class="fas fa-broadcast-tower text-orange-400"></i> Race Updates
                    <span class="absolute -top-1 -right-2 w-2 h-2 bg-orange-500 rounded-full animate-pulse border border-orange-950"></span>
                </a>
                <a href="leaderboard.php" class="text-white font-bold text-sm uppercase tracking-wide transition flex items-center gap-2 border-b-2 border-yellow-500 pb-1">
                    <i class="fas fa-trophy text-yellow-500"></i> Leaderboard
                </a>
                <a href="achievements.php" class="text-gray-300 hover:text-white font-bold text-sm uppercase tracking-wide transition flex items-center gap-2">
                    <i class="fas fa-medal text-purple-500/80"></i> Achievements
                </a>
            </div>
        </div>
        
        <div class="flex items-center gap-6">
            <div class="flex items-center gap-3 pl-6 border-l border-white/10">
                <div class="text-right hidden sm:block">
                    <div class="text-xs text-gray-400 font-bold uppercase tracking-wider">Driver</div>
                    <div class="text-sm font-bold text-white leading-none"><?php echo htmlspecialchars($user['username']); ?></div>
                </div>
                <a href="profile.php" class="w-10 h-10 rounded-full bg-slate-700 border-2 border-white/10 overflow-hidden hover:border-orange-500 transition cursor-pointer relative group shadow-lg shadow-black/50">
                     <img src="<?php echo getAvatarUrl($user['avatar_style'] ?? 'avataaars', $user['username']); ?>" alt="Avatar" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                </a>
            </div>
            <a href="logout.php" class="text-gray-400 hover:text-white transition hover:rotate-90 duration-300" title="Sign Out">
                <i class="fas fa-sign-out-alt text-lg"></i>
            </a>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="pt-24 pb-12 px-4 md:px-8 max-w-4xl mx-auto">
        
        <!-- Profile Header -->
        <div class="g-card p-8 mb-8">
            <div class="flex items-center gap-6 mb-6">
                <img src="<?php echo getAvatarUrl($profileUser['avatar_style'] ?? 'avataaars', $profileUser['username']); ?>" class="w-20 h-20 rounded-full bg-slate-700 border-4 border-orange-500">
                <div>
                    <h1 class="text-3xl font-black text-white mb-2"><?php echo htmlspecialchars($profileUser['username']); ?></h1>
                    <?php if (!empty($profileUser['full_name'])): ?>
                        <p class="text-gray-400 mb-2"><?php echo htmlspecialchars($profileUser['full_name']); ?></p>
                    <?php endif; ?>
                    <div class="flex items-center gap-4 text-sm">
                        <span class="text-gray-500">Joined <?php echo date('M Y', strtotime($profileUser['created_at'])); ?></span>
                        <span class="text-orange-400 font-bold">Rank #<?php echo $rank; ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Stats Row -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="text-center">
                    <div class="text-2xl font-black text-orange-400"><?php echo number_format($totalPoints); ?></div>
                    <div class="text-xs text-gray-500 uppercase tracking-wider">Total Points</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-black text-blue-400"><?php echo $racesParticipated; ?></div>
                    <div class="text-xs text-gray-500 uppercase tracking-wider">Races</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-black text-green-400"><?php echo $racesParticipated > 0 ? round($totalPoints / $racesParticipated, 1) : 0; ?></div>
                    <div class="text-xs text-gray-500 uppercase tracking-wider">Avg Points</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-black text-purple-400"><?php echo round($accuracy, 1); ?>%</div>
                    <div class="text-xs text-gray-500 uppercase tracking-wider">Accuracy</div>
                </div>
            </div>
        </div>
        
        <!-- Recent Race Results -->
        <div class="g-card p-8">
            <h2 class="text-2xl font-black text-white mb-6 flex items-center gap-3">
                <i class="fas fa-history text-orange-500"></i> Recent Race Results
            </h2>
            
            <?php if (empty($recentResults)): ?>
                <p class="text-gray-500 text-center py-8">No race results yet.</p>
            <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($recentResults as $result): ?>
                        <div class="flex items-center justify-between p-4 bg-slate-800/50 rounded-xl border border-white/5">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-red-600 to-orange-500 flex items-center justify-center">
                                    <i class="fas fa-flag-checkered text-white"></i>
                                </div>
                                <div>
                                    <?php if ($result['status'] === 'completed'): ?>
                                        <a href="race-results.php?user_id=<?php echo $profileUserId; ?>&race_id=<?php echo $result['race_id']; ?>" class="font-bold text-white hover:text-orange-400 transition-colors">
                                            <?php echo htmlspecialchars($result['race_name']); ?>
                                        </a>
                                    <?php else: ?>
                                        <div class="font-bold text-white"><?php echo htmlspecialchars($result['race_name']); ?></div>
                                    <?php endif; ?>
                                    <div class="text-sm text-gray-400"><?php echo htmlspecialchars($result['country']); ?> • <?php echo date('M d, Y', strtotime($result['race_date'])); ?></div>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-xl font-black text-orange-400"><?php echo number_format($result['total_points']); ?></div>
                                <div class="text-xs text-gray-500">points</div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Back to Leaderboard -->
        <div class="text-center mt-8">
            <a href="leaderboard.php" class="g-btn g-btn-blue px-8 py-3">
                <i class="fas fa-arrow-left mr-2"></i> Back to Leaderboard
            </a>
        </div>
    </main>
    
    <footer class="mt-12 border-t border-white/10 py-6 text-center">
        <p class="text-gray-500 text-sm">&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</p>
    </footer>

</body>
</html>