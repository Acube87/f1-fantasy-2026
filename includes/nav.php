<?php
$_navUser = $user ?? $currentUser ?? null;
$_navPage = basename($_SERVER['PHP_SELF']);
$_navItems = [
    ['href' => 'index.php#dashboard',   'icon' => 'fas fa-home',          'label' => ''],
    ['href' => 'index.php#predict',     'icon' => 'fas fa-pencil-alt',    'label' => ''],
    ['href' => 'index.php#leaderboard', 'icon' => 'fas fa-trophy',        'label' => ''],
    ['href' => 'index.php#updates',     'icon' => 'fas fa-broadcast-tower','label' => '', 'pulse' => true],
    ['href' => 'index.php#results',     'icon' => 'fas fa-flag-checkered', 'label' => ''],
    ['href' => 'index.php#achievements','icon' => 'fas fa-medal',         'label' => ''],
];
$totalPoints = $_SESSION['nav_points'] ?? 0;
$navStats = $stats ?? [];
?>
<nav class="topnav">
  <div class="topnav-inner">
    <div class="topnav-left">
      <a href="index.php" style="display:flex;align-items:center;gap:10px;text-decoration:none;">
        <div style="width:34px;height:34px;background:linear-gradient(135deg,var(--accent-purple),#6d28d9);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:16px;color:white;">
          <i class="fas fa-flag-checkered"></i>
        </div>
        <span style="font-weight:800;font-size:15px;color:var(--text-primary);letter-spacing:-0.02em;">PADDOCK</span>
      </a>
      <?php if ($_navUser): ?>
      <div class="topnav-icons">
        <div class="topnav-icon"><i class="fas fa-bell"></i><span class="topnav-dot"></span></div>
        <div class="topnav-icon"><i class="fas fa-video"></i><span class="topnav-dot"></span></div>
      </div>
      <?php endif; ?>
    </div>

    <?php if ($_navUser): ?>
    <div class="topnav-center">
      <?php foreach ($_navItems as $_item):
        $isActive = ($_navPage === $_item['href']);
      ?>
      <a href="<?php echo $_item['href']; ?>"
         class="topnav-link <?php echo $isActive ? 'active' : ''; ?>"
         title="<?php echo $_item['label'] ?: basename($_item['href'], '.php'); ?>"
         data-nav-link>
        <i class="<?php echo $_item['icon']; ?>"></i>
        <?php if (!empty($_item['pulse'])): ?><span class="topnav-dot"></span><?php endif; ?>
      </a>
      <?php endforeach; ?>
      <?php if (isset($_navUser['username']) && ($_navUser['username'] === 'Angrycube' || !empty($_navUser['is_admin']))): ?>
      <a href="admin/race-control.php" class="topnav-link" title="Race Control"><i class="fas fa-tower-broadcast"></i></a>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="topnav-right">
      <?php if ($_navUser): ?>
      <a href="login.php" class="topnav-refill">Refill</a>
      <div class="topnav-wallet">
        <i class="fas fa-coins topnav-wallet-icon"></i>
        <span class="topnav-wallet-value"><?php echo number_format($navStats['total_points'] ?? $totalPoints); ?> $</span>
      </div>
      <a href="index.php#profile" class="topnav-profile">
        <div class="topnav-avatar">
          <img src="<?php echo getAvatarUrl($_navUser['avatar_style'] ?? 'avataaars', $_navUser['username']); ?>" alt="">
        </div>
        <span class="topnav-username"><?php echo htmlspecialchars($_navUser['username']); ?></span>
      </a>
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

<script>
(function() {
  var pts = document.querySelector('.topnav-wallet-value');
  if (pts && window.f1WalletPoints) pts.textContent = window.f1WalletPoints;
})();
</script>
