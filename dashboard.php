<?php
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

// AUTO-FIX: Ensure avatar_style column exists
try {
    $checkCol = $db->query("SHOW COLUMNS FROM users LIKE 'avatar_style'");
    if ($checkCol->num_rows == 0) {
        $db->query("ALTER TABLE users ADD COLUMN avatar_style VARCHAR(100) DEFAULT 'avataaars' AFTER email");
    }
} catch (Exception $e) {
    // Continue if error
}

// Get User Stats (Points, Rank)
$stats = getUserStats($userId);
$totalPoints = $stats['total_points'] ?? 0;
$racesParticipated = $stats['races_participated'] ?? 0;
$level = $racesParticipated; // Level = Number of races participated
$rank = $stats['rank'] ?? '-';
$rankSuffix = match($rank) {
    1 => 'st', 2 => 'nd', 3 => 'rd', default => 'th'
};
if (!is_numeric($rank)) $rankSuffix = '';

// Calculate Prediction Accuracy
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

// Get Next Race
$nextRace = getNextRace();

// Check if predictions are open for next race
$predictionsOpen = false;
$predictionStatus = 'CLOSED';
$predictionStatusColor = 'text-red-400';
$countdownText = '';
$progressBarWidth = 100;

if ($nextRace) {
    $raceDateTime = new DateTime($nextRace['race_date'] . ' ' . ($nextRace['race_time'] ?? '14:00:00'));
    $now = new DateTime('now', new DateTimeZone('UTC'));
    
    // Predictions are open if race hasn't started yet
    if ($now < $raceDateTime) {
        $predictionsOpen = true;
        $predictionStatus = 'OPEN';
        $predictionStatusColor = 'text-green-400';
        
        // Calculate time remaining
        $interval = $now->diff($raceDateTime);
        $totalDays = $interval->days;
        $hours = $interval->h;
        $minutes = $interval->i;
        
        // Format countdown text
        if ($totalDays > 0) {
            $countdownText = $totalDays . ' day' . ($totalDays > 1 ? 's' : '') . ' left';
        } elseif ($hours > 0) {
            $countdownText = $hours . ' hour' . ($hours > 1 ? 's' : '') . ' left';
        } else {
            $countdownText = $minutes . ' min left';
        }
        
        // Calculate progress bar (assumes 30 days before race = 0%, race day = 100%)
        $maxDaysBeforeRace = 30;
        $daysRemaining = $totalDays + ($hours / 24);
        $progressPercentage = max(0, min(100, (($maxDaysBeforeRace - $daysRemaining) / $maxDaysBeforeRace) * 100));
        $progressBarWidth = round($progressPercentage, 2);
    } else {
        $countdownText = 'Race Started';
        $progressBarWidth = 100;
    }
}

$leaderboard = getLeaderboard(5);

// Get Recent Results (Last 3)
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

// Get Upcoming Races (Next 5)
$upcomingRaces = [];
$stmt = $db->prepare("
    SELECT id, race_name, country, circuit_name, race_date 
    FROM races 
    WHERE race_date >= CURDATE() 
    ORDER BY race_date ASC 
    LIMIT 5
");
$stmt->execute();
$racesData = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Calculate unlock status for each race
$now = new DateTime('now', new DateTimeZone('UTC'));
foreach ($racesData as $race) {
    $raceDate = new DateTime($race['race_date'], new DateTimeZone('UTC'));
    
    // Find previous race
    $prevRaceStmt = $db->prepare("SELECT race_date FROM races WHERE race_date < ? ORDER BY race_date DESC LIMIT 1");
    $prevRaceStmt->bind_param("s", $race['race_date']);
    $prevRaceStmt->execute();
    $prevRaceResult = $prevRaceStmt->get_result()->fetch_assoc();
    
    // Unlock on Monday 00:00 after previous Sunday race
    if ($prevRaceResult) {
        $prevRaceDate = new DateTime($prevRaceResult['race_date'], new DateTimeZone('UTC'));
        // Find the Monday after the previous race
        $unlockDate = clone $prevRaceDate;
        $dayOfWeek = (int)$unlockDate->format('N'); // 1=Monday, 7=Sunday
        if ($dayOfWeek == 7) {
            // Race is Sunday, unlock next day (Monday)
            $unlockDate->modify('+1 day')->setTime(0, 0, 0);
        } else {
            // Race on other day, find next Monday
            $daysUntilMonday = (8 - $dayOfWeek) % 7;
            if ($daysUntilMonday == 0) $daysUntilMonday = 7;
            $unlockDate->modify("+{$daysUntilMonday} days")->setTime(0, 0, 0);
        }
        $race['unlocked'] = $now >= $unlockDate;
        $race['unlock_date'] = $unlockDate->format('M d');
    } else {
        // First race of season, always unlocked
        $race['unlocked'] = true;
        $race['unlock_date'] = null;
    }
    
    $upcomingRaces[] = $race;
}
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
                    <div class="text-[10px] text-green-400 font-bold">LEVEL <?php echo $level; ?></div>
                </div>
                <a href="profile.php" class="w-10 h-10 rounded-full bg-slate-700 border-2 border-white/10 overflow-hidden hover:border-blue-500 transition cursor-pointer">
                    <img src="<?php echo getAvatarUrl($user['avatar_style'] ?? 'avataaars', $user['username']); ?>" alt="Avatar" class="w-full h-full object-cover"> 
                </a>
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
                                        <span class="status-label <?php echo $predictionStatusColor; ?>"><?php echo $predictionStatus; ?></span>
                                    </div>
                                    <div class="countdown-text text-[10px] text-gray-300 mb-1 text-center font-mono font-bold">
                                        <?php echo $countdownText; ?>
                                    </div>
                                    <div class="h-2 bg-gray-700 rounded-full overflow-hidden">
                                        <div id="race-countdown-bar" class="h-full bg-gradient-to-r from-orange-500 to-red-600 transition-all duration-1000 ease-linear" style="width: <?php echo $progressBarWidth; ?>%"></div>
                                    </div>
                                </div>
                                <a href="predict.php?race_id=<?php echo $nextRace['id']; ?>" class="g-btn g-btn-blue px-6 py-2 text-sm">
                                    ENTER
                                </a>
                            </div>
                            
                            <script>
                                // LIVE COUNTDOWN TO RACE
                                const raceDateTime = new Date('<?php echo $nextRace['race_date']; ?>T<?php echo $nextRace['race_time'] ?? '14:00:00'; ?>').getTime();
                                const maxDaysBeforeRace = 30;
                                
                                function updateLiveCountdown() {
                                    const now = Date.now();
                                    const timeRemaining = raceDateTime - now;
                                    
                                    const bar = document.getElementById('race-countdown-bar');
                                    const countdownEl = document.querySelector('.countdown-text');
                                    const statusEl = document.querySelector('.status-label');
                                    
                                    if (timeRemaining <= 0) {
                                        // Race has started
                                        if (bar) bar.style.width = '100%';
                                        if (countdownEl) countdownEl.textContent = 'Race Started!';
                                        if (statusEl) {
                                            statusEl.className = 'status-label text-red-400';
                                            statusEl.textContent = 'CLOSED';
                                        }
                                        return;
                                    }
                                    
                                    // Calculate time components
                                    const days = Math.floor(timeRemaining / (1000 * 60 * 60 * 24));
                                    const hours = Math.floor((timeRemaining % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                                    const minutes = Math.floor((timeRemaining % (1000 * 60 * 60)) / (1000 * 60));
                                    const seconds = Math.floor((timeRemaining % (1000 * 60)) / 1000);
                                    
                                    // Update countdown text with live timer
                                    if (countdownEl) {
                                        if (days > 0) {
                                            countdownEl.textContent = `${days}d ${hours}h ${minutes}m ${seconds}s`;
                                        } else if (hours > 0) {
                                            countdownEl.textContent = `${hours}h ${minutes}m ${seconds}s`;
                                        } else if (minutes > 0) {
                                            countdownEl.textContent = `${minutes}m ${seconds}s`;
                                        } else {
                                            countdownEl.textContent = `${seconds}s`;
                                        }
                                    }
                                    
                                    // Calculate progress bar (0% at 30 days, 100% at race time)
                                    const maxTime = maxDaysBeforeRace * 24 * 60 * 60 * 1000;
                                    const elapsed = maxTime - timeRemaining;
                                    const progress = Math.min(Math.max((elapsed / maxTime) * 100, 0), 100);
                                    
                                    if (bar) {
                                        bar.style.width = progress.toFixed(2) + '%';
                                    }
                                    
                                    // Update status based on time
                                    if (statusEl) {
                                        if (timeRemaining > 0) {
                                            statusEl.className = 'status-label text-green-400';
                                            statusEl.textContent = 'OPEN';
                                        }
                                    }
                                }
                                
                                // Update immediately
                                updateLiveCountdown();
                                
                                // Update every second for LIVE countdown
                                setInterval(updateLiveCountdown, 1000);
                            </script>
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

                    <!-- Accuracy -->
                    <div class="g-card p-5 flex flex-col items-center justify-center text-center">
                        <div class="w-12 h-12 rounded-full bg-green-500/20 text-green-400 flex items-center justify-center text-xl mb-3">
                            <i class="fas fa-bullseye"></i>
                        </div>
                        <div class="text-3xl font-black text-white italic"><?php echo number_format($accuracy, 1); ?>%</div>
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
                            <a href="race-results.php?race_id=<?php echo $res['race_id']; ?>" class="g-card p-4 flex items-center justify-between hover:bg-white/10 transition cursor-pointer group">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-lg bg-gray-800 flex items-center justify-center text-gray-400 group-hover:text-orange-400 group-hover:bg-orange-500/20 transition">
                                        <i class="fas fa-flag"></i>
                                    </div>
                                    <div>
                                        <div class="text-sm font-bold text-white group-hover:text-orange-400 transition"><?php echo htmlspecialchars($res['country']); ?></div>
                                        <div class="text-[10px] text-gray-500"><?php echo date('M d', strtotime($res['race_date'])); ?></div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-lg font-bold text-green-400">+<?php echo $res['total_points']; ?></div>
                                    <div class="text-[10px] text-gray-500 font-bold uppercase">Points</div>
                                    <div class="text-[9px] text-blue-400 opacity-0 group-hover:opacity-100 transition">View Details →</div>
                                </div>
                            </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Upcoming Races -->
                <div class="g-card p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="font-bold text-white text-lg flex items-center gap-2">
                            <i class="fas fa-calendar-alt text-blue-500"></i> Upcoming Races
                        </h3>
                    </div>

                    <div class="space-y-2">
                        <?php if (empty($upcomingRaces)): ?>
                            <div class="text-center py-8 text-gray-500">
                                No upcoming races scheduled
                            </div>
                        <?php else: ?>
                            <?php foreach ($upcomingRaces as $idx => $uRace): 
                                $flag = match($uRace['country']) {
                                    'Australia' => '🇦🇺',
                                    'China' => '🇨🇳',
                                    'Japan' => '🇯🇵',
                                    'Bahrain' => '🇧🇭',
                                    'Saudi Arabia' => '🇸🇦',
                                    'Miami' => '🇺🇸',
                                    'Italy' => '🇮🇹',
                                    'Monaco' => '🇲🇨',
                                    'Canada' => '🇨🇦',
                                    'Spain' => '🇪🇸',
                                    'Austria' => '🇦🇹',
                                    'UK' => '🇬🇧',
                                    'Hungary' => '🇭🇺',
                                    'Belgium' => '🇧🇪',
                                    'Netherlands' => '🇳🇱',
                                    'Azerbaijan' => '🇦🇿',
                                    'Singapore' => '🇸🇬',
                                    'USA' => '🇺🇸',
                                    'Mexico' => '🇲🇽',
                                    'Brazil' => '🇧🇷',
                                    'Las Vegas' => '🇺🇸',
                                    'Qatar' => '🇶🇦',
                                    'Abu Dhabi' => '🇦🇪',
                                    default => '🏁'
                                };
                            ?>
                            <a href="<?php echo $uRace['unlocked'] ? 'predict.php?race_id=' . $uRace['id'] : '#'; ?>" 
                               class="block g-card p-4 border-l-4 <?php echo $uRace['unlocked'] ? 'border-l-green-500 hover:bg-white/10' : 'border-l-gray-600 opacity-60'; ?> transition group <?php echo !$uRace['unlocked'] ? 'cursor-not-allowed' : ''; ?>">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="text-3xl"><?php echo $flag; ?></div>
                                        <div>
                                            <div class="text-sm font-bold text-white flex items-center gap-2">
                                                <?php echo htmlspecialchars($uRace['country']); ?>
                                                <?php if (!$uRace['unlocked']): ?>
                                                    <span class="text-[9px] bg-red-500/20 text-red-400 px-1.5 py-0.5 rounded font-bold">🔒 LOCKED</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="text-[10px] text-gray-400"><?php echo date('M d, Y', strtotime($uRace['race_date'])); ?></div>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <?php if ($uRace['unlocked']): ?>
                                            <div class="text-green-400 text-xs font-bold">OPEN</div>
                                            <div class="text-[9px] text-gray-500">Click to predict</div>
                                        <?php else: ?>
                                            <div class="text-red-400 text-xs font-bold">LOCKED</div>
                                            <div class="text-[9px] text-gray-500">Opens <?php echo $uRace['unlock_date']; ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </a>
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
                            <i class="fas fa-trophy text-orange-500"></i> TOP STRATEGY ENGINEERS
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
                                <img src="<?php echo getAvatarUrl($player['avatar_style'] ?? 'avataaars', $player['username']); ?>" alt="Avatar" class="w-full h-full object-cover">
                            </div>
                            <div class="flex-1">
                                <div class="text-sm font-bold text-white group-hover:text-orange-400 transition">
                                    <?php echo htmlspecialchars($player['username']); ?>
                                </div>
                                <?php if (!empty($player['full_name'])): ?>
                                    <div class="text-[9px] text-gray-500"><?php echo htmlspecialchars($player['full_name']); ?></div>
                                <?php endif; ?>
                                <div class="text-[10px] text-gray-500">Level <?php echo $player['races_participated'] ?? 0; ?></div>
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

            </div>
        
        </div>
        
        <!-- Footer info matches others -->
        <footer class="mt-12 border-t border-white/10 py-6 text-center">
            <p class="text-gray-500 text-sm mb-2">&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</p>
            <p class="text-gray-600 text-xs mb-3">
                Powered by <a href="https://www.scanerrific.com" target="_blank" class="text-orange-500 hover:text-orange-400 font-semibold transition">Scanerrific</a>
            </p>
            <div class="flex items-center justify-center gap-2 text-gray-500 text-xs">
                <span>Follow Scanerrific on</span>
                <a href="https://www.linkedin.com/company/86236157/admin/dashboard/" target="_blank" rel="noopener noreferrer" class="inline-flex items-center text-blue-400 hover:text-blue-300 transition-colors">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="inline-block">
                        <path d="M17.303 2.25H6.69698C5.51757 2.25 4.38647 2.71852 3.5525 3.55249C2.71853 4.38646 2.25 5.51757 2.25 6.69698V17.303C2.25 18.4824 2.71853 19.6135 3.5525 20.4475C4.38647 21.2815 5.51757 21.75 6.69698 21.75H17.303C18.4824 21.75 19.6136 21.2815 20.4475 20.4475C21.2815 19.6135 21.75 18.4824 21.75 17.303V6.69698C21.75 5.51757 21.2815 4.38646 20.4475 3.55249C19.6136 2.71852 18.4824 2.25 17.303 2.25ZM8.84265 17.9923C8.84568 18.0467 8.83757 18.1011 8.81882 18.1523C8.80007 18.2035 8.77106 18.2502 8.73359 18.2898C8.69612 18.3293 8.65097 18.3608 8.6009 18.3823C8.55084 18.4038 8.49691 18.4149 8.44243 18.4148H6.66363C6.55647 18.4119 6.45468 18.3673 6.37992 18.2904C6.30517 18.2136 6.26336 18.1106 6.2634 18.0034V10.5992C6.26191 10.5457 6.27118 10.4925 6.29063 10.4426C6.31007 10.3928 6.3393 10.3473 6.37662 10.309C6.41393 10.2706 6.45857 10.2402 6.50787 10.2193C6.55716 10.1985 6.61012 10.1878 6.66363 10.1879H8.44243C8.49593 10.1878 8.54889 10.1985 8.59819 10.2193C8.64749 10.2402 8.69212 10.2706 8.72944 10.309C8.76675 10.3473 8.796 10.3928 8.81544 10.4426C8.83489 10.4925 8.84414 10.5457 8.84265 10.5992V17.9923ZM7.51968 8.63141C7.22991 8.62922 6.94729 8.54129 6.70743 8.37871C6.46757 8.21613 6.2812 7.98618 6.17183 7.71784C6.06246 7.4495 6.03499 7.15479 6.09286 6.87086C6.15073 6.58693 6.29137 6.32649 6.49704 6.12236C6.70271 5.91824 6.9642 5.77957 7.24856 5.72385C7.53292 5.66812 7.82742 5.69782 8.09492 5.80921C8.36242 5.9206 8.59096 6.10869 8.75173 6.34978C8.91249 6.59086 8.99829 6.87414 8.9983 7.16391C8.9983 7.35757 8.95998 7.54931 8.88554 7.72808C8.8111 7.90685 8.702 8.06913 8.56455 8.20554C8.4271 8.34196 8.26401 8.44982 8.08467 8.52291C7.90534 8.59601 7.71332 8.63288 7.51968 8.63141ZM18.3369 17.9812C18.337 18.0825 18.2975 18.1798 18.2269 18.2525C18.1564 18.3251 18.0602 18.3674 17.959 18.3703H16.0801C15.9788 18.3674 15.8827 18.3251 15.8121 18.2525C15.7415 18.1798 15.7021 18.0825 15.7021 17.9812V14.557C15.7021 14.0456 15.8578 12.3335 14.3458 12.3335C13.1673 12.3335 12.9339 13.5342 12.8894 14.0678V18.059C12.8894 18.1603 12.85 18.2576 12.7794 18.3303C12.7088 18.403 12.6127 18.4452 12.5114 18.4481H10.6882C10.6371 18.4481 10.5865 18.4381 10.5392 18.4185C10.492 18.3989 10.4491 18.3703 10.413 18.3342C10.3769 18.298 10.3482 18.2551 10.3287 18.2079C10.3091 18.1607 10.299 18.1101 10.299 18.059V10.5658C10.3019 10.4646 10.3442 10.3685 10.4169 10.2979C10.4895 10.2273 10.5869 10.1878 10.6882 10.1879H12.5114C12.6127 10.1878 12.71 10.2273 12.7827 10.2979C12.8554 10.3685 12.8976 10.4646 12.9005 10.5658V11.2107C13.1667 10.8212 13.5341 10.5119 13.9632 10.316C14.3922 10.12 14.8666 10.045 15.3352 10.0989C18.3703 10.0989 18.3592 12.9339 18.3592 14.5459L18.3369 17.9812Z" fill="currentColor"/>
                    </svg>
                    <span class="ml-1">LinkedIn</span>
                </a>
            </div>
        </footer>

    </main>

</body>
</html>
