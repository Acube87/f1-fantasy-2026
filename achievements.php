<?php
require_once 'includes/auth.php';
require_once 'includes/avatars.php';
// We will include achievements.php once it exists, but for now let's handle the case where it doesn't
if (file_exists('includes/achievements.php')) {
    require_once 'includes/achievements.php';
    $hasBackend = true;
} else {
    $hasBackend = false;
}

$user = getCurrentUser();
if (!$user) {
    header('Location: login.php');
    exit;
}

$db = getDB();
$userId = $user['id'];

// Get user's unlocked achievements
$unlockedIds = [];
$stats = [
    'unlocked' => 0,
    'total' => 26,
    'completion' => 0,
    'displayed' => null
];

if ($hasBackend) {
    // Check for new unlocks on page load
    checkAndUnlockAchievements($userId, $db);
    
    $userAchievements = getUserAchievements($userId, $db);
    $unlockedIds = array_column($userAchievements, 'id');
    $stats = getAchievementStats($userId, $db);
}

$pageTitle = "Achievements";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="css/gaming-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .achievement-card {
            transition: all 0.3s ease;
        }
        .achievement-card:hover {
            transform: translateY(-4px);
        }
        .achievement-locked {
            opacity: 0.4;
            filter: grayscale(100%);
        }
        .achievement-locked:hover {
            opacity: 0.6;
            filter: grayscale(70%);
        }
        .tier-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
    </style>
</head>
<body class="gaming-theme text-gray-200">

    <!-- Navbar -->
    <nav class="g-nav fixed w-full z-50 px-6 py-4 flex justify-between items-center">
        <div class="flex items-center gap-8">
            <a href="index.php" class="flex items-center gap-4 hover:opacity-80 transition group">
                <div class="w-10 h-10 bg-gradient-to-br from-red-600 to-orange-500 rounded-xl flex items-center justify-center shadow-lg shadow-orange-500/20 group-hover:scale-105 transition-transform duration-300">
                    <i class="fas fa-flag-checkered text-white text-lg"></i>
                </div>
                <!-- Hide text on very small screens -->
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
                <a href="leaderboard.php" class="text-gray-300 hover:text-white font-bold text-sm uppercase tracking-wide transition flex items-center gap-2">
                    <i class="fas fa-trophy text-yellow-500/80"></i> Leaderboard
                </a>
                <a href="achievements.php" class="text-white font-bold text-sm uppercase tracking-wide transition flex items-center gap-2 border-b-2 border-purple-500 pb-1">
                    <i class="fas fa-medal text-purple-500"></i> Achievements
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
    <main class="pt-24 pb-12 px-4 md:px-8 max-w-7xl mx-auto">
        
        <!-- Header -->
        <div class="text-center mb-12">
            <h1 class="text-4xl md:text-6xl font-black text-white mb-4 uppercase italic">
                <span class="g-text-gradient">🏆 Achievements</span>
            </h1>
            <p class="text-gray-400 text-lg">Unlock badges and show off your skills</p>
        </div>

        <!-- Stats Overview -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-12">
            <div class="g-card p-6 text-center">
                <div class="text-4xl font-black text-green-400 mb-2"><?php echo $stats['unlocked']; ?></div>
                <div class="text-xs text-gray-400 uppercase font-bold">Unlocked</div>
            </div>
            <div class="g-card p-6 text-center">
                <div class="text-4xl font-black text-blue-400 mb-2"><?php echo $stats['total']; ?></div>
                <div class="text-xs text-gray-400 uppercase font-bold">Total</div>
            </div>
            <div class="g-card p-6 text-center">
                <div class="text-4xl font-black text-purple-400 mb-2"><?php echo $stats['completion']; ?>%</div>
                <div class="text-xs text-gray-400 uppercase font-bold">Completion</div>
            </div>
            <div class="g-card p-6 text-center">
                <div class="text-4xl font-black text-orange-400 mb-2">
                    <?php echo isset($stats['displayed']) && is_array($stats['displayed']) ? count($stats['displayed']) : 0; ?>
                </div>
                <div class="text-xs text-gray-400 uppercase font-bold">Displayed</div>
            </div>
        </div>

        <?php
        // Define all achievements with tier colors - CLEANED & REVISED LIST
        $achievements = [
            'common' => [
                'color' => 'green',
                'label' => 'COMMON',
                'icon' => '🟢',
                'items' => [
                    ['id' => 'first_prediction', 'name' => 'Rookie Driver', 'desc' => 'Make your first prediction', 'icon' => 'fa-flag', 'unlock' => 'Submit 1 prediction'],
                    ['id' => 'welcome_aboard', 'name' => 'Welcome to the Paddock', 'desc' => 'Complete your profile setup', 'icon' => 'fa-user-check', 'unlock' => 'Add full name & avatar'],
                    ['id' => 'first_points', 'name' => 'On the Board', 'desc' => 'Score your first points', 'icon' => 'fa-star', 'unlock' => 'Earn any points'],
                    ['id' => 'participation_5', 'name' => 'Racing Regular', 'desc' => 'Participate in 5 races', 'icon' => 'fa-calendar-check', 'unlock' => 'Complete 5 predictions'],
                    ['id' => 'streak_3', 'name' => 'Consistency Counts', 'desc' => 'Score points 3 races in a row', 'icon' => 'fa-fire', 'unlock' => 'Points in 3 consecutive races'],
                ]
            ],
            'rare' => [
                'color' => 'blue',
                'label' => 'RARE',
                'icon' => '🔵',
                'items' => [
                    ['id' => 'participation_10', 'name' => 'Season Veteran', 'desc' => 'Participate in 10 races', 'icon' => 'fa-medal', 'unlock' => 'Complete 10 predictions'],
                    ['id' => 'podium_sweep_1', 'name' => 'Podium Prophet', 'desc' => 'Get your first podium sweep', 'icon' => 'fa-trophy', 'unlock' => 'All 3 podium correct once'],
                    ['id' => 'total_500', 'name' => 'Point Collector', 'desc' => 'Score 100 total points', 'icon' => 'fa-coins', 'unlock' => '100 total points'],
                    ['id' => 'constructor_correct_5', 'name' => 'Team Tactician', 'desc' => 'Predict winning constructor 5 times', 'icon' => 'fa-wrench', 'unlock' => '5 correct constructors'],
                    ['id' => 'perfectionist', 'name' => 'Perfectionist', 'desc' => 'Get 5+ exact predictions in one race', 'icon' => 'fa-bullseye', 'unlock' => '5+ exact matches in single race'],
                    ['id' => 'accuracy_20', 'name' => 'Sharp Shooter', 'desc' => 'Achieve 45% prediction accuracy', 'icon' => 'fa-crosshairs', 'unlock' => '45% exact match rate'],
                ]
            ],
            'epic' => [
                'color' => 'purple',
                'label' => 'EPIC',
                'icon' => '🟣',
                'items' => [
                    ['id' => 'big_score', 'name' => 'Big Score', 'desc' => 'Score 150+ points in one race', 'icon' => 'fa-bolt', 'unlock' => '150+ in single race'],
                    ['id' => 'podium_sweep_3', 'name' => 'Crystal Ball', 'desc' => 'Get 5 podium sweeps', 'icon' => 'fa-eye', 'unlock' => '5 podium sweeps total'],
                    ['id' => 'streak_10', 'name' => 'Unbreakable Focus', 'desc' => 'Predict 10 races in a row', 'icon' => 'fa-fire', 'unlock' => '10-race streak'],
                    ['id' => 'double_points_master', 'name' => 'Double Trouble', 'desc' => 'Score 200+ in a 2x points race', 'icon' => 'fa-gem', 'unlock' => '200+ in China/UK/Singapore'],
                    ['id' => 'accuracy_30', 'name' => 'Precision Engineer', 'desc' => 'Achieve 30% prediction accuracy', 'icon' => 'fa-bullseye', 'unlock' => '30% exact match rate'],
                    ['id' => 'total_1000', 'name' => 'Points Millionaire', 'desc' => 'Score 1000 total points', 'icon' => 'fa-sack-dollar', 'unlock' => '1000 total points'],
                    ['id' => 'race_winner_3', 'name' => 'Hat Trick Hero', 'desc' => 'Win 3 individual races', 'icon' => 'fa-crown', 'unlock' => 'Rank #1 in 3 different races'],
                ]
            ],
            'legendary' => [
                'color' => 'red',
                'label' => 'LEGENDARY',
                'icon' => '🔴',
                'items' => [
                    ['id' => 'legendary_performance', 'name' => 'Legendary Performance', 'desc' => 'Score 150+ points in single race', 'icon' => 'fa-trophy', 'unlock' => '150+ in regular race'],
                    ['id' => 'podium_sweep_5', 'name' => 'Oracle of the Grid', 'desc' => 'Get 10 podium sweeps', 'icon' => 'fa-eye', 'unlock' => '10 podium sweeps total'],
                    ['id' => 'accuracy_40', 'name' => 'The Nostradamus', 'desc' => 'Achieve 66% prediction accuracy', 'icon' => 'fa-magic', 'unlock' => '66% exact match rate'],
                    ['id' => 'total_2500', 'name' => 'Point Legend', 'desc' => 'Score 2000 total points', 'icon' => 'fa-infinity', 'unlock' => '2000 total points'],
                ]
            ],
            'special' => [
                'color' => 'yellow',
                'label' => 'SPECIAL',
                'icon' => '🟡',
                'items' => [
                    ['id' => 'first_race_winner', 'name' => 'Early Bird', 'desc' => 'Win the opening race', 'icon' => 'fa-bolt', 'unlock' => 'Rank #1 in first race'],
                    ['id' => 'constructor_sweep', 'name' => 'Team Whisperer', 'desc' => 'Predict constructor correctly 7 times', 'icon' => 'fa-handshake', 'unlock' => '7 correct constructor picks'],
                    ['id' => 'perfect_weekend', 'name' => 'Perfect Weekend', 'desc' => 'Score 100+ points in 3 consecutive races', 'icon' => 'fa-check-double', 'unlock' => '100+ in 3 races in a row'],
                    ['id' => 'mega_race', 'name' => 'Mega Race', 'desc' => 'Score 200+ points in a 2x event', 'icon' => 'fa-rocket', 'unlock' => '200+ in double points race'],
                    ['id' => 'silver_arrows', 'name' => 'Silver Arrows', 'desc' => 'Predict Mercedes 1-2 finish correctly', 'icon' => 'fa-star', 'unlock' => 'Correct Mercedes 1st & 2nd prediction'],
                    ['id' => 'columbus', 'name' => 'Columbus', 'desc' => 'Win in all continents', 'icon' => 'fa-globe-americas', 'unlock' => 'Rank #1 in race from each continent'],
                    ['id' => 'f1_hero', 'name' => 'F1 Hero', 'desc' => 'Compete in all races', 'icon' => 'fa-flag-checkered', 'unlock' => 'Participate in every season race'],
                ]
            ],
        ];

        foreach ($achievements as $tier => $tierData):
            $color = $tierData['color'];
            
            // Calculate unlocked count for this tier
            $tierTotal = count($tierData['items']);
            $tierUnlockedCount = 0;
            foreach ($tierData['items'] as $item) {
                if (in_array($item['id'], $unlockedIds)) {
                    $tierUnlockedCount++;
                }
            }
        ?>
        
        <!-- Tier Section -->
        <div class="mb-12">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-black text-white uppercase flex items-center gap-3">
                    <span class="text-3xl"><?php echo $tierData['icon']; ?></span>
                    <?php echo $tierData['label']; ?> Achievements
                </h2>
                <span class="inline-flex items-center rounded-md bg-<?php echo $color; ?>-400/10 px-3 py-1.5 text-sm font-bold text-<?php echo $color; ?>-400 ring-1 ring-inset ring-<?php echo $color; ?>-400/20">
                    <?php echo $tierUnlockedCount; ?> / <?php echo $tierTotal; ?>
                </span>
            </div>
            
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php 
                $displayedIds = isset($stats['displayed']) && is_array($stats['displayed']) ? array_column($stats['displayed'], 'id') : [];
                
                foreach ($tierData['items'] as $achievement): 
                    $isLocked = !in_array($achievement['id'], $unlockedIds);
                    $cardClass = $isLocked ? 'achievement-card achievement-locked' : 'achievement-card';
                    $isDisplayed = in_array($achievement['id'], $displayedIds);
                ?>
                <div class="g-card p-6 <?php echo $cardClass; ?> border-l-4 border-l-<?php echo $color; ?>-500 relative">
                    <div class="flex items-start justify-between mb-4">
                        <div class="w-14 h-14 rounded-xl bg-<?php echo $color; ?>-500/20 flex items-center justify-center text-<?php echo $color; ?>-400 text-2xl">
                            <i class="fas <?php echo $achievement['icon']; ?>"></i>
                        </div>
                        <?php if ($isLocked): ?>
                            <i class="fas fa-lock text-gray-600 text-xl"></i>
                        <?php elseif ($isDisplayed): ?>
                            <span class="bg-blue-500 text-white text-[10px] uppercase font-bold px-2 py-1 rounded-full">Active</span>
                        <?php else: ?>
                            <i class="fas fa-check-circle text-<?php echo $color; ?>-400 text-xl"></i>
                        <?php endif; ?>
                    </div>
                    
                    <h3 class="text-lg font-bold text-white mb-2"><?php echo htmlspecialchars($achievement['name']); ?></h3>
                    <p class="text-sm text-gray-400 mb-4"><?php echo htmlspecialchars($achievement['desc']); ?></p>
                    
                    <div class="pt-4 border-t border-white/10">
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-gray-500 font-bold uppercase">How to Unlock</span>
                            <span class="inline-flex items-center rounded-md bg-<?php echo $color; ?>-400/10 px-2 py-1 text-xs font-medium text-<?php echo $color; ?>-400 ring-1 ring-inset ring-<?php echo $color; ?>-400/20">
                                <?php echo $tierData['label']; ?>
                            </span>
                        </div>
                        <p class="text-xs text-gray-400 mt-2"><?php echo htmlspecialchars($achievement['unlock']); ?></p>
                    </div>
                    
                    <?php if (!$isLocked): ?>
                    <div class="mt-4">
                        <?php if ($isDisplayed): ?>
                            <button onclick="toggleDisplayBadge('<?php echo $achievement['id']; ?>', 'hide')" class="w-full g-btn bg-red-600/20 text-red-400 border border-red-500/50 py-2 text-xs hover:bg-red-600/30 transition">
                                <i class="fas fa-eye-slash mr-1"></i> Hide Badge
                            </button>
                        <?php else: ?>
                            <button onclick="toggleDisplayBadge('<?php echo $achievement['id']; ?>', 'show')" class="w-full g-btn g-btn-blue py-2 text-xs hover:scale-105 transition">
                                <i class="fas fa-eye mr-1"></i> Show on Profile
                            </button>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <?php endforeach; ?>

        <!-- Info Card -->
        <div class="g-card p-8 bg-blue-500/10 border border-blue-500/30">
            <h3 class="text-xl font-bold text-white mb-4 flex items-center gap-2">
                <i class="fas fa-info-circle text-blue-400"></i>
                About Achievements
            </h3>
            <div class="space-y-3 text-sm text-gray-300">
                <p>🏆 <strong>Unlock achievements</strong> by completing various challenges and milestones throughout the season.</p>
                <p>⭐ <strong>Display your favorite badges</strong> on the leaderboard to show off your accomplishments.</p>
                <p>🎯 <strong>Achievement tiers</strong> range from Common (easiest) to Legendary (extremely rare).</p>
                <p>📊 <strong>Track your progress</strong> and see how close you are to unlocking new badges.</p>
            </div>
        </div>

    </main>

    <!-- Footer -->
    <footer class="mt-12 border-t border-white/10 py-6 text-center">
        <p class="text-gray-500 text-sm mb-2">&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</p>
        <p class="text-gray-600 text-xs mb-3">
            Powered by <a href="https://www.scanerrific.com" target="_blank" class="text-orange-500 hover:text-orange-400 font-semibold transition">Scanerrific</a>
        </p>
    </footer>

    <script>
        function toggleDisplayBadge(achievementId, action) {
            // No confirmation needed for simple toggle
            
            fetch('api/set-badge.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ achievement_id: achievementId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while updating badge');
            });
        }
    </script>
</body>
</html>
