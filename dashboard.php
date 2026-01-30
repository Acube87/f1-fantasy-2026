<?php
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$user = getCurrentUser();
if (!$user) {
    header('Location: index.php');
    exit;
}

$db = getDB();
$userId = $user['id'];

// Get User Stats (Points, Rank)
$stats = getUserStats($userId);
$totalPoints = $stats['total_points'] ?? 0;
$rank = $stats['rank'] ?? '-';
$rankSuffix = match($rank) {
    1 => 'st', 2 => 'nd', 3 => 'rd', default => 'th'
};
if (!is_numeric($rank)) $rankSuffix = '';

// Get Next Race
$nextRace = getNextRace();

// Get Leaderboard (Top 5)
$leaderboard = getLeaderboard(5);

// Get Recent Results (Last 3)
$recentResults = [];
$stmt = $db->prepare("
    SELECT r.race_name, r.country, s.total_score, r.race_date 
    FROM scores s 
    JOIN races r ON s.race_id = r.id 
    WHERE s.user_id = ? 
    ORDER BY r.race_date DESC 
    LIMIT 3
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$recentResults = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="css/gaming-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="gaming-theme text-gray-200">

    <!-- Navbar -->
    <nav class="g-nav fixed w-full z-50 px-6 py-4 flex justify-between items-center">
        <div class="flex items-center gap-4">
            <div class="w-10 h-10 bg-gradient-to-br from-red-600 to-orange-500 rounded-xl flex items-center justify-center shadow-lg shadow-orange-500/20">
                <i class="fas fa-flag-checkered text-white text-lg"></i>
            </div>
            <span class="font-bold text-xl tracking-wide text-white">PADDOCK PICKS</span>
        </div>
        
        <div class="flex items-center gap-6">
            <!-- Wallet / Points Pill -->
            <div class="g-stat-pill hidden md:flex">
                <div class="g-icon-circle bg-blue-500/20 text-blue-400">
                    <i class="fas fa-trophy"></i>
                </div>
                <div class="flex flex-col leading-none">
                    <span class="text-[10px] text-gray-400 uppercase font-bold">Points</span>
                    <span class="font-bold text-white"><?php echo number_format($totalPoints); ?></span>
                </div>
            </div>

            <!-- User Menu -->
            <div class="flex items-center gap-3 pl-6 border-l border-white/10">
                <div class="text-right hidden sm:block">
                    <div class="text-sm font-bold text-white"><?php echo htmlspecialchars($user['username']); ?></div>
                    <div class="text-[10px] text-green-400 font-bold">LEVEL <?php echo floor($totalPoints / 100) + 1; ?></div>
                </div>
                <div class="w-10 h-10 rounded-full bg-slate-700 border-2 border-white/10 overflow-hidden">
                    <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=<?php echo $user['username']; ?>" alt="Avatar" class="w-full h-full"> 
                </div>
                <a href="logout.php" class="text-gray-400 hover:text-white transition ml-2">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="pt-24 pb-12 px-4 md:px-8 max-w-7xl mx-auto">
        
        <!-- Header / Welcome -->
        <div class="mb-10 flex flex-col md:flex-row justify-between items-end gap-4">
            <div>
                <h1 class="text-3xl md:text-5xl font-black text-white mb-2 uppercase italic">
                    Ready to <span class="g-text-gradient">Race?</span>
                </h1>
                <p class="text-gray-400">Round <?php echo $nextRace ? $nextRace['race_number'] : '-'; ?> is approaching fast.</p>
            </div>
            
            <?php if ($nextRace): ?>
            <a href="predict.php?race_id=<?php echo $nextRace['id']; ?>" class="g-btn g-btn-orange px-8 py-4 text-lg flex items-center gap-3 animate-pulse">
                <i class="fas fa-gamepad"></i> Make Prediction
            </a>
            <?php endif; ?>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            
            <!-- LEFT COLUMN (Main Stats & Next Race) -->
            <div class="lg:col-span-8 space-y-8">
                
                <!-- NEXT RACE CARD (The "Car" Card) -->
                <div class="g-card p-0 relative group h-96 flex flex-col justify-end overflow-hidden">
                    <!-- Background Image (Dynamic based on country if possible, or generic) -->
                    <div class="absolute inset-0 bg-[url('https://images.unsplash.com/photo-1541336528065-8f1fdc435835?q=80&w=2070&auto=format&fit=crop')] bg-cover bg-center transition duration-700 group-hover:scale-110"></div>
                    <div class="absolute inset-0 bg-gradient-to-t from-[#0f172a] via-[#0f172a]/70 to-transparent"></div>
                    
                    <div class="relative z-10 p-8">
                        <?php if ($nextRace): ?>
                            <div class="flex items-center gap-3 mb-3">
                                <span class="bg-red-600 text-white text-xs font-bold px-3 py-1 rounded-full uppercase tracking-wider">
                                    Next Event
                                </span>
                                <span class="text-orange-400 font-mono font-bold">
                                    <i class="far fa-clock"></i> <?php echo date('M d', strtotime($nextRace['race_date'])); ?>
                                </span>
                            </div>
                            <h2 class="text-4xl md:text-5xl font-black text-white mb-2 uppercase">
                                <?php echo htmlspecialchars($nextRace['country']); ?>
                            </h2>
                            <p class="text-lg text-gray-300 mb-6 font-medium">
                                <?php echo htmlspecialchars($nextRace['circuit_name']); ?>
                            </p>
                            
                            <!-- Progress/Bet Bar Style -->
                            <div class="flex items-center gap-4 bg-black/40 backdrop-blur-md p-4 rounded-xl border border-white/5 max-w-lg">
                                <div class="flex-1">
                                    <div class="flex justify-between text-xs mb-2 font-bold text-gray-400 uppercase">
                                        <span>Prediction Status</span>
                                        <span class="text-green-400">OPEN</span>
                                    </div>
                                    <div class="h-2 bg-gray-700 rounded-full overflow-hidden">
                                        <div class="h-full bg-gradient-to-r from-orange-500 to-red-600 w-3/4"></div>
                                    </div>
                                </div>
                                <a href="predict.php?race_id=<?php echo $nextRace['id']; ?>" class="g-btn g-btn-blue px-6 py-2 text-sm">
                                    ENTER
                                </a>
                            </div>
                        <?php else: ?>
                            <h2 class="text-3xl font-bold text-white">Season Completed</h2>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- STATS GRID (Coins/Items style) -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <!-- Rank Card -->
                    <div class="g-card p-5 g-border-glow-orange flex flex-col items-center justify-center text-center">
                        <div class="w-12 h-12 rounded-full bg-orange-500/20 text-orange-500 flex items-center justify-center text-xl mb-3 shadow-[0_0_15px_rgba(249,115,22,0.3)]">
                            <i class="fas fa-crown"></i>
                        </div>
                        <div class="text-3xl font-black text-white italic">
                            #<?php echo $rank; ?>
                        </div>
                        <div class="text-xs text-gray-400 uppercase font-bold tracking-wider mt-1">Global Rank</div>
                    </div>

                    <!-- Points Card -->
                    <div class="g-card p-5 g-border-glow-blue flex flex-col items-center justify-center text-center">
                        <div class="w-12 h-12 rounded-full bg-blue-500/20 text-blue-400 flex items-center justify-center text-xl mb-3 shadow-[0_0_15px_rgba(59,130,246,0.3)]">
                            <i class="fas fa-coins"></i>
                        </div>
                        <div class="text-3xl font-black text-white italic">
                            <?php echo $totalPoints; ?>
                        </div>
                        <div class="text-xs text-gray-400 uppercase font-bold tracking-wider mt-1">Total Points</div>
                    </div>

                    <!-- Avg Points (Mockup) -->
                    <div class="g-card p-5 flex flex-col items-center justify-center text-center opacity-75">
                        <div class="w-12 h-12 rounded-full bg-purple-500/20 text-purple-400 flex items-center justify-center text-xl mb-3">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="text-3xl font-black text-white italic">--</div>
                        <div class="text-xs text-gray-400 uppercase font-bold tracking-wider mt-1">Avg Score</div>
                    </div>

                    <!-- Win Rate (Mockup) -->
                    <div class="g-card p-5 flex flex-col items-center justify-center text-center opacity-75">
                        <div class="w-12 h-12 rounded-full bg-green-500/20 text-green-400 flex items-center justify-center text-xl mb-3">
                            <i class="fas fa-bullseye"></i>
                        </div>
                        <div class="text-3xl font-black text-white italic">--%</div>
                        <div class="text-xs text-gray-400 uppercase font-bold tracking-wider mt-1">Accuracy</div>
                    </div>
                </div>

                <!-- RECENT DROPS (History) -->
                <div>
                    <h3 class="text-white font-bold text-lg mb-4 flex items-center gap-2">
                        <i class="fas fa-history text-gray-500"></i> Recent Results
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <?php if (empty($recentResults)): ?>
                            <div class="col-span-3 text-center py-8 text-gray-500 g-card">
                                No race history yet. Start predicting!
                            </div>
                        <?php else: ?>
                            <?php foreach ($recentResults as $res): ?>
                            <div class="g-card p-4 flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-lg bg-gray-800 flex items-center justify-center text-gray-400">
                                        <i class="fas fa-flag"></i>
                                    </div>
                                    <div>
                                        <div class="text-sm font-bold text-white"><?php echo htmlspecialchars($res['country']); ?></div>
                                        <div class="text-[10px] text-gray-500"><?php echo date('M d', strtotime($res['race_date'])); ?></div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-lg font-bold text-green-400">+<?php echo $res['total_score']; ?></div>
                                    <div class="text-[10px] text-gray-500 font-bold uppercase">Points</div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
            
            <!-- RIGHT COLUMN (Leaderboard / "Daily Race") -->
            <div class="lg:col-span-4 space-y-6">
                
                <div class="g-card p-6 h-full border-t-4 border-t-orange-500">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="font-bold text-white text-lg flex items-center gap-2">
                            <i class="fas fa-trophy text-orange-500"></i> TOP RACERS
                        </h3>
                        <span class="bg-orange-500/10 text-orange-500 text-[10px] px-2 py-1 rounded font-bold uppercase">Global</span>
                    </div>

                    <div class="space-y-3">
                        <?php foreach ($leaderboard as $idx => $player): 
                            $isMe = ($player['username'] === $user['username']);
                            $rowClass = $isMe ? 'bg-orange-500/10 border-orange-500/30' : 'bg-white/5 border-transparent hover:bg-white/10';
                            $rankColor = match($idx + 1) {
                                1 => 'text-yellow-400',
                                2 => 'text-gray-300',
                                3 => 'text-amber-600',
                                default => 'text-gray-500'
                            };
                        ?>
                        <div class="flex items-center gap-3 p-3 rounded-xl border <?php echo $rowClass; ?> transition-all group cursor-pointer">
                            <div class="font-black text-lg w-6 text-center <?php echo $rankColor; ?>">
                                <?php echo $idx + 1; ?>
                            </div>
                            <div class="w-8 h-8 rounded-full bg-slate-700 overflow-hidden">
                                <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=<?php echo $player['username']; ?>" alt="Avatar">
                            </div>
                            <div class="flex-1">
                                <div class="text-sm font-bold text-white group-hover:text-orange-400 transition">
                                    <?php echo htmlspecialchars($player['username']); ?>
                                </div>
                                <div class="text-[10px] text-gray-500">Level <?php echo floor($player['total_points'] / 100) + 1; ?></div>
                            </div>
                            <div class="text-right">
                                <div class="font-mono font-bold text-blue-400"><?php echo $player['total_points']; ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="mt-6 pt-6 border-t border-white/5 text-center">
                        <a href="leaderboard.php" class="g-btn g-btn-blue w-full py-3 block text-center text-sm">
                            View Full Standings
                        </a>
                    </div>
                </div>

                <!-- Mini Chat / Updates (Visual Mockup) -->
                <div class="g-card p-6 relative overflow-hidden">
                    <div class="absolute top-0 right-0 p-4 opacity-10 text-6xl text-white">
                        <i class="fas fa-comment-alt"></i>
                    </div>
                    <h3 class="font-bold text-white text-md mb-4">Paddock Chat</h3>
                    <div class="space-y-4">
                        <div class="flex gap-3">
                            <div class="w-8 h-8 rounded-full bg-pink-500 flex-shrink-0"></div>
                            <div class="bg-white/5 p-3 rounded-r-xl rounded-bl-xl text-xs text-gray-300">
                                <span class="font-bold text-pink-400 block mb-1">System</span>
                                Welcome to the 2026 Season! Don't forget to lock in your predictions for Melbourne.
                            </div>
                        </div>
                    </div>
                    <div class="mt-4">
                        <input type="text" placeholder="Type a message..." class="w-full bg-black/30 border border-white/10 rounded-lg px-3 py-2 text-xs text-white focus:border-orange-500 outline-none transition">
                    </div>
                </div>

            </div>
        
        </div>
        
        <!-- Footer info matches others -->
        <footer class="mt-12 border-t border-white/10 py-6 text-center">
            <p class="text-gray-500 text-sm mb-2">&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</p>
            <p class="text-gray-600 text-xs">
                Powered by <a href="https://www.scanerrific.com" target="_blank" class="text-orange-500 hover:text-orange-400 font-semibold transition">Scanerrific</a>
            </p>
        </footer>

    </main>

</body>
</html>
