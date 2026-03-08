<?php
require_once __DIR__ . '/includes/maintenance-gate.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'includes/avatars.php';

$leaderboard = getLeaderboard(100);
$user = getCurrentUser(); // Standard variable name $user
$currentUser = $user;     // Alias for existing logic

// Get the latest completed race so we can link to users' latest predictions
$db = getDB();
$latestRaceQuery = $db->query("SELECT id FROM races WHERE status = 'completed' ORDER BY race_date DESC LIMIT 1");
$latestRace = $latestRaceQuery->fetch_assoc();
$latestRaceId = $latestRace['id'] ?? 1; // Default to 1 (Australia) if none completed
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leaderboard - <?php
require_once __DIR__ . '/includes/maintenance-gate.php'; echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="css/gaming-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Titillium+Web:wght@700;900&display=swap" rel="stylesheet">
    <style>
        .f1-number {
            font-family: 'Titillium Web', sans-serif;
            font-weight: 900;
            font-style: italic;
            letter-spacing: -0.02em;
        }
    </style>
</head>
<body class="gaming-theme text-gray-200">

    <!-- Navbar -->
    <!-- Navbar -->
    <nav class="g-nav fixed w-full z-50 px-6 py-4 flex justify-between items-center">
        <div class="flex items-center gap-8">
            <a href="index.php" class="flex items-center gap-4 hover:opacity-80 transition group">
                <div class="w-10 h-10 bg-gradient-to-br from-red-600 to-orange-500 rounded-xl flex items-center justify-center shadow-lg shadow-orange-500/20 group-hover:scale-105 transition-transform duration-300">
                    <i class="fas fa-flag-checkered text-white text-lg"></i>
                </div>
                <span class="font-bold text-xl tracking-wide text-white hidden sm:block group-hover:text-orange-400 transition-colors">PADDOCK PICKS</span>
            </a>
            
            <?php
require_once __DIR__ . '/includes/maintenance-gate.php'; if ($user): ?>
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
            <?php
require_once __DIR__ . '/includes/maintenance-gate.php'; endif; ?>
        </div>
        
        <?php
require_once __DIR__ . '/includes/maintenance-gate.php'; if ($user): ?>
        <div class="flex items-center gap-6">
            <div class="flex items-center gap-3 pl-6 border-l border-white/10">
                <div class="text-right hidden sm:block">
                    <div class="text-xs text-gray-400 font-bold uppercase tracking-wider">Driver</div>
                    <div class="text-sm font-bold text-white leading-none"><?php
require_once __DIR__ . '/includes/maintenance-gate.php'; echo htmlspecialchars($user['username']); ?></div>
                </div>
                <a href="profile.php" class="w-10 h-10 rounded-full bg-slate-700 border-2 border-white/10 overflow-hidden hover:border-orange-500 transition cursor-pointer relative group shadow-lg shadow-black/50">
                     <img src="<?php
require_once __DIR__ . '/includes/maintenance-gate.php'; echo getAvatarUrl($user['avatar_style'] ?? 'avataaars', $user['username']); ?>" alt="Avatar" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                </a>
            </div>
            <a href="logout.php" class="text-gray-400 hover:text-white transition hover:rotate-90 duration-300" title="Sign Out">
                <i class="fas fa-sign-out-alt text-lg"></i>
            </a>
        </div>
        <?php
require_once __DIR__ . '/includes/maintenance-gate.php'; else: ?>
        <div class="flex items-center gap-4">
            <a href="login.php" class="text-gray-300 hover:text-white font-medium text-sm uppercase tracking-wide">Log In</a>
            <a href="signup.php" class="g-btn g-btn-orange px-6 py-2 text-sm uppercase tracking-wide font-bold hover:shadow-lg hover:shadow-orange-500/20 transition-all">Sign Up</a>
        </div>
        <?php
require_once __DIR__ . '/includes/maintenance-gate.php'; endif; ?>
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
        <?php
require_once __DIR__ . '/includes/maintenance-gate.php'; if (!empty($leaderboard) && count($leaderboard) >= 3): ?>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12 items-end">
            
            <!-- 2nd Place -->
            <div class="order-2 md:order-1 g-card p-6 md:p-8 text-center border-t-4 border-t-gray-400">
                <div class="w-16 h-16 rounded-full bg-gray-400/20 mx-auto mb-4 flex items-center justify-center text-3xl shadow-[0_0_20px_rgba(156,163,175,0.3)]">🥈</div>
                <h3 class="font-bold text-xl text-white mb-1"><?php
require_once __DIR__ . '/includes/maintenance-gate.php'; echo htmlspecialchars($leaderboard[1]['username']); ?></h3>
                <div class="text-3xl font-black text-gray-300"><?php
require_once __DIR__ . '/includes/maintenance-gate.php'; echo number_format($leaderboard[1]['total_points'] ?? 0); ?> pts</div>
            </div>

            <!-- 1st Place -->
            <div class="order-1 md:order-2 g-card p-8 md:p-10 text-center border-t-4 border-t-yellow-400 transform scale-105 z-10 shadow-2xl">
                <div class="w-20 h-20 rounded-full bg-yellow-400/20 mx-auto mb-4 flex items-center justify-center text-4xl shadow-[0_0_30px_rgba(250,204,21,0.4)]">👑</div>
                <h3 class="font-black text-2xl text-white mb-1"><?php
require_once __DIR__ . '/includes/maintenance-gate.php'; echo htmlspecialchars($leaderboard[0]['username']); ?></h3>
                <div class="text-4xl font-black text-yellow-400"><?php
require_once __DIR__ . '/includes/maintenance-gate.php'; echo number_format($leaderboard[0]['total_points'] ?? 0); ?> pts</div>
                <div class="mt-2 text-xs font-bold bg-yellow-400/20 text-yellow-400 py-1 px-3 rounded-full inline-block">SEASON LEADER</div>
            </div>

            <!-- 3rd Place -->
            <div class="order-3 md:order-3 g-card p-6 md:p-8 text-center border-t-4 border-t-orange-700">
                <div class="w-16 h-16 rounded-full bg-orange-700/20 mx-auto mb-4 flex items-center justify-center text-3xl shadow-[0_0_20px_rgba(194,65,12,0.3)]">🥉</div>
                <h3 class="font-bold text-xl text-white mb-1"><?php
require_once __DIR__ . '/includes/maintenance-gate.php'; echo htmlspecialchars($leaderboard[2]['username']); ?></h3>
                <div class="text-3xl font-black text-orange-600"><?php
require_once __DIR__ . '/includes/maintenance-gate.php'; echo number_format($leaderboard[2]['total_points'] ?? 0); ?> pts</div>
            </div>
        </div>
        <?php
require_once __DIR__ . '/includes/maintenance-gate.php'; endif; ?>

        <!-- Full Table -->
        <div class="g-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="border-b border-white/10 bg-white/5">
                            <th class="p-4 text-xs font-bold text-gray-400 uppercase tracking-wider">Rank</th>
                            <th class="p-4 text-xs font-bold text-gray-400 uppercase tracking-wider text-center">#</th>
                            <th class="p-4 text-xs font-bold text-gray-400 uppercase tracking-wider">Strategy Engineer</th>
                            <th class="p-4 text-xs font-bold text-gray-400 uppercase tracking-wider text-right">Points</th>
                            <th class="p-4 text-xs font-bold text-gray-400 uppercase tracking-wider text-right">Races</th>
                            <th class="p-4 text-xs font-bold text-gray-400 uppercase tracking-wider text-right">Avg</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        <?php
require_once __DIR__ . '/includes/maintenance-gate.php'; 
                        $rank = 1;
                        foreach ($leaderboard as $entry): 
                            $avgPoints = $entry['races_participated'] > 0 
                                ? number_format($entry['total_points'] / $entry['races_participated'], 1) 
                                : '0.0';
                            $isMe = ($user && $user['username'] === $entry['username']);
                            $rowClass = $isMe ? 'bg-orange-500/10' : 'hover:bg-white/5';
                        ?>
                        <tr class="<?php
require_once __DIR__ . '/includes/maintenance-gate.php'; echo $rowClass; ?> transition">
                            <td class="p-4">
                                <?php
require_once __DIR__ . '/includes/maintenance-gate.php'; if ($rank === 1): ?>
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
                                <?php
require_once __DIR__ . '/includes/maintenance-gate.php'; elseif ($rank === 2): ?>
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
                                <?php
require_once __DIR__ . '/includes/maintenance-gate.php'; elseif ($rank === 3): ?>
                                    <div class="w-10 h-10 inline-block">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64">
                                          <rect width="22" height="7" x="21" y="54" fill="#CD7F32" rx="2"></rect>
                                          <path fill="#fff" d="M40 54v3a2.006 2.006 0 0 1-2 2H21v-3a2.006 2.006 0 0 1 2-2Z" opacity=".2"></path>
                                          <path fill="#CD7F32" d="M35 45v9a2.006 2.006 0 0 1-2 2h-2a2.006 2.006 0 0 1-2-2v-9Z"></path>
                                          <path fill="#CD7F32" d="M47.93 20.1C47.57 26.76 45.37 47 32 47c-12.875.337-15.72-20.003-15.93-26.9a1.998 1.998 0 0 1 2-2.1h27.86a1.998 1.998 0 0 1 2 2.1Z"></path>
                                          <path fill="#E6A877" d="m38.65 18 .17 6L32 21.57 25.18 24c.06-2.063.152-5.187.2-7.24l-4.42-5.74 6.95-2.05C28.515 8.087 31.469 3.789 32 3l4.09 5.97c1.717.508 5.223 1.542 6.95 2.05l-4.42 5.74Z"></path>
                                        </svg>
                                    </div>
                                <?php
require_once __DIR__ . '/includes/maintenance-gate.php'; else: ?>
                                    <div class="w-8 h-8 inline-block opacity-40">
                                        <img src="https://img.icons8.com/ios-filled/50/CCCCCC/motorbike-helmet.png" alt="helmet" class="w-full h-full">
                                    </div>
                                <?php
require_once __DIR__ . '/includes/maintenance-gate.php'; endif; ?>
                            </td>
                            <td class="p-4 text-center">
                                <span class="f1-number text-3xl <?php
require_once __DIR__ . '/includes/maintenance-gate.php'; echo $isMe ? 'text-orange-400' : 'text-white'; ?>"><?php echo $rank; ?></span>
                            </td>
                            <td class="p-4 flex items-center gap-3">
                                <img src="<?php
require_once __DIR__ . '/includes/maintenance-gate.php'; echo getAvatarUrl($entry['avatar_style'] ?? 'avataaars', $entry['username']); ?>" class="w-8 h-8 rounded-full bg-slate-700">
                                <div>
                                    <div class="font-bold text-white <?php
require_once __DIR__ . '/includes/maintenance-gate.php'; echo $isMe ? 'text-orange-400' : ''; ?> flex items-center flex-wrap gap-2">
                                        <?php if (!$isMe): ?>
                                            <a href="user-profile.php?user_id=<?php echo $entry['id']; ?>" class="hover:text-orange-400 transition-colors">
                                                <?php echo htmlspecialchars($entry['username']); ?>
                                            </a>
                                        <?php else: ?>
                                            <?php echo htmlspecialchars($entry['username']); ?>
                                        <?php endif; ?>
                                        <?php
require_once __DIR__ . '/includes/maintenance-gate.php'; 
                                        if (isset($entry['badges_data']) && $entry['badges_data']) {
                                            $badges = explode('|', $entry['badges_data']);
                                            $tierColors = [
                                                'common' => 'green',
                                                'rare' => 'blue',
                                                'epic' => 'purple',
                                                'legendary' => 'red',
                                                'special' => 'yellow'
                                            ];
                                            
                                            // Limit to 22 (max total)
                                            $displayLimit = 22;
                                            $count = 0;
                                            
                                            foreach ($badges as $badgeStr) {
                                                if ($count >= $displayLimit) break;
                                                
                                                $parts = explode(':', $badgeStr);
                                                if (count($parts) >= 3) {
                                                    $icon = $parts[0];
                                                    $tier = $parts[1];
                                                    $name = $parts[2];
                                                    $badgeColor = $tierColors[$tier ?? 'common'] ?? 'gray';
                                                    
                                                    echo '<span class="inline-flex items-center justify-center w-5 h-5 rounded bg-' . $badgeColor . '-500/20 text-' . $badgeColor . '-400 text-xs hover:scale-110 transition cursor-help" title="' . htmlspecialchars($name) . '">';
                                                    echo '<i class="fas ' . htmlspecialchars($icon) . '"></i>';
                                                    echo '</span>';
                                                    $count++;
                                                }
                                            }
                                        }
                                        ?>
                                        <?php
require_once __DIR__ . '/includes/maintenance-gate.php'; if($isMe) echo '<span class="bg-orange-500 text-white text-[10px] px-1 rounded ml-1">YOU</span>'; ?>
                                    </div>
                                    <?php
require_once __DIR__ . '/includes/maintenance-gate.php'; if (!empty($entry['full_name'])): ?>
                                        <div class="text-[10px] text-gray-500"><?php
require_once __DIR__ . '/includes/maintenance-gate.php'; echo htmlspecialchars($entry['full_name']); ?></div>
                                    <?php
require_once __DIR__ . '/includes/maintenance-gate.php'; endif; ?>
                                    <div class="text-xs text-gray-500">Level <?php
require_once __DIR__ . '/includes/maintenance-gate.php'; echo $entry['races_participated'] ?? 0; ?></div>
                                </div>
                            </td>
                            <td class="p-4 text-right font-mono font-bold text-white text-lg">
                                <?php
require_once __DIR__ . '/includes/maintenance-gate.php'; echo number_format($entry['total_points'] ?? 0); ?>
                            </td>
                            <td class="p-4 text-right text-gray-400">
                                <?php
require_once __DIR__ . '/includes/maintenance-gate.php'; echo $entry['races_participated'] ?? 0; ?>
                            </td>
                            <td class="p-4 text-right text-gray-500">
                                <?php
require_once __DIR__ . '/includes/maintenance-gate.php'; echo $avgPoints; ?>
                            </td>
                        </tr>
                        <?php
require_once __DIR__ . '/includes/maintenance-gate.php'; $rank++; endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>
    
    <footer class="mt-12 border-t border-white/10 py-6 text-center">
        <p class="text-gray-500 text-sm mb-2">&copy; <?php
require_once __DIR__ . '/includes/maintenance-gate.php'; echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</p>
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

</body>
</html>
