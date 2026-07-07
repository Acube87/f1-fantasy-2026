<?php
$_navUser  = $user ?? $currentUser ?? null;
$_navPage  = basename($_SERVER['PHP_SELF']);
$_navPts   = (int)(($navStats['total_points'] ?? 0) ?: ($stats['total_points'] ?? 0));
$_isAdmin  = isset($_navUser['username']) && ($_navUser['username'] === 'Angrycube' || !empty($_navUser['is_admin']));

// Links that live inside the index.php SPA (hash-based routing)
// Active state for these is set by JS after page load
$_navLinks = [
    ['href' => 'index.php#dashboard',    'hash' => 'dashboard',    'icon' => 'fa-grip',           'label' => 'Dashboard',    'spa' => true],
    ['href' => 'index.php#predict',      'hash' => 'predict',      'icon' => 'fa-pencil-alt',     'label' => 'Predict',      'spa' => true],
    ['href' => 'index.php#results',      'hash' => 'results',      'icon' => 'fa-flag-checkered', 'label' => 'Results',      'spa' => true],
    ['href' => 'index.php#updates',      'hash' => 'updates',      'icon' => 'fa-newspaper',      'label' => 'Updates',      'spa' => true],
    ['href' => 'index.php#news',         'hash' => 'news',         'icon' => 'fa-rss',            'label' => 'News',         'spa' => true],
    ['href' => 'leaderboard.php',        'hash' => '',             'icon' => 'fa-trophy',         'label' => 'Leaderboard',  'spa' => false],
    ['href' => 'index.php#achievements', 'hash' => 'achievements', 'icon' => 'fa-medal',          'label' => 'Achievements', 'spa' => true],
    ['href' => 'index.php#profile',      'hash' => 'profile',      'icon' => 'fa-user',           'label' => 'Profile',      'spa' => true],
    ['href' => 'points-system.php',      'hash' => '',             'icon' => 'fa-bolt',           'label' => 'Points',       'spa' => false, 'gold' => true],
];
?>
<style>
.pp-nav{position:fixed;top:0;left:0;right:0;z-index:1000;height:56px;background:rgba(12,15,22,0.95);backdrop-filter:blur(16px);-webkit-backdrop-filter:blur(16px);border-bottom:1px solid rgba(255,255,255,0.07);display:flex;align-items:center;padding:0 20px;}
.pp-nav-inner{display:flex;align-items:center;justify-content:space-between;width:100%;max-width:1400px;margin:0 auto;gap:12px;}
.pp-brand{display:flex;align-items:center;gap:10px;font-weight:800;font-size:15px;letter-spacing:-0.02em;color:#f0f2f5;text-decoration:none;flex-shrink:0;}
.pp-brand-icon{width:32px;height:32px;background:#7c3aed;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:15px;color:#fff;flex-shrink:0;}
.pp-links{display:flex;align-items:center;gap:1px;flex:1;justify-content:center;}
.pp-link{height:36px;border-radius:8px;display:inline-flex;align-items:center;gap:6px;padding:0 11px;color:#9aa0b0;font-size:13px;font-weight:600;cursor:pointer;transition:color 0.15s,background 0.15s;text-decoration:none;white-space:nowrap;font-family:'Inter',-apple-system,sans-serif;border:1px solid transparent;}
.pp-link:hover{color:#f0f2f5;background:rgba(255,255,255,0.06);}
.pp-link.pp-active{color:#c084fc;background:rgba(124,58,237,0.12);border-color:rgba(124,58,237,0.2);}
.pp-link-pts{color:#ffd700 !important;background:rgba(255,215,0,0.06);border-color:rgba(255,215,0,0.2);}
.pp-link-pts:hover{background:rgba(255,215,0,0.12) !important;border-color:rgba(255,215,0,0.4) !important;color:#ffd700 !important;}
.pp-link-label{font-size:12px;}
.pp-right{display:flex;align-items:center;gap:8px;flex-shrink:0;}
.pp-pts-pill{display:flex;align-items:center;gap:5px;padding:5px 11px;border-radius:8px;background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.07);cursor:default;}
.pp-pts-pill span{font-size:13px;font-weight:800;color:#f0f2f5;font-family:'Inter',-apple-system,sans-serif;}
.pp-pts-pill small{font-size:10px;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:0.05em;}
.pp-profile{display:flex;align-items:center;gap:7px;padding:4px 10px 4px 4px;border-radius:8px;background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.07);text-decoration:none;transition:border-color 0.15s,background 0.15s;}
.pp-profile:hover{border-color:rgba(124,58,237,0.35);background:rgba(124,58,237,0.07);}
.pp-avatar{width:27px;height:27px;border-radius:50%;overflow:hidden;flex-shrink:0;background:rgba(255,255,255,0.05);}
.pp-avatar img{width:100%;height:100%;object-fit:cover;display:block;}
.pp-username{font-size:13px;font-weight:700;color:#f0f2f5;}
.pp-logout{width:34px;height:34px;display:flex;align-items:center;justify-content:center;border-radius:8px;color:#6b7280;text-decoration:none;transition:color 0.15s,background 0.15s;font-size:14px;}
.pp-logout:hover{color:#f0f2f5;background:rgba(255,255,255,0.06);}
@media(max-width:900px){.pp-link-label{display:none;}.pp-link{padding:0 9px;}}
@media(max-width:600px){.pp-username{display:none;}.pp-pts-pill{display:none;}}
</style>

<nav class="pp-nav">
  <div class="pp-nav-inner">

    <a href="index.php" class="pp-brand">
      <div class="pp-brand-icon"><i class="fa-solid fa-flag-checkered"></i></div>
      PADDOCK
    </a>

    <?php if ($_navUser): ?>
    <div class="pp-links">
      <?php foreach ($_navLinks as $lnk):
        // Only highlight non-SPA links server-side (exact page match)
        $isActive = !$lnk['spa'] && !($lnk['gold'] ?? false) && ($_navPage === basename($lnk['href']));
        $cls = 'pp-link';
        if ($isActive) $cls .= ' pp-active';
        if ($lnk['gold'] ?? false) $cls .= ' pp-link-pts';
        // SPA links get a data-hash attr so JS can activate them
        $dataHash = $lnk['spa'] ? ' data-spa-hash="' . $lnk['hash'] . '"' : '';
      ?>
      <a href="<?php echo $lnk['href']; ?>" class="<?php echo $cls; ?>"<?php echo $dataHash; ?>>
        <i class="fa-solid <?php echo $lnk['icon']; ?>"></i>
        <span class="pp-link-label"><?php echo $lnk['label']; ?></span>
      </a>
      <?php endforeach; ?>
      <?php if ($_isAdmin): ?>
      <a href="admin/race-control.php" class="pp-link" style="color:#fb923c;">
        <i class="fa-solid fa-tower-broadcast"></i>
        <span class="pp-link-label">Admin</span>
      </a>
      <?php endif; ?>
    </div>

    <div class="pp-right">
      <div class="pp-pts-pill" title="Your total points">
        <i class="fa-solid fa-star" style="color:#ffd700;font-size:11px;"></i>
        <span><?php echo number_format($_navPts); ?></span>
        <small>pts</small>
      </div>
      <a href="index.php#profile" class="pp-profile">
        <div class="pp-avatar">
          <img src="<?php echo getAvatarUrl($_navUser['avatar_style'] ?? 'avataaars', $_navUser['username']); ?>" alt="">
        </div>
        <span class="pp-username"><?php echo htmlspecialchars($_navUser['username']); ?></span>
      </a>
      <a href="logout.php" class="pp-logout" title="Sign Out"><i class="fa-solid fa-sign-out-alt"></i></a>
    </div>

    <?php else: ?>
    <div class="pp-links"></div>
    <div class="pp-right">
      <a href="login.php"  class="pp-link">Log In</a>
      <a href="signup.php" class="pp-link pp-active">Sign Up</a>
    </div>
    <?php endif; ?>

  </div>
</nav>

<script>
// Highlight the correct SPA link based on the current URL hash
(function() {
  function setActive() {
    var hash = window.location.hash.replace('#','').split('?')[0] || 'dashboard';
    document.querySelectorAll('[data-spa-hash]').forEach(function(el) {
      el.classList.toggle('pp-active', el.dataset.spaHash === hash);
    });
  }
  // Only run on index.php (the SPA host)
  if (window.location.pathname.replace(/.*\//,'') === 'index.php' || window.location.pathname === '/' || window.location.pathname.endsWith('/')) {
    setActive();
    window.addEventListener('hashchange', setActive);
  }
})();
</script>
