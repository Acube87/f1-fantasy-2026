<?php
require_once 'includes/auth.php';
require_once 'includes/csrf.php';

$error = '';
$success = false;
$validToken = false;
$token = $_GET['token'] ?? '';

// Validate token
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

// Process password reset
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
            // Update password
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            $stmt->bind_param("si", $passwordHash, $resetData['user_id']);
            
            if ($stmt->execute()) {
                // Mark token as used
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
    <title>Reset Password - <?php echo SITE_NAME; ?></title>
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
        <a href="login.php" class="g-btn g-btn-orange px-6 py-2 text-sm">Login</a>
    </nav>

    <!-- Main Content -->
    <main class="flex-grow flex items-center justify-center relative pt-20 pb-12 px-4">
        <!-- Decorative Background Elements -->
        <div class="absolute top-1/4 left-1/4 w-64 h-64 bg-green-600/20 rounded-full blur-[100px]"></div>
        <div class="absolute bottom-1/4 right-1/4 w-64 h-64 bg-blue-600/20 rounded-full blur-[100px]"></div>

        <div class="g-card p-8 md:p-10 max-w-md w-full relative z-10 border-t-4 border-t-green-500">
            <?php if ($success): ?>
                <!-- Success State -->
                <div class="text-center">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-green-500/20 rounded-full mb-4">
                        <i class="fas fa-check text-green-400 text-2xl"></i>
                    </div>
                    <h1 class="text-3xl font-black text-white italic mb-2">PASSWORD RESET</h1>
                    <p class="text-gray-400 text-sm mb-6">Your password has been successfully updated</p>
                    
                    <div class="bg-green-500/10 border border-green-500/30 text-green-400 p-4 rounded-xl mb-6 text-sm">
                        <i class="fas fa-check-circle mr-2"></i> You can now log in with your new password
                    </div>
                    
                    <a href="login.php" class="inline-block w-full bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white py-3 rounded-xl transition-all font-bold text-sm uppercase tracking-wider shadow-lg hover:shadow-green-500/50">
                        GO TO LOGIN <i class="fas fa-arrow-right ml-2 opacity-70"></i>
                    </a>
                </div>
            <?php elseif (!$validToken): ?>
                <!-- Invalid Token State -->
                <div class="text-center">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-red-500/20 rounded-full mb-4">
                        <i class="fas fa-times text-red-400 text-2xl"></i>
                    </div>
                    <h1 class="text-3xl font-black text-white italic mb-2">INVALID LINK</h1>
                    <p class="text-gray-400 text-sm mb-6"><?php echo htmlspecialchars($error); ?></p>
                    
                    <a href="forgot-password.php" class="inline-block w-full bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white py-3 rounded-xl transition-all font-bold text-sm uppercase tracking-wider shadow-lg hover:shadow-blue-500/50">
                        REQUEST NEW LINK <i class="fas fa-paper-plane ml-2 opacity-70"></i>
                    </a>
                </div>
            <?php else: ?>
                <!-- Reset Form -->
                <div class="text-center mb-8">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-green-500/20 rounded-full mb-4">
                        <i class="fas fa-lock text-green-400 text-2xl"></i>
                    </div>
                    <h1 class="text-3xl font-black text-white italic mb-2">RESET PASSWORD</h1>
                    <p class="text-gray-400 text-sm">Enter your new password</p>
                </div>

                <?php if ($error): ?>
                    <div class="bg-red-500/10 border border-red-500/30 text-red-400 p-3 rounded-xl mb-6 text-sm text-center font-bold">
                        <i class="fas fa-exclamation-circle mr-2"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="reset-password.php?token=<?php echo htmlspecialchars($token); ?>" class="space-y-5">
                    <?php csrfField(); ?>
                    
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">New Password</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-500">
                                <i class="fas fa-lock"></i>
                            </div>
                            <input type="password" 
                                   name="password" 
                                   class="w-full bg-black/30 border border-white/10 rounded-xl px-10 py-3 text-white focus:border-green-500 focus:ring-2 focus:ring-green-500/20 outline-none transition"
                                   placeholder="Enter new password"
                                   minlength="6"
                                   required>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Minimum 6 characters</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Confirm Password</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-500">
                                <i class="fas fa-lock"></i>
                            </div>
                            <input type="password" 
                                   name="confirm_password" 
                                   class="w-full bg-black/30 border border-white/10 rounded-xl px-10 py-3 text-white focus:border-green-500 focus:ring-2 focus:ring-green-500/20 outline-none transition"
                                   placeholder="Confirm new password"
                                   minlength="6"
                                   required>
                        </div>
                    </div>

                    <button type="submit" class="w-full bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white py-3 rounded-xl transition-all font-bold text-sm uppercase tracking-wider shadow-lg hover:shadow-green-500/50 mt-4">
                        RESET PASSWORD <i class="fas fa-check ml-2 opacity-70"></i>
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </main>
    
    <footer class="border-t border-white/5 py-6 text-center z-10 relative bg-slate-900/50 backdrop-blur-md">
        <p class="text-gray-600 text-xs">
            &copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. Powered by <a href="https://www.scanerrific.com" target="_blank" class="text-orange-500 hover:text-white font-bold transition">Scanerrific</a>
        </p>
    </footer>

</body>
</html>
