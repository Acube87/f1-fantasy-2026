<?php
require_once 'includes/auth.php';
require_once 'includes/csrf.php';
require_once 'includes/ratelimit.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!validateCSRF()) {
        $error = 'Security validation failed. Please try again.';
    } else {
        // Check rate limit
        $rateLimit = checkRateLimit('login', 5, 15);
        
        if (!$rateLimit['allowed']) {
            $retryMsg = getRetryAfterMessage($rateLimit['retry_after']);
            $error = "Too many failed attempts. Please try again in {$retryMsg}.";
        } else {
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';
            
            if (empty($username) || empty($password)) {
                $error = 'Please fill in all fields';
            } else {
                if (loginUser($username, $password)) {
                    // Reset rate limit on successful login
                    resetRateLimit('login');
                    header('Location: dashboard.php');
                    exit;
                } else {
                    // Record failed attempt
                    recordFailedAttempt('login');
                    $attemptsLeft = $rateLimit['attempts_remaining'] - 1;
                    $error = "Invalid username or password. ({$attemptsLeft} attempts remaining)";
                }
            }
        }
    }
}

// Redirect if already logged in
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
    <title>Login - <?php echo SITE_NAME; ?></title>
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
        <a href="signup.php" class="g-btn g-btn-orange px-6 py-2 text-sm">Sign Up</a>
    </nav>

    <!-- Main Content -->
    <main class="flex-grow flex items-center justify-center relative pt-20 pb-12 px-4">
        <!-- Decorative Background Elements -->
        <div class="absolute top-1/4 left-1/4 w-64 h-64 bg-blue-600/20 rounded-full blur-[100px]"></div>
        <div class="absolute bottom-1/4 right-1/4 w-64 h-64 bg-orange-600/20 rounded-full blur-[100px]"></div>

        <div class="g-card p-8 md:p-10 max-w-md w-full relative z-10 border-t-4 border-t-orange-500">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-black text-white italic mb-2">WELCOME BACK</h1>
                <p class="text-gray-400 text-sm">Log in to manage your team and predictions</p>
            </div>

            <?php if ($error): ?>
                <div class="bg-red-500/10 border border-red-500/30 text-red-400 p-3 rounded-xl mb-6 text-sm text-center font-bold">
                    <i class="fas fa-exclamation-circle mr-2"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="login.php" class="space-y-5">
                <?php csrfField(); ?>
                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Username</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-500">
                            <i class="fas fa-user"></i>
                        </div>
                        <input type="text" 
                               name="username" 
                               class="w-full bg-black/30 border border-white/10 rounded-xl px-10 py-3 text-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-none transition"
                               placeholder="Enter username"
                               required>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-500">
                            <i class="fas fa-lock"></i>
                        </div>
                        <input type="password" 
                               name="password" 
                               class="w-full bg-black/30 border border-white/10 rounded-xl px-10 py-3 text-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-none transition"
                               placeholder="Enter password"
                               required>
                    </div>
                </div>

                <button type="submit" class="w-full bg-gradient-to-r from-orange-500 to-red-600 hover:from-orange-600 hover:to-red-700 text-white py-3 rounded-xl transition-all font-bold text-sm uppercase tracking-wider shadow-lg hover:shadow-orange-500/50 mt-4">
                    LOGIN <i class="fas fa-arrow-right ml-2 opacity-70"></i>
                </button>
            </form>

            <div class="mt-8 text-center text-sm text-gray-500">
                Don't have an account? 
                <a href="signup.php" class="text-orange-500 font-bold hover:underline">Join the Race</a>
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
