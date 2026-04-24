<?php
require_once 'includes/auth.php';
require_once 'includes/maintenance-gate.php';
require_once 'includes/functions.php';

$user = getCurrentUser();
if (!$user) {
    header('Location: index.php');
    exit;
}

$db = getDB();
$loggedInUserId = $user['id'];
$userId = isset($_GET['user_id']) ? intval($_GET['user_id']) : $loggedInUserId;

$isMe = ($userId === $loggedInUserId);

// Setup the display name
$viewUserQuery = $db->prepare("SELECT username FROM users WHERE id = ?");
$viewUserQuery->bind_param("i", $userId);
$viewUserQuery->execute();
$viewUser = $viewUserQuery->get_result()->fetch_assoc();
$viewUserName = $viewUser ? $viewUser['username'] : 'User';

// Get race_id from URL
$raceId = isset($_GET['race_id']) ? intval($_GET['race_id']) : 0;

if (!$raceId) {
    header('Location: dashboard.php');
    exit;
}

// Get race details
$stmt = $db->prepare("SELECT * FROM races WHERE id = ?");
$stmt->bind_param("i", $raceId);
$stmt->execute();
$race = $stmt->get_result()->fetch_assoc();

if (!$race) {
    header('Location: dashboard.php');
    exit;
}

// Check if race has results
$hasResults = $race['status'] === 'completed';

// Get user's predictions for this race
$stmt = $db->prepare("
    SELECT p.*, d.driver_name, d.team
    FROM predictions p
    LEFT JOIN drivers d ON p.driver_id = d.id
    WHERE p.user_id = ? AND p.race_id = ?
    ORDER BY p.predicted_position ASC
");
$stmt->bind_param("ii", $userId, $raceId);
$stmt->execute();
$predictions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get actual race results
$stmt = $db->prepare("
    SELECT * FROM race_results 
    WHERE race_id = ? 
    ORDER BY position ASC
");
$stmt->bind_param("i", $raceId);
$stmt->execute();
$actualResults = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Build a lookup map: driver_id => actual_position
$actualPositions = [];
foreach ($actualResults as $result) {
    $actualPositions[$result['driver_id']] = $result['position'];
}

// Use F1 Points System to match process-results.php
$f1Points = [1 => 25, 2 => 18, 3 => 15, 4 => 12, 5 => 10, 6 => 8, 7 => 6, 8 => 4, 9 => 2, 10 => 1];

$totalPoints = 0;
$exactMatches = 0;
$top3Bonus = 0;

foreach ($predictions as &$pred) {
    $pred['actual_position'] = $actualPositions[$pred['driver_id']] ?? null;
    $pred['points_earned'] = 0;
    $pred['is_exact'] = false;
    $pred['is_top3'] = false;
    
    if ($pred['actual_position'] !== null && $pred['predicted_position'] == $pred['actual_position']) {
        // Exact match
        $pred['is_exact'] = true;
        $exactMatches++;
        
        // Base points + strategy bonus
        $basePts = $f1Points[$pred['actual_position']] ?? 0;
        $pred['points_earned'] = $basePts + 3; // +3 strategy bonus
        
        $totalPoints += $pred['points_earned'];
    }
}

// Get user's total score for this race from scores table (which includes Constructor & Podium bonuses)
$stmt = $db->prepare("SELECT * FROM scores WHERE user_id = ? AND race_id = ?");
$stmt->bind_param("ii", $userId, $raceId);
$stmt->execute();
$scoreRecord = $stmt->get_result()->fetch_assoc();

// Get user stats
$stats = getUserStats($userId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($race['country']); ?> - Race Results - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="css/gaming-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="gaming-theme text-gray-200">

    <!-- Navbar -->
    <nav class="g-nav fixed w-full z-50 px-6 py-4 flex justify-between items-center">
        <div class="flex items-center gap-4">
            <div class="w-10 h-10 bg-gradient-to-br from-red-600 to-orange-500 rounded-xl flex items-center justify-center shadow-lg shadow-orange-500/20">
                <i class="fas fa-flag-checkered text-white text-lg"></i>
            </div>
            <span class="font-bold text-xl tracking-wide text-white">PADDOCK PICKS</span>
        </div>
        
        <div class="flex items-center gap-6">
            <div class="hidden md:flex items-center gap-4">
                <a href="dashboard.php" class="text-gray-300 hover:text-white transition">Dashboard</a>
                <a href="leaderboard.php" class="text-gray-300 hover:text-white transition">Leaderboard</a>
            </div>
            
            <div class="flex items-center gap-3 pl-6 border-l border-white/10">
                <a href="profile.php" class="w-10 h-10 rounded-full bg-slate-700 border-2 border-white/10 overflow-hidden hover:border-blue-500 transition cursor-pointer">
                    <img src="https://api.dicebear.com/7.x/<?php echo $user['avatar_style'] ?? 'avataaars'; ?>/svg?seed=<?php echo $user['username']; ?>" alt="Avatar" class="w-full h-full">
                </a>
                <a href="logout.php" class="text-gray-400 hover:text-white transition">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="pt-24 pb-12 px-4 md:px-8 max-w-7xl mx-auto">
        
        <!-- Race Header -->
        <div class="mb-8">
            <a href="dashboard.php" class="text-gray-400 hover:text-white transition mb-4 inline-flex items-center gap-2">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
            
            <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-4 mt-4">
                <div>
                    <div class="flex items-center gap-3 mb-2">
                        <span class="bg-<?php echo $hasResults ? 'green' : 'orange'; ?>-600 text-white text-xs font-bold px-3 py-1 rounded-full uppercase tracking-wider">
                            <?php echo $hasResults ? 'Race Complete' : 'Awaiting Results'; ?>
                        </span>
                        <span class="text-gray-400 font-mono text-sm">
                            <i class="far fa-calendar"></i> <?php echo date('M d, Y', strtotime($race['race_date'])); ?>
                        </span>
                    </div>
                    <h1 class="text-4xl md:text-6xl font-black text-white mb-2 uppercase italic">
                        <?php echo htmlspecialchars($race['country']); ?>
                    </h1>
                    <p class="text-xl text-gray-300 font-medium">
                        <?php echo htmlspecialchars($race['circuit_name']); ?>
                    </p>
                </div>
                
                <?php if ($hasResults && $scoreRecord): ?>
                <div class="g-card p-6 text-center">
                    <div class="text-sm text-gray-400 uppercase font-bold mb-2"><?php echo $isMe ? 'Your' : htmlspecialchars($viewUserName) . "'s"; ?> Score</div>
                    <div class="text-5xl font-black text-transparent bg-clip-text bg-gradient-to-r from-orange-500 to-red-600">
                        <?php echo $scoreRecord['total_points'] ?? 0; ?>
                    </div>
                    <div class="text-xs text-gray-500 mt-2">Points Earned</div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!$hasResults): ?>
            <!-- Race Not Completed Yet -->
            <div class="g-card p-12 text-center">
                <div class="w-20 h-20 rounded-full bg-orange-500/20 text-orange-500 flex items-center justify-center text-4xl mx-auto mb-6">
                    <i class="fas fa-hourglass-half"></i>
                </div>
                <h2 class="text-2xl font-bold text-white mb-4">Race Results Pending</h2>
                <p class="text-gray-400 mb-6">
                    Results will be available after the race on <?php echo date('M d, Y', strtotime($race['race_date'])); ?>
                </p>
                
                <?php if (!empty($predictions)): ?>
                    <div class="mt-8">
                        <h3 class="text-white font-bold mb-4"><?php echo $isMe ? 'Your' : htmlspecialchars($viewUserName) . "'s"; ?> Predictions</h3>
                        <div class="max-w-2xl mx-auto space-y-2">
                            <?php foreach ($predictions as $pred): ?>
                            <div class="g-card p-4 flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-orange-500/20 text-orange-400 flex items-center justify-center font-bold">
                                        P<?php echo $pred['predicted_position']; ?>
                                    </div>
                                    <div>
                                        <div class="text-sm font-bold text-white"><?php echo htmlspecialchars($pred['driver_name']); ?></div>
                                        <div class="text-xs text-gray-500"><?php echo htmlspecialchars($pred['team']); ?></div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="predict.php?race_id=<?php echo $raceId; ?>" class="g-btn g-btn-orange inline-flex items-center gap-2 px-6 py-3">
                        <i class="fas fa-gamepad"></i> Make Your Predictions
                    </a>
                <?php endif; ?>
            </div>
        
        <?php else: ?>
            <!-- Race Completed - Show Results -->
            
            <!-- Score Breakdown -->
            <?php if ($scoreRecord): ?>
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
                <div class="g-card p-5 text-center">
                    <div class="text-3xl font-black text-orange-400"><?php echo $scoreRecord['driver_points'] ?? 0; ?></div>
                    <div class="text-[10px] text-gray-400 uppercase font-bold mt-1">Driver Points</div>
                    <div class="text-[9px] text-orange-400 font-bold mt-1 text-opacity-80">Base Points</div>
                </div>
                <div class="g-card p-5 text-center border-l-2 border-blue-500/30">
                    <div class="text-3xl font-black text-blue-400"><?php echo $scoreRecord['top3_bonus'] ?? 0; ?></div>
                    <div class="text-[10px] text-gray-400 uppercase font-bold mt-1">Podium Bonus</div>
                    <div class="text-[9px] text-blue-400 font-bold mt-1 text-opacity-80">All 3 Correct = +10</div>
                </div>
                <div class="g-card p-5 text-center border-l-2 border-purple-500/30">
                    <div class="text-3xl font-black text-purple-400"><?php echo $scoreRecord['constructor_points'] ?? 0; ?></div>
                    <div class="text-[10px] text-gray-400 uppercase font-bold mt-1">Constr. Bonus</div>
                    <div class="text-[9px] text-purple-400 font-bold mt-1 text-opacity-80">Winner Pick = +5</div>
                </div>
                <div class="g-card p-5 text-center border-l-2 border-green-500/30">
                    <div class="text-3xl font-black text-green-400"><?php echo $exactMatches; ?></div>
                    <div class="text-[10px] text-gray-400 uppercase font-bold mt-1">Exact Hits</div>
                    <div class="text-[9px] text-green-400 font-bold mt-1 text-opacity-80">Drivers in exact pos</div>
                </div>
                <div class="g-card p-5 text-center border-2 border-orange-500/50 bg-orange-500/5 shadow-[0_0_15px_rgba(249,115,22,0.1)]">
                    <div class="text-3xl font-black text-white"><?php echo $scoreRecord['total_points'] ?? 0; ?></div>
                    <div class="text-xs text-orange-400 uppercase font-black mt-1">Total Score</div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Predictions vs Actual Results -->
            <div class="g-card p-6 mb-8">
                <h2 class="text-2xl font-bold text-white mb-6 flex items-center gap-2">
                    <i class="fas fa-trophy text-orange-500"></i>
                    <?php echo $isMe ? 'Your' : htmlspecialchars($viewUserName) . "'s"; ?> Predictions vs Actual Results
                </h2>

                <?php if (empty($predictions)): ?>
                    <div class="text-center py-12 text-gray-500">
                        <i class="fas fa-info-circle text-4xl mb-4"></i>
                        <p>You didn't make any predictions for this race.</p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-white/10">
                                    <th class="p-4 text-left text-xs font-bold text-gray-400 uppercase tracking-wider">Driver</th>
                                    <th class="p-4 text-center text-xs font-bold text-gray-400 uppercase tracking-wider">Your Prediction</th>
                                    <th class="p-4 text-center text-xs font-bold text-gray-400 uppercase tracking-wider">Actual Position</th>
                                    <th class="p-4 text-center text-xs font-bold text-gray-400 uppercase tracking-wider">Result</th>
                                    <th class="p-4 text-right text-xs font-bold text-gray-400 uppercase tracking-wider">Points</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($predictions as $pIdx => $pred): ?>
                                <tr class="border-b border-white/5 hover:bg-white/5 transition" id="row-<?php echo $pIdx; ?>">
                                    <!-- Driver Info -->
                                    <td class="p-4">
                                        <div class="flex items-center gap-3">
                                     <div class="w-10 h-10 rounded-full bg-slate-700 overflow-hidden">
                                                 <div class="w-full h-full flex items-center justify-center text-gray-500">
                                                     <i class="fas fa-user"></i>
                                                 </div>
                                             </div>
                                            <div>
                                                <div class="text-sm font-bold text-white"><?php echo htmlspecialchars($pred['driver_name']); ?></div>
                                                <div class="text-xs text-gray-500"><?php echo htmlspecialchars($pred['team']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    
                                    <!-- Predicted Position -->
                                    <td class="p-4 text-center">
                                        <div class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-blue-500/20 text-blue-400 font-bold">
                                            P<?php echo ($pIdx + 1); ?>
                                        </div>
                                    </td>
                                    
                                    <!-- Actual Position -->
                                    <td class="p-4 text-center">
                                        <?php if ($pred['actual_position']): ?>
                                        <div class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-orange-500/20 text-orange-400 font-bold">
                                            P<?php echo $pred['actual_position']; ?>
                                        </div>
                                        <?php else: ?>
                                        <span class="text-gray-600 text-sm">DNF</span>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <!-- Match Status -->
                                    <td class="p-4 text-center">
                                        <?php if ($pred['is_exact']): ?>
                                        <div class="flex flex-col items-center gap-1">
                                            <span class="bg-green-500/20 text-green-400 text-xs font-bold px-3 py-1 rounded-full">
                                                <i class="fas fa-check-circle"></i> BASE
                                            </span>
                                        </div>
                                        <?php elseif ($pred['actual_position']): ?>
                                        <span class="text-gray-500 text-sm">
                                            <i class="fas fa-times-circle"></i> Miss
                                        </span>
                                        <?php else: ?>
                                        <span class="text-red-500 text-sm">
                                            <i class="fas fa-flag"></i> DNF
                                        </span>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <!-- Points Earned -->
                                    <td class="p-4 text-right">
                                        <?php if ($pred['points_earned'] > 0): ?>
                                        <div class="text-lg font-bold text-green-400">
                                            +<?php echo $pred['points_earned']; ?>
                                        </div>
                                        <?php else: ?>
                                        <div class="text-lg font-bold text-gray-600">
                                            0
                                        </div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Full Race Results -->
            <?php if (!empty($actualResults)): ?>
            <div class="g-card p-6">
                <h2 class="text-2xl font-bold text-white mb-6 flex items-center gap-2">
                    <i class="fas fa-flag-checkered text-green-500"></i>
                    Official Race Results
                </h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <?php foreach ($actualResults as $rIdx => $result): 
                        $displayPos = $rIdx + 1;
                        $podiumClass = match($displayPos) {
                            1 => 'border-l-yellow-400 bg-yellow-500/5',
                            2 => 'border-l-gray-300 bg-gray-500/5',
                            3 => 'border-l-amber-600 bg-amber-500/5',
                            default => 'border-l-gray-700'
                        };
                    ?>
                    <div class="g-card p-4 border-l-4 <?php echo $podiumClass; ?>">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 rounded-full bg-slate-700 flex items-center justify-center font-black text-white text-lg">
                                    <?php echo $displayPos; ?>
                                </div>
                                <div>
                                    <div class="text-sm font-bold text-white"><?php echo htmlspecialchars($result['driver_name']); ?></div>
                                    <div class="text-xs text-gray-500"><?php echo htmlspecialchars($result['constructor_name'] ?? 'Unknown'); ?></div>
                                </div>
                            </div>
                            <?php if ($result['fastest_lap']): ?>
                            <div class="text-purple-400 text-xs font-bold">
                                <i class="fas fa-bolt"></i> FL
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

        <?php endif; ?>

    </main>

    <!-- Footer -->
    <footer class="mt-12 border-t border-white/10 py-6 text-center">
        <p class="text-gray-500 text-sm mb-2">&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</p>
        <p class="text-gray-600 text-xs mb-3">
            Powered by <a href="https://www.scanerrific.com" target="_blank" class="text-orange-500 hover:text-orange-400 font-semibold transition">Scanerrific</a>
        </p>
        <div class="flex items-center justify-center gap-2 text-gray-500 text-xs">
            <span>Follow Scanerrific on</span>
            <a href="https://www.linkedin.com/company/86236157/admin/dashboard/" target="_blank" rel="noopener noreferrer" class="inline-flex items-center text-blue-400 hover:text-blue-300 transition-colors">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="inline-block">
                    <path d="M17.303 2.25H6.69698C5.51757 2.25 4.38647 2.71852 3.5525 3.55249C2.71853 4.38646 2.25 5.51757 2.25 6.69698V17.303C2.25 18.4824 2.71853 19.6135 3.5525 20.4475C4.38647 21.2815 5.51757 21.75 6.69698 21.75H17.303C18.4824 21.75 19.6136 21.2815 20.4475 20.4475C21.2815 19.6135 21.75 18.4824 21.75 17.303V6.69698C21.75 5.51757 21.2815 4.38646 20.4475 3.55249C19.6136 2.71852 18.4824 2.25 17.303 2.25ZM8.84265 17.9923C8.84568 18.0467 8.83757 18.1011 8.81882 18.1523C8.80007 18.2035 8.77106 18.2502 8.73359 18.2898C8.69612 18.3293 8.65097 18.3608 8.6009 18.3823C8.55084 18.4038 8.49691 18.4149 8.44243 18.4148H6.66363C6.55647 18.4119 6.45468 18.3673 6.37992 18.2904C6.30517 18.2136 6.26336 18.1106 6.2634 18.0034V10.5992C6.26191 10.5457 6.27118 10.4925 6.29063 10.4426C6.31007 10.3928 6.3393 10.3473 6.37662 10.309C6.41393 10.2706 6.45857 10.2402 6.50787 10.2193C6.55716 10.1985 6.61012 10.1878 6.66363 10.1879H8.44243C8.49593 10.1878 8.54889 10.1985 8.59819 10.2193C8.64749 10.2402 8.69212 10.2706 8.72944 10.309C8.76675 10.3473 8.796 10.3928 8.81544 10.4426C8.83489 10.4925 8.84414 10.5457 8.84265 10.5992V17.9923ZM7.51968 8.63141C7.22991 8.62922 6.94729 8.54129 6.70743 8.37871C6.46757 8.21613 6.2812 7.98618 6.17183 7.71784C6.06246 7.4495 6.03499 7.15479 6.09286 6.87086C6.15073 6.58693 6.29137 6.32649 6.49704 6.12236C6.70271 5.91824 6.9642 5.77957 7.24856 5.72385C7.53292 5.66812 7.82742 5.69782 8.09492 5.80921C8.36242 5.9206 8.59096 6.10869 8.75173 6.34978C8.91249 6.59086 8.9983 6.87414 8.9983 7.16391C8.9983 7.35757 8.95998 7.54931 8.88554 7.72808C8.8111 7.90685 8.702 8.06913 8.56455 8.20554C8.4271 8.34196 8.26401 8.44982 8.08467 8.52291C7.90534 8.59601 7.71332 8.63288 7.51968 8.63141ZM18.3369 17.9812C18.337 18.0825 18.2975 18.1798 18.2269 18.2525C18.1564 18.3251 18.0602 18.3674 17.959 18.3703H16.0801C15.9788 18.3674 15.8827 18.3251 15.8121 18.2525C15.7415 18.1798 15.7021 18.0825 15.7021 17.9812V14.557C15.7021 14.0456 15.8578 12.3335 14.3458 12.3335C13.1673 12.3335 12.9339 13.5342 12.8894 14.0678V18.059C12.8894 18.1603 12.85 18.2576 12.7794 18.3303C12.7088 18.403 12.6127 18.4452 12.5114 18.4481H10.6882C10.6371 18.4481 10.5865 18.4381 10.5392 18.4185C10.492 18.3989 10.4491 18.3703 10.413 18.3342C10.3769 18.298 10.3482 18.2551 10.3287 18.2079C10.3091 18.1607 10.299 18.1101 10.299 18.059V10.5658C10.3019 10.4646 10.3442 10.3685 10.4169 10.2979C10.4895 10.2273 10.5869 10.1878 10.6882 10.1879H12.5114C12.6127 10.1878 12.71 10.2273 12.7827 10.2979C12.8554 10.3685 12.8976 10.4646 12.9005 10.5658V11.2107C13.1667 10.8212 13.5341 10.5119 13.9632 10.316C14.3922 10.12 14.8666 10.045 15.3352 10.0989C18.3703 10.0989 18.3592 12.9339 18.3592 14.5459L18.3369 17.9812Z" fill="currentColor"/>
                </svg>
                <span class="ml-1">LinkedIn</span>
            </a>
        </div>
    </footer>

</body>
</html>
