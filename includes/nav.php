<?php
// Shared navigation bar — included on every page
// Expects $user to be defined (or $currentUser for legacy pages)
$_navUser = $user ?? $currentUser ?? null;
$_navPage = basename($_SERVER['PHP_SELF']);

$_navItems = [
    ['href' => 'dashboard.php',   'icon' => 'fas fa-home',            'iconColor' => 'text-orange-500',  'label' => 'Dashboard'],
    ['href' => 'predict.php',     'icon' => 'fas fa-pencil-alt',      'iconColor' => 'text-orange-400',  'label' => 'Predict'],
    ['href' => 'updates.php',     'icon' => 'fas fa-broadcast-tower', 'iconColor' => 'text-orange-400',  'label' => 'Race Updates', 'pulse' => true],
    ['href' => 'news.php',         'icon' => 'fas fa-flag-checkered',  'iconColor' => 'text-green-400',   'label' => 'Race Debrief'],
    ['href' => 'leaderboard.php', 'icon' => 'fas fa-trophy',          'iconColor' => 'text-yellow-500',  'label' => 'Leaderboard'],
    ['href' => 'achievements.php','icon' => 'fas fa-medal',           'iconColor' => 'text-purple-400',  'label' => 'Achievements'],
];
?>
<nav class="g-nav fixed w-full z-50 px-6 py-4 flex justify-between items-center">
    <div class="flex items-center gap-8">
        <a href="index.php" class="flex items-center gap-4 hover:opacity-80 transition group">
            <div class="w-10 h-10 bg-gradient-to-br from-red-600 to-orange-500 rounded-xl flex items-center justify-center shadow-lg shadow-orange-500/20 group-hover:scale-105 transition-transform duration-300">
                <i class="fas fa-flag-checkered text-white text-lg"></i>
            </div>
            <span class="font-bold text-xl tracking-wide text-white hidden sm:block group-hover:text-orange-400 transition-colors">PADDOCK PICKS</span>
        </a>

        <?php if ($_navUser): ?>
        <div class="hidden md:flex items-center gap-6">
            <?php foreach ($_navItems as $_item):
                $isActive = ($_navPage === $_item['href']);
                $linkClass = $isActive
                    ? 'text-white border-b-2 border-orange-500 pb-1'
                    : 'text-gray-300 hover:text-white';
            ?>
            <a href="<?php echo $_item['href']; ?>"
               class="<?php echo $linkClass; ?> font-bold text-sm uppercase tracking-wide transition flex items-center gap-2 relative">
                <i class="<?php echo $_item['icon'] . ' ' . $_item['iconColor']; ?>"></i>
                <?php echo $_item['label']; ?>
                <?php if (!empty($_item['pulse'])): ?>
                <span class="absolute -top-1 -right-2 w-2 h-2 bg-orange-500 rounded-full animate-pulse border border-orange-950"></span>
                <?php endif; ?>
            </a>
            <?php endforeach; ?>

            <?php if (isset($_navUser['username']) && ($_navUser['username'] === 'Angrycube' || !empty($_navUser['is_admin']))): ?>
            <a href="admin/race-control.php"
               class="text-orange-400 hover:text-orange-300 font-bold text-[10px] uppercase tracking-widest transition flex items-center gap-2 border-l border-orange-500/30 pl-6">
                <i class="fas fa-tower-broadcast"></i> Race Control
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <?php if ($_navUser): ?>
    <div class="flex items-center gap-6">
        <div class="flex items-center gap-3 pl-6 border-l border-white/10">
            <div class="text-right hidden sm:block">
                <div class="text-xs text-gray-400 font-bold uppercase tracking-wider">Driver</div>
                <div class="text-sm font-bold text-white leading-none"><?php echo htmlspecialchars($_navUser['username']); ?></div>
            </div>
            <a href="profile.php"
               class="w-10 h-10 rounded-full bg-slate-700 border-2 border-white/10 overflow-hidden hover:border-orange-500 transition cursor-pointer relative group shadow-lg shadow-black/50">
                <img src="<?php echo getAvatarUrl($_navUser['avatar_style'] ?? 'avataaars', $_navUser['username']); ?>"
                     alt="Avatar" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
            </a>
        </div>
        <a href="logout.php" class="text-gray-400 hover:text-white transition hover:rotate-90 duration-300" title="Sign Out">
            <i class="fas fa-sign-out-alt text-lg"></i>
        </a>
    </div>
    <?php else: ?>
    <div class="flex items-center gap-4">
        <a href="login.php" class="text-gray-300 hover:text-white font-medium text-sm uppercase tracking-wide">Log In</a>
        <a href="signup.php" class="g-btn g-btn-orange px-6 py-2 text-sm uppercase tracking-wide font-bold hover:shadow-lg hover:shadow-orange-500/20 transition-all">Sign Up</a>
    </div>
    <?php endif; ?>
</nav>
