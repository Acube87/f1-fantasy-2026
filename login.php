<?php
require_once 'includes/auth.php';
require_once 'includes/csrf.php';
require_once 'includes/ratelimit.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRF()) {
        $error = 'Security validation failed. Please try again.';
    } else {
        $rateLimit = checkRateLimit('login', 5, 15);
        
        if (!$rateLimit['allowed']) {
            $retryMsg = getRetryAfterMessage($rateLimit['retry_after']);
            $error = "Too many failed attempts. Try again in {$retryMsg}.";
        } else {
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';
            
            if (empty($username) || empty($password)) {
                $error = 'Please fill in all fields';
            } else {
                if (loginUser($username, $password)) {
                    resetRateLimit('login');
                    header('Location: index.php#dashboard');
                    exit;
                } else {
                    recordFailedAttempt('login');
                    $attemptsLeft = $rateLimit['attempts_remaining'] - 1;
                    $error = "Invalid username or password. ({$attemptsLeft} attempts remaining)";
                }
            }
        }
    }
}

if (isLoggedIn()) {
    header('Location: index.php#dashboard');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log In — Paddock Picks</title>
    <link rel="stylesheet" href="css/gaming-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
</head>
<body class="auth-page">

    <div class="auth-bg-glow"></div>
    <div class="auth-bg-glow-2"></div>

    <nav class="topnav" style="background:rgba(26,31,46,0.8);">
        <div class="topnav-inner">
            <a href="index.php" class="topnav-left" style="gap:8px;text-decoration:none;">
                <div style="width:34px;height:34px;background:linear-gradient(135deg,var(--accent-purple),#6d28d9);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:16px;color:white;">
                    <i class="fas fa-flag-checkered"></i>
                </div>
                <span style="font-weight:800;font-size:15px;color:var(--text-primary);">PADDOCK</span>
            </a>
            <div style="display:flex;gap:8px;">
                <a href="signup.php" class="btn btn-primary btn-sm">Sign Up</a>
            </div>
        </div>
    </nav>

    <div class="auth-container">
        <div class="auth-card anim-scale-in">
            <div style="text-align:center;margin-bottom:24px;">
                <div style="font-size:28px;font-weight:800;color:var(--text-primary);margin-bottom:4px;">Welcome back</div>
                <div style="font-size:13px;color:var(--text-secondary);">Log in to manage your predictions</div>
            </div>

            <?php if ($error): ?>
                <div class="auth-error"><i class="fas fa-exclamation-circle" style="margin-right:6px;"></i> <?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST" action="login.php">
                <?php csrfField(); ?>
                <div class="auth-field">
                    <label class="auth-label">Username</label>
                    <div class="input-wrap">
                        <i class="fas fa-user input-wrap-icon"></i>
                        <input type="text" name="username" class="input" placeholder="Enter username" required autofocus>
                    </div>
                </div>

                <div class="auth-field">
                    <label class="auth-label">Password</label>
                    <div class="input-wrap">
                        <i class="fas fa-lock input-wrap-icon"></i>
                        <input type="password" name="password" class="input" placeholder="Enter password" required>
                    </div>
                    <div style="text-align:right;margin-top:6px;">
                        <a href="forgot-password.php" style="font-size:11px;color:var(--text-muted);text-decoration:none;">
                            <i class="fas fa-key" style="margin-right:3px;"></i> Forgot password?
                        </a>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" style="width:100%;padding:10px;font-size:14px;margin-top:4px;">
                    Log In <i class="fas fa-arrow-right" style="font-size:12px;"></i>
                </button>
            </form>

            <div class="auth-footer">
                Don't have an account? <a href="signup.php">Join the Grid</a>
            </div>
        </div>
    </div>

    <footer class="footer" style="position:relative;z-index:1;">
        <p class="footer-text">&copy; <?php echo date('Y'); ?> Paddock Picks</p>
    </footer>

    <script src="app.js"></script>
</body>
</html>
