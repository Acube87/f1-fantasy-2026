<?php
require_once 'includes/auth.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // HONEYPOT: If the hidden 'website' field is filled, it's a bot.
    // We fail silently or show a generic error to confuse them.
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
            $success = 'Registration successful! You can now login.';
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
    <link rel="stylesheet" href="css/style.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap');
        body { font-family: 'Inter', sans-serif; background: #0f0f0f; color: white; }
        .honey { display: none; }
    </style>
</head>
<body class="bg-gray-900 text-white min-h-screen flex flex-col">
    <header class="bg-red-900 border-b border-red-800 p-4">
        <nav class="container mx-auto flex justify-between items-center">
            <h1 class="text-xl font-bold">🏎️ <?php echo SITE_NAME; ?></h1>
            <ul class="flex space-x-4">
                <li><a href="index.php" class="hover:text-red-300">Home</a></li>
                <li><a href="login.php" class="hover:text-red-300">Login</a></li>
            </ul>
        </nav>
    </header>

    <main class="container mx-auto flex-grow flex items-center justify-center py-12 px-4">
        <div class="w-full max-w-md bg-white/5 p-8 rounded-xl border border-white/10">
            <h2 class="text-3xl font-bold mb-6 text-center">Sign Up</h2>
            
            <?php if ($error): ?>
                <div class="bg-red-500/20 text-red-300 p-3 rounded mb-4 text-center"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="bg-green-500/20 text-green-300 p-3 rounded mb-4 text-center"><?php echo htmlspecialchars($success); ?></div>
                <p class="text-center"><a href="login.php" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded w-full block transition">Go to Login</a></p>
            <?php else: ?>
                <form method="POST" action="signup.php" class="space-y-4">
                    <!-- HONEYPOT FIELD (Hidden) -->
                    <div class="honey">
                        <label for="website">Website</label>
                        <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
                    </div>

                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-400">Username *</label>
                        <input type="text" id="username" name="username" required autofocus class="mt-1 w-full bg-white/10 border border-white/20 rounded p-2 focus:border-red-500 outline-none">
                    </div>
                    
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-400">Email *</label>
                        <input type="email" id="email" name="email" required class="mt-1 w-full bg-white/10 border border-white/20 rounded p-2 focus:border-red-500 outline-none">
                    </div>
                    
                    <div>
                        <label for="full_name" class="block text-sm font-medium text-gray-400">Full Name</label>
                        <input type="text" id="full_name" name="full_name" class="mt-1 w-full bg-white/10 border border-white/20 rounded p-2 focus:border-red-500 outline-none">
                    </div>
                    
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-400">Password *</label>
                        <input type="password" id="password" name="password" required minlength="6" class="mt-1 w-full bg-white/10 border border-white/20 rounded p-2 focus:border-red-500 outline-none">
                        <small class="text-gray-500">Minimum 6 characters</small>
                    </div>
                    
                    <div>
                        <label for="confirm_password" class="block text-sm font-medium text-gray-400">Confirm Password *</label>
                        <input type="password" id="confirm_password" name="confirm_password" required class="mt-1 w-full bg-white/10 border border-white/20 rounded p-2 focus:border-red-500 outline-none">
                    </div>
                    
                    <button type="submit" class="w-full bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white font-bold py-3 rounded transition shadow-lg">Sign Up</button>
                </form>
            <?php endif; ?>
            
            <p class="mt-6 text-center text-gray-400">Already have an account? <a href="login.php" class="text-red-400 hover:text-red-300">Login here</a></p>
        </div>
    </main>
    
    <footer class="mt-auto border-t border-white/10 py-6 bg-black/20">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <p class="text-gray-400 mb-2">&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</p>
            <p class="text-gray-500 text-sm">
                Powered by <a href="https://www.scanerrific.com" target="_blank" class="text-red-400 hover:text-red-300 font-semibold transition">Scanerrific</a>
            </p>
        </div>
    </footer>
</body>
</html>
