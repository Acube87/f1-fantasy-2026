<?php
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$leaderboard = getLeaderboard(100);
$user = getCurrentUser(); // Standard variable name $user
$currentUser = $user;     // Alias for existing logic
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leaderboard - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="css/gaming-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="gaming-theme text-gray-200">

    <!-- Navbar -->
    <nav class="g-nav fixed w-full z-50 px-6 py-4 flex justify-between items-center">
        <div class="flex items-center gap-4">
            <a href="index.php" class="flex items-center gap-4 hover:opacity-80 transition">
                <div class="w-10 h-10 bg-gradient-to-br from-red-600 to-orange-500 rounded-xl flex items-center justify-center shadow-lg shadow-orange-500/20">
                    <i class="fas fa-flag-checkered text-white text-lg"></i>
                </div>
                <span class="font-bold text-xl tracking-wide text-white">PADDOCK PICKS</span>
            </a>
        </div>
        
        <?php if ($user): ?>
            <div class="flex items-center gap-6">
                <!-- User Menu -->
                <div class="flex items-center gap-3 pl-6 border-l border-white/10">
                    <a href="dashboard.php" class="text-gray-300 hover:text-white font-bold text-sm mr-4">Dashboard</a>
                    <div class="w-10 h-10 rounded-full bg-slate-700 border-2 border-white/10 overflow-hidden">
                        <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=<?php echo $user['username']; ?>" alt="Avatar" class="w-full h-full"> 
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="flex items-center gap-4">
                <a href="login.php" class="text-gray-300 hover:text-white font-medium text-sm">Log In</a>
                <a href="signup.php" class="g-btn g-btn-orange px-6 py-2 text-sm">Sign Up</a>
            </div>
        <?php endif; ?>
    </nav>

    <!-- Main Content -->
    <main class="pt-24 pb-12 px-4 md:px-8 max-w-7xl mx-auto">
        
        <div class="text-center mb-12">
            <h1 class="text-4xl md:text-5xl font-black text-white italic mb-2 uppercase">
                Global <span class="g-text-gradient">Leaderboard</span>
            </h1>
            <p class="text-gray-400">The fastest predictors in the paddock</p>
        </div>

        <!-- Top 3 Podium Cards -->
        <?php if (!empty($leaderboard) && count($leaderboard) >= 3): ?>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12 items-end">
            
            <!-- 2nd Place -->
            <div class="order-2 md:order-1 g-card p-6 md:p-8 text-center border-t-4 border-t-gray-400">
                <div class="w-16 h-16 rounded-full bg-gray-400/20 mx-auto mb-4 flex items-center justify-center text-3xl shadow-[0_0_20px_rgba(156,163,175,0.3)]">🥈</div>
                <h3 class="font-bold text-xl text-white mb-1"><?php echo htmlspecialchars($leaderboard[1]['username']); ?></h3>
                <div class="text-3xl font-black text-gray-300"><?php echo number_format($leaderboard[1]['total_points']); ?> pts</div>
            </div>

            <!-- 1st Place -->
            <div class="order-1 md:order-2 g-card p-8 md:p-10 text-center border-t-4 border-t-yellow-400 transform scale-105 z-10 shadow-2xl">
                <div class="w-20 h-20 rounded-full bg-yellow-400/20 mx-auto mb-4 flex items-center justify-center text-4xl shadow-[0_0_30px_rgba(250,204,21,0.4)]">👑</div>
                <h3 class="font-black text-2xl text-white mb-1"><?php echo htmlspecialchars($leaderboard[0]['username']); ?></h3>
                <div class="text-4xl font-black text-yellow-400"><?php echo number_format($leaderboard[0]['total_points']); ?> pts</div>
                <div class="mt-2 text-xs font-bold bg-yellow-400/20 text-yellow-400 py-1 px-3 rounded-full inline-block">SEASON LEADER</div>
            </div>

            <!-- 3rd Place -->
            <div class="order-3 md:order-3 g-card p-6 md:p-8 text-center border-t-4 border-t-orange-700">
                <div class="w-16 h-16 rounded-full bg-orange-700/20 mx-auto mb-4 flex items-center justify-center text-3xl shadow-[0_0_20px_rgba(194,65,12,0.3)]">🥉</div>
                <h3 class="font-bold text-xl text-white mb-1"><?php echo htmlspecialchars($leaderboard[2]['username']); ?></h3>
                <div class="text-3xl font-black text-orange-600"><?php echo number_format($leaderboard[2]['total_points']); ?> pts</div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Full Table -->
        <div class="g-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="border-b border-white/10 bg-white/5">
                            <th class="p-4 text-xs font-bold text-gray-400 uppercase tracking-wider">Rank</th>
                            <th class="p-4 text-xs font-bold text-gray-400 uppercase tracking-wider">Racer</th>
                            <th class="p-4 text-xs font-bold text-gray-400 uppercase tracking-wider text-right">Points</th>
                            <th class="p-4 text-xs font-bold text-gray-400 uppercase tracking-wider text-right">Races</th>
                            <th class="p-4 text-xs font-bold text-gray-400 uppercase tracking-wider text-right">Avg</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        <?php 
                        $rank = 1;
                        foreach ($leaderboard as $entry): 
                            $avgPoints = $entry['races_participated'] > 0 
                                ? number_format($entry['total_points'] / $entry['races_participated'], 1) 
                                : '0.0';
                            $isMe = ($user && $user['username'] === $entry['username']);
                            $rowClass = $isMe ? 'bg-orange-500/10' : 'hover:bg-white/5';
                        ?>
                        <tr class="<?php echo $rowClass; ?> transition">
                            <td class="p-4">
                                <span class="font-black text-white <?php echo $rank <= 3 ? 'text-orange-400 text-lg' : 'text-gray-500'; ?> w-8 inline-block text-center">
                                    <?php echo $rank; ?>
                                </span>
                            </td>
                            <td class="p-4 flex items-center gap-3">
                                <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=<?php echo $entry['username']; ?>" class="w-8 h-8 rounded-full bg-slate-700">
                                <div>
                                    <div class="font-bold text-white <?php echo $isMe ? 'text-orange-400' : ''; ?>">
                                        <?php echo htmlspecialchars($entry['username']); ?>
                                        <?php if($isMe) echo '<span class="bg-orange-500 text-white text-[10px] px-1 rounded ml-2">YOU</span>'; ?>
                                    </div>
                                    <div class="text-xs text-gray-500">Level <?php echo floor($entry['total_points'] / 100) + 1; ?></div>
                                </div>
                            </td>
                            <td class="p-4 text-right font-mono font-bold text-white text-lg">
                                <?php echo number_format($entry['total_points']); ?>
                            </td>
                            <td class="p-4 text-right text-gray-400">
                                <?php echo $entry['races_participated']; ?>
                            </td>
                            <td class="p-4 text-right text-gray-500">
                                <?php echo $avgPoints; ?>
                            </td>
                        </tr>
                        <?php $rank++; endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>
    
    <footer class="mt-12 border-t border-white/10 py-6 text-center">
        <p class="text-gray-500 text-sm mb-2">&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</p>
        <p class="text-gray-600 text-xs">
            Powered by <a href="https://www.scanerrific.com" target="_blank" class="text-orange-500 hover:text-orange-400 font-semibold transition">Scanerrific</a>
        </p>
    </footer>

</body>
</html>
