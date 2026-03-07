<?php
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'includes/avatars.php';

// Check for admin/owner access (optional)
$user = getCurrentUser();
if (!$user) {
    header('Location: login.php');
    exit;
}

// 2026 Australia Mock Data (Based on user-defined lineup)
$race = [
    'country' => 'Australia',
    'circuit_name' => 'Albert Park Circuit',
    'race_date' => '2026-03-08',
    'race_number' => 1,
    'status' => 'completed',
];

// Mock Results from today's "qualifying" (Using as placeholder for race results)
$mockActualResults = [
    ['id' => 'verstappen', 'name' => 'Max Verstappen', 'team' => 'Red Bull Racing', 'pos' => 1],
    ['id' => 'norris', 'name' => 'Lando Norris', 'team' => 'McLaren', 'pos' => 2],
    ['id' => 'leclerc', 'name' => 'Charles Leclerc', 'team' => 'Ferrari', 'pos' => 3],
    ['id' => 'russell', 'name' => 'George Russell', 'team' => 'Mercedes', 'pos' => 4],
    ['id' => 'sainz', 'name' => 'Carlos Sainz', 'team' => 'Williams', 'pos' => 5],
    ['id' => 'piastri', 'name' => 'Oscar Piastri', 'team' => 'McLaren', 'pos' => 6],
    ['id' => 'hamilton', 'name' => 'Lewis Hamilton', 'team' => 'Ferrari', 'pos' => 7],
    ['id' => 'alonso', 'name' => 'Fernando Alonso', 'team' => 'Aston Martin', 'pos' => 8],
    ['id' => 'albon', 'name' => 'Alexander Albon', 'team' => 'Williams', 'pos' => 9],
    ['id' => 'antonelli', 'name' => 'Kimi Antonelli', 'team' => 'Mercedes', 'pos' => 10],
];

// Mock User Predictions
$mockPredictions = [
    ['id' => 'verstappen', 'name' => 'Max Verstappen', 'team' => 'Red Bull Racing', 'pred' => 1], // Correct
    ['id' => 'leclerc', 'name' => 'Charles Leclerc', 'team' => 'Ferrari', 'pred' => 2],    // Incorrect (pos 3)
    ['id' => 'norris', 'name' => 'Lando Norris', 'team' => 'McLaren', 'pred' => 3],        // Incorrect (pos 2)
    ['id' => 'piastri', 'name' => 'Oscar Piastri', 'team' => 'McLaren', 'pred' => 4],      // Incorrect (pos 6)
    ['id' => 'russell', 'name' => 'George Russell', 'team' => 'Mercedes', 'pred' => 5],   // Incorrect (pos 4)
];

// Mock Constructor Prediction
$mockConstructorPred = ['name' => 'Red Bull Racing', 'pred' => 1]; // Correct

// Simulation Logic
$totalPoints = 0;
$exactPositionBonusTotal = 0;
$podiumSweepBonus = 0; // Set to 10 if all P1-P3 are correct
$constructorBonus = 5; // Set to 5 if correct

// Calculate Points
foreach ($mockPredictions as &$p) {
    // Find actual position
    $actual = null;
    foreach ($mockActualResults as $ar) {
        if ($ar['id'] === $p['id']) {
            $actual = $ar['pos'];
            break;
        }
    }
    $p['actual'] = $actual;
    $p['is_exact'] = ($p['pred'] === $actual);
    
    // Points System:
    // P1: 25, P2: 18, P3: 15, P4: 12, P5: 10
    $f1Points = [1 => 25, 2 => 18, 3 => 15, 4 => 12, 5 => 10];
    
    $p['points'] = 0;
    if ($p['is_exact']) {
        $p['points'] += ($f1Points[$actual] ?? 0);
        $p['points'] += 3; // +3 Strategy Bonus for exact match
        $exactPositionBonusTotal += 3;
    }
    $totalPoints += $p['points'];
}

$totalPoints += $constructorBonus;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Australia Results Mockup - Paddock Picks</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="css/gaming-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .g-card-premium {
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }
        .status-badge {
            font-size: 10px;
            padding: 2px 8px;
            border-radius: 4px;
            font-weight: 800;
            text-transform: uppercase;
        }
    </style>
</head>
<body class="gaming-theme text-gray-200">

    <!-- Mock Navbar -->
    <nav class="g-nav fixed w-full z-50 px-6 py-4 flex justify-between items-center bg-slate-900/80 backdrop-blur-md border-b border-white/5">
        <div class="flex items-center gap-4">
            <div class="w-10 h-10 bg-gradient-to-br from-red-600 to-orange-500 rounded-xl flex items-center justify-center shadow-lg">
                <i class="fas fa-flag-checkered text-white text-lg"></i>
            </div>
            <span class="font-bold text-xl tracking-wide text-white uppercase italic">Paddock Picks</span>
        </div>
        <div class="flex items-center gap-4">
             <span class="bg-blue-500/10 text-blue-400 text-xs font-bold px-3 py-1 rounded-full border border-blue-500/20">MOCKUP PAGE</span>
             <a href="dashboard.php" class="text-gray-400 hover:text-white transition text-sm">Dashboard</a>
        </div>
    </nav>

    <main class="pt-28 pb-12 px-4 md:px-8 max-w-6xl mx-auto">
        
        <!-- Hero Header -->
        <div class="mb-12 relative overflow-hidden rounded-3xl p-8 md:p-12 g-card-premium border-l-4 border-l-orange-500">
            <div class="absolute top-0 right-0 w-64 h-64 bg-orange-600/10 rounded-full blur-[100px]"></div>
            
            <div class="relative z-10 flex flex-col md:flex-row justify-between items-start md:items-end gap-8">
                <div>
                    <div class="flex items-center gap-3 mb-4">
                        <span class="bg-green-500 text-white text-[10px] font-black px-3 py-1 rounded italic uppercase tracking-widest">Race Analysis Complete</span>
                        <span class="text-gray-500 font-mono text-xs font-bold tracking-tighter">RD 01 / MARCH 2026</span>
                    </div>
                    <h1 class="text-5xl md:text-7xl font-black text-white italic mb-2 leading-none">AUSTRALIA</h1>
                    <p class="text-xl text-orange-500/80 font-bold uppercase tracking-[0.2em]">Albert Park Circuit</p>
                </div>
                
                <div class="bg-white/5 border border-white/10 rounded-2xl p-6 text-center shadow-2xl min-w-[200px]">
                    <div class="text-[10px] text-gray-400 uppercase font-black tracking-widest mb-1">Total Event Score</div>
                    <div class="text-6xl font-black text-white italic leading-none g-text-gradient"><?php echo $totalPoints; ?></div>
                    <div class="text-[10px] text-orange-500 font-bold mt-2 uppercase tracking-wide">Points Earned</div>
                </div>
            </div>
        </div>

        <!-- Performance Stats Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-12">
            <div class="g-card-premium p-6 rounded-2xl border-b-4 border-b-green-500/50">
                <div class="flex items-center gap-3 text-green-400 mb-3">
                    <i class="fas fa-bullseye"></i>
                    <span class="text-[10px] font-black uppercase tracking-widest">Precision</span>
                </div>
                <div class="text-3xl font-black text-white">1/5</div>
                <div class="text-[10px] text-gray-500 font-bold uppercase mt-1">Exact Position Matches</div>
            </div>
            
            <div class="g-card-premium p-6 rounded-2xl border-b-4 border-b-blue-500/50">
                <div class="flex items-center gap-3 text-blue-400 mb-3">
                    <i class="fas fa-microchip"></i>
                    <span class="text-[10px] font-black uppercase tracking-widest">Strategy Bonus</span>
                </div>
                <div class="text-3xl font-black text-white">+<?php echo $exactPositionBonusTotal; ?></div>
                <div class="text-[10px] text-gray-500 font-bold uppercase mt-1">+3 per exact match</div>
            </div>

            <div class="g-card-premium p-6 rounded-2xl border-b-4 border-b-purple-500/50 opacity-50">
                <div class="flex items-center gap-3 text-purple-400 mb-3">
                    <i class="fas fa-trophy"></i>
                    <span class="text-[10px] font-black uppercase tracking-widest">Podium Sweep</span>
                </div>
                <div class="text-3xl font-black text-white">No</div>
                <div class="text-[10px] text-gray-500 font-bold uppercase mt-1">+10 for full top 3</div>
            </div>

            <div class="g-card-premium p-6 rounded-2xl border-b-4 border-b-yellow-500/50">
                <div class="flex items-center gap-3 text-yellow-400 mb-3">
                    <i class="fas fa-industry"></i>
                    <span class="text-[10px] font-black uppercase tracking-widest">Constructor</span>
                </div>
                <div class="text-3xl font-black text-white">+<?php echo $constructorBonus; ?></div>
                <div class="text-[10px] text-gray-500 font-bold uppercase mt-1">Correct Team Win</div>
            </div>
        </div>

        <!-- Main Results Table -->
        <div class="g-card-premium rounded-3xl overflow-hidden mb-12 border border-white/5">
            <div class="p-6 md:p-8 bg-white/5 flex flex-col md:flex-row justify-between items-center gap-4">
                <h2 class="text-2xl font-black text-white italic uppercase tracking-tight flex items-center gap-3">
                    <i class="fas fa-list-ol text-orange-500"></i> Detailed Analytics
                </h2>
                <div class="flex items-center gap-2 text-xs font-bold text-gray-400 uppercase tracking-widest bg-black/30 px-4 py-2 rounded-xl">
                    Season Start <i class="fas fa-chevron-right text-[8px] opacity-30"></i> Australia <i class="fas fa-check-circle text-green-500"></i>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-black/20 border-b border-white/5">
                            <th class="p-6 text-[10px] font-black text-gray-500 uppercase tracking-widest">Driver / Team</th>
                            <th class="p-6 text-center text-[10px] font-black text-gray-500 uppercase tracking-widest">Predicted</th>
                            <th class="p-6 text-center text-[10px] font-black text-gray-500 uppercase tracking-widest">Actual</th>
                            <th class="p-6 text-center text-[10px] font-black text-gray-500 uppercase tracking-widest">Status</th>
                            <th class="p-6 text-right text-[10px] font-black text-gray-500 uppercase tracking-widest">Points Allocation</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        <?php foreach ($mockPredictions as $p): 
                            $rowClass = $p['is_exact'] ? 'bg-green-500/5' : 'hover:bg-white/[0.02]';
                            $statusColor = $p['is_exact'] ? 'bg-green-500 text-white' : 'bg-red-500/10 text-red-400 border border-red-500/20';
                            $icon = $p['is_exact'] ? 'fa-check-circle' : 'fa-times-circle';
                        ?>
                        <tr class="<?php echo $rowClass; ?> transition-colors group">
                            <td class="p-6">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 bg-slate-800 rounded-xl overflow-hidden border border-white/10 group-hover:scale-110 transition duration-300">
                                        <img src="https://api.dicebear.com/7.x/identicon/svg?seed=<?php echo $p['id']; ?>" class="w-full h-full opacity-50">
                                    </div>
                                    <div>
                                        <div class="text-base font-black text-white italic uppercase leading-tight"><?php echo $p['name']; ?></div>
                                        <div class="text-[10px] font-bold text-gray-500 uppercase tracking-tighter"><?php echo $p['team']; ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="p-6 text-center">
                                <div class="inline-block px-4 py-2 bg-blue-500/10 border border-blue-500/30 rounded-xl text-blue-400 font-black italic">
                                    P<?php echo $p['pred']; ?>
                                </div>
                            </td>
                            <td class="p-6 text-center">
                                <div class="inline-block px-4 py-2 bg-slate-800 border border-white/10 rounded-xl text-white font-black italic">
                                    P<?php echo $p['actual']; ?>
                                </div>
                            </td>
                            <td class="p-6 text-center">
                                <span class="status-badge <?php echo $statusColor; ?> flex items-center justify-center gap-1 mx-auto w-fit">
                                    <i class="fas <?php echo $icon; ?> text-[9px]"></i>
                                    <?php echo $p['is_exact'] ? 'Direct Hit' : 'Position Miss'; ?>
                                </span>
                            </td>
                            <td class="p-6 text-right">
                                <?php if ($p['points'] > 0): ?>
                                    <div class="text-2xl font-black text-green-400 italic leading-none">+<?php echo $p['points']; ?></div>
                                    <div class="text-[9px] text-gray-600 font-bold uppercase mt-1">Base + Strategy</div>
                                <?php else: ?>
                                    <div class="text-2xl font-black text-gray-700 italic leading-none">0</div>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <!-- Constructor Row -->
                        <tr class="bg-yellow-500/5 hover:bg-yellow-500/10 transition-colors">
                            <td class="p-6">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 bg-yellow-500/20 rounded-xl flex items-center justify-center text-yellow-500 text-xl border border-yellow-500/30">
                                        <i class="fas fa-industry"></i>
                                    </div>
                                    <div>
                                        <div class="text-base font-black text-white italic uppercase leading-tight"><?php echo $mockConstructorPred['name']; ?></div>
                                        <div class="text-[10px] font-bold text-gray-500 uppercase tracking-tighter">Event Winner Constructor</div>
                                    </div>
                                </div>
                            </td>
                            <td class="p-6 text-center">
                                <span class="text-[10px] font-black text-yellow-500 bg-yellow-500/10 px-3 py-1 rounded">WINNER</span>
                            </td>
                            <td class="p-6 text-center">
                                <span class="text-[10px] font-black text-white bg-slate-800 px-3 py-1 rounded">WINNER</span>
                            </td>
                            <td class="p-6 text-center">
                                <span class="status-badge bg-yellow-500 text-black flex items-center justify-center gap-1 mx-auto w-fit">
                                    <i class="fas fa-star text-[9px]"></i> Match
                                </span>
                            </td>
                            <td class="p-6 text-right">
                                <div class="text-2xl font-black text-yellow-500 italic leading-none">+<?php echo $constructorBonus; ?></div>
                                <div class="text-[9px] text-gray-600 font-bold uppercase mt-1">Engineering Bonus</div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="p-8 bg-black/40 border-t border-white/5 text-center">
                <p class="text-[10px] text-gray-500 font-bold uppercase tracking-widest max-w-lg mx-auto leading-relaxed">
                    Strategy Bonus of +3 points is awarded for every driver position predicted exactly correctly. F1 standard points are awarded for podium hits in exact positions.
                </p>
            </div>
        </div>

        <!-- Call to Action -->
        <div class="flex flex-col md:flex-row gap-4 justify-center items-center">
            <a href="dashboard.php" class="g-btn bg-white text-slate-950 px-10 py-4 font-black text-sm rounded-2xl flex items-center gap-3 transition hover:scale-105 active:scale-95">
                <i class="fas fa-arrow-left"></i> RETURN TO DASHBOARD
            </a>
            <p class="text-xs text-gray-500 font-bold">Mockup Revision 1.0 - Private Access</p>
        </div>

    </main>

    <footer class="mt-12 border-t border-white/5 py-8 text-center text-gray-600 font-bold text-[10px] uppercase tracking-widest">
        &copy; 2026 Paddock Picks • Powered by Scanerrific
    </footer>

</body>
</html>
