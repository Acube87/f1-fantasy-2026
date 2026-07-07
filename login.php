<?php
require_once __DIR__ . '/includes/maintenance-gate.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'includes/avatars.php';
require_once 'includes/csrf.php';
require_once 'includes/ratelimit.php';

$user = getCurrentUser();
$currentUser = $user;
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
    <title>Log In — <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root{--canvas:#F5F3EF;--bg:#F5F3EF;--bg2:#FAF9F7;--surface:#FFF;--card:#FFF;--card2:#FAF9F7;--surface-muted:#FAF9F7;--border:#E8E5E0;--border-light:#F0EDE8;--text:#1A1A1A;--text2:#6B6864;--text3:#A09C96;--accent:#C41E3A;--accent-soft:#F5E6E9;--live:#2D6A4F;--gold:#C9A96E;--success:#2D6A4F}
        *{box-sizing:border-box;margin:0;padding:0}
        body{background:var(--canvas);color:var(--text);font-family:'Inter',sans-serif;min-height:100vh;display:flex;flex-direction:column}
        .auth-wrap{flex:1;display:flex;align-items:center;justify-content:center;padding:100px 20px 40px}
        .auth-card{background:var(--surface);border:1px solid var(--border);width:100%;max-width:400px;padding:32px}
        .auth-error{background:var(--accent-soft);border:1px solid rgba(196,30,58,0.2);padding:10px 14px;font-size:12px;color:var(--accent);margin-bottom:16px}
        .auth-field{margin-bottom:14px}
        .auth-label{display:block;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.1em;color:var(--text2);margin-bottom:6px}
        .input-wrap{position:relative;display:flex;align-items:center;border:1px solid var(--border);background:var(--surface);transition:border-color 0.15s}
        .input-wrap:focus-within{border-color:var(--text)}
        .input-wrap-icon{position:absolute;left:10px;color:var(--text3);font-size:12px;pointer-events:none}
        .input{width:100%;padding:10px 10px 10px 30px;border:none;background:transparent;font-family:'Inter',sans-serif;font-size:13px;color:var(--text);outline:none}
        .input::placeholder{color:var(--text3)}
        .btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:10px 20px;font-family:'Inter',sans-serif;font-size:13px;font-weight:600;border:none;cursor:pointer;transition:opacity 0.15s;text-decoration:none}
        .btn-primary{background:var(--text);color:#fff}
        .btn-primary:hover{opacity:0.85}
        .btn-outline{border:1px solid var(--border);color:var(--text2);background:transparent}
        .btn-outline:hover{border-color:var(--text);color:var(--text)}
        .auth-footer{text-align:center;margin-top:20px;padding-top:16px;border-top:1px solid var(--border);font-size:12px;color:var(--text2)}
        .auth-footer a{color:var(--accent);text-decoration:none;font-weight:600}
        .auth-footer a:hover{text-decoration:underline}
        footer{padding:24px;text-align:center;border-top:1px solid var(--border)}
        footer p{font-size:11px;color:var(--text2)}
    </style>
</head>
<body>

    <?php require_once __DIR__ . '/includes/nav.php'; ?>

    <div class="auth-wrap">
        <div class="auth-card">
            <div style="text-align:center;margin-bottom:24px;">
                <div style="font-size:22px;font-weight:700;color:var(--text);margin-bottom:4px;">Welcome back</div>
                <div style="font-size:12px;color:var(--text2);">Log in to manage your predictions</div>
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
                        <a href="forgot-password.php" style="font-size:11px;color:var(--text2);text-decoration:none;">
                            <i class="fas fa-key" style="margin-right:3px;"></i> Forgot password?
                        </a>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" style="width:100%;padding:10px;font-size:13px;margin-top:4px;">
                    Log In <i class="fas fa-arrow-right" style="font-size:11px;"></i>
                </button>
            </form>

            <div class="auth-footer">
                Don't have an account? <a href="signup.php">Join the Grid</a>
            </div>
        </div>
    </div>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?></p>
    </footer>

    <script src="app.js"></script>
</body>
</html>
