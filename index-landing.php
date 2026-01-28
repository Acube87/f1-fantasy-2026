<?php
require_once 'includes/auth.php';

$error = '';
$login_attempted = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $login_attempted = true;
    
    if (empty($username) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        if (loginUser($username, $password)) {
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Invalid username or password';
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
    <title>F1 2026 Prediction - Office Racing League</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;600;700;800;900&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            overflow-x: hidden;
        }

        .racing-font {
            font-family: 'Orbitron', sans-serif;
        }

        .gradient-bg {
            background: linear-gradient(135deg, #0a0a0a 0%, #1a0a1f 25%, #2d1b3d 50%, #1a0a1f 75%, #0a0a0a 100%);
            position: relative;
            min-height: 100vh;
        }

        .gradient-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at 20% 50%, rgba(236, 72, 153, 0.15) 0%, transparent 50%),
                        radial-gradient(circle at 80% 50%, rgba(59, 130, 246, 0.15) 0%, transparent 50%);
            pointer-events: none;
        }

        .neon-text {
            text-shadow: 0 0 10px rgba(236, 72, 153, 0.8),
                         0 0 20px rgba(236, 72, 153, 0.6),
                         0 0 30px rgba(236, 72, 153, 0.4),
                         0 0 40px rgba(236, 72, 153, 0.2);
        }

        .neon-blue {
            text-shadow: 0 0 10px rgba(59, 130, 246, 0.8),
                         0 0 20px rgba(59, 130, 246, 0.6),
                         0 0 30px rgba(59, 130, 246, 0.4);
        }

        .glass-card {
            background: rgba(30, 20, 40, 0.6);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
        }

        .btn-neon {
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .btn-neon::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.5s;
        }

        .btn-neon:hover::before {
            left: 100%;
        }

        .btn-neon:hover {
            box-shadow: 0 0 20px rgba(236, 72, 153, 0.6),
                        0 0 40px rgba(236, 72, 153, 0.4);
            transform: translateY(-2px);
        }

        .racing-stripes {
            position: absolute;
            width: 100%;
            height: 3px;
            background: linear-gradient(90deg, 
                transparent 0%, 
                #ec4899 25%, 
                #3b82f6 50%, 
                #ec4899 75%, 
                transparent 100%);
            animation: slide 3s linear infinite;
        }

        @keyframes slide {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }

        .floating {
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }

        .grid-pattern {
            background-image: 
                linear-gradient(rgba(236, 72, 153, 0.05) 1px, transparent 1px),
                linear-gradient(90deg, rgba(59, 130, 246, 0.05) 1px, transparent 1px);
            background-size: 50px 50px;
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            pointer-events: none;
        }

        input:focus {
            outline: none;
            border-color: #ec4899;
            box-shadow: 0 0 0 3px rgba(236, 72, 153, 0.1);
        }

        .stat-badge {
            background: linear-gradient(135deg, rgba(236, 72, 153, 0.2) 0%, rgba(59, 130, 246, 0.2) 100%);
            border: 1px solid rgba(236, 72, 153, 0.3);
        }

        .f1-car-silhouette {
            position: absolute;
            opacity: 0.03;
            width: 800px;
            height: 400px;
            background-image: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 400"><path fill="white" d="M100,200 L200,180 L300,170 L400,165 L500,170 L600,180 L700,200 L650,220 L550,230 L450,232 L350,230 L250,220 Z M200,200 L220,210 L240,215 L260,210 L240,200 Z M560,200 L580,210 L600,215 L620,210 L600,200 Z"/></svg>');
            background-repeat: no-repeat;
            background-size: contain;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid rgba(239, 68, 68, 0.4);
            color: #fca5a5;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="gradient-bg">
        <div class="gradient-overlay"></div>
        <div class="grid-pattern"></div>
        <div class="f1-car-silhouette" style="top: 10%; left: -10%; transform: rotate(-15deg);"></div>
        <div class="f1-car-silhouette" style="bottom: 10%; right: -10%; transform: rotate(15deg) scaleX(-1);"></div>

        <!-- Top Racing Stripe -->
        <div class="racing-stripes" style="top: 0;"></div>

        <!-- Header -->
        <header class="relative z-10 px-6 py-6">
            <div class="max-w-7xl mx-auto flex justify-between items-center">
                <div class="flex items-center space-x-3">
                    <div class="w-12 h-12 bg-gradient-to-br from-pink-500 to-blue-600 rounded-lg flex items-center justify-center">
                        <span class="text-2xl">🏎️</span>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold racing-font text-white">F1 2026</h1>
                        <p class="text-xs text-gray-400">Office Racing League</p>
                    </div>
                </div>
                <nav class="flex items-center space-x-6">
                    <a href="index.php" class="text-gray-300 hover:text-pink-400 transition font-medium">Home</a>
                    <a href="leaderboard.php" class="text-gray-300 hover:text-pink-400 transition font-medium">Leaderboard</a>
                    <a href="signup.php" class="px-6 py-2 bg-gradient-to-r from-pink-500 to-purple-600 rounded-full text-white font-semibold hover:shadow-lg hover:shadow-pink-500/50 transition">Sign Up</a>
                </nav>
            </div>
        </header>

        <!-- Main Content -->
        <main class="relative z-10 px-6 py-12">
            <div class="max-w-7xl mx-auto">
                <div class="grid lg:grid-cols-2 gap-12 items-center">
                    
                    <!-- Left Side - Hero Content -->
                    <div class="space-y-8">
                        <div class="inline-block">
                            <span class="px-4 py-2 bg-gradient-to-r from-pink-500/20 to-blue-500/20 border border-pink-500/30 rounded-full text-pink-400 text-sm font-semibold racing-font">
                                Season 2026
                            </span>
                        </div>

                        <div class="space-y-4">
                            <h2 class="text-5xl lg:text-7xl font-black racing-font text-transparent bg-clip-text bg-gradient-to-r from-pink-500 via-purple-500 to-blue-500 neon-text leading-tight">
                                Predict
                            </h2>
                            <h3 class="text-6xl lg:text-8xl font-black racing-font neon-text text-pink-500">
                                The Race
                            </h3>
                            <p class="text-xl text-gray-300 max-w-md leading-relaxed">
                                Join your office league and predict race outcomes. Compete with colleagues, climb the leaderboard, and become the ultimate F1 predictor.
                            </p>
                        </div>

                        <!-- Stats -->
                        <div class="grid grid-cols-3 gap-4 pt-8">
                            <div class="stat-badge rounded-xl p-4 text-center">
                                <div class="text-3xl font-bold racing-font text-pink-400">24</div>
                                <div class="text-xs text-gray-400 mt-1">Races</div>
                            </div>
                            <div class="stat-badge rounded-xl p-4 text-center">
                                <div class="text-3xl font-bold racing-font text-blue-400">10</div>
                                <div class="text-xs text-gray-400 mt-1">Teams</div>
                            </div>
                            <div class="stat-badge rounded-xl p-4 text-center">
                                <div class="text-3xl font-bold racing-font text-purple-400">20</div>
                                <div class="text-xs text-gray-400 mt-1">Drivers</div>
                            </div>
                        </div>

                        <!-- Features -->
                        <div class="space-y-3 pt-4">
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 bg-pink-500/20 rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </div>
                                <span class="text-gray-300">Real-time predictions & scoring</span>
                            </div>
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 bg-blue-500/20 rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </div>
                                <span class="text-gray-300">Office leaderboards & rankings</span>
                            </div>
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 bg-purple-500/20 rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </div>
                                <span class="text-gray-300">Weekly prizes & bragging rights</span>
                            </div>
                        </div>
                    </div>

                    <!-- Right Side - Login Card -->
                    <div class="floating">
                        <div class="glass-card rounded-3xl p-8 lg:p-10 relative overflow-hidden">
                            <!-- Decorative Elements -->
                            <div class="absolute top-0 right-0 w-40 h-40 bg-pink-500/10 rounded-full blur-3xl"></div>
                            <div class="absolute bottom-0 left-0 w-40 h-40 bg-blue-500/10 rounded-full blur-3xl"></div>
                            
                            <div class="relative z-10">
                                <!-- Card Header -->
                                <div class="text-center mb-8">
                                    <div class="inline-block p-4 bg-gradient-to-br from-pink-500/20 to-blue-500/20 rounded-2xl mb-4">
                                        <svg class="w-12 h-12 text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                                        </svg>
                                    </div>
                                    <h4 class="text-3xl font-bold racing-font text-white mb-2">Login to Race</h4>
                                    <p class="text-gray-400">Enter your credentials to start predicting</p>
                                </div>

                                <?php if ($error): ?>
                                    <div class="alert-error"><?php echo htmlspecialchars($error); ?></div>
                                <?php endif; ?>

                                <!-- Login Form -->
                                <form method="POST" action="index-landing.php" class="space-y-6">
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-300 mb-2">Username or Email</label>
                                        <input 
                                            type="text" 
                                            name="username"
                                            placeholder="your@email.com"
                                            class="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white placeholder-gray-500 focus:border-pink-500 transition"
                                            required
                                        />
                                    </div>

                                    <div>
                                        <label class="block text-sm font-semibold text-gray-300 mb-2">Password</label>
                                        <input 
                                            type="password"
                                            name="password" 
                                            placeholder="••••••••"
                                            class="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white placeholder-gray-500 focus:border-pink-500 transition"
                                            required
                                        />
                                    </div>

                                    <div class="flex items-center justify-between text-sm">
                                        <label class="flex items-center space-x-2 cursor-pointer">
                                            <input type="checkbox" class="w-4 h-4 rounded border-gray-600 bg-white/5 text-pink-500 focus:ring-pink-500 focus:ring-offset-0">
                                            <span class="text-gray-400">Remember me</span>
                                        </label>
                                        <a href="#" class="text-pink-400 hover:text-pink-300 transition">Forgot password?</a>
                                    </div>

                                    <button 
                                        type="submit"
                                        class="w-full py-4 bg-gradient-to-r from-pink-500 to-purple-600 rounded-xl text-white font-bold racing-font text-lg btn-neon shadow-lg shadow-pink-500/30"
                                    >
                                        START RACING
                                    </button>
                                </form>

                                <!-- Sign Up Link -->
                                <div class="mt-8 text-center">
                                    <p class="text-gray-400">
                                        Don't have an account? 
                                        <a href="signup.php" class="text-pink-400 hover:text-pink-300 font-semibold transition ml-1">Sign up here</a>
                                    </p>
                                </div>

                                <!-- Powered By -->
                                <div class="mt-8 pt-6 border-t border-white/10 text-center">
                                    <p class="text-xs text-gray-500 mb-2">POWERED BY</p>
                                    <div class="flex items-center justify-center space-x-2">
                                        <div class="w-8 h-8 bg-gradient-to-br from-pink-500 to-purple-600 rounded-lg flex items-center justify-center">
                                            <span class="text-white font-bold text-xs">F1</span>
                                        </div>
                                        <span class="text-lg font-bold racing-font text-transparent bg-clip-text bg-gradient-to-r from-pink-400 to-purple-400">
                                            Office League
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Bottom Feature Cards -->
                <div class="grid md:grid-cols-3 gap-6 mt-16">
                    <div class="glass-card rounded-2xl p-6 hover:border-pink-500/50 transition">
                        <div class="text-4xl mb-4">🏆</div>
                        <h5 class="text-xl font-bold racing-font text-white mb-2">Win Prizes</h5>
                        <p class="text-gray-400 text-sm">Compete for weekly prizes and end-of-season championships</p>
                    </div>
                    <div class="glass-card rounded-2xl p-6 hover:border-blue-500/50 transition">
                        <div class="text-4xl mb-4">📊</div>
                        <h5 class="text-xl font-bold racing-font text-white mb-2">Live Stats</h5>
                        <p class="text-gray-400 text-sm">Track your predictions and compare with colleagues in real-time</p>
                    </div>
                    <div class="glass-card rounded-2xl p-6 hover:border-purple-500/50 transition">
                        <div class="text-4xl mb-4">⚡</div>
                        <h5 class="text-xl font-bold racing-font text-white mb-2">Quick Setup</h5>
                        <p class="text-gray-400 text-sm">Join your office league in seconds and start predicting today</p>
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="relative z-10 px-6 py-8 mt-12">
            <div class="max-w-7xl mx-auto text-center">
                <div class="flex items-center justify-center space-x-2 mb-4">
                    <svg class="w-5 h-5 text-pink-400" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                    <span class="text-gray-400 text-sm">TrustScore: 4.9</span>
                </div>
                <p class="text-gray-500 text-sm">© 2026 F1 Office Racing League. All rights reserved.</p>
            </div>
        </footer>

        <!-- Bottom Racing Stripe -->
        <div class="racing-stripes" style="bottom: 0;"></div>
    </div>
</body>
</html>
