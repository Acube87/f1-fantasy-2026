<?php
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();
$user = getCurrentUser();

// Get user stats
$db = getDB();
$stmt = $db->prepare("SELECT total_points, races_participated FROM user_totals WHERE user_id = ?");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

// Get recent scores
$scoresStmt = $db->prepare("SELECT s.*, r.race_name, r.race_date FROM scores s JOIN races r ON s.race_id = r.id WHERE s.user_id = ? ORDER BY r.race_date DESC LIMIT 10");
$scoresStmt->bind_param("i", $user['id']);
$scoresStmt->execute();
$recentScores = $scoresStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get upcoming races
$upcomingRaces = getUpcomingRaces(5);

// Get leaderboard position
$leaderboardStmt = $db->prepare("SELECT COUNT(*) + 1 as position FROM user_totals WHERE total_points > (SELECT total_points FROM user_totals WHERE user_id = ?)");
$leaderboardStmt->bind_param("i", $user['id']);
$leaderboardStmt->execute();
$positionResult = $leaderboardStmt->get_result()->fetch_assoc();
$leaderboardPosition = $positionResult['position'] ?? 1;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap');
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0f0f0f 0%, #1a1a1a 50%, #0f0f0f 100%);
            min-height: 100vh;
        }
        
        .gradient-text {
            background: linear-gradient(135deg, #e10600 0%, #ff4444 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .card-glass {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .stat-card-premium {
            background: linear-gradient(135deg, rgba(225, 6, 0, 0.1) 0%, rgba(225, 6, 0, 0.05) 100%);
            border: 1px solid rgba(225, 6, 0, 0.2);
            transition: all 0.3s ease;
        }
        
        .stat-card-premium:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(225, 6, 0, 0.2);
        }
        
        .points-glow {
            text-shadow: 0 0 20px rgba(225, 6, 0, 0.5);
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .animate-fade-in-up {
            animation: fadeInUp 0.6s ease-out;
        }
        
        .race-card-premium {
            transition: all 0.3s ease;
        }
        
        .race-card-premium:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(225, 6, 0, 0.2);
        }
    </style>
</head>
<body class="text-white">
    <!-- Navigation -->
    <nav class="bg-gradient-to-r from-red-900 via-red-800 to-red-900 border-b border-red-700/50 shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-3">
                    <span class="text-2xl">🏎️</span>
                    <h1 class="text-xl font-bold"><?php echo SITE_NAME; ?></h1>
                </div>
                <div class="flex items-center space-x-6">
                    <a href="index.php" class="text-white/80 hover:text-white transition">Home</a>
                    <a href="dashboard.php" class="text-white font-semibold border-b-2 border-white">Dashboard</a>
                    <a href="leaderboard.php" class="text-white/80 hover:text-white transition">Leaderboard</a>
                    <a href="predict.php" class="text-white/80 hover:text-white transition">Predict</a>
                    <a href="logout.php" class="text-white/80 hover:text-white transition"><?php echo htmlspecialchars($user['username']); ?></a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <!-- Welcome Header -->
        <div class="mb-8 animate-fade-in-up">
            <h1 class="text-4xl md:text-5xl font-black mb-2 gradient-text">
                Welcome back, <?php echo htmlspecialchars($user['full_name'] ?: $user['username']); ?>!
            </h1>
            <p class="text-gray-400">Track your predictions and compete for the top spot</p>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8 animate-fade-in-up">
            <div class="stat-card-premium rounded-xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 rounded-full bg-red-500/20 flex items-center justify-center">
                        <i class="fas fa-trophy text-red-400 text-xl"></i>
                    </div>
                </div>
                <p class="text-gray-400 text-sm mb-1">Total Points</p>
                <p class="text-3xl font-black text-red-400 points-glow"><?php echo number_format($stats['total_points'] ?? 0); ?></p>
            </div>
            
            <div class="stat-card-premium rounded-xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 rounded-full bg-blue-500/20 flex items-center justify-center">
                        <i class="fas fa-flag-checkered text-blue-400 text-xl"></i>
                    </div>
                </div>
                <p class="text-gray-400 text-sm mb-1">Races Participated</p>
                <p class="text-3xl font-black text-blue-400"><?php echo $stats['races_participated'] ?? 0; ?></p>
            </div>
            
            <div class="stat-card-premium rounded-xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 rounded-full bg-green-500/20 flex items-center justify-center">
                        <i class="fas fa-chart-line text-green-400 text-xl"></i>
                    </div>
                </div>
                <p class="text-gray-400 text-sm mb-1">Average Points</p>
                <p class="text-3xl font-black text-green-400">
                    <?php 
                    $avg = ($stats['races_participated'] ?? 0) > 0 
                        ? round(($stats['total_points'] ?? 0) / $stats['races_participated'], 1) 
                        : 0; 
                    echo $avg;
                    ?>
                </p>
            </div>
            
            <div class="stat-card-premium rounded-xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 rounded-full bg-yellow-500/20 flex items-center justify-center">
                        <i class="fas fa-medal text-yellow-400 text-xl"></i>
                    </div>
                </div>
                <p class="text-gray-400 text-sm mb-1">Leaderboard Rank</p>
                <p class="text-3xl font-black text-yellow-400">#<?php echo $leaderboardPosition; ?></p>
            </div>
        </div>

        <!-- Upcoming Races -->
        <div class="mb-8 animate-fade-in-up">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-bold flex items-center">
                    <i class="fas fa-calendar-alt text-red-400 mr-3"></i>
                    Upcoming Races
                </h2>
                <a href="predict.php" class="text-red-400 hover:text-red-300 transition flex items-center">
                    Make Predictions <i class="fas fa-arrow-right ml-2"></i>
                </a>
            </div>
            
            <?php if (empty($upcomingRaces)): ?>
                <div class="card-glass rounded-xl p-8 text-center">
                    <i class="fas fa-calendar-times text-4xl text-gray-600 mb-4"></i>
                    <p class="text-gray-400">No upcoming races scheduled.</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($upcomingRaces as $race): ?>
                        <div class="race-card-premium card-glass rounded-xl p-6">
                            <div class="flex items-start justify-between mb-4">
                                <div>
                                    <h3 class="text-xl font-bold mb-1"><?php echo htmlspecialchars($race['race_name']); ?></h3>
                                    <p class="text-gray-400 text-sm"><?php echo htmlspecialchars($race['circuit_name']); ?></p>
                                </div>
                                <div class="w-12 h-12 rounded-full bg-red-500/20 flex items-center justify-center">
                                    <i class="fas fa-flag-checkered text-red-400"></i>
                                </div>
                            </div>
                            <div class="flex items-center text-gray-300 mb-4">
                                <i class="fas fa-calendar mr-2"></i>
                                <span><?php echo date('F j, Y', strtotime($race['race_date'])); ?></span>
                            </div>
                            <a href="predict.php?race_id=<?php echo $race['id']; ?>" class="block w-full bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white text-center py-2 rounded-lg font-semibold transition">
                                Make Prediction
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Recent Scores -->
        <div class="animate-fade-in-up">
            <h2 class="text-2xl font-bold mb-6 flex items-center">
                <i class="fas fa-history text-red-400 mr-3"></i>
                Recent Race Scores
            </h2>
            
            <?php if (empty($recentScores)): ?>
                <div class="card-glass rounded-xl p-8 text-center">
                    <i class="fas fa-trophy text-4xl text-gray-600 mb-4"></i>
                    <p class="text-gray-400">No scores yet. Make your first prediction!</p>
                </div>
            <?php else: ?>
                <div class="card-glass rounded-xl overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-white/10">
                                    <th class="text-left p-4 text-gray-400 font-semibold uppercase text-sm">Race</th>
                                    <th class="text-left p-4 text-gray-400 font-semibold uppercase text-sm">Date</th>
                                    <th class="text-right p-4 text-gray-400 font-semibold uppercase text-sm">Driver</th>
                                    <th class="text-right p-4 text-gray-400 font-semibold uppercase text-sm">Constructor</th>
                                    <th class="text-right p-4 text-gray-400 font-semibold uppercase text-sm">Bonus</th>
                                    <th class="text-right p-4 text-gray-400 font-semibold uppercase text-sm">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentScores as $score): ?>
                                    <tr class="border-b border-white/5 hover:bg-white/5 transition">
                                        <td class="p-4">
                                            <div class="font-semibold"><?php echo htmlspecialchars($score['race_name']); ?></div>
                                        </td>
                                        <td class="p-4 text-gray-400">
                                            <?php echo date('M j, Y', strtotime($score['race_date'])); ?>
                                        </td>
                                        <td class="p-4 text-right">
                                            <span class="text-blue-400 font-semibold"><?php echo $score['driver_points']; ?></span>
                                        </td>
                                        <td class="p-4 text-right">
                                            <span class="text-green-400 font-semibold"><?php echo $score['constructor_points']; ?></span>
                                        </td>
                                        <td class="p-4 text-right">
                                            <span class="text-yellow-400 font-semibold"><?php echo $score['top3_bonus'] + $score['constructor_top3_bonus']; ?></span>
                                        </td>
                                        <td class="p-4 text-right">
                                            <span class="text-xl font-black text-red-400 points-glow"><?php echo $score['total_points']; ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Footer -->
    <footer class="mt-16 border-t border-white/10 py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center text-gray-400">
            <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</p>
            <p class="text-gray-500 text-sm mt-2">
                Powered by <a href="https://www.scanerrific.com" target="_blank" class="text-red-400 hover:text-red-300 font-semibold transition">Scanerrific</a>
            </p>
        </div>
    </footer>
</body>
</html>
