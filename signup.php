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
    header('Location: index.php#dashboard');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up — <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root{--canvas:#F5F3EF;--bg:#F5F3EF;--bg2:#FAF9F7;--surface:#FFF;--card:#FFF;--card2:#FAF9F7;--surface-muted:#FAF9F7;--border:#E8E5E0;--border-light:#F0EDE8;--text:#1A1A1A;--text2:#6B6864;--text3:#A09C96;--accent:#C41E3A;--accent-soft:#F5E6E9;--live:#2D6A4F;--gold:#C9A96E;--success:#2D6A4F}
        *{box-sizing:border-box;margin:0;padding:0}
        body{background:var(--canvas);color:var(--text);font-family:'Inter',sans-serif;min-height:100vh;display:flex;flex-direction:column}
        .auth-wrap{flex:1;display:flex;align-items:center;justify-content:center;padding:100px 20px 40px}
        .auth-card{background:var(--surface);border:1px solid var(--border);width:100%;max-width:460px;padding:32px}
        .auth-error{background:var(--accent-soft);border:1px solid rgba(196,30,58,0.2);padding:10px 14px;font-size:12px;color:var(--accent);margin-bottom:16px}
        .auth-success{background:rgba(45,106,79,0.08);border:1px solid rgba(45,106,79,0.2);padding:10px 14px;font-size:12px;color:var(--success);margin-bottom:16px}
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
        .auth-footer{text-align:center;margin-top:20px;padding-top:16px;border-top:1px solid var(--border);font-size:12px;color:var(--text2)}
        .auth-footer a{color:var(--accent);text-decoration:none;font-weight:600}
        .auth-footer a:hover{text-decoration:underline}
        .honey{display:none}
        footer{padding:24px;text-align:center;border-top:1px solid var(--border)}
        footer p{font-size:11px;color:var(--text2)}
        @media(max-width:480px){.auth-card{padding:24px 16px}}
    </style>
</head>
<body>

    <?php require_once __DIR__ . '/includes/nav.php'; ?>

    <div class="auth-wrap">
        <div class="auth-card">
            <div style="text-align:center;margin-bottom:24px;">
                <div style="font-size:22px;font-weight:700;color:var(--text);margin-bottom:4px;">Join the Grid</div>
                <div style="font-size:12px;color:var(--text2);">Create your account to start predicting</div>
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
                    <div class="auth-field" style="margin-bottom:0">
                        <label class="auth-label">Password *</label>
                        <div class="input-wrap">
                            <i class="fas fa-lock input-wrap-icon"></i>
                            <input type="password" name="password" class="input" placeholder="Min 8 chars" required>
                        </div>
                    </div>
                    <div class="auth-field" style="margin-bottom:0">
                        <label class="auth-label">Confirm *</label>
                        <div class="input-wrap">
                            <i class="fas fa-check input-wrap-icon"></i>
                            <input type="password" name="confirm_password" class="input" placeholder="Repeat" required>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" style="width:100%;padding:10px;font-size:13px;margin-top:16px;">
                    Start Engine <i class="fas fa-rocket" style="font-size:11px;"></i>
                </button>
            </form>
            <?php endif; ?>

            <div class="auth-footer">
                Already have an account? <a href="login.php">Log In</a>
            </div>
        </div>
    </div>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?></p>
    </footer>

    <script src="app.js"></script>
</body>
</html>
