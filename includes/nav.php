<?php
$_navUser  = $user ?? $currentUser ?? null;
$_navPage  = basename($_SERVER['PHP_SELF']);
$_navPts   = (int)(($navStats['total_points'] ?? 0) ?: ($stats['total_points'] ?? 0));
$_isAdmin  = isset($_navUser['username']) && ($_navUser['username'] === 'Angrycube' || !empty($_navUser['is_admin']));
$_navLinks = [
    ['href' => 'index.php#dashboard',    'hash' => 'dashboard',    'icon' => 'fa-grip',           'label' => 'Dashboard'],
    ['href' => 'index.php#predict',      'hash' => 'predict',      'icon' => 'fa-pencil-alt',     'label' => 'Predict'],
    ['href' => 'index.php#results',      'hash' => 'results',      'icon' => 'fa-flag-checkered', 'label' => 'Results'],
    ['href' => 'index.php#updates',      'hash' => 'updates',      'icon' => 'fa-newspaper',      'label' => 'Updates'],
    ['href' => 'leaderboard.php',        'hash' => '',             'icon' => 'fa-trophy',         'label' => 'Standings'],
    ['href' => 'index.php#achievements', 'hash' => 'achievements', 'icon' => 'fa-medal',          'label' => 'Achievements'],
    ['href' => 'index.php#profile',      'hash' => 'profile',      'icon' => 'fa-user',           'label' => 'Profile'],
];
?>
<style>
.pp-pills{position:fixed;top:24px;left:50%;transform:translateX(-50%);z-index:100;display:flex;align-items:center;gap:4px;padding:4px;background:var(--surface);border:1px solid var(--border);border-radius:24px;box-shadow:0 2px 12px rgba(0,0,0,0.04)}
.pp-pill{height:36px;padding:0 16px;border-radius:18px;display:inline-flex;align-items:center;gap:6px;font-size:13px;font-weight:500;color:var(--text2);cursor:pointer;text-decoration:none;transition:all 150ms;font-family:'Inter',sans-serif;border:none;background:transparent;white-space:nowrap}
.pp-pill:hover{background:var(--canvas)}
.pp-pill.pp-active{background:var(--text);color:#fff}
.pp-pill.pp-pts{color:var(--text);font-weight:600;gap:4px;padding:0 10px}
.pp-pill.pp-pts i{color:var(--gold)}
.pp-right-pills{display:flex;align-items:center;gap:4px;margin-left:8px;padding-left:8px;border-left:1px solid var(--border-light)}
.pp-avatar-pill{height:36px;padding:0 12px 0 6px;border-radius:18px;display:inline-flex;align-items:center;gap:6px;font-size:13px;font-weight:500;color:var(--text);cursor:pointer;text-decoration:none;transition:all 150ms;font-family:'Inter',sans-serif;border:none;background:transparent}
.pp-avatar-pill:hover{background:var(--canvas)}
.pp-avatar-pill img{width:24px;height:24px;border-radius:50%;object-fit:cover}
@media(max-width:900px){.pp-pills{top:8px;padding:2px;gap:2px}.pp-pill{padding:0 10px;font-size:12px}}
@media(max-width:700px){.pp-pill .pp-label{display:none}.pp-pill{padding:0 10px}}
</style>

<nav class="pp-pills">
  <?php if ($_navUser): ?>
    <?php foreach ($_navLinks as $lnk): ?>
      <a href="<?php echo $lnk['href']; ?>" class="pp-pill" data-spa-hash="<?php echo $lnk['hash']; ?>">
        <i class="fa-solid <?php echo $lnk['icon']; ?>"></i>
        <span class="pp-label"><?php echo $lnk['label']; ?></span>
      </a>
    <?php endforeach; ?>
    <?php if ($_isAdmin): ?>
      <a href="admin/race-control.php" class="pp-pill" style="color:var(--accent)"><i class="fa-solid fa-tower-broadcast"></i><span class="pp-label">Admin</span></a>
    <?php endif; ?>
    <div class="pp-right-pills">
      <a href="points-system.php" class="pp-pill pp-pts"><i class="fa-solid fa-star"></i><?php echo number_format($_navPts); ?></a>
      <a href="index.php#profile" class="pp-avatar-pill">
        <img src="<?php echo getAvatarUrl($_navUser['avatar_style'] ?? 'avataaars', $_navUser['username']); ?>" alt="">
        <?php echo htmlspecialchars($_navUser['username']); ?>
      </a>
      <a href="logout.php" class="pp-pill" title="Sign Out"><i class="fa-solid fa-sign-out-alt"></i></a>
    </div>
  <?php else: ?>
    <a href="index.php" class="pp-pill pp-active" data-spa-hash="dashboard"><i class="fa-solid fa-grip"></i><span class="pp-label">Dashboard</span></a>
    <a href="leaderboard.php" class="pp-pill"><i class="fa-solid fa-trophy"></i><span class="pp-label">Standings</span></a>
    <div class="pp-right-pills">
      <a href="login.php" class="pp-pill">Log In</a>
      <a href="signup.php" class="pp-pill pp-active">Sign Up</a>
    </div>
  <?php endif; ?>
</nav>

<script>
(function(){function a(){var h=window.location.hash.replace('#','').split('?')[0]||'dashboard';document.querySelectorAll('[data-spa-hash]').forEach(function(e){e.classList.toggle('pp-active',e.dataset.spaHash===h)})}if(window.location.pathname.replace(/.*\//,'')==='index.php'||window.location.pathname==='/'||window.location.pathname.endsWith('/')){a();window.addEventListener('hashchange',a)}})();
</script>
