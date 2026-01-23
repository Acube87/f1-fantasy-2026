<?php
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$leaderboard = getLeaderboard(100);
$currentUser = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leaderboard - <?php echo SITE_NAME; ?></title>
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
        
        .rank-badge {
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            font-weight: 700;
            font-size: 18px;
        }
        
        .rank-1 {
            background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
            color: #000;
            box-shadow: 0 8px 24px rgba(255, 215, 0, 0.4);
        }
        
        .rank-2 {
            background: linear-gradient(135deg, #C0C0C0 0%, #808080 100%);
            color: #000;
            box-shadow: 0 8px 24px rgba(192, 192, 192, 0.4);
        }
        
        .rank-3 {
            background: linear-gradient(135deg, #CD7F32 0%, #8B4513 100%);
            color: #fff;
            box-shadow: 0 8px 24px rgba(205, 127, 50, 0.4);
        }
        
        .rank-other {
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
        }
        
        .leaderboard-row {
            transition: all 0.3s ease;
        }
        
        .leaderboard-row:hover {
            transform: translateX(8px);
            background: rgba(225, 6, 0, 0.1);
        }
        
        .current-user-row {
            background: linear-gradient(90deg, rgba(225, 6, 0, 0.2) 0%, rgba(225, 6, 0, 0.05) 100%);
            border-left: 4px solid #e10600;
        }
        
        .points-glow {
            text-shadow: 0 0 20px rgba(225, 6, 0, 0.5);
        }
        
        .stat-card-premium {
            background: linear-gradient(135deg, rgba(225, 6, 0, 0.1) 0%, rgba(225, 6, 0, 0.05) 100%);
            border: 1px solid rgba(225, 6, 0, 0.2);
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
        
        .delay-1 { animation-delay: 0.1s; }
        .delay-2 { animation-delay: 0.2s; }
        .delay-3 { animation-delay: 0.3s; }
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
                    <?php if ($currentUser): ?>
                        <a href="dashboard.php" class="text-white/80 hover:text-white transition">Dashboard</a>
                        <a href="leaderboard.php" class="text-white font-semibold border-b-2 border-white">Leaderboard</a>
                        <a href="predict.php" class="text-white/80 hover:text-white transition">Predict</a>
                        <a href="logout.php" class="text-white/80 hover:text-white transition"><?php echo htmlspecialchars($currentUser['username']); ?></a>
                    <?php else: ?>
                        <a href="login.php" class="text-white/80 hover:text-white transition">Login</a>
                        <a href="signup.php" class="bg-white text-red-800 px-4 py-2 rounded-lg font-semibold hover:bg-gray-100 transition">Sign Up</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <!-- Header Section -->
        <div class="text-center mb-12 animate-fade-in-up">
            <h1 class="text-5xl md:text-6xl font-black mb-4 gradient-text">Leaderboard</h1>
            <p class="text-gray-400 text-lg">Compete with your colleagues and friends</p>
        </div>

        <!-- Top 3 Podium -->
        <?php if (!empty($leaderboard) && count($leaderboard) >= 3): ?>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12 animate-fade-in-up delay-1">
            <!-- 2nd Place -->
            <div class="order-2 md:order-1 mt-8">
                <div class="card-glass rounded-2xl p-6 text-center transform hover:scale-105 transition-all duration-300">
                    <div class="rank-badge rank-2 mx-auto mb-4 text-3xl">🥈</div>
                    <h3 class="text-xl font-bold mb-2"><?php echo htmlspecialchars($leaderboard[1]['full_name'] ?: $leaderboard[1]['username']); ?></h3>
                    <div class="text-3xl font-black text-gray-300 mb-1"><?php echo number_format($leaderboard[1]['total_points'] ?? 0); ?></div>
                    <p class="text-gray-400 text-sm">points</p>
                </div>
            </div>

            <!-- 1st Place -->
            <div class="order-1 md:order-2">
                <div class="card-glass rounded-2xl p-8 text-center transform hover:scale-105 transition-all duration-300 border-2 border-yellow-500/50">
                    <div class="rank-badge rank-1 mx-auto mb-4 text-4xl">👑</div>
                    <h3 class="text-2xl font-bold mb-2"><?php echo htmlspecialchars($leaderboard[0]['full_name'] ?: $leaderboard[0]['username']); ?></h3>
                    <div class="text-4xl font-black text-yellow-400 mb-1 points-glow"><?php echo number_format($leaderboard[0]['total_points'] ?? 0); ?></div>
                    <p class="text-gray-400 text-sm">points</p>
                    <div class="mt-4 inline-block bg-yellow-500/20 text-yellow-400 px-3 py-1 rounded-full text-xs font-semibold">CHAMPION</div>
                </div>
            </div>

            <!-- 3rd Place -->
            <div class="order-3 mt-8">
                <div class="card-glass rounded-2xl p-6 text-center transform hover:scale-105 transition-all duration-300">
                    <div class="rank-badge rank-3 mx-auto mb-4 text-3xl">🥉</div>
                    <h3 class="text-xl font-bold mb-2"><?php echo htmlspecialchars($leaderboard[2]['full_name'] ?: $leaderboard[2]['username']); ?></h3>
                    <div class="text-3xl font-black text-orange-400 mb-1"><?php echo number_format($leaderboard[2]['total_points'] ?? 0); ?></div>
                    <p class="text-gray-400 text-sm">points</p>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Leaderboard Table -->
        <div class="card-glass rounded-2xl overflow-hidden animate-fade-in-up delay-2">
            <div class="p-6 border-b border-white/10">
                <h2 class="text-2xl font-bold flex items-center">
                    <i class="fas fa-trophy text-yellow-500 mr-3"></i>
                    Full Rankings
                </h2>
            </div>
            
            <?php if (empty($leaderboard)): ?>
                <div class="p-12 text-center">
                    <i class="fas fa-users text-6xl text-gray-600 mb-4"></i>
                    <p class="text-gray-400 text-lg">No players yet. Be the first to sign up!</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-white/10">
                                <th class="text-left p-4 text-gray-400 font-semibold uppercase text-sm">Rank</th>
                                <th class="text-left p-4 text-gray-400 font-semibold uppercase text-sm">Player</th>
                                <th class="text-right p-4 text-gray-400 font-semibold uppercase text-sm">Total Points</th>
                                <th class="text-center p-4 text-gray-400 font-semibold uppercase text-sm">Races</th>
                                <th class="text-right p-4 text-gray-400 font-semibold uppercase text-sm">Avg Points</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $rank = 1;
                            foreach ($leaderboard as $entry): 
                                $avgPoints = $entry['races_participated'] > 0 
                                    ? round($entry['total_points'] / $entry['races_participated'], 1) 
                                    : 0;
                                $isCurrentUser = $currentUser && $currentUser['id'] == $entry['id'];
                                $rankClass = '';
                                if ($rank === 1) $rankClass = 'rank-1';
                                elseif ($rank === 2) $rankClass = 'rank-2';
                                elseif ($rank === 3) $rankClass = 'rank-3';
                                else $rankClass = 'rank-other';
                            ?>
                                <tr class="leaderboard-row border-b border-white/5 <?php echo $isCurrentUser ? 'current-user-row' : ''; ?>">
                                    <td class="p-4">
                                        <div class="flex items-center">
                                            <?php if ($rank <= 3): ?>
                                                <div class="rank-badge <?php echo $rankClass; ?> mr-3">
                                                    <?php echo $rank === 1 ? '👑' : ($rank === 2 ? '🥈' : '🥉'); ?>
                                                </div>
                                            <?php else: ?>
                                                <div class="rank-badge <?php echo $rankClass; ?> mr-3">
                                                    <?php echo $rank; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="p-4">
                                        <div class="flex items-center">
                                            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-red-500 to-red-700 flex items-center justify-center text-white font-bold mr-3">
                                                <?php echo strtoupper(substr($entry['username'], 0, 1)); ?>
                                            </div>
                                            <div>
                                                <div class="font-semibold text-white">
                                                    <?php echo htmlspecialchars($entry['full_name'] ?: $entry['username']); ?>
                                                    <?php if ($isCurrentUser): ?>
                                                        <span class="ml-2 inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-red-500/20 text-red-400 border border-red-500/30">
                                                            <i class="fas fa-user-circle mr-1"></i>You
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                                <?php if ($entry['full_name']): ?>
                                                    <div class="text-sm text-gray-400">@<?php echo htmlspecialchars($entry['username']); ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="p-4 text-right">
                                        <div class="text-xl font-black text-red-400 points-glow">
                                            <?php echo number_format($entry['total_points'] ?? 0); ?>
                                        </div>
                                    </td>
                                    <td class="p-4 text-center">
                                        <div class="inline-flex items-center px-3 py-1 rounded-full bg-white/5 text-sm font-semibold">
                                            <i class="fas fa-flag-checkered mr-2 text-red-400"></i>
                                            <?php echo $entry['races_participated'] ?? 0; ?>
                                        </div>
                                    </td>
                                    <td class="p-4 text-right">
                                        <div class="text-lg font-bold text-gray-300">
                                            <?php echo $avgPoints; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php 
                                $rank++;
                            endforeach; 
                            ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Stats Cards -->
        <?php if (!empty($leaderboard)): ?>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-8 animate-fade-in-up delay-3">
            <div class="stat-card-premium rounded-xl p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm mb-1">Total Players</p>
                        <p class="text-3xl font-black text-white"><?php echo count($leaderboard); ?></p>
                    </div>
                    <div class="w-12 h-12 rounded-full bg-red-500/20 flex items-center justify-center">
                        <i class="fas fa-users text-red-400 text-xl"></i>
                    </div>
                </div>
            </div>
            
            <div class="stat-card-premium rounded-xl p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm mb-1">Total Points</p>
                        <p class="text-3xl font-black text-white">
                            <?php 
                            $totalPoints = array_sum(array_column($leaderboard, 'total_points'));
                            echo number_format($totalPoints);
                            ?>
                        </p>
                    </div>
                    <div class="w-12 h-12 rounded-full bg-red-500/20 flex items-center justify-center">
                        <i class="fas fa-trophy text-red-400 text-xl"></i>
                    </div>
                </div>
            </div>
            
            <div class="stat-card-premium rounded-xl p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm mb-1">Avg Points/Player</p>
                        <p class="text-3xl font-black text-white">
                            <?php 
                            $avgTotal = count($leaderboard) > 0 ? round($totalPoints / count($leaderboard), 0) : 0;
                            echo number_format($avgTotal);
                            ?>
                        </p>
                    </div>
                    <div class="w-12 h-12 rounded-full bg-red-500/20 flex items-center justify-center">
                        <i class="fas fa-chart-line text-red-400 text-xl"></i>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </main>

    <!-- Footer -->
    <footer class="mt-16 border-t border-white/10 py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center text-gray-400">
            <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
