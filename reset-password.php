<?php
require_once __DIR__ . '/includes/maintenance-gate.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'includes/avatars.php';
require_once 'includes/csrf.php';

$user = getCurrentUser();
$currentUser = $user;
$error = '';
$success = false;
$validToken = false;
$token = $_GET['token'] ?? '';

if (!empty($token)) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT prt.*, u.username, u.email 
        FROM password_reset_tokens prt
        JOIN users u ON prt.user_id = u.id
        WHERE prt.token = ? 
        AND prt.used = FALSE 
        AND prt.expires_at > NOW()
    ");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    $resetData = $result->fetch_assoc();
    
    if ($resetData) {
        $validToken = true;
    } else {
        $error = 'This reset link is invalid or has expired. Please request a new one.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken) {
    if (!validateCSRF()) {
        $error = 'Security validation failed. Please try again.';
    } else {
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        if (empty($password) || empty($confirmPassword)) {
            $error = 'Please fill in all fields';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters';
        } elseif ($password !== $confirmPassword) {
            $error = 'Passwords do not match';
        } else {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            $stmt->bind_param("si", $passwordHash, $resetData['user_id']);
            
            if ($stmt->execute()) {
                $stmt = $db->prepare("UPDATE password_reset_tokens SET used = TRUE WHERE token = ?");
                $stmt->bind_param("s", $token);
                $stmt->execute();
                $success = true;
            } else {
                $error = 'Failed to update password. Please try again.';
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
    <title>Reset Password — <?php echo SITE_NAME; ?></title>
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
            <?php if ($success): ?>
                <div style="text-align:center;">
                    <div style="width:48px;height:48px;background:rgba(45,106,79,0.1);border:1px solid rgba(45,106,79,0.2);display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:20px;color:var(--success);">
                        <i class="fas fa-check"></i>
                    </div>
                    <div style="font-size:20px;font-weight:700;color:var(--text);margin-bottom:8px;">Password Reset</div>
                    <p style="font-size:12px;color:var(--text2);margin-bottom:20px;line-height:1.5;">Your password has been successfully updated. You can now log in with your new password.</p>
                    <a href="login.php" class="btn btn-primary" style="width:100%;padding:10px;font-size:13px;">
                        Go to Login <i class="fas fa-arrow-right" style="font-size:11px;"></i>
                    </a>
                </div>
            <?php elseif (!$validToken): ?>
                <div style="text-align:center;">
                    <div style="width:48px;height:48px;background:var(--accent-soft);border:1px solid rgba(196,30,58,0.2);display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:20px;color:var(--accent);">
                        <i class="fas fa-times"></i>
                    </div>
                    <div style="font-size:20px;font-weight:700;color:var(--text);margin-bottom:8px;">Invalid Link</div>
                    <p style="font-size:12px;color:var(--text2);margin-bottom:20px;line-height:1.5;"><?php echo htmlspecialchars($error); ?></p>
                    <a href="forgot-password.php" class="btn btn-primary" style="width:100%;padding:10px;font-size:13px;">
                        Request New Link <i class="fas fa-paper-plane" style="font-size:11px;"></i>
                    </a>
                </div>
            <?php else: ?>
                <div style="text-align:center;margin-bottom:24px;">
                    <div style="width:40px;height:40px;background:var(--surface-muted);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;margin:0 auto 12px;font-size:16px;color:var(--text2);">
                        <i class="fas fa-lock"></i>
                    </div>
                    <div style="font-size:20px;font-weight:700;color:var(--text);margin-bottom:4px;">Reset Password</div>
                    <div style="font-size:12px;color:var(--text2);">Enter your new password</div>
                </div>

                <?php if ($error): ?>
                    <div class="auth-error"><i class="fas fa-exclamation-circle" style="margin-right:6px;"></i> <?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <form method="POST" action="reset-password.php?token=<?php echo htmlspecialchars($token); ?>">
                    <?php csrfField(); ?>
                    <div class="auth-field">
                        <label class="auth-label">New Password</label>
                        <div class="input-wrap">
                            <i class="fas fa-lock input-wrap-icon"></i>
                            <input type="password" name="password" class="input" placeholder="Min 6 characters" minlength="6" required>
                        </div>
                    </div>

                    <div class="auth-field">
                        <label class="auth-label">Confirm Password</label>
                        <div class="input-wrap">
                            <i class="fas fa-check input-wrap-icon"></i>
                            <input type="password" name="confirm_password" class="input" placeholder="Confirm new password" minlength="6" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width:100%;padding:10px;font-size:13px;margin-top:4px;">
                        Reset Password <i class="fas fa-check" style="font-size:11px;"></i>
                    </button>
                </form>

                <div class="auth-footer">
                    Remember your password? <a href="login.php">Back to Login</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?></p>
    </footer>

    <script src="app.js"></script>
</body>
</html>
