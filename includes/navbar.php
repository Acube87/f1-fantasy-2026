<?php
/**
 * Shared Navigation Bar
 * Include this in all pages that need the authenticated user navbar
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

$user = getCurrentUser();
$currentUser = $user;
?>
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
            <a href="dashboard.php" class="text-gray-300 hover:text-white font-bold text-sm uppercase tracking-wide transition flex items-center gap-2<?php echo (basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? ' border-b-2 border-orange-500 pb-1' : ''; ?>">
                <i class="fas fa-home text-orange-500/80"></i> Dashboard
            </a>
            <a href="news.php" class="text-gray-300 hover:text-white font-bold text-sm uppercase tracking-wide transition flex items-center gap-2<?php echo (basename($_SERVER['PHP_SELF']) === 'news.php' ? ' border-b-2 border-orange-500 pb-1' : ''; ?>">
                <i class="fas fa-newspaper text-orange-400"></i> News & Posts
            </a>
            <a href="updates.php" class="text-gray-300 hover:text-white font-bold text-sm uppercase tracking-wide transition flex items-center gap-2 relative<?php echo (basename($_SERVER['PHP_SELF']) === 'updates.php' ? ' border-b-2 border-orange-500 pb-1' : ''; ?>">
                <i class="fas fa-broadcast-tower text-orange-400"></i> Race Updates
                <span class="absolute -top-1 -right-2 w-2 h-2 bg-orange-500 rounded-full animate-pulse border border-orange-950"></span>
            </a>
            <a href="leaderboard.php" class="text-gray-300 hover:text-white font-bold text-sm uppercase tracking-wide transition flex items-center gap-2<?php echo (basename($_SERVER['PHP_SELF']) === 'leaderboard.php' ? ' border-b-2 border-orange-500 pb-1' : ''; ?>">
                <i class="fas fa-trophy text-yellow-500/80"></i> Leaderboard
            </a>
            <a href="achievements.php" class="text-gray-300 hover:text-white font-bold text-sm uppercase tracking-wide transition flex items-center gap-2<?php echo (basename($_SERVER['PHP_SELF']) === 'achievements.php' ? ' border-b-2 border-orange-500 pb-1' : ''; ?>">
                <i class="fas fa-medal text-purple-500/80"></i> Achievements
            </a>
            <?php if ($user && ($user['username'] === 'Angrycube' || (isset($user['is_admin']) && $user['is_admin'] == 1)): ?>
            <a href="admin/race-control.php" class="text-orange-400 hover:text-orange-300 font-bold text-[10px] uppercase tracking-widest transition flex items-center gap-2 border-l border-orange-500/30 pl-6">
                <i class="fas fa-tower-broadcast"></i> Race Control
            </a>
            <?php endif; ?>
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