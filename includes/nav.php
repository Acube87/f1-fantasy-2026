<?php
$_navUser  = $user ?? $currentUser ?? null;
$_navPage  = basename($_SERVER['PHP_SELF']);
$_navPts   = (int)(($navStats['total_points'] ?? 0) ?: ($stats['total_points'] ?? 0));
$_isAdmin  = isset($_navUser['username']) && ($_navUser['username'] === 'Angrycube' || !empty($_navUser['is_admin']));
$_navLinks = [
    ['href' => 'index.php',          'hash' => 'dashboard',   'icon' => 'fa-grip',          'label' => 'Dashboard'],
    ['href' => 'index.php',          'hash' => 'predict',     'icon' => 'fa-pencil-alt',    'label' => 'Predict'],
    ['href' => 'index.php',          'hash' => 'results',     'icon' => 'fa-flag-checkered','label' => 'Results'],
    ['href' => 'index.php',          'hash' => 'updates',     'icon' => 'fa-newspaper',     'label' => 'Updates'],
    ['href' => 'index.php',          'hash' => 'news',        'icon' => 'fa-rss',           'label' => 'News'],
    ['href' => 'leaderboard.php',    'hash' => '',            'icon' => 'fa-trophy',        'label' => 'Leaderboard'],
    ['href' => 'index.php',          'hash' => 'achievements','icon' => 'fa-medal',         'label' => 'Achievements'],
    ['href' => 'index.php',          'hash' => 'profile',     'icon' => 'fa-user',          'label' => 'Profile'],
    ['href' => 'points-system.php',  'hash' => '',            'icon' => 'fa-bolt',          'label' => 'Points'],
];
?>
<style>
.pp-nav{position:fixed;top:0;left:0;right:0;z-index:1000;height:56px;background:rgba(12,15,22,0.95);backdrop-filter:blur(16px);-webkit-backdrop-filter:blur(16px);border-bottom:1px solid rgba(255,255,255,0.05);display:flex;align-items:center;padding:0 20px;}
.pp-nav-inner{display:flex;align-items:center;justify-content:space-between;width:100%;max-width:1400px;margin:0 auto;}
.pp-brand{display:flex;align-items:center;gap:10px;font-weight:800;font-size:15px;letter-spacing:-0.02em;color:#f0f2f5;text-decoration:none;cursor:pointer;}
.pp-brand-icon{width:32px;height:32px;background:#7c3aed;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:15px;color:#fff;flex-shrink:0;}
.pp-links{display:flex;align-items:center;gap:2px;}
.pp-link{height:36px;border-radius:8px;display:inline-flex;align-items:center;gap:6px;padding:0 10px;color:#555b6e;font-size:13px;font-weight:500;cursor:pointer;transition:color 0.15s,background 0.15s;text-decoration:none;white-space:nowrap;font-family:'Inter',-apple-system,sans-serif;}
.pp-link:hover{color:#f0f2f5;background:rgba(255,255,255,0.04);}
.pp-link.pp-active{color:#a855f7;background:rgba(124,58,237,0.1);}
.pp-link.pp-pts{color:#ffd700;background:rgba(255,215,0,0.06);border:1px solid rgba(255,215,0,0.18);}
.pp-link.pp-pts:hover{background:rgba(255,215,0,0.12);border-color:rgba(255,215,0,0.35);}
.pp-link-label{font-size:11px;font-weight:600;}
.pp-right{display:flex;align-items:center;gap:10px;}
.pp-pts-display{display:flex;align-items:center;gap:6px;padding:5px 12px;border-radius:8px;background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.06);color:#f0f2f5;font-size:13px;font-weight:700;cursor:default;}
.pp-pts-num{font-family:'Inter',-apple-system,sans-serif;font-weight:800;}
.pp-pts-lbl{font-size:10px;font-weight:600;color:#555b6e;text-transform:uppercase;letter-spacing:0.05em;}
.pp-profile{display:flex;align-items:center;gap:6px;padding:4px 10px 4px 4px;border-radius:8px;background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.06);cursor:pointer;text-decoration:none;transition:border-color 0.15s,background 0.15s;}
.pp-profile:hover{border-color:rgba(124,58,237,0.4);background:rgba(124,58,237,0.06);}
.pp-avatar{width:28px;height:28px;border-radius:50%;overflow:hidden;background:rgba(255,255,255,0.05);flex-shrink:0;}
.pp-avatar img{width:100%;height:100%;object-fit:cover;}
.pp-username{font-size:13px;font-weight:600;color:#f0f2f5;}
.pp-logout{display:flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:8px;color:#555b6e;text-decoration:none;transition:color 0.15s,background 0.15s;font-size:14px;}
.pp-logout:hover{color:#f0f2f5;background:rgba(255,255,255,0.05);}
@media(max-width:768px){.pp-link-label{display:none;}.pp-link{padding:0 8px;}.pp-username{display:none;}.pp-pts-display{display:none;}}
</style>

<nav class="pp-nav">
  <div class="pp-nav-inner">

    <!-- Logo -->
    <a href="index.php" class="pp-brand">
      <div class="pp-brand-icon"><i class="fa-solid fa-flag-checkered"></i></div>
      PADDOCK
    </a>

    <?php if ($_navUser): ?>
    <!-- Nav links -->
    <div class="pp-links">
      <?php foreach ($_navLinks as $lnk):
        $href     = $lnk['href'] . ($lnk['hash'] ? '#' . $lnk['hash'] : '');
        $isPoints = $lnk['href'] === 'points-system.php';
        $isActive = !$isPoints && (
            ($_navPage === $lnk['href']) ||
            ($_navPage === 'index.php' && $lnk['hash'] === 'dashboard' && empty($_SERVER['QUERY_STRING']))
        );
        $cls = 'pp-link' . ($isActive ? ' pp-active' : '') . ($isPoints ? ' pp-pts' : '');
      ?>
      <a href="<?php echo $href; ?>" class="<?php echo $cls; ?>">
        <i class="fa-solid <?php echo $lnk['icon']; ?>"></i>
        <span class="pp-link-label"><?php echo $lnk['label']; ?></span>
      </a>
      <?php endforeach; ?>

      <?php if ($_isAdmin): ?>
      <a href="admin/race-control.php" class="pp-link" title="Race Control" style="color:#fb923c;">
        <i class="fa-solid fa-tower-broadcast"></i>
        <span class="pp-link-label">Admin</span>
      </a>
      <?php endif; ?>
    </div>

    <!-- Right -->
    <div class="pp-right">
      <div class="pp-pts-display" title="Your total points">
        <i class="fa-solid fa-star" style="color:#ffd700;font-size:12px;"></i>
        <span class="pp-pts-num"><?php echo number_format($_navPts); ?></span>
        <span class="pp-pts-lbl">pts</span>
      </div>
      <a href="index.php#profile" class="pp-profile">
        <div class="pp-avatar">
          <img src="<?php echo getAvatarUrl($_navUser['avatar_style'] ?? 'avataaars', $_navUser['username']); ?>" alt="">
        </div>
        <span class="pp-username"><?php echo htmlspecialchars($_navUser['username']); ?></span>
      </a>
      <a href="logout.php" class="pp-logout" title="Sign Out">
        <i class="fa-solid fa-sign-out-alt"></i>
      </a>
    </div>

    <?php else: ?>
    <div class="pp-links"></div>
    <div class="pp-right">
      <a href="login.php" class="pp-link">Log In</a>
      <a href="signup.php" class="pp-link pp-active">Sign Up</a>
    </div>
    <?php endif; ?>

  </div>
</nav>
