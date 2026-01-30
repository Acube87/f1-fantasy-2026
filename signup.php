<?php
require_once 'includes/auth.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // HONEYPOT
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
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address';
    } else {
        $result = registerUser($username, $email, $password, $fullName);
        if ($result['success']) {
            $success = 'Account created successfully! Redirecting...';
            echo '<script>setTimeout(function(){ window.location.href="login.php"; }, 2000);</script>';
        } else {
            $error = $result['message'];
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
    <title>Sign Up - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="css/gaming-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>.honey { display: none; }</style>
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
        <a href="login.php" class="g-btn g-btn-blue px-6 py-2 text-sm">Login</a>
    </nav>

    <!-- Main Content -->
    <main class="flex-grow flex items-center justify-center relative pt-24 pb-12 px-4">
        <!-- Decorative Background Elements -->
        <div class="absolute top-1/4 right-1/4 w-64 h-64 bg-purple-600/20 rounded-full blur-[100px]"></div>
        <div class="absolute bottom-1/4 left-1/4 w-64 h-64 bg-orange-600/20 rounded-full blur-[100px]"></div>

        <div class="g-card p-8 md:p-10 max-w-md w-full relative z-10 border-t-4 border-t-blue-500">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-black text-white italic mb-2">JOIN THE GRID</h1>
                <p class="text-gray-400 text-sm">Create your account to start predicting</p>
            </div>

            <?php if ($error): ?>
                <div class="bg-red-500/10 border border-red-500/30 text-red-400 p-3 rounded-xl mb-6 text-sm text-center font-bold">
                    <i class="fas fa-exclamation-circle mr-2"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="bg-green-500/10 border border-green-500/30 text-green-400 p-3 rounded-xl mb-6 text-sm text-center font-bold">
                    <i class="fas fa-check-circle mr-2"></i> <?php echo htmlspecialchars($success); ?>
                </div>
            <?php else: ?>

            <form method="POST" action="signup.php" class="space-y-4">
                <!-- HONEYPOT -->
                <div class="honey">
                    <label for="website">Website</label>
                    <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Username *</label>
                    <input type="text" name="username" class="w-full bg-slate-900/50 border border-white/10 rounded-xl py-3 px-4 text-white placeholder-gray-600 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition" required autofocus>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Email *</label>
                    <input type="email" name="email" class="w-full bg-slate-900/50 border border-white/10 rounded-xl py-3 px-4 text-white placeholder-gray-600 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition" required>
                </div>
                
                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Full Name</label>
                    <input type="text" name="full_name" class="w-full bg-slate-900/50 border border-white/10 rounded-xl py-3 px-4 text-white placeholder-gray-600 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Password *</label>
                        <input type="password" name="password" class="w-full bg-slate-900/50 border border-white/10 rounded-xl py-3 px-4 text-white placeholder-gray-600 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition" required minlength="6">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Confirm *</label>
                        <input type="password" name="confirm_password" class="w-full bg-slate-900/50 border border-white/10 rounded-xl py-3 px-4 text-white placeholder-gray-600 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition" required>
                    </div>
                </div>

                <button type="submit" class="g-btn g-btn-orange w-full py-4 text-lg shadow-lg shadow-orange-500/20 mt-4">
                    START ENGINE <i class="fas fa-rocket ml-2 opacity-70"></i>
                </button>
            </form>
            <?php endif; ?>

            <div class="mt-8 text-center text-sm text-gray-500">
                Already have an account? 
                <a href="login.php" class="text-blue-500 font-bold hover:underline">Log In</a>
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
