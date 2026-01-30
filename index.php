<?php
// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php'; // Ensure config is loaded first
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$user = getCurrentUser();
$upcomingRaces = [];
$completedRaces = [];

// Try to get races, but don't fail if database isn't set up yet
try {
    $upcomingRaces = getUpcomingRaces(5);
    $completedRaces = getCompletedRaces(5);
} catch (Exception $e) {
    // Database might not be set up yet - continue without races
    $upcomingRaces = [];
    $completedRaces = [];
}

// Get user stats if logged in
$stats = null;
if ($user) {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT total_points, races_participated FROM user_totals WHERE user_id = ?");
        $stmt->bind_param("i", $user['id']);
        $stmt->execute();
        $stats = $stmt->get_result()->fetch_assoc();
    } catch (Exception $e) {
        // Stats might not be available yet
        $stats = null;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Home</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap');
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0f0f0f 0%, #1a1a1a 50%, #0f0f0f 100%);
            min-height: 100vh;
        }
        
        .gradient-text {
            background: linear-gradient(135deg, #e10600 0%, #ff4444 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .card-glass {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .stat-card-premium {
            background: linear-gradient(135deg, rgba(225, 6, 0, 0.1) 0%, rgba(225, 6, 0, 0.05) 100%);
            border: 1px solid rgba(225, 6, 0, 0.2);
            transition: all 0.3s ease;
        }
        
        .stat-card-premium:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(225, 6, 0, 0.2);
        }
        
        .race-card-premium {
            transition: all 0.3s ease;
        }
        
        .race-card-premium:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(225, 6, 0, 0.2);
        }
        
        .points-glow {
            text-shadow: 0 0 20px rgba(225, 6, 0, 0.5);
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .animate-fade-in-up {
            animation: fadeInUp 0.6s ease-out;
        }
    </style>
</head>
<body class="text-white">
    <!-- Navigation -->
    <nav class="bg-gradient-to-r from-red-900 via-red-800 to-red-900 border-b border-red-700/50 shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-3">
                    <span class="text-2xl">🏎️</span>
                    <h1 class="text-xl font-bold"><?php echo SITE_NAME; ?></h1>
                </div>
                <div class="flex items-center space-x-6">
                    <?php if ($user): ?>
                        <a href="dashboard.php" class="text-white/80 hover:text-white transition">Dashboard</a>
                        <a href="leaderboard.php" class="text-white/80 hover:text-white transition">Leaderboard</a>
                        <a href="predict.php" class="text-white/80 hover:text-white transition">Predict</a>
                        <a href="logout.php" class="text-white/80 hover:text-white transition"><?php echo htmlspecialchars($user['username']); ?></a>
                    <?php else: ?>
                        <a href="login.php" class="text-white/80 hover:text-white transition">Login</a>
                        <a href="signup.php" class="bg-white text-red-800 px-4 py-2 rounded-lg font-semibold hover:bg-gray-100 transition">Sign Up</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <!-- Hero Section -->
        <section class="text-center mb-16 animate-fade-in-up">
            <h1 class="text-5xl md:text-7xl font-black mb-6 gradient-text">
                F1 2026 Fantasy
            </h1>
            <p class="text-xl text-gray-300 mb-8 max-w-2xl mx-auto">
                Predict race winners, compete with friends, and climb the leaderboard!
            </p>
            <?php if (!$user): ?>
                <a href="signup.php" class="inline-block bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white px-8 py-4 rounded-xl font-bold text-lg transition transform hover:scale-105 shadow-lg">
                    Get Started <i class="fas fa-arrow-right ml-2"></i>
                </a>
            <?php endif; ?>
        </section>

        <!-- Quick Stats (if logged in) -->
        <?php if ($user && $stats): ?>
        <section class="mb-12 animate-fade-in-up">
            <h2 class="text-3xl font-bold mb-6 flex items-center">
                <i class="fas fa-chart-bar text-red-400 mr-3"></i>
                Your Stats
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="stat-card-premium rounded-xl p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-400 text-sm mb-1">Total Points</p>
                            <p class="text-4xl font-black text-red-400 points-glow"><?php echo number_format($stats['total_points'] ?? 0); ?></p>
                        </div>
                        <div class="w-16 h-16 rounded-full bg-red-500/20 flex items-center justify-center">
                            <i class="fas fa-trophy text-red-400 text-2xl"></i>
                        </div>
                    </div>
                </div>
                <div class="stat-card-premium rounded-xl p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-400 text-sm mb-1">Races Participated</p>
                            <p class="text-4xl font-black text-blue-400"><?php echo $stats['races_participated'] ?? 0; ?></p>
                        </div>
                        <div class="w-16 h-16 rounded-full bg-blue-500/20 flex items-center justify-center">
                            <i class="fas fa-flag-checkered text-blue-400 text-2xl"></i>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <!-- Upcoming Races -->
        <section class="mb-12 animate-fade-in-up">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-3xl font-bold flex items-center">
                    <i class="fas fa-calendar-alt text-red-400 mr-3"></i>
                    Upcoming Races
                </h2>
                <?php if ($user): ?>
                    <a href="predict.php" class="text-red-400 hover:text-red-300 transition flex items-center">
                        Make Predictions <i class="fas fa-arrow-right ml-2"></i>
                    </a>
                <?php endif; ?>
            </div>
            
            <?php if (empty($upcomingRaces)): ?>
                <div class="card-glass rounded-xl p-12 text-center">
                    <i class="fas fa-calendar-times text-5xl text-gray-600 mb-4"></i>
                    <p class="text-gray-400 text-lg mb-2">No upcoming races scheduled.</p>
                    <p class="text-gray-500 text-sm">Races will appear here once the calendar is set up.</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($upcomingRaces as $race): ?>
                        <div class="race-card-premium card-glass rounded-xl p-6">
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex-1">
                                    <h3 class="text-xl font-bold mb-1"><?php echo htmlspecialchars($race['race_name']); ?></h3>
                                    <p class="text-gray-400 text-sm"><?php echo htmlspecialchars($race['circuit_name']); ?></p>
                                </div>
                                <div class="w-12 h-12 rounded-full bg-red-500/20 flex items-center justify-center flex-shrink-0 ml-4">
                                    <i class="fas fa-flag-checkered text-red-400"></i>
                                </div>
                            </div>
                            <div class="flex items-center text-gray-300 mb-4">
                                <i class="fas fa-calendar mr-2"></i>
                                <span><?php echo date('F j, Y', strtotime($race['race_date'])); ?></span>
                            </div>
                            <?php if ($user): ?>
                                <a href="predict.php?race_id=<?php echo $race['id']; ?>" class="block w-full bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white text-center py-2 rounded-lg font-semibold transition">
                                    Make Prediction
                                </a>
                            <?php else: ?>
                                <a href="signup.php" class="block w-full bg-gradient-to-r from-gray-700 to-gray-800 hover:from-gray-600 hover:to-gray-700 text-white text-center py-2 rounded-lg font-semibold transition">
                                    Sign Up to Predict
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <!-- Recent Completed Races -->
        <section class="animate-fade-in-up">
            <h2 class="text-3xl font-bold mb-6 flex items-center">
                <i class="fas fa-history text-red-400 mr-3"></i>
                Recent Completed Races
            </h2>
            
            <?php if (empty($completedRaces)): ?>
                <div class="card-glass rounded-xl p-12 text-center">
                    <i class="fas fa-trophy text-5xl text-gray-600 mb-4"></i>
                    <p class="text-gray-400 text-lg">No completed races yet.</p>
                    <p class="text-gray-500 text-sm mt-2">Race results will appear here after races are completed.</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($completedRaces as $race): ?>
                        <div class="race-card-premium card-glass rounded-xl p-6">
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex-1">
                                    <h3 class="text-xl font-bold mb-1"><?php echo htmlspecialchars($race['race_name']); ?></h3>
                                    <p class="text-gray-400 text-sm"><?php echo htmlspecialchars($race['circuit_name']); ?></p>
                                </div>
                                <div class="w-12 h-12 rounded-full bg-green-500/20 flex items-center justify-center flex-shrink-0 ml-4">
                                    <i class="fas fa-check-circle text-green-400"></i>
                                </div>
                            </div>
                            <div class="flex items-center text-gray-300 mb-4">
                                <i class="fas fa-calendar mr-2"></i>
                                <span><?php echo date('F j, Y', strtotime($race['race_date'])); ?></span>
                            </div>
                            <?php if ($user): ?>
                                <a href="race-results.php?race_id=<?php echo $race['id']; ?>" class="block w-full bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 text-white text-center py-2 rounded-lg font-semibold transition">
                                    View Results
                                </a>
                            <?php else: ?>
                                <a href="signup.php" class="block w-full bg-gradient-to-r from-gray-700 to-gray-800 hover:from-gray-600 hover:to-gray-700 text-white text-center py-2 rounded-lg font-semibold transition">
                                    Sign Up to View
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <!-- Footer -->
    <footer class="mt-16 border-t border-white/10 py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center text-gray-400">
            <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
