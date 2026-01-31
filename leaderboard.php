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
                        <img src="https://api.dicebear.com/7.x/<?php echo $user['avatar_style'] ?? 'avataaars'; ?>/svg?seed=<?php echo $user['username']; ?>" alt="Avatar" class="w-full h-full"> 
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
                <div class="text-3xl font-black text-gray-300"><?php echo number_format($leaderboard[1]['total_points'] ?? 0); ?> pts</div>
            </div>

            <!-- 1st Place -->
            <div class="order-1 md:order-2 g-card p-8 md:p-10 text-center border-t-4 border-t-yellow-400 transform scale-105 z-10 shadow-2xl">
                <div class="w-20 h-20 rounded-full bg-yellow-400/20 mx-auto mb-4 flex items-center justify-center text-4xl shadow-[0_0_30px_rgba(250,204,21,0.4)]">👑</div>
                <h3 class="font-black text-2xl text-white mb-1"><?php echo htmlspecialchars($leaderboard[0]['username']); ?></h3>
                <div class="text-4xl font-black text-yellow-400"><?php echo number_format($leaderboard[0]['total_points'] ?? 0); ?> pts</div>
                <div class="mt-2 text-xs font-bold bg-yellow-400/20 text-yellow-400 py-1 px-3 rounded-full inline-block">SEASON LEADER</div>
            </div>

            <!-- 3rd Place -->
            <div class="order-3 md:order-3 g-card p-6 md:p-8 text-center border-t-4 border-t-orange-700">
                <div class="w-16 h-16 rounded-full bg-orange-700/20 mx-auto mb-4 flex items-center justify-center text-3xl shadow-[0_0_20px_rgba(194,65,12,0.3)]">🥉</div>
                <h3 class="font-bold text-xl text-white mb-1"><?php echo htmlspecialchars($leaderboard[2]['username']); ?></h3>
                <div class="text-3xl font-black text-orange-600"><?php echo number_format($leaderboard[2]['total_points'] ?? 0); ?> pts</div>
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
                                <?php if ($rank === 1): ?>
                                    <div class="w-10 h-10 inline-block">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32">
                                          <defs>
                                            <linearGradient id="trophy1" x1="9.106" x2="22.419" y1="28.638" y2="5.579" gradientUnits="userSpaceOnUse">
                                              <stop offset="0" stop-color="#FFD700"></stop>
                                              <stop offset="1" stop-color="#FFA500"></stop>
                                            </linearGradient>
                                          </defs>
                                          <path fill="url(#trophy1)" d="M23.24,9H22.114l.706-.711A1,1,0,0,0,22.26,6.6l-3.7-.555L16.9,2.568a1.041,1.041,0,0,0-1.8,0L13.438,6.04,9.74,6.6a1,1,0,0,0-.56,1.694L9.886,9H8.76a2,2,0,0,0-1.28,3.538,11.478,11.478,0,0,1,4.085,7.592l.319,2.882A2,2,0,0,0,10,25v3a2,2,0,0,0,2,2h8a2,2,0,0,0,2-2V25a2,2,0,0,0-1.884-1.988l.319-2.881a11.483,11.483,0,0,1,4.088-7.6A2,2,0,0,0,23.24,9ZM15,19.19V23H13.9l-.342-3.092a13.587,13.587,0,0,0-1.066-3.95.961.961,0,0,0,.211-.079l1.513-.821A11.434,11.434,0,0,1,15,19.19Zm2,0a11.434,11.434,0,0,1,.788-4.132l1.513.821a1.016,1.016,0,0,0,.21.082,13.6,13.6,0,0,0-1.065,3.948L18.1,23H17ZM14.26,7.939a1,1,0,0,0,.754-.557L16,5.318l.986,2.064a1,1,0,0,0,.754.557l2.27.34L18.347,9.953a1,1,0,0,0-.278.869l.386,2.323-1.978-1.074a1,1,0,0,0-.954,0l-1.978,1.074.386-2.323a1,1,0,0,0-.278-.869L11.99,8.279ZM16.025,14.1c-.008.02-.017.039-.025.059-.008-.02-.017-.039-.025-.059L16,14.088ZM8.76,11h3.114l-.487,2.928A13.552,13.552,0,0,0,8.76,11ZM12,28V25h8l0,3Zm8.613-14.072L20.126,11H23.24A13.547,13.547,0,0,0,20.613,13.928Z"></path>
                                        </svg>
                                    </div>
                                <?php elseif ($rank === 2): ?>
                                    <div class="w-10 h-10 inline-block">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64">
                                          <rect width="22" height="7" x="21" y="54" fill="#C0C0C0" rx="2"></rect>
                                          <path fill="#fff" d="M40 54v3a2.006 2.006 0 0 1-2 2H21v-3a2.006 2.006 0 0 1 2-2Z" opacity=".2"></path>
                                          <path fill="#C0C0C0" d="M35 45v9a2.006 2.006 0 0 1-2 2h-2a2.006 2.006 0 0 1-2-2v-9Z"></path>
                                          <path fill="#fff" d="M32 45v7a2.006 2.006 0 0 1-2 2h-1v-9Z" opacity=".2"></path>
                                          <path fill="#C0C0C0" d="M47.93 20.1C47.57 26.76 45.37 47 32 47c-12.875.337-15.72-20.003-15.93-26.9a1.998 1.998 0 0 1 2-2.1h27.86a1.998 1.998 0 0 1 2 2.1Z"></path>
                                          <path fill="#fff" d="M44.93 18c-.051 8.295-4.449 32.43-20.74 25.98-6.51-5.89-7.85-18.81-8.12-23.88a1.998 1.998 0 0 1 2-2.1Z" opacity=".2"></path>
                                          <path fill="#E8E8E8" d="m38.65 18 .17 6L32 21.57 25.18 24c.06-2.063.152-5.187.2-7.24l-4.42-5.74 6.95-2.05C28.515 8.087 31.469 3.789 32 3l4.09 5.97c1.717.508 5.223 1.542 6.95 2.05l-4.42 5.74Z"></path>
                                        </svg>
                                    </div>
                                <?php elseif ($rank === 3): ?>
                                    <div class="w-10 h-10 inline-block">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64">
                                          <rect width="22" height="7" x="21" y="54" fill="#CD7F32" rx="2"></rect>
                                          <path fill="#fff" d="M40 54v3a2.006 2.006 0 0 1-2 2H21v-3a2.006 2.006 0 0 1 2-2Z" opacity=".2"></path>
                                          <path fill="#CD7F32" d="M35 45v9a2.006 2.006 0 0 1-2 2h-2a2.006 2.006 0 0 1-2-2v-9Z"></path>
                                          <path fill="#CD7F32" d="M47.93 20.1C47.57 26.76 45.37 47 32 47c-12.875.337-15.72-20.003-15.93-26.9a1.998 1.998 0 0 1 2-2.1h27.86a1.998 1.998 0 0 1 2 2.1Z"></path>
                                          <path fill="#E6A877" d="m38.65 18 .17 6L32 21.57 25.18 24c.06-2.063.152-5.187.2-7.24l-4.42-5.74 6.95-2.05C28.515 8.087 31.469 3.789 32 3l4.09 5.97c1.717.508 5.223 1.542 6.95 2.05l-4.42 5.74Z"></path>
                                        </svg>
                                    </div>
                                <?php else: ?>
                                    <div class="w-8 h-8 inline-block opacity-40">
                                        <img src="https://img.icons8.com/ios-filled/50/CCCCCC/motorbike-helmet.png" alt="helmet" class="w-full h-full">
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="p-4 flex items-center gap-3">
                                <img src="https://api.dicebear.com/7.x/<?php echo $entry['avatar_style'] ?? 'avataaars'; ?>/svg?seed=<?php echo $entry['username']; ?>" class="w-8 h-8 rounded-full bg-slate-700">
                                <div>
                                    <div class="font-bold text-white <?php echo $isMe ? 'text-orange-400' : ''; ?>">
                                        <?php echo htmlspecialchars($entry['username']); ?>
                                        <?php if($isMe) echo '<span class="bg-orange-500 text-white text-[10px] px-1 rounded ml-2">YOU</span>'; ?>
                                    </div>
                                    <div class="text-xs text-gray-500">Level <?php echo $entry['races_participated'] ?? 0; ?></div>
                                </div>
                            </td>
                            <td class="p-4 text-right font-mono font-bold text-white text-lg">
                                <?php echo number_format($entry['total_points'] ?? 0); ?>
                            </td>
                            <td class="p-4 text-right text-gray-400">
                                <?php echo $entry['races_participated'] ?? 0; ?>
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
