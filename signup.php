<?php
require_once 'includes/auth.php';
require_once 'includes/csrf.php';
require_once 'includes/ratelimit.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRF()) {
        $error = 'Security validation failed. Please try again.';
    } else {
        $rateLimit = checkRateLimit('signup', 3, 15);
        
        if (!$rateLimit['allowed']) {
            $retryMsg = getRetryAfterMessage($rateLimit['retry_after']);
            $error = "Too many signup attempts. Try again in {$retryMsg}.";
        } else {
            if (!empty($_POST['website'])) {
                die('Bot detected.');
            }

            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            $fullName = trim($_POST['full_name'] ?? '');
            
            if (empty($username) || empty($email) || empty($password)) {
                $error = 'Please fill in all required fields';
            } elseif ($password !== $confirmPassword) {
                $error = 'Passwords do not match';
            } elseif (strlen($password) < 8) {
                $error = 'Password must be at least 8 characters';
            } elseif (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password)) {
                $error = 'Password must contain uppercase, lowercase, and numbers';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Invalid email address';
            } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
                $error = 'Username can only contain letters, numbers, and underscores';
            } else {
                $result = registerUser($username, $email, $password, $fullName);
                if ($result['success']) {
                    $success = 'Account created! Redirecting...';
                    echo '<script>setTimeout(function(){ window.location.href="login.php"; }, 2000);</script>';
                } else {
                    $error = $result['message'];
                    recordFailedAttempt('signup');
                }
            }
        }
    }
}

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up — Paddock Picks</title>
    <link rel="stylesheet" href="css/gaming-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>.honey { display: none; }</style>
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
                <a href="login.php" class="btn btn-primary btn-sm">Log In</a>
            </div>
        </div>
    </nav>

    <div class="auth-container">
        <div class="auth-card anim-scale-in">
            <div style="text-align:center;margin-bottom:24px;">
                <div style="font-size:28px;font-weight:800;color:var(--text-primary);margin-bottom:4px;">Join the Grid</div>
                <div style="font-size:13px;color:var(--text-secondary);">Create your account to start predicting</div>
            </div>

            <?php if ($error): ?>
                <div class="auth-error"><i class="fas fa-exclamation-circle" style="margin-right:6px;"></i> <?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="auth-success"><i class="fas fa-check-circle" style="margin-right:6px;"></i> <?php echo htmlspecialchars($success); ?></div>
            <?php else: ?>

            <form method="POST" action="signup.php">
                <?php csrfField(); ?>
                <div class="honey">
                    <label for="website">Website</label>
                    <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
                </div>

                <div class="auth-field">
                    <label class="auth-label">Username *</label>
                    <div class="input-wrap">
                        <i class="fas fa-user input-wrap-icon"></i>
                        <input type="text" name="username" class="input" placeholder="Choose a username" required autofocus>
                    </div>
                </div>

                <div class="auth-field">
                    <label class="auth-label">Email *</label>
                    <div class="input-wrap">
                        <i class="fas fa-envelope input-wrap-icon"></i>
                        <input type="email" name="email" class="input" placeholder="Enter your email" required>
                    </div>
                </div>

                <div class="auth-field">
                    <label class="auth-label">Full Name</label>
                    <div class="input-wrap">
                        <i class="fas fa-id-card input-wrap-icon"></i>
                        <input type="text" name="full_name" class="input" placeholder="Your name (optional)">
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div class="auth-field">
                        <label class="auth-label">Password *</label>
                        <div class="input-wrap">
                            <i class="fas fa-lock input-wrap-icon"></i>
                            <input type="password" name="password" class="input" placeholder="Min 8 chars" required>
                        </div>
                    </div>
                    <div class="auth-field">
                        <label class="auth-label">Confirm *</label>
                        <div class="input-wrap">
                            <i class="fas fa-check input-wrap-icon"></i>
                            <input type="password" name="confirm_password" class="input" placeholder="Repeat" required>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" style="width:100%;padding:10px;font-size:14px;margin-top:4px;">
                    Start Engine <i class="fas fa-rocket" style="font-size:12px;"></i>
                </button>
            </form>
            <?php endif; ?>

            <div class="auth-footer">
                Already have an account? <a href="login.php">Log In</a>
            </div>
        </div>
    </div>

    <footer class="footer" style="position:relative;z-index:1;">
        <p class="footer-text">&copy; <?php echo date('Y'); ?> Paddock Picks</p>
    </footer>

    <script src="app.js"></script>
</body>
</html>
