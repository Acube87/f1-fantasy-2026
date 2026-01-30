<?php
// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/auth.php';
require_once 'includes/functions.php';

$user = getCurrentUser();

// Redirect if already logged in (optional, but good UX for "Landing Page" vs "App")
if ($user) {
    header('Location: dashboard.php');
    exit;
}

// Get Next Race for the Hero Section
$nextRace = getNextRace();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="css/gaming-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="gaming-theme flex flex-col min-h-screen">

    <!-- Navbar -->
    <nav class="g-nav fixed w-full z-50 px-6 py-4 flex justify-between items-center">
        <div class="flex items-center gap-4">
            <div class="w-10 h-10 bg-gradient-to-br from-red-600 to-orange-500 rounded-xl flex items-center justify-center shadow-lg shadow-orange-500/20">
                <i class="fas fa-flag-checkered text-white text-lg"></i>
            </div>
            <span class="font-bold text-xl tracking-wide text-white">PADDOCK PICKS</span>
        </div>
        
        <div class="flex items-center gap-4">
            <a href="login.php" class="text-gray-300 hover:text-white font-bold text-sm transition hidden md:block">Log In</a>
            <a href="signup.php" class="g-btn g-btn-orange px-6 py-2 text-sm">Sign Up</a>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="flex-grow pt-32 pb-12 px-4 relative overflow-hidden">
        
        <!-- Background Glows -->
        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[800px] h-[600px] bg-blue-600/10 rounded-full blur-[120px] pointer-events-none"></div>

        <div class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
            
            <!-- Left Column: Copy -->
            <div class="lg:col-span-5 space-y-8 relative z-10 text-center lg:text-left">
                <div>
                    <span class="bg-orange-500/10 border border-orange-500/20 text-orange-500 text-xs font-bold px-3 py-1 rounded-full uppercase tracking-wider mb-4 inline-block">
                        Season 2026 Live
                    </span>
                    <h1 class="text-5xl md:text-7xl font-black text-white italic leading-tight mb-4">
                        READY TO <br>
                        <span class="g-text-gradient">RACE?</span>
                    </h1>
                    <p class="text-lg text-gray-400 leading-relaxed max-w-lg mx-auto lg:mx-0">
                        Join the ultimate office prediction league. Compete with friends, predict race results, and climb the global leaderboard.
                    </p>
                </div>

                <div class="flex flex-col sm:flex-row gap-4 justify-center lg:justify-start">
                    <a href="signup.php" class="g-btn g-btn-orange px-8 py-4 text-lg flex items-center justify-center gap-2 shadow-lg shadow-orange-500/20">
                        START PLAYING <i class="fas fa-arrow-right"></i>
                    </a>
                    <a href="login.php" class="g-btn px-8 py-4 text-lg border border-white/10 hover:bg-white/5 text-white flex items-center justify-center gap-2">
                        LOGIN
                    </a>
                </div>

                <div class="flex items-center justify-center lg:justify-start gap-8 pt-4 opacity-70">
                    <div class="flex flex-col">
                        <span class="text-2xl font-black text-white">24</span>
                        <span class="text-xs uppercase text-gray-500 font-bold">Races</span>
                    </div>
                    <div class="w-px h-8 bg-white/10"></div>
                    <div class="flex flex-col">
                        <span class="text-2xl font-black text-white">11</span>
                        <span class="text-xs uppercase text-gray-500 font-bold">Teams</span>
                    </div>
                    <div class="w-px h-8 bg-white/10"></div>
                    <div class="flex flex-col">
                        <span class="text-2xl font-black text-white">22</span>
                        <span class="text-xs uppercase text-gray-500 font-bold">Drivers</span>
                    </div>
                </div>
            </div>

            <!-- Right Column: Visual Hero (The Car Card) -->
            <div class="lg:col-span-7 relative z-10">
                <!-- Tilted Card Effect -->
                <div class="relative transition-transform duration-500 hover:scale-[1.02]">
                    
                    <!-- The Card -->
                    <div class="g-card p-0 relative h-[450px] md:h-[500px] flex flex-col justify-end overflow-hidden shadow-2xl shadow-blue-900/40 rounded-3xl border border-white/10">
                        <!-- BG Image: Penguin/Australia Theme or Generic Racing -->
                        <div class="absolute inset-0 bg-[url('https://images.unsplash.com/photo-1541336528065-8f1fdc435835?q=80&w=2070&auto=format&fit=crop')] bg-cover bg-center"></div>
                        <div class="absolute inset-0 bg-gradient-to-t from-[#0f172a] via-[#0f172a]/40 to-transparent"></div>
                        
                        <div class="relative z-10 p-8 md:p-10">
                            <?php if ($nextRace): ?>
                                <div class="flex items-center gap-3 mb-4">
                                    <span class="bg-red-600 text-white text-xs font-bold px-3 py-1 rounded-full uppercase tracking-wider">
                                        Next Event
                                    </span>
                                    <span class="text-orange-400 font-mono font-bold flex items-center gap-2">
                                        <i class="far fa-clock"></i> <?php echo date('M d', strtotime($nextRace['race_date'])); ?>
                                    </span>
                                </div>
                                <h2 class="text-4xl md:text-6xl font-black text-white mb-2 uppercase drop-shadow-lg">
                                    <?php echo htmlspecialchars($nextRace['country']); ?>
                                </h2>
                                <p class="text-xl text-gray-200 mb-8 font-medium drop-shadow-md flex items-center gap-2">
                                    <i class="fas fa-map-marker-alt text-red-500"></i> <?php echo htmlspecialchars($nextRace['circuit_name']); ?>
                                </p>
                                
                                <!-- Locked Interaction -->
                                <div class="bg-black/60 backdrop-blur-md p-6 rounded-2xl border border-white/10">
                                    <div class="flex justify-between items-center mb-4">
                                        <div>
                                            <div class="text-gray-400 text-xs font-bold uppercase mb-1">Your Prediction</div>
                                            <div class="text-white font-bold flex items-center gap-2">
                                                <i class="fas fa-lock text-gray-500"></i> <span class="text-gray-500 italic">Login to predict</span>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-gray-400 text-xs font-bold uppercase mb-1">Status</div>
                                            <div class="text-green-400 font-bold uppercase">OPEN</div>
                                        </div>
                                    </div>
                                    <a href="login.php" class="g-btn g-btn-blue w-full py-3 text-center block">
                                        LOG IN TO PLAY
                                    </a>
                                </div>
                            <?php else: ?>
                                <h2 class="text-4xl font-black text-white mb-4">SEASON PREVIEW</h2>
                                <p class="text-gray-300">Get ready for 2026.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Floating Elements decoration -->
                    <div class="absolute -top-6 -right-6 w-24 h-24 bg-orange-500 rounded-2xl rotate-12 -z-10 opacity-20 blur-xl"></div>
                    <div class="absolute -bottom-6 -left-6 w-32 h-32 bg-blue-500 rounded-full -z-10 opacity-20 blur-xl"></div>
                </div>
            </div>

        </div>

    </main>
    
    <footer class="border-t border-white/5 py-8 text-center bg-slate-900/50 backdrop-blur-md">
        <div class="flex justify-center items-center gap-2 mb-4 opacity-50">
            <i class="fas fa-flag-checkered text-white"></i>
            <span class="font-bold text-white">PADDOCK PICKS</span>
        </div>
        <p class="text-gray-600 text-xs">
            &copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. Powered by <a href="https://www.scanerrific.com" target="_blank" class="text-orange-500 hover:text-white font-bold transition">Scanerrific</a>
        </p>
    </footer>

</body>
</html>
