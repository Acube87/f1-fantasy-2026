<?php
ob_start();
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Check if user is logged in
$user = getCurrentUser();
if (!$user) {
    ob_end_flush();
    header('Location: login.php');
    exit;
}

$db = getDB();
$raceId = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (isset($input['race_id'])) {
        $raceId = $input['race_id'];
    }
} else {
    $raceId = $_GET['race_id'] ?? null;
}
$userId = $user['id'];

if (!$raceId) {
    ob_end_flush();
    header('Location: index.php');
    exit;
}

// Get race info
$stmt = $db->prepare("SELECT * FROM races WHERE id = ?");
$stmt->bind_param("i", $raceId);
$stmt->execute();
$race = $stmt->get_result()->fetch_assoc();

if (!$race) {
    die("Race not found");
}

// Calculate Prediction Deadline (Saturday 00:00 before race)
$raceDate = new DateTime($race['race_date'], new DateTimeZone('UTC'));
// Get the Saturday before the race (could be same day if race is Sunday)
$raceDayOfWeek = (int)$raceDate->format('N'); // 1=Monday, 7=Sunday
if ($raceDayOfWeek == 7) {
    // Race is on Sunday, deadline is Saturday 00:00 (1 day before)
    $deadline = clone $raceDate;
    $deadline->modify('-1 day')->setTime(0, 0, 0);
} elseif ($raceDayOfWeek == 6) {
    // Race is on Saturday (Sprint weekend), deadline is Saturday 00:00 (same day)
    $deadline = clone $raceDate;
    $deadline->setTime(0, 0, 0);
} else {
    // Race on other day, find previous Saturday
    $daysToSubtract = ($raceDayOfWeek == 0 ? 1 : 8 - $raceDayOfWeek);
    $deadline = clone $raceDate;
    $deadline->modify("-{$daysToSubtract} days")->setTime(0, 0, 0);
}

$now = new DateTime('now', new DateTimeZone('UTC'));
$isPredictionOpen = $now < $deadline;
$deadlineTimestamp = $deadline->getTimestamp();
$nowTimestamp = $now->getTimestamp();
$raceDateTimestamp = $raceDate->getTimestamp();

// Get all drivers
$stmt = $db->prepare("SELECT id, driver_name, team FROM drivers ORDER BY team, driver_name");
$stmt->execute();
$drivers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get existing predictions for this race
$predictions = [];
$stmt = $db->prepare("SELECT driver_id, predicted_position FROM predictions WHERE race_id = ? AND user_id = ?");
$stmt->bind_param("ii", $raceId, $userId);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $predictions[$row['driver_id']] = $row['predicted_position'];
}

// Get unique constructors for sidebar
$stmt = $db->prepare("SELECT DISTINCT team FROM drivers ORDER BY team");
$stmt->execute();
$constructors = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Handle POST requests for saving predictions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $input && isset($input['action'])) {
    
    // Handle copy from previous race
    if ($input['action'] === 'copy_previous') {
        $stmt = $db->prepare("
            SELECT rp.driver_id, rp.predicted_position 
            FROM predictions rp
            JOIN races r ON rp.race_id = r.id
            WHERE rp.user_id = ? AND r.race_date < (SELECT race_date FROM races WHERE id = ?)
            ORDER BY r.race_date DESC 
            LIMIT 1
        ");
        $stmt->bind_param("ii", $userId, $raceId);
        $stmt->execute();
        $result = $stmt->get_result();
        $prevPreds = [];
        while ($row = $result->fetch_assoc()) {
            $prevPreds[$row['driver_id']] = $row['predicted_position'];
        }
        
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'predictions' => $prevPreds]);
        exit;
    }
    
    if ($input['action'] === 'save_predictions') {
        ob_clean(); // Clear buffer
        $predictionsInput = $input['predictions'];
        $constructorPredictions = $input['constructor_predictions'] ?? [];
        
        try {
            $db->begin_transaction();

            // Clear existing
            $stmt = $db->prepare("DELETE FROM predictions WHERE race_id = ? AND user_id = ?");
            $stmt->bind_param("ii", $raceId, $userId);
            $stmt->execute();
            
            $stmt = $db->prepare("DELETE FROM constructor_predictions WHERE race_id = ? AND user_id = ?");
            $stmt->bind_param("ii", $raceId, $userId);
            $stmt->execute();
            
            // Insert Driver Preds
            $stmt = $db->prepare("INSERT INTO predictions (race_id, user_id, driver_id, driver_name, predicted_position) VALUES (?, ?, ?, ?, ?)");
            foreach ($predictionsInput as $pred) {
                $stmt->bind_param("iissi", $raceId, $userId, $pred['driver_id'], $pred['driver_name'], $pred['predicted_position']);
                $stmt->execute();
            }
            
            // Insert Constructor Preds
            if (!empty($constructorPredictions)) {
                $stmt = $db->prepare("INSERT INTO constructor_predictions (race_id, user_id, constructor_id, constructor_name, predicted_position) VALUES (?, ?, ?, ?, ?)");
                foreach ($constructorPredictions as $pred) {
                    $stmt->bind_param("iissi", $raceId, $userId, $pred['constructor_id'], $pred['constructor_name'], $pred['predicted_position']);
                    $stmt->execute();
                }
            }
            
            $db->commit();
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
            exit;
        } catch (Exception $e) {
            $db->rollback();
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Predict - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="css/gaming-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- SortableJS for smooth Drag & Drop -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js"></script>
    <style>
        /* Compact List Item */
        .prediction-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 6px 12px;
            background: rgba(255, 255, 255, 0.03);
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            transition: background 0.2s, transform 0.2s;
            cursor: grab;
            position: relative;
        }

        .prediction-item:hover {
            background: rgba(255, 255, 255, 0.06);
            z-index: 5;
        }

        /* Sortable Ghost (Placeholder) */
        .sortable-ghost {
            background: rgba(59, 130, 246, 0.1) !important;
            border: 1px dashed rgba(59, 130, 246, 0.5);
            opacity: 0.5;
        }

        /* Sortable Drag (Active Item) */
        .sortable-drag {
            background: rgba(30, 58, 138, 0.9);
            box-shadow: 0 10px 25px rgba(0,0,0,0.5);
            cursor: grabbing;
            transform: scale(1.02);
            z-index: 100 !important;
        }

        .position-num {
            font-variant-numeric: tabular-nums;
            width: 24px;
            text-align: center;
            font-weight: 800;
            color: #64748b;
            font-size: 0.8rem;
        }

        /* Team Badge Styles - Official F1 2026 Colors */
        .team-badge {
            width: 28px;
            height: 28px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.6rem;
            font-weight: 900;
            letter-spacing: -0.5px;
            flex-shrink: 0;
            box-shadow: 0 2px 8px rgba(0,0,0,0.3);
        }

        .badge-ferrari { background: linear-gradient(135deg, #DC0000 0%, #E8002D 100%); color: white; }
        .badge-mercedes { background: linear-gradient(135deg, #00D2BE 0%, #27F4D2 100%); color: #000; }
        .badge-red-bull { background: linear-gradient(135deg, #3671C6 0%, #1E41FF 100%); color: white; }
        .badge-mclaren { background: linear-gradient(135deg, #FF8000 0%, #FFA04D 100%); color: white; }
        .badge-aston-martin { background: linear-gradient(135deg, #00665F 0%, #229971 100%); color: white; }
        .badge-alpine { background: linear-gradient(135deg, #0090FF 0%, #2E9AFF 100%); color: white; }
        .badge-williams { background: linear-gradient(135deg, #005AFF 0%, #4280FF 100%); color: white; }
        .badge-haas { background: linear-gradient(135deg, #FFFFFF 0%, #B6BABD 100%); color: #000; border: 1px solid rgba(255,255,255,0.2); }
        .badge-rb, .badge-racing-bulls { background: linear-gradient(135deg, #6692FF 0%, #1E41FF 100%); color: white; }
        .badge-sauber, .badge-kick-sauber { background: linear-gradient(135deg, #00E701 0%, #52B256 100%); color: #000; }
        .badge-audi { background: linear-gradient(135deg, #000000 0%, #FF1721 100%); color: white; }
        .badge-cadillac { background: linear-gradient(135deg, #0C1C8C 0%, #C41E3A 100%); color: white; }
        
        /* Team Color Left Borders */
        .team-ferrari { border-left: 3px solid #DC0000; }
        .team-mercedes { border-left: 3px solid #00D2BE; }
        .team-red-bull { border-left: 3px solid #3671C6; }
        .team-mclaren { border-left: 3px solid #FF8000; }
        .team-aston-martin { border-left: 3px solid #00665F; }
        .team-alpine { border-left: 3px solid #0090FF; }
        .team-williams { border-left: 3px solid #005AFF; }
        .team-haas { border-left: 3px solid #B6BABD; }
        .team-rb, .team-racing-bulls { border-left: 3px solid #6692FF; }
        .team-sauber, .team-kick-sauber { border-left: 3px solid #00E701; }
        .team-audi { border-left: 3px solid #FF1721; }
        .team-cadillac { border-left: 3px solid #C41E3A; }
        
        /* Mobile Controls */
        .mobile-controls {
            display: none;
            gap: 4px;
        }
        
        @media (max-width: 768px) {
            .mobile-controls {
                display: flex;
            }
            
            .prediction-item {
                padding: 10px 12px; /* More touch space */
            }
        }
        
        .mobile-btn {
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(59, 130, 246, 0.2);
            border: 1px solid rgba(59, 130, 246, 0.3);
            border-radius: 6px;
            color: #60a5fa;
            cursor: pointer;
            transition: all 0.2s;
            flex-shrink: 0;
        }
        
        .mobile-btn:active {
            background: rgba(59, 130, 246, 0.4);
            transform: scale(0.95);
        }
        
        .mobile-btn.disabled {
            opacity: 0.3;
            cursor: not-allowed;
        }
        
        .driver-name-text {
            font-size: 0.9rem;
            font-weight: 600;
        }
        
        .team-name-small {
            font-size: 0.65rem;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            font-weight: 500;
        }
        
        /* Success Modal */
        #successModal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(8px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            animation: fadeIn 0.3s ease;
        }
        
        #successModal.show {
            display: flex;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .success-content {
            background: rgba(15, 23, 42, 0.95);
            padding: 2rem;
            border-radius: 24px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
            animation: scaleIn 0.3s ease;
        }
        
        @keyframes scaleIn {
            from { transform: scale(0.9); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }
        
        /* Prediction Deadline Progress Bar */
        .deadline-container {
            background: rgba(15, 23, 42, 0.8);
            padding: 12px 16px;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .progress-bar-bg {
            background: rgba(255, 255, 255, 0.1);
            height: 8px;
            border-radius: 999px;
            overflow: hidden;
            position: relative;
        }
        
        .progress-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #10b981 0%, #22c55e 50%, #facc15 100%);
            border-radius: 999px;
            transition: width 1s ease;
            box-shadow: 0 0 10px rgba(34, 197, 94, 0.5);
        }
        
        .progress-bar-fill.warning {
            background: linear-gradient(90deg, #f59e0b 0%, #ef4444 100%);
            box-shadow: 0 0 10px rgba(239, 68, 68, 0.5);
        }
        
        .progress-bar-fill.closed {
            background: #64748b;
            box-shadow: none;
        }
    </style>
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
            <div class="flex items-center gap-3 pl-6 border-l border-white/10">
                <a href="dashboard.php" class="text-gray-300 hover:text-white font-bold text-sm mr-4">Dashboard</a>
                <div class="w-10 h-10 rounded-full bg-slate-700 border-2 border-white/10 overflow-hidden">
                    <img src="https://api.dicebear.com/7.x/<?php echo $user['avatar_style'] ?? 'avataaars'; ?>/svg?seed=<?php echo $user['username']; ?>" alt="Avatar" class="w-full h-full"> 
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="pt-24 pb-12 px-4 md:px-8 max-w-7xl mx-auto">
        
        <!-- Compact Header -->
        <div class="mb-6 space-y-3">
            <!-- Race Info & Actions -->
            <div class="flex justify-between items-center bg-slate-900/50 p-4 rounded-xl border border-white/5 backdrop-blur-md sticky top-20 z-40 shadow-xl">
                <div class="flex items-center gap-4">
                    <div class="text-3xl">🇦🇺</div>
                    <div>
                        <h1 class="text-xl font-bold text-white uppercase tracking-wider"><?php echo htmlspecialchars($race['country']); ?></h1>
                        <div class="text-xs text-gray-400"><?php echo htmlspecialchars($race['circuit_name']); ?></div>
                    </div>
                </div>
                <div class="flex gap-2">
                    <button onclick="copyFromPreviousRace()" class="text-xs bg-white/5 hover:bg-white/10 border border-white/10 px-3 py-2 rounded text-gray-300 transition">
                        <i class="fas fa-history mr-1"></i> Copy Prev
                    </button>
                     <button id="saveButton" class="g-btn g-btn-blue px-6 py-2 text-sm shadow-lg hover:shadow-blue-500/20" onclick="savePredictions()" <?php if (!$isPredictionOpen) echo 'disabled style="opacity:0.5; cursor:not-allowed;"'; ?>>
                        SAVE <i class="fas fa-check ml-1"></i>
                    </button>
                </div>
            </div>
            
            <!-- Prediction Deadline Progress Bar -->
            <div class="deadline-container">
                <div class="flex justify-between items-center mb-2">
                    <div class="text-xs font-bold text-gray-400 uppercase tracking-wider">
                        <i class="fas fa-clock mr-1"></i> Prediction Status
                    </div>
                    <div id="deadlineText" class="text-xs font-bold" data-deadline="<?php echo $deadlineTimestamp; ?>" data-now="<?php echo $nowTimestamp; ?>" data-racedate="<?php echo $raceDateTimestamp; ?>">
                        <?php if ($isPredictionOpen): ?>
                            <span class="text-green-400">⚡ OPEN</span>
                        <?php else: ?>
                            <span class="text-red-400">🔒 CLOSED</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="progress-bar-bg">
                    <div id="progressBar" class="progress-bar-fill" style="width: 0%"></div>
                </div>
                <div id="timeRemaining" class="text-[10px] text-gray-500 mt-1.5 text-center font-mono">
                    Calculating...
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            
            <!-- Main Drag List (Compact) -->
            <div class="lg:col-span-8">
                <div class="g-card overflow-hidden">
                    <!-- Search -->
                     <div class="p-2 border-b border-white/10 bg-black/10">
                        <input type="text" id="searchDrivers" placeholder="🔍 Filter drivers..." 
                               class="w-full bg-transparent border-none text-sm text-white focus:ring-0 placeholder-gray-600 px-2 py-1">
                    </div>

                    <div id="predictionList" class="bg-black/20 min-h-[500px]">
                        <?php 
                        $orderedDrivers = $drivers;
                        // Sort by current prediction (if any)
                        usort($orderedDrivers, function($a, $b) use ($predictions) {
                            $posA = $predictions[$a['id']] ?? 999;
                            $posB = $predictions[$b['id']] ?? 999;
                            return $posA - $posB;
                        });
                        
                        // Team abbreviations mapping
                        $teamAbbr = [
                            'Ferrari' => 'FER',
                            'Mercedes' => 'MER',
                            'Red Bull' => 'RBR',
                            'McLaren' => 'MCL',
                            'Aston Martin' => 'AMR',
                            'Alpine' => 'ALP',
                            'Williams' => 'WIL',
                            'Haas' => 'HAA',
                            'RB' => 'RB',
                            'Racing Bulls' => 'RB',
                            'Sauber' => 'SAU',
                            'Kick Sauber' => 'SAU',
                            'Audi' => 'AUD',
                            'Cadillac' => 'CAD'
                        ];
                        
                        foreach ($orderedDrivers as $idx => $driver): 
                            $position = $predictions[$driver['id']] ?? ($idx + 1);
                            $teamSlug = strtolower(str_replace(' ', '-', $driver['team']));
                            $abbr = $teamAbbr[$driver['team']] ?? strtoupper(substr($driver['team'], 0, 3));
                        ?>
                        <div class="prediction-item group team-<?php echo $teamSlug; ?>" 
                             data-driver-id="<?php echo $driver['id']; ?>" 
                             data-team="<?php echo htmlspecialchars($driver['team']); ?>" 
                             data-driver-name="<?php echo htmlspecialchars($driver['driver_name']); ?>">
                            
                            <!-- Grip Handle -->
                            <div class="text-gray-600 group-hover:text-gray-400 cursor-grab px-1">
                                <i class="fas fa-grip-lines"></i>
                            </div>
                            
                            <!-- Position -->
                            <div class="position-num"><?php echo $position; ?></div>
                            
                            <!-- Team Badge -->
                            <div class="team-badge badge-<?php echo $teamSlug; ?>">
                                <?php echo $abbr; ?>
                            </div>
                            
                            <!-- Driver Info -->
                            <div class="flex-1 flex items-center justify-between">
                                <div>
                                    <div class="driver-name-text text-white"><?php echo htmlspecialchars($driver['driver_name']); ?></div>
                                    <div class="team-name-small"><?php echo htmlspecialchars($driver['team']); ?></div>
                                </div>
                            </div>
                            
                            <!-- Mobile Up/Down Controls -->
                            <div class="mobile-controls">
                                <button class="mobile-btn move-up" onclick="moveItem(this, -1)" type="button">
                                    <i class="fas fa-chevron-up"></i>
                                </button>
                                <button class="mobile-btn move-down" onclick="moveItem(this, 1)" type="button">
                                    <i class="fas fa-chevron-down"></i>
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Sidebar (Points & Info) -->
            <div class="lg:col-span-4 space-y-4 sticky top-40">
                <!-- Live Points -->
                <div class="g-card p-4 border-t-2 border-t-green-500">
                    <h3 class="font-bold text-white text-sm mb-3 flex items-center gap-2">
                        <i class="fas fa-chart-pie text-green-500"></i> Projected Points
                    </h3>
                    <div id="constructorPoints" class="space-y-1">
                        <!-- Populated by JS -->
                    </div>
                </div>
                
                <!-- Points System Explained -->
                <div class="g-card p-4 border-t-2 border-t-blue-500">
                    <h3 class="font-bold text-white text-sm mb-3 flex items-center gap-2">
                        <i class="fas fa-info-circle text-blue-500"></i> Points System
                    </h3>
                    
                    <?php 
                    $isDoublePoints = in_array($race['country'], ['China', 'UK', 'Singapore']);
                    $isSprint = !empty($race['is_sprint']);
                    ?>
                    
                    <?php if ($isDoublePoints): ?>
                        <div class="bg-orange-500/10 border border-orange-500/30 rounded-lg p-3 mb-3">
                            <div class="flex items-center gap-2 mb-1">
                                <i class="fas fa-star text-orange-400"></i>
                                <span class="text-orange-400 font-bold text-xs uppercase">Double Points Event!</span>
                            </div>
                            <p class="text-[10px] text-gray-300">All points are <strong>DOUBLED</strong> for this race!</p>
                        </div>
                    <?php endif; ?>
                    
                    <div class="space-y-3 text-[11px] text-gray-400">
                        <!-- Driver Position Points -->
                        <div>
                            <div class="font-bold text-white mb-1.5 flex items-center gap-1">
                                <i class="fas fa-trophy text-yellow-500 text-xs"></i>
                                Driver Position (Exact Match)
                            </div>
                            <p class="leading-relaxed mb-2">
                                Get the position <strong class="text-white">exactly right</strong> (1-10), earn <strong class="text-green-400">F1 points + 3 precision bonus</strong>
                            </p>
                            <div class="bg-black/20 rounded p-2 space-y-0.5 font-mono text-[10px]">
                                <div class="flex justify-between"><span>1st:</span> <strong class="text-green-400">25+3 = 28 pts</strong></div>
                                <div class="flex justify-between"><span>2nd:</span> <strong>18+3 = 21 pts</strong></div>
                                <div class="flex justify-between"><span>3rd:</span> <strong>15+3 = 18 pts</strong></div>
                                <div class="flex justify-between"><span>4th:</span> <strong>12+3 = 15 pts</strong></div>
                                <div class="flex justify-between"><span>5th:</span> <strong>10+3 = 13 pts</strong></div>
                                <div class="text-gray-600 text-[9px] mt-1">Points down to 10th (1+3=4pts)</div>
                            </div>
                        </div>
                        
                        <!-- Podium Sweep Bonus -->
                        <div>
                            <div class="font-bold text-white mb-1.5 flex items-center gap-1">
                                <i class="fas fa-medal text-orange-500 text-xs"></i>
                                Podium Sweep Bonus
                            </div>
                            <p class="leading-relaxed">
                                Get <strong class="text-white">P1, P2, AND P3</strong> all correct in exact order: 
                                <strong class="text-orange-400">+10 bonus points</strong>
                            </p>
                        </div>
                        
                        <!-- Constructor Bonus -->
                        <div>
                            <div class="font-bold text-white mb-1.5 flex items-center gap-1">
                                <i class="fas fa-wrench text-blue-400 text-xs"></i>
                                Top Constructor Bonus
                            </div>
                            <p class="leading-relaxed">
                                Predict which <strong class="text-white">Constructor scores the most</strong> points in the race: 
                                <strong class="text-blue-400">+5 bonus points</strong>
                            </p>
                        </div>
                        
                        <!-- Example -->
                        <div class="border-t border-white/10 pt-3 mt-3">
                            <div class="font-bold text-white mb-2 text-xs">💡 Example:</div>
                            <div class="bg-green-500/5 border border-green-500/20 rounded p-2 text-[10px]">
                                <div class="text-white font-bold mb-1">You predict: Leclerc (P1), Hamilton (P2), Norris (P3)</div>
                                <div class="text-white font-bold mb-2">Result: Leclerc (P1), Hamilton (P2), Norris (P3), Ferrari wins</div>
                                <div class="space-y-0.5">
                                    <div>Leclerc (25+3) = <strong class="text-green-400">28 pts</strong></div>
                                    <div>Hamilton (18+3) = <strong class="text-green-400">21 pts</strong></div>
                                    <div>Norris (15+3) = <strong class="text-green-400">18 pts</strong></div>
                                    <div>Podium Sweep = <strong class="text-orange-400">+10 pts</strong></div>
                                    <div>Constructor (Ferrari) = <strong class="text-blue-400">+5 pts</strong></div>
                                    <div class="border-t border-white/10 mt-2 pt-2 text-white font-bold">
                                        Total = <strong class="text-green-400">82 points!</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

    </main>
    
    <!-- Success Modal with Lottie Animation -->
    <div id="successModal">
        <div class="success-content">
            <dotlottie-player 
                src="Success Lottie Icons.lottie" 
                background="transparent" 
                speed="1" 
                style="width: 200px; height: 200px; margin: 0 auto;"
                autoplay>
            </dotlottie-player>
            <h2 class="text-2xl font-black text-white mt-4 mb-2">PREDICTIONS LOCKED IN!</h2>
            <p class="text-gray-400 text-sm">Your lineup has been saved successfully</p>
        </div>
    </div>
    
    <!-- Lottie Player Script -->
    <script src="https://unpkg.com/@dotlottie/player-component@latest/dist/dotlottie-player.mjs" type="module"></script>
    
    <!-- Scripts -->
    <script>
        // --- Initializes SortableJS ---
        document.addEventListener('DOMContentLoaded', function() {
            var el = document.getElementById('predictionList');
            var sortable = Sortable.create(el, {
                animation: 150,  // Smooth animation (ms)
                ghostClass: 'sortable-ghost', // Class for the placeholder
                dragClass: 'sortable-drag',   // Class for the dragging item
                handle: '.prediction-item',   // Draggable by entire row (or use .fa-grip-lines for handle only)
                onEnd: function (evt) {
                    updatePositionNumbers();
                    updateConstructorPoints();
                }
            });
            
            // Initial calculation
            updateConstructorPoints();
            
            // Initialize Deadline Countdown
            updateDeadlineProgress();
            setInterval(updateDeadlineProgress, 1000); // Update every second
        });
        
        function updateDeadlineProgress() {
            const deadlineEl = document.getElementById('deadlineText');
            const progressBar = document.getElementById('progressBar');
            const timeRemainingEl = document.getElementById('timeRemaining');
            
            if (!deadlineEl) return;
            
            const deadlineTimestamp = parseInt(deadlineEl.dataset.deadline) * 1000;
            const raceDateTimestamp = parseInt(deadlineEl.dataset.racedate) * 1000;
            const now = Date.now();
            
            // Calculate time windows
            const totalTime = deadlineTimestamp - (deadlineTimestamp - (7 * 24 * 60 * 60 * 1000)); // 7 days before deadline
            const elapsed = now - (deadlineTimestamp - (7 * 24 * 60 * 60 * 1000));
            const remaining = deadlineTimestamp - now;
            
            // Calculate percentage (0-100)
            let percentage = (elapsed / totalTime) * 100;
            percentage = Math.max(0, Math.min(100, percentage));
            
            // Update progress bar
            progressBar.style.width = percentage + '%';
            
            // Update bar color based on time remaining
            progressBar.classList.remove('warning', 'closed');
            if (remaining <= 0) {
                progressBar.classList.add('closed');
                timeRemainingEl.textContent = '🔒 Predictions closed';
                deadlineEl.innerHTML = '<span class="text-red-400">🔒 CLOSED</span>';
            } else if (remaining < 24 * 60 * 60 * 1000) { // Less than 24 hours
                progressBar.classList.add('warning');
                const hours = Math.floor(remaining / (60 * 60 * 1000));
                const minutes = Math.floor((remaining % (60 * 60 * 1000)) / (60 * 1000));
                timeRemainingEl.textContent = `⚠️ ${hours}h ${minutes}m until lockdown`;                deadlineEl.innerHTML = '<span class="text-orange-400">⏰ CLOSING SOON</span>';
            } else {
                const days = Math.floor(remaining / (24 * 60 * 60 * 1000));
                const hours = Math.floor((remaining % (24 * 60 * 60 * 1000)) / (60 * 60 * 1000));
                timeRemainingEl.textContent = `${days}d ${hours}h remaining • Locks Saturday 00:00 UTC`;
                deadlineEl.innerHTML = '<span class="text-green-400">⚡ OPEN</span>';
            }
        }

        function updatePositionNumbers() {
            const list = document.getElementById('predictionList');
            const items = list.querySelectorAll('.prediction-item');
            
            items.forEach((item, index) => {
                const position = index + 1;
                const positionNum = item.querySelector('.position-num');
                if (positionNum) {
                    positionNum.textContent = position;
                }
            });
        }
        
        // Mobile Up/Down Controls
        function moveItem(button, direction) {
            const item = button.closest('.prediction-item');
            const list = document.getElementById('predictionList');
            const items = Array.from(list.querySelectorAll('.prediction-item'));
            const currentIndex = items.indexOf(item);
            const newIndex = currentIndex + direction;
            
            // Check bounds
            if (newIndex < 0 || newIndex >= items.length) return;
            
            // Swap items
            if (direction === -1) {
                // Move up
                list.insertBefore(item, items[newIndex]);
            } else {
                // Move down
                list.insertBefore(items[newIndex], item);
            }
            
            // Update UI
            updatePositionNumbers();
            updateConstructorPoints();
        }
        
        // --- Constructor Logic (Same as before) ---
         function calculateTeamRankings() {
            const list = document.getElementById('predictionList');
            const items = list.querySelectorAll('.prediction-item');
            
            const isSprint = <?php echo !empty($race['is_sprint']) ? 'true' : 'false'; ?>;
            const isDoublePoints = <?php echo (in_array($race['country'], ['China', 'UK', 'Singapore'])) ? 'true' : 'false'; ?>;
            
            let pointsSystem = isSprint ? {
                1: 8, 2: 7, 3: 6, 4: 5, 5: 4, 6: 3, 7: 2, 8: 1
            } : {
                1: 25, 2: 18, 3: 15, 4: 12, 5: 10, 
                6: 8, 7: 6, 8: 4, 9: 2, 10: 1
            };

            if (isDoublePoints) {
                for (let key in pointsSystem) { pointsSystem[key] = pointsSystem[key] * 2; }
            }
            
            const teamPoints = {};
            
            items.forEach((item, index) => {
                const position = index + 1;
                const team = item.getAttribute('data-team');
                const points = pointsSystem[position] || 0;
                
                if (!teamPoints[team]) teamPoints[team] = 0;
                teamPoints[team] += points;
            });
            
            return Object.entries(teamPoints)
                .sort((a, b) => b[1] - a[1])
                .map((entry, index) => ({
                    team: entry[0],
                    totalPoints: entry[1],
                    constructorRank: index + 1
                }));
        }

        function updateConstructorPoints() {
            const teamRankings = calculateTeamRankings();
            const container = document.getElementById('constructorPoints');
            container.innerHTML = ''; 
            
            teamRankings.forEach(ranking => {
                const item = document.createElement('div');
                item.className = 'flex justify-between items-center p-1.5 hover:bg-white/5 rounded transition border-b border-white/5 last:border-0';
                item.innerHTML = `
                    <div class="flex items-center gap-2">
                         <span class="text-xs text-gray-400 w-4 text-center">${ranking.constructorRank}</span>
                         <span class="font-bold text-gray-200 text-xs">${ranking.team}</span>
                    </div>
                    <div class="font-bold text-green-400 text-xs">${ranking.totalPoints} pts</div>
                `;
                container.appendChild(item);
            });
        }

        function savePredictions() {
            const list = document.getElementById('predictionList');
            const items = list.querySelectorAll('.prediction-item');
            const predictions = [];
            
            items.forEach((item, index) => {
                predictions.push({
                    driver_id: item.getAttribute('data-driver-id'),
                    driver_name: item.getAttribute('data-driver-name'),
                    predicted_position: index + 1
                });
            });
            
            const teamRankings = calculateTeamRankings();
            const constructorPredictions = teamRankings.map(ranking => ({
                constructor_id: ranking.team,
                constructor_name: ranking.team,
                predicted_position: ranking.constructorRank
            }));
            
            fetch('predict.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    race_id: <?php echo $raceId; ?>,
                    predictions: predictions,
                    constructor_predictions: constructorPredictions,
                    action: 'save_predictions'
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    // Show success modal
                    const modal = document.getElementById('successModal');
                    modal.classList.add('show');
                    
                    // Redirect after animation
                    setTimeout(() => {
                        window.location.href = 'dashboard.php';
                    }, 2000);
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(err => alert('Network error'));
        }
        
        // Basic filter
        document.getElementById('searchDrivers').addEventListener('input', (e) => {
            const term = e.target.value.toLowerCase();
            document.querySelectorAll('.prediction-item').forEach(item => {
                const text = item.innerText.toLowerCase();
                 if (text.includes(term)) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            });
        });

    </script>

</body>
</html>
