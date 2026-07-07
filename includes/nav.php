<?php
$_navUser = $user ?? $currentUser ?? null;
$_navPage = basename($_SERVER['PHP_SELF']);
$_navItems = [
    ['href' => 'index.php#dashboard',    'icon' => 'fas fa-home',            'label' => 'Dashboard'],
    ['href' => 'index.php#predict',      'icon' => 'fas fa-pencil-alt',      'label' => 'Predict'],
    ['href' => 'leaderboard.php',        'icon' => 'fas fa-trophy',          'label' => 'Leaderboard'],
    ['href' => 'index.php#updates',      'icon' => 'fas fa-broadcast-tower', 'label' => 'Updates', 'pulse' => true],
    ['href' => 'index.php#results',      'icon' => 'fas fa-flag-checkered',  'label' => 'Results'],
    ['href' => 'index.php#achievements', 'icon' => 'fas fa-medal',           'label' => 'Achievements'],
];
$_navPoints = $navStats['total_points'] ?? ($stats['total_points'] ?? ($_SESSION['nav_points'] ?? 0));
?>
<nav class="topnav">
  <div class="topnav-inner">

    <!-- Logo -->
    <div class="topnav-left">
      <a href="index.php" style="display:flex;align-items:center;gap:10px;text-decoration:none;">
        <div style="width:34px;height:34px;background:linear-gradient(135deg,var(--accent-purple),#cc0055);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:16px;color:white;flex-shrink:0;">
          <i class="fas fa-flag-checkered"></i>
        </div>
        <span style="font-weight:800;font-size:15px;color:var(--text-primary);letter-spacing:-0.02em;">PADDOCK PITS</span>
      </a>
    </div>

    <!-- Centre nav links (logged-in only) -->
    <?php if ($_navUser): ?>
    <div class="topnav-center">
      <?php foreach ($_navItems as $_item):
        $isActive = ($_navPage === basename(strtok($_item['href'], '#')));
      ?>
      <a href="<?php echo $_item['href']; ?>"
         class="topnav-link <?php echo $isActive ? 'active' : ''; ?>"
         title="<?php echo $_item['label']; ?>"
         data-nav-link>
        <i class="<?php echo $_item['icon']; ?>"></i>
        <?php if (!empty($_item['pulse'])): ?><span class="topnav-dot"></span><?php endif; ?>
      </a>
      <?php endforeach; ?>

      <!-- Points System — labelled so it's obvious -->
      <a href="points-system.php"
         class="topnav-link <?php echo $_navPage === 'points-system.php' ? 'active' : ''; ?>"
         title="Points System"
         style="width:auto;padding:0 10px;gap:6px;font-size:12px;font-weight:700;letter-spacing:0.01em;">
        <i class="fas fa-bolt"></i>
        <span style="font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:0.04em;">Points</span>
      </a>

      <?php if (isset($_navUser['username']) && ($_navUser['username'] === 'Angrycube' || !empty($_navUser['is_admin']))): ?>
      <a href="admin/race-control.php" class="topnav-link" title="Race Control"><i class="fas fa-tower-broadcast"></i></a>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Right side -->
    <div class="topnav-right">
      <?php if ($_navUser): ?>

      <!-- Points display -->
      <div class="topnav-wallet" title="Your total points">
        <i class="fas fa-star topnav-wallet-icon" style="color:#ffd700;"></i>
        <span class="topnav-wallet-value"><?php echo number_format((int)$_navPoints); ?></span>
        <span style="font-size:10px;font-weight:700;color:var(--text-muted);letter-spacing:0.04em;">PTS</span>
      </div>

      <!-- Profile -->
      <a href="index.php#profile" class="topnav-profile">
        <div class="topnav-avatar">
          <img src="<?php echo getAvatarUrl($_navUser['avatar_style'] ?? 'avataaars', $_navUser['username']); ?>" alt="">
        </div>
        <span class="topnav-username"><?php echo htmlspecialchars($_navUser['username']); ?></span>
      </a>

      <!-- Sign out -->
      <a href="logout.php" style="color:var(--text-muted);font-size:15px;padding:6px;text-decoration:none;" title="Sign Out">
        <i class="fas fa-sign-out-alt"></i>
      </a>

      <?php else: ?>
      <a href="login.php" class="btn btn-primary btn-sm">Log In</a>
      <a href="signup.php" class="btn btn-outline btn-sm">Sign Up</a>
      <?php endif; ?>
    </div>

  </div>
</nav>
