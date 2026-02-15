<?php
require_once 'includes/auth.php';
require_once 'includes/csrf.php';
require_once 'includes/ratelimit.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!validateCSRF()) {
        $error = 'Security validation failed. Please try again.';
    } else {
        // Check rate limit (3 attempts per 15 minutes)
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
                // Look up user by email
                $db = getDB();
                $stmt = $db->prepare("SELECT id, username, email FROM users WHERE email = ?");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();
                $user = $result->fetch_assoc();
                
                if ($user) {
                    // Generate secure reset token
                    $token = bin2hex(random_bytes(32));
                    $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
                    
                    // Save token to database
                    $stmt = $db->prepare("INSERT INTO password_reset_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
                    $stmt->bind_param("iss", $user['id'], $token, $expiresAt);
                    $stmt->execute();
                    
                    // Build reset link
                    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
                    $host = $_SERVER['HTTP_HOST'];
                    $resetLink = "{$protocol}://{$host}/reset-password.php?token={$token}";
                    
                    // Send email
                    $subject = "Password Reset - " . SITE_NAME;
                    $emailBody = "Hi {$user['username']},\n\n";
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
                    
                    // Send email using PHP mail()
                    mail($email, $subject, $emailBody, $headers);
                }
                
                // Always show success message (security: don't reveal if email exists)
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
    <title>Forgot Password - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="css/gaming-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="gaming-theme flex flex-col min-h-screen">

    <!-- Navbar -->
    <nav class="g-nav fixed w-full z-50 px-6 py-4 flex justify-between items-center">
        <div class="flex items-center gap-4">
            <a href="index.php" class="flex items-center gap-4 hover:opacity-80 transition">
                <div class="w-10 h-10 bg-gradient-to-br from-red-600 to-orange-500 rounded-xl flex items-center justify-center shadow-lg shadow-orange-500/20">
                    <i class="fas fa-flag-checkered text-white text-lg"></i>
                </div>
                <span class="font-bold text-xl tracking-wide text-white">PADDOCK PICKS</span>
            </a>
        </div>
        <a href="login.php" class="g-btn g-btn-orange px-6 py-2 text-sm">Back to Login</a>
    </nav>

    <!-- Main Content -->
    <main class="flex-grow flex items-center justify-center relative pt-20 pb-12 px-4">
        <!-- Decorative Background Elements -->
        <div class="absolute top-1/4 left-1/4 w-64 h-64 bg-blue-600/20 rounded-full blur-[100px]"></div>
        <div class="absolute bottom-1/4 right-1/4 w-64 h-64 bg-orange-600/20 rounded-full blur-[100px]"></div>

        <div class="g-card p-8 md:p-10 max-w-md w-full relative z-10 border-t-4 border-t-blue-500">
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-blue-500/20 rounded-full mb-4">
                    <i class="fas fa-key text-blue-400 text-2xl"></i>
                </div>
                <h1 class="text-3xl font-black text-white italic mb-2">FORGOT PASSWORD</h1>
                <p class="text-gray-400 text-sm">Enter your email to receive a reset link</p>
            </div>

            <?php if ($error): ?>
                <div class="bg-red-500/10 border border-red-500/30 text-red-400 p-3 rounded-xl mb-6 text-sm text-center font-bold">
                    <i class="fas fa-exclamation-circle mr-2"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($message): ?>
                <div class="bg-green-500/10 border border-green-500/30 text-green-400 p-4 rounded-xl mb-6 text-sm text-center">
                    <i class="fas fa-check-circle mr-2"></i> <?php echo htmlspecialchars($message); ?>
                    <p class="mt-2 text-xs text-gray-400">Check your email inbox (and spam folder)</p>
                </div>
            <?php endif; ?>

            <form method="POST" action="forgot-password.php" class="space-y-5">
                <?php csrfField(); ?>
                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Email Address</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-500">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <input type="email" 
                               name="email" 
                               class="w-full bg-black/30 border border-white/10 rounded-xl px-10 py-3 text-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-none transition"
                               placeholder="Enter your email"
                               required>
                    </div>
                </div>

                <button type="submit" class="w-full bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white py-3 rounded-xl transition-all font-bold text-sm uppercase tracking-wider shadow-lg hover:shadow-blue-500/50 mt-4">
                    SEND RESET LINK <i class="fas fa-paper-plane ml-2 opacity-70"></i>
                </button>
            </form>

            <div class="mt-8 text-center text-sm text-gray-500">
                Remember your password? 
                <a href="login.php" class="text-blue-500 font-bold hover:underline">Back to Login</a>
            </div>
        </div>
    </main>
    
    <footer class="border-t border-white/5 py-6 text-center z-10 relative bg-slate-900/50 backdrop-blur-md">
        <p class="text-gray-600 text-xs">
            &copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. Powered by <a href="https://www.scanerrific.com" target="_blank" class="text-orange-500 hover:text-white font-bold transition">Scanerrific</a>
        </p>
    </footer>

</body>
</html>
