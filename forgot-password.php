<?php
require_once __DIR__ . '/includes/maintenance-gate.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'includes/avatars.php';
require_once 'includes/csrf.php';
require_once 'includes/ratelimit.php';

$user = getCurrentUser();
$currentUser = $user;
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRF()) {
        $error = 'Security validation failed. Please try again.';
    } else {
        $rateLimit = checkRateLimit('forgot_password', 3, 15);
        
        if (!$rateLimit['allowed']) {
            $retryMsg = getRetryAfterMessage($rateLimit['retry_after']);
            $error = "Too many password reset requests. Please try again in {$retryMsg}.";
        } else {
            $email = trim($_POST['email'] ?? '');
            
            if (empty($email)) {
                $error = 'Please enter your email address';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Please enter a valid email address';
            } else {
                $db = getDB();
                $stmt = $db->prepare("SELECT id, username, email FROM users WHERE email = ?");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();
                $userRow = $result->fetch_assoc();
                
                if ($userRow) {
                    $token = bin2hex(random_bytes(32));
                    $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
                    
                    $stmt = $db->prepare("INSERT INTO password_reset_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
                    $stmt->bind_param("iss", $userRow['id'], $token, $expiresAt);
                    $stmt->execute();
                    
                    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
                    $host = $_SERVER['HTTP_HOST'];
                    $resetLink = "{$protocol}://{$host}/reset-password.php?token={$token}";
                    
                    $subject = "Password Reset - " . SITE_NAME;
                    $emailBody = "Hi {$userRow['username']},\n\n";
                    $emailBody .= "You requested a password reset for your " . SITE_NAME . " account.\n\n";
                    $emailBody .= "Click the link below to reset your password:\n";
                    $emailBody .= "{$resetLink}\n\n";
                    $emailBody .= "This link will expire in 1 hour.\n\n";
                    $emailBody .= "If you didn't request this, please ignore this email.\n\n";
                    $emailBody .= "Thanks,\n";
                    $emailBody .= SITE_NAME . " Team";
                    
                    $headers = "From: noreply@" . $_SERVER['HTTP_HOST'] . "\r\n";
                    $headers .= "Reply-To: noreply@" . $_SERVER['HTTP_HOST'] . "\r\n";
                    $headers .= "X-Mailer: PHP/" . phpversion();
                    
                    mail($email, $subject, $emailBody, $headers);
                }
                
                $message = 'If an account exists with that email, a password reset link has been sent.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password — <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root{--canvas:#F5F3EF;--bg:#F5F3EF;--bg2:#FAF9F7;--surface:#FFF;--card:#FFF;--card2:#FAF9F7;--surface-muted:#FAF9F7;--border:#E8E5E0;--border-light:#F0EDE8;--text:#1A1A1A;--text2:#6B6864;--text3:#A09C96;--accent:#C41E3A;--accent-soft:#F5E6E9;--live:#2D6A4F;--gold:#C9A96E;--success:#2D6A4F}
        *{box-sizing:border-box;margin:0;padding:0}
        body{background:var(--canvas);color:var(--text);font-family:'Inter',sans-serif;min-height:100vh;display:flex;flex-direction:column}
        .auth-wrap{flex:1;display:flex;align-items:center;justify-content:center;padding:100px 20px 40px}
        .auth-card{background:var(--surface);border:1px solid var(--border);width:100%;max-width:400px;padding:32px}
        .auth-error{background:var(--accent-soft);border:1px solid rgba(196,30,58,0.2);padding:10px 14px;font-size:12px;color:var(--accent);margin-bottom:16px}
        .auth-success{background:rgba(45,106,79,0.08);border:1px solid rgba(45,106,79,0.2);padding:14px;font-size:12px;color:var(--success);margin-bottom:16px;line-height:1.5}
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
        footer{padding:24px;text-align:center;border-top:1px solid var(--border)}
        footer p{font-size:11px;color:var(--text2)}
    </style>
</head>
<body>

    <?php require_once __DIR__ . '/includes/nav.php'; ?>

    <div class="auth-wrap">
        <div class="auth-card">
            <div style="text-align:center;margin-bottom:24px;">
                <div style="width:40px;height:40px;background:var(--surface-muted);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;margin:0 auto 12px;font-size:16px;color:var(--text2);">
                    <i class="fas fa-key"></i>
                </div>
                <div style="font-size:20px;font-weight:700;color:var(--text);margin-bottom:4px;">Forgot Password</div>
                <div style="font-size:12px;color:var(--text2);">Enter your email to receive a reset link</div>
            </div>

            <?php if ($error): ?>
                <div class="auth-error"><i class="fas fa-exclamation-circle" style="margin-right:6px;"></i> <?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if ($message): ?>
                <div class="auth-success"><i class="fas fa-check-circle" style="margin-right:6px;"></i> <?php echo htmlspecialchars($message); ?><br><span style="font-size:11px;color:var(--text3);margin-top:4px;display:block;">Check your email inbox (and spam folder)</span></div>
            <?php endif; ?>

            <form method="POST" action="forgot-password.php">
                <?php csrfField(); ?>
                <div class="auth-field">
                    <label class="auth-label">Email Address</label>
                    <div class="input-wrap">
                        <i class="fas fa-envelope input-wrap-icon"></i>
                        <input type="email" name="email" class="input" placeholder="Enter your email" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" style="width:100%;padding:10px;font-size:13px;margin-top:4px;">
                    Send Reset Link <i class="fas fa-paper-plane" style="font-size:11px;"></i>
                </button>
            </form>

            <div class="auth-footer">
                Remember your password? <a href="login.php">Back to Login</a>
            </div>
        </div>
    </div>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?></p>
    </footer>

    <script src="app.js"></script>
</body>
</html>
