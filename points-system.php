<?php
require_once 'includes/auth.php';

$user = getCurrentUser();
$pageTitle = "Points System Explained";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="css/gaming-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="gaming-theme text-gray-200">

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
        
        <div class="flex items-center gap-6">
            <?php if ($user): ?>
                <a href="dashboard.php" class="text-gray-300 hover:text-white font-bold text-sm">Dashboard</a>
                <a href="logout.php" class="text-gray-400 hover:text-white transition">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            <?php else: ?>
                <a href="login.php" class="g-btn g-btn-blue px-6 py-2">Login</a>
            <?php endif; ?>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="pt-24 pb-12 px-4 md:px-8 max-w-5xl mx-auto">
        
        <!-- Header -->
        <div class="text-center mb-12">
            <h1 class="text-4xl md:text-6xl font-black text-white mb-4 uppercase italic">
                <span class="g-text-gradient">Points System</span>
            </h1>
            <p class="text-gray-400 text-lg">Understand how you earn points and climb the leaderboard</p>
        </div>

        <!-- Quick Summary -->
        <div class="g-card p-8 mb-8 border-t-4 border-t-green-500">
            <h2 class="text-2xl font-bold text-white mb-4 flex items-center gap-3">
                <i class="fas fa-star text-green-500"></i>
                How to Earn Maximum Points
            </h2>
            <div class="space-y-3 text-gray-300">
                <p class="leading-relaxed">
                    🎯 <strong class="text-white">Predict exact positions</strong> to earn F1 base points + 3 strategy bonus
                </p>
                <p class="leading-relaxed">
                    🏆 <strong class="text-white">Get the entire podium correct</strong> (P1, P2, P3) to earn +10 bonus points
                </p>
                <p class="leading-relaxed">
                    🔧 <strong class="text-white">Predict the winning constructor</strong> to earn +5 bonus points
                </p>
                <p class="leading-relaxed">
                    ⚡ <strong class="text-white">Double Points races</strong> multiply ALL your points by 2x (China, UK, Singapore)
                </p>
            </div>
        </div>

        <!-- Grid Position Points Table -->
        <div class="g-card p-8 mb-8">
            <h2 class="text-2xl font-bold text-white mb-6 flex items-center gap-3">
                <i class="fas fa-trophy text-yellow-500"></i>
                Position Points Breakdown
            </h2>
            
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-white/20">
                            <th class="text-left py-3 px-4 text-gray-400 font-bold uppercase text-xs">Position</th>
                            <th class="text-center py-3 px-4 text-blue-400 font-bold uppercase text-xs">Base Points<br><span class="text-[10px] text-gray-500">(F1 Standard)</span></th>
                            <th class="text-center py-3 px-4 text-green-400 font-bold uppercase text-xs">Strategy Bonus<br><span class="text-[10px] text-gray-500">(Exact Match)</span></th>
                            <th class="text-center py-3 px-4 text-orange-400 font-bold uppercase text-xs">Podium Bonus<br><span class="text-[10px] text-gray-500">(All 3 Correct)</span></th>
                            <th class="text-right py-3 px-4 text-white font-bold uppercase text-xs">Total Possible</th>
                        </tr>
                    </thead>
                    <tbody class="font-mono">
                        <?php
                        $positions = [
                            ['pos' => 1, 'base' => 25, 'strategy' => 3, 'podium' => 10],
                            ['pos' => 2, 'base' => 18, 'strategy' => 3, 'podium' => 10],
                            ['pos' => 3, 'base' => 15, 'strategy' => 3, 'podium' => 10],
                            ['pos' => 4, 'base' => 12, 'strategy' => 3, 'podium' => 0],
                            ['pos' => 5, 'base' => 10, 'strategy' => 3, 'podium' => 0],
                            ['pos' => 6, 'base' => 8, 'strategy' => 3, 'podium' => 0],
                            ['pos' => 7, 'base' => 6, 'strategy' => 3, 'podium' => 0],
                            ['pos' => 8, 'base' => 4, 'strategy' => 3, 'podium' => 0],
                            ['pos' => 9, 'base' => 2, 'strategy' => 3, 'podium' => 0],
                            ['pos' => 10, 'base' => 1, 'strategy' => 3, 'podium' => 0],
                        ];
                        
                        for ($i = 11; $i <= 22; $i++) {
                            $positions[] = ['pos' => $i, 'base' => 0, 'strategy' => 3, 'podium' => 0];
                        }
                        
                        foreach ($positions as $p):
                            $isPodium = $p['pos'] <= 3;
                            $total = $p['base'] + $p['strategy'];
                            $rowClass = $isPodium ? 'bg-orange-500/5 border-l-4 border-l-orange-500' : 'bg-white/5';
                        ?>
                        <tr class="border-b border-white/5 hover:bg-white/10 transition <?php echo $rowClass; ?>">
                            <td class="py-3 px-4">
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-white">P<?php echo $p['pos']; ?></span>
                                    <?php if ($isPodium): ?>
                                        <i class="fas fa-medal text-orange-400 text-xs"></i>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="text-center py-3 px-4 text-blue-400 font-bold">
                                <?php echo $p['base'] > 0 ? $p['base'] : '-'; ?>
                            </td>
                            <td class="text-center py-3 px-4 text-green-400 font-bold">
                                +<?php echo $p['strategy']; ?>
                            </td>
                            <td class="text-center py-3 px-4 text-orange-400 font-bold">
                                <?php echo $p['podium'] > 0 ? '+' . $p['podium'] : '-'; ?>
                            </td>
                            <td class="text-right py-3 px-4 text-white font-bold">
                                <?php echo $total; ?> pts
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="mt-6 p-4 bg-yellow-500/10 border border-yellow-500/30 rounded-lg">
                <p class="text-xs text-gray-300 leading-relaxed">
                    <strong class="text-yellow-400">📝 Note:</strong> Base points follow the standard F1 points system (25-18-15-12-10-8-6-4-2-1 for P1-P10).
                    Positions 11-22 earn 0 base points but still award +3 strategy bonus for exact prediction.
                </p>
            </div>
        </div>

        <!-- Bonus Points -->
        <div class="grid md:grid-cols-2 gap-6 mb-8">
            
            <!-- Podium Sweep -->
            <div class="g-card p-6 border-t-4 border-t-orange-500">
                <h3 class="text-xl font-bold text-white mb-4 flex items-center gap-2">
                    <i class="fas fa-medal text-orange-500"></i>
                    Podium Sweep Bonus
                </h3>
                <div class="space-y-3">
                    <p class="text-gray-300 text-sm leading-relaxed">
                        Predict <strong class="text-white">ALL THREE podium positions</strong> (P1, P2, P3) in the <strong class="text-white">exact correct order</strong>.
                    </p>
                    <div class="bg-orange-500/10 border border-orange-500/30 rounded-lg p-4">
                        <div class="text-center">
                            <div class="text-4xl font-black text-orange-400 mb-1">+10</div>
                            <div class="text-xs text-gray-400 uppercase font-bold">Bonus Points</div>
                        </div>
                    </div>
                    <p class="text-[11px] text-gray-500 leading-relaxed">
                        This bonus is ONLY awarded if you get P1, P2, AND P3 all correct. Getting just 1 or 2 podium positions correct does not earn this bonus.
                    </p>
                </div>
            </div>

            <!-- Constructor Bonus -->
            <div class="g-card p-6 border-t-4 border-t-blue-500">
                <h3 class="text-xl font-bold text-white mb-4 flex items-center gap-2">
                    <i class="fas fa-wrench text-blue-400"></i>
                    Top Constructor Bonus
                </h3>
                <div class="space-y-3">
                    <p class="text-gray-300 text-sm leading-relaxed">
                        Predict which <strong class="text-white">Constructor</strong> will score the <strong class="text-white">most combined points</strong> in the race.
                    </p>
                    <div class="bg-blue-500/10 border border-blue-500/30 rounded-lg p-4">
                        <div class="text-center">
                            <div class="text-4xl font-black text-blue-400 mb-1">+5</div>
                            <div class="text-xs text-gray-400 uppercase font-bold">Bonus Points</div>
                        </div>
                    </div>
                    <p class="text-[11px] text-gray-500 leading-relaxed">
                        The constructor is automatically calculated based on your driver predictions. The team with the highest combined driver points wins.
                    </p>
                </div>
            </div>
        </div>

        <!-- Double Points Races -->
        <div class="g-card p-8 mb-8 border-t-4 border-t-purple-500">
            <h2 class="text-2xl font-bold text-white mb-4 flex items-center gap-3">
                <i class="fas fa-star text-purple-500"></i>
                Double Points Events
            </h2>
            <p class="text-gray-300 mb-6">
                The following Grand Prix events award <strong class="text-purple-400">2x ALL POINTS</strong>:
            </p>
            
            <div class="grid md:grid-cols-3 gap-4 mb-6">
                <div class="bg-purple-500/10 border border-purple-500/30 rounded-lg p-4 text-center">
                    <div class="text-3xl mb-2">🇨🇳</div>
                    <div class="font-bold text-white">China GP</div>
                    <div class="text-xs text-purple-400 font-bold mt-1">2x POINTS</div>
                </div>
                <div class="bg-purple-500/10 border border-purple-500/30 rounded-lg p-4 text-center">
                    <div class="text-3xl mb-2">🇬🇧</div>
                    <div class="font-bold text-white">British GP</div>
                    <div class="text-xs text-purple-400 font-bold mt-1">2x POINTS</div>
                </div>
                <div class="bg-purple-500/10 border border-purple-500/30 rounded-lg p-4 text-center">
                    <div class="text-3xl mb-2">🇸🇬</div>
                    <div class="font-bold text-white">Singapore GP</div>
                    <div class="text-xs text-purple-400 font-bold mt-1">2x POINTS</div>
                </div>
            </div>
            
            <div class="p-4 bg-purple-500/10 border border-purple-500/30 rounded-lg">
                <p class="text-xs text-gray-300 leading-relaxed">
                    <strong class="text-purple-400">⚡ Example:</strong> If you earn 50 points in a regular race, you would earn <strong class="text-white">100 points</strong> in a Double Points race with the exact same predictions!
                </p>
            </div>
        </div>

        <!-- Example Calculation -->
        <div class="g-card p-8 border-t-4 border-t-green-500">
            <h2 class="text-2xl font-bold text-white mb-6 flex items-center gap-3">
                <i class="fas fa-calculator text-green-500"></i>
                Example Points Calculation
            </h2>
            
            <div class="bg-black/20 rounded-lg p-6 mb-4">
                <h3 class="text-sm font-bold text-green-400 mb-4 uppercase">Your Predictions:</h3>
                <div class="space-y-2 text-sm font-mono">
                    <div class="flex justify-between items-center py-2 border-b border-white/10">
                        <span class="text-gray-300">P1: Charles Leclerc (Ferrari)</span>
                    </div>
                    <div class="flex justify-between items-center py-2 border-b border-white/10">
                        <span class="text-gray-300">P2: Lewis Hamilton (Mercedes)</span>
                    </div>
                    <div class="flex justify-between items-center py-2 border-b border-white/10">
                        <span class="text-gray-300">P3: Lando Norris (McLaren)</span>
                    </div>
                    <div class="flex justify-between items-center py-2">
                        <span class="text-gray-300">Top Constructor: Ferrari</span>
                    </div>
                </div>
            </div>
            
            <div class="bg-green-500/10 border border-green-500/30 rounded-lg p-6">
                <h3 class="text-sm font-bold text-green-400 mb-4 uppercase">Actual Race Result (All Correct!):</h3>
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between items-center py-2 bg-black/20 px-3 rounded">
                        <span class="text-white font-bold">P1: Leclerc</span>
                        <span class="text-green-400 font-mono">25 + 3 = <strong>28 pts</strong></span>
                    </div>
                    <div class="flex justify-between items-center py-2 bg-black/20 px-3 rounded">
                        <span class="text-white font-bold">P2: Hamilton</span>
                        <span class="text-green-400 font-mono">18 + 3 = <strong>21 pts</strong></span>
                    </div>
                    <div class="flex justify-between items-center py-2 bg-black/20 px-3 rounded">
                        <span class="text-white font-bold">P3: Norris</span>
                        <span class="text-green-400 font-mono">15 + 3 = <strong>18 pts</strong></span>
                    </div>
                    <div class="flex justify-between items-center py-2 bg-orange-500/20 border border-orange-500/30 px-3 rounded">
                        <span class="text-orange-400 font-bold">Podium Sweep Bonus</span>
                        <span class="text-orange-400 font-mono font-bold">+10 pts</span>
                    </div>
                    <div class="flex justify-between items-center py-2 bg-blue-500/20 border border-blue-500/30 px-3 rounded">
                        <span class="text-blue-400 font-bold">Constructor Bonus (Ferrari)</span>
                        <span class="text-blue-400 font-mono font-bold">+5 pts</span>
                    </div>
                    
                    <div class="border-t-2 border-green-500 pt-4 mt-4">
                        <div class="flex justify-between items-center">
                            <span class="text-white font-black text-lg uppercase">Total Score:</span>
                            <span class="text-green-400 font-black text-3xl font-mono">82 pts</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="mt-4 p-4 bg-purple-500/10 border border-purple-500/30 rounded-lg">
                <p class="text-xs text-gray-300 leading-relaxed">
                    <strong class="text-purple-400">💰 If this was a Double Points race:</strong> You would earn <strong class="text-white text-lg">164 points</strong> (82 × 2)!
                </p>
            </div>
        </div>

        <!-- Tips -->
        <div class="g-card p-8">
            <h2 class="text-2xl font-bold text-white mb-6 flex items-center gap-3">
                <i class="fas fa-lightbulb text-yellow-500"></i>
                Pro Tips
            </h2>
            <div class="space-y-4">
                <div class="flex gap-3">
                    <div class="text-green-400 text-xl">✓</div>
                    <div>
                        <strong class="text-white">Focus on accuracy:</strong>
                        <span class="text-gray-300 text-sm"> Even predicting P11-P22 correctly earns you +3 strategy points!</span>
                    </div>
                </div>
                <div class="flex gap-3">
                    <div class="text-green-400 text-xl">✓</div>
                    <div>
                        <strong class="text-white">Podium is valuable:</strong>
                        <span class="text-gray-300 text-sm"> Getting all 3 podium positions correct adds a massive +10 bonus on top of your regular points.</span>
                    </div>
                </div>
                <div class="flex gap-3">
                    <div class="text-green-400 text-xl">✓</div>
                    <div>
                        <strong class="text-white">Don't forget constructors:</strong>
                        <span class="text-gray-300 text-sm"> Your constructor prediction is automatic based on driver positions, but picking well can add +5 points.</span>
                    </div>
                </div>
                <div class="flex gap-3">
                    <div class="text-green-400 text-xl">✓</div>
                    <div>
                        <strong class="text-white">Plan for Double Points:</strong>
                        <span class="text-gray-300 text-sm"> Save your best predictions for China, UK, and Singapore GP to maximize your leaderboard gains!</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- CTA Button -->
        <?php if ($user): ?>
        <div class="text-center mt-12">
            <a href="dashboard.php" class="g-btn g-btn-orange px-8 py-4 text-lg inline-flex items-center gap-3">
                <i class="fas fa-chart-line"></i>
                Go to Dashboard
            </a>
        </div>
        <?php else: ?>
        <div class="text-center mt-12">
            <a href="signup.php" class="g-btn g-btn-orange px-8 py-4 text-lg inline-flex items-center gap-3">
                <i class="fas fa-user-plus"></i>
                Sign Up to Start Playing
            </a>
        </div>
        <?php endif; ?>

    </main>

    <!-- Footer -->
    <footer class="mt-12 border-t border-white/10 py-6 text-center">
        <p class="text-gray-500 text-sm mb-2">&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</p>
        <p class="text-gray-600 text-xs mb-3">
            Powered by <a href="https://www.scanerrific.com" target="_blank" class="text-orange-500 hover:text-orange-400 font-semibold transition">Scanerrific</a>
        </p>
    </footer>

</body>
</html>
