<?php
require_once 'includes/auth.php';
require_once 'includes/maintenance-gate.php';
require_once 'includes/functions.php';
require_once 'includes/avatars.php';

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
    header('Location: index.php#dashboard');
    exit;
}

// Get race details
$stmt = $db->prepare("SELECT * FROM races WHERE id = ?");
$stmt->bind_param("i", $raceId);
$stmt->execute();
$race = $stmt->get_result()->fetch_assoc();

if (!$race) {
    header('Location: index.php#dashboard');
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
unset($pred); // critical: break reference to last element to prevent foreach corruption below

// Get user's total score for this race from scores table (which includes Constructor & Podium bonuses)
$stmt = $db->prepare("SELECT * FROM scores WHERE user_id = ? AND race_id = ?");
$stmt->bind_param("ii", $userId, $raceId);
$stmt->execute();
$scoreRecord = $stmt->get_result()->fetch_assoc();

// Get user stats
$stats = getUserStats($userId);

// Race leaderboard — all users' scores for this race
$raceLeaderboard = [];
$lbStmt = $db->prepare("
    SELECT s.user_id, s.total_points, s.driver_points, s.top3_bonus, s.constructor_points,
           u.username
    FROM scores s
    JOIN users u ON s.user_id = u.id
    WHERE s.race_id = ?
    ORDER BY s.total_points DESC
");
$lbStmt->bind_param("i", $raceId);
$lbStmt->execute();
$raceLeaderboard = $lbStmt->get_result()->fetch_all(MYSQLI_ASSOC);
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
<body style="background:var(--f1-carbon);color:var(--f1-text);min-height:100vh;">

    <!-- Navbar -->
    <?php require_once __DIR__ . '/includes/nav.php'; ?>

    <!-- Main Content -->
    <main class="pt-24 pb-12 px-4 md:px-8 max-w-7xl mx-auto">
        
        <!-- Race Header -->
        <div class="mb-8">
            <a href="index.php#dashboard" class="text-gray-400 hover:text-white transition mb-4 inline-flex items-center gap-2">
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
                    <div class="text-center py-8 text-gray-500 text-sm">
                        <i class="fas fa-info-circle mb-2 block text-2xl"></i>
                        No predictions made for this race.
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-white/10 text-[10px] uppercase tracking-wider text-gray-500">
                                    <th class="pb-2 text-left">Driver</th>
                                    <th class="pb-2 text-center">Predicted</th>
                                    <th class="pb-2 text-center">Actual</th>
                                    <th class="pb-2 text-center">Result</th>
                                    <th class="pb-2 text-right">Pts</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5">
                                <?php foreach ($predictions as $pIdx => $pred): ?>
                                <tr class="hover:bg-white/5 transition <?php echo $pred['is_exact'] ? 'bg-green-500/5' : ''; ?>">
                                    <td class="py-2 pr-4">
                                        <div class="font-bold text-white"><?php echo htmlspecialchars($pred['driver_name']); ?></div>
                                        <div class="text-[10px] text-gray-500"><?php echo htmlspecialchars($pred['team']); ?></div>
                                    </td>
                                    <td class="py-2 text-center text-blue-400 font-bold">P<?php echo $pred['predicted_position']; ?></td>
                                    <td class="py-2 text-center font-bold <?php echo $pred['is_exact'] ? 'text-green-400' : 'text-gray-400'; ?>">
                                        <?php echo $pred['actual_position'] ? 'P' . $pred['actual_position'] : '<span class="text-gray-600">—</span>'; ?>
                                    </td>
                                    <td class="py-2 text-center">
                                        <?php if ($pred['is_exact']): ?>
                                            <span class="text-green-400 text-xs font-bold"><i class="fas fa-check"></i> Hit</span>
                                        <?php elseif ($pred['actual_position']): ?>
                                            <span class="text-gray-600 text-xs"><i class="fas fa-times"></i> Miss</span>
                                        <?php else: ?>
                                            <span class="text-gray-600 text-xs">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-2 text-right font-black <?php echo $pred['points_earned'] > 0 ? 'text-green-400' : 'text-gray-600'; ?>">
                                        <?php echo $pred['points_earned'] > 0 ? '+' . $pred['points_earned'] : '0'; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Race Leaderboard -->
            <?php if (!empty($raceLeaderboard)): ?>
            <div class="g-card p-6 mb-8">
                <h2 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
                    <i class="fas fa-trophy text-yellow-500"></i> Race Leaderboard
                    <span class="text-xs text-gray-500 font-normal ml-1"><?php echo count($raceLeaderboard); ?> drivers</span>
                </h2>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-white/10 text-[10px] uppercase tracking-wider text-gray-500">
                                <th class="pb-2 text-left w-8">#</th>
                                <th class="pb-2 text-left">Driver</th>
                                <th class="pb-2 text-center">Base</th>
                                <th class="pb-2 text-center">Podium</th>
                                <th class="pb-2 text-center">Constr.</th>
                                <th class="pb-2 text-right font-bold">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            <?php foreach ($raceLeaderboard as $lbIdx => $lbRow):
                                $isCurrentUser = ($lbRow['user_id'] == $loggedInUserId);
                                $rowClass = $isCurrentUser ? 'bg-orange-500/10' : '';
                                $rank = $lbIdx + 1;
                                $rankDisplay = match($rank) { 1 => '🥇', 2 => '🥈', 3 => '🥉', default => $rank };
                            ?>
                            <tr class="<?php echo $rowClass; ?> hover:bg-white/5 transition">
                                <td class="py-2 pr-3 text-center text-xs text-gray-400"><?php echo $rankDisplay; ?></td>
                                <td class="py-2">
                                    <span class="font-bold <?php echo $isCurrentUser ? 'text-orange-400' : 'text-white'; ?>">
                                        <?php echo htmlspecialchars($lbRow['username']); ?>
                                    </span>
                                    <?php if ($isCurrentUser): ?><span class="text-[10px] text-orange-500 ml-1">you</span><?php endif; ?>
                                </td>
                                <td class="py-2 text-center text-gray-300"><?php echo $lbRow['driver_points'] ?? 0; ?></td>
                                <td class="py-2 text-center text-blue-400"><?php echo $lbRow['top3_bonus'] ?? 0; ?></td>
                                <td class="py-2 text-center text-purple-400"><?php echo $lbRow['constructor_points'] ?? 0; ?></td>
                                <td class="py-2 text-right font-black <?php echo $isCurrentUser ? 'text-orange-400' : 'text-white'; ?>">
                                    <?php echo $lbRow['total_points']; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- Full Race Results -->
            <?php if (!empty($actualResults)): ?>
            <div class="g-card p-6">
                <h2 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
                    <i class="fas fa-flag-checkered text-green-500"></i> Official Results
                    <span class="text-xs text-gray-500 font-normal ml-1"><?php echo count($actualResults); ?> finishers</span>
                </h2>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-white/10 text-[10px] uppercase tracking-wider text-gray-500">
                                <th class="pb-2 text-left w-8">#</th>
                                <th class="pb-2 text-left">Driver</th>
                                <th class="pb-2 text-left">Constructor</th>
                                <th class="pb-2 text-right">FL</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            <?php foreach ($actualResults as $rIdx => $result):
                                $pos = $rIdx + 1;
                                $podiumColor = match($pos) { 1 => 'text-yellow-400', 2 => 'text-gray-300', 3 => 'text-amber-600', default => 'text-gray-400' };
                            ?>
                            <tr class="hover:bg-white/5 transition">
                                <td class="py-2 pr-3 font-black <?php echo $podiumColor; ?> w-8"><?php echo $pos; ?></td>
                                <td class="py-2 font-bold text-white"><?php echo htmlspecialchars($result['driver_name']); ?></td>
                                <td class="py-2 text-gray-500 text-xs"><?php echo htmlspecialchars($result['constructor_name'] ?? '—'); ?></td>
                                <td class="py-2 text-right">
                                    <?php if ($result['fastest_lap']): ?>
                                    <span class="text-purple-400 text-xs font-bold"><i class="fas fa-bolt"></i></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
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

<script src="app.js"></script>
</body>
</html>
