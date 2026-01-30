<?php
ob_start(); // Start output buffering at the very beginning

require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Check if user is logged in
$user = getCurrentUser();
if (!$user) {
    ob_end_flush(); // Flush buffer before redirect
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
    ob_end_flush(); // Flush buffer before redirect
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

// Get previous race predictions (to offer as template)
$previousPredictions = [];
$stmt = $db->prepare("
    SELECT rp.driver_id, rp.predicted_position 
    FROM predictions rp
    JOIN races r ON rp.race_id = r.id
    WHERE rp.user_id = ? AND r.race_date < ? 
    ORDER BY r.race_date DESC 
    LIMIT 1
");
$stmt->bind_param("is", $userId, $race['race_date']);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $previousPredictions[$row['driver_id']] = $row['predicted_position'];
}

// Scoring system
$pointsSystem = [
    'exact' => 10,
    'top3PodiumBonus' => 3
];

// Handle POST requests for saving predictions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $input && isset($input['action'])) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Handle copy from previous race
    if ($input && $input['action'] === 'copy_previous') {
        $raceId = $input['race_id'];
        
        // Get previous race predictions
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
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'predictions' => $prevPreds]);
        exit;
    }
    
    if ($input && $input['action'] === 'save_predictions') {
        // Start output buffering to capture any unexpected output
        ob_start();

        $raceId = $input['race_id'];
        $predictions = $input['predictions'];
        $constructorPredictions = $input['constructor_predictions'] ?? [];
        
        try {
            // Clear existing predictions
            $stmt = $db->prepare("DELETE FROM predictions WHERE race_id = ? AND user_id = ?");
            if (!$stmt) {
                throw new Exception('Prepare delete failed: ' . $db->error);
            }
            $stmt->bind_param("ii", $raceId, $userId);
            if (!$stmt->execute()) {
                throw new Exception('Delete failed: ' . $stmt->error);
            }
            
            // Clear existing constructor predictions
            $stmt = $db->prepare("DELETE FROM constructor_predictions WHERE race_id = ? AND user_id = ?");
            if (!$stmt) {
                throw new Exception('Prepare constructor delete failed: ' . $db->error);
            }
            $stmt->bind_param("ii", $raceId, $userId);
            if (!$stmt->execute()) {
                throw new Exception('Constructor delete failed: ' . $stmt->error);
            }
            
            // Insert new predictions
            $stmt = $db->prepare("INSERT INTO predictions (race_id, user_id, driver_id, driver_name, predicted_position) VALUES (?, ?, ?, ?, ?)");
            if (!$stmt) {
                throw new Exception('Prepare insert failed: ' . $db->error);
            }
            
            foreach ($predictions as $pred) {
                $driverId = $pred['driver_id'];
                $driverName = $pred['driver_name'];
                $position = $pred['predicted_position'];
                $stmt->bind_param("iissi", $raceId, $userId, $driverId, $driverName, $position);
                if (!$stmt->execute()) {
                    throw new Exception('Insert failed: ' . $stmt->error);
                }
            }
            
            // Insert new constructor predictions
            if (!empty($constructorPredictions)) {
                $stmt = $db->prepare("INSERT INTO constructor_predictions (race_id, user_id, constructor_id, constructor_name, predicted_position) VALUES (?, ?, ?, ?, ?)");
                if (!$stmt) {
                    throw new Exception('Prepare constructor insert failed: ' . $db->error);
                }
                
                foreach ($constructorPredictions as $pred) {
                    $constructorId = $pred['constructor_id'];
                    $constructorName = $pred['constructor_name'];
                    $position = $pred['predicted_position'];
                    $stmt->bind_param("iissi", $raceId, $userId, $constructorId, $constructorName, $position);
                    if (!$stmt->execute()) {
                        throw new Exception('Constructor insert failed: ' . $stmt->error);
                    }
                }
            }
            
            // If successful, clear any buffered output and send success JSON
            ob_end_clean(); 
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Predictions saved']);
            exit;
        } catch (Exception $e) {
            // An exception occurred. Get any buffered output and include it in the error message.
            $bufferedOutput = ob_get_clean(); 
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage(),
                'serverOutput' => $bufferedOutput // Include any unexpected output
            ]);
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap');
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0f0f0f 0%, #1a1a1a 50%, #0f0f0f 100%);
            min-height: 100vh;
        }
        
        .card-glass {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .gradient-text {
            background: linear-gradient(135deg, #e10600 0%, #ff4444 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .prediction-list {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .prediction-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(225, 6, 0, 0.2);
            border-radius: 8px;
            cursor: move;
            transition: all 0.2s ease;
            user-select: none;
        }
        
        .prediction-item:hover {
            background: rgba(225, 6, 0, 0.1);
            border-color: rgba(225, 6, 0, 0.4);
            transform: translateX(4px);
        }
        
        .prediction-item.dragging {
            opacity: 0.5;
            background: rgba(225, 6, 0, 0.2);
        }
        
        .prediction-item.drag-over {
            background: rgba(225, 6, 0, 0.15);
            border-color: rgba(225, 6, 0, 0.6);
            box-shadow: 0 0 12px rgba(225, 6, 0, 0.3);
        }
        
        .drag-handle {
            color: #e10600;
            font-size: 16px;
            cursor: grab;
            opacity: 0.7;
        }
        
        .drag-handle:active {
            cursor: grabbing;
        }
        
        .position-num {
            min-width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, rgba(225, 6, 0, 0.2), rgba(225, 6, 0, 0.1));
            border-radius: 8px;
            font-weight: 700;
            color: #ff4444;
            font-size: 14px;
        }
        
        .driver-info {
            flex: 1;
        }
        
        .driver-name {
            font-weight: 600;
            color: #fff;
            font-size: 14px;
        }
        
        .driver-team {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.6);
            margin-top: 2px;
        }
        
        .team-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            white-space: nowrap;
        }
        
        .team-mercedes { background: rgba(0, 210, 190, 0.2); color: #00d2be; }
        .team-ferrari { background: rgba(225, 6, 0, 0.2); color: #e10600; }
        .team-redbull { background: rgba(0, 51, 102, 0.2); color: #0099ff; }
        .team-mclaren { background: rgba(255, 135, 0, 0.2); color: #ff8700; }
        .team-alpine { background: rgba(0, 150, 200, 0.2); color: #0096c8; }
        .team-aston-martin { background: rgba(0, 160, 0, 0.2); color: #00a000; }
        .team-alfa-romeo { background: rgba(200, 0, 0, 0.2); color: #c80000; }
        .team-williams { background: rgba(0, 100, 255, 0.2); color: #0064ff; }
        .team-haas { background: rgba(255, 200, 0, 0.2); color: #ffc800; }
        .team-racing-bulls { background: rgba(0, 100, 150, 0.2); color: #006496; }
        
        .constructor-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 10px;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(225, 6, 0, 0.1);
            border-radius: 6px;
            transition: all 0.2s ease;
        }
        
        .constructor-item:hover {
            background: rgba(225, 6, 0, 0.1);
            border-color: rgba(225, 6, 0, 0.3);
        }
        
        .constructor-name {
            font-weight: 500;
            font-size: 12px;
            color: #fff;
        }
        
        .constructor-points {
            font-weight: 700;
            color: #ff4444;
            font-size: 14px;
        }
        
        .points-value {
            font-size: 18px;
        }
        
        .btn-modern {
            padding: 10px 24px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 14px;
        }
        
        .btn-save {
            background: linear-gradient(135deg, #e10600 0%, #ff4444 100%);
            color: white;
        }
        
        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(225, 6, 0, 0.3);
        }
        
        .btn-reset {
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .btn-reset:hover {
            background: rgba(255, 255, 255, 0.15);
            border-color: rgba(255, 255, 255, 0.3);
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
                    <a href="dashboard.php" class="text-white/80 hover:text-white transition">Dashboard</a>
                    <a href="leaderboard.php" class="text-white/80 hover:text-white transition">Leaderboard</a>
                    <a href="index.php" class="text-white/80 hover:text-white transition">Home</a>
                    <a href="logout.php" class="text-white/80 hover:text-white transition"><?php echo htmlspecialchars($user['username']); ?></a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-4xl md:text-5xl font-black gradient-text mb-2">
                Race Prediction
            </h1>
            <p class="text-gray-300 text-lg">
                📍 <strong><?php echo htmlspecialchars($race['race_name']); ?></strong> • <strong><?php echo date('M d, Y', strtotime($race['race_date'])); ?></strong>
            </p>
        </div>
        
        <!-- Main Container -->
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            <!-- Prediction List (Main) -->
            <div class="lg:col-span-3">
                <div class="card-glass rounded-xl p-6 border border-white/10">
                    <h2 class="text-xl font-bold mb-2 flex items-center gap-2">
                        <i class="fas fa-grip-vertical text-red-500"></i>
                        Drag to Reorder Drivers
                    </h2>
                    <p class="text-gray-400 text-sm mb-4">Arrange drivers 1-22 in your predicted finishing order. Drag up/down to reorder.</p>
                    
                    <!-- Search and Controls -->
                    <div class="flex gap-2 mb-4">
                        <div class="flex-1">
                            <input type="text" id="searchDrivers" placeholder="🔍 Search drivers..." 
                                   class="w-full px-4 py-2 bg-white/5 border border-white/10 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-red-500 transition">
                        </div>
                        <?php if (!empty($previousPredictions)): ?>
                        <button onclick="copyFromPreviousRace()" class="px-4 py-2 bg-white/10 border border-white/20 rounded-lg text-white hover:bg-white/15 transition flex items-center gap-2 whitespace-nowrap">
                            <i class="fas fa-copy"></i> Use Previous
                        </button>
                        <?php endif; ?>
                    </div>
                    
                    <ul class="prediction-list" id="predictionList">
                        <?php 
                        $orderedDrivers = $drivers;
                        usort($orderedDrivers, function($a, $b) use ($predictions) {
                            $posA = $predictions[$a['id']] ?? 999;
                            $posB = $predictions[$b['id']] ?? 999;
                            return $posA - $posB;
                        });
                        
                        foreach ($orderedDrivers as $idx => $driver): 
                            $position = $predictions[$driver['id']] ?? ($idx + 1);
                            $teamClass = strtolower(str_replace([' ', '-'], '-', $driver['team']));
                        ?>
                        <li class="prediction-item" draggable="true" data-driver-id="<?php echo $driver['id']; ?>" data-team="<?php echo htmlspecialchars($driver['team']); ?>" data-driver-name="<?php echo htmlspecialchars($driver['driver_name']); ?>">
                            <div class="drag-handle">
                                <i class="fas fa-grip-vertical"></i>
                            </div>
                            <div class="position-num" id="pos-<?php echo $driver['id']; ?>"><?php echo $position; ?></div>
                            <div class="driver-info">
                                <div class="driver-name"><?php echo htmlspecialchars($driver['driver_name']); ?></div>
                                <div class="driver-team"><?php echo htmlspecialchars($driver['team']); ?></div>
                            </div>
                            <div class="team-badge team-<?php echo $teamClass; ?>">
                                <?php echo htmlspecialchars($driver['team']); ?>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    
                    <div class="flex gap-3 mt-8 pt-6 border-t border-white/10">
                        <button class="btn-modern btn-save flex-1 flex items-center justify-center gap-2" onclick="savePredictions()">
                            <i class="fas fa-check"></i> Save Predictions
                        </button>
                        <button class="btn-modern btn-reset flex-1 flex items-center justify-center gap-2" onclick="resetPredictions()">
                            <i class="fas fa-redo"></i> Reset
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Sidebar -->
            <div class="lg:col-span-1">
                <!-- Constructor Points -->
                <div class="card-glass rounded-xl p-6 border border-white/10 mb-6 sticky top-4">
                    <h3 class="text-lg font-bold mb-4 flex items-center gap-2">
                        <i class="fas fa-trophy text-yellow-500"></i>
                        Points
                    </h3>
                    
                    <div class="space-y-2" id="constructorPoints">
                        <?php foreach ($constructors as $const): ?>
                            <div class="constructor-item" data-constructor="<?php echo htmlspecialchars($const['team']); ?>">
                                <div class="constructor-name"><?php echo htmlspecialchars($const['team']); ?></div>
                                <div class="constructor-points">
                                    <span class="points-value">0</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Scoring Info -->
                    <div class="mt-6 pt-6 border-t border-white/10">
                        <h4 class="font-bold text-sm mb-3 text-yellow-400">📊 Paddock Picks Scoring</h4>
                        
                        <!-- Driver Points -->
                        <div class="mb-4">
                            <p class="font-semibold text-xs text-white mb-2">🏎️ Driver Scoring</p>
                            <div class="text-xs text-gray-300 space-y-1 pl-2">
                                <p class="text-blue-400 font-bold"><strong><?php echo $race['is_sprint'] ? 'Sprint' : 'F1 System'; ?> Points:</strong> (ALL drivers)</p>
                                <?php if ($race['is_sprint']): ?>
                                    <p class="pl-2 text-[10px]">P1: 8 | P2: 7 | P3: 6 | P4: 5</p>
                                    <p class="pl-2 text-[10px]">P5: 4 | P6: 3 | P7: 2 | P8: 1</p>
                                    <p class="pl-2 text-[10px]">P9+: 0 pts</p>
                                <?php else: ?>
                                    <p class="pl-2 text-[10px]">P1: 25 | P2: 18 | P3: 15 | P4: 12 | P5: 10</p>
                                    <p class="pl-2 text-[10px]">P6: 8 | P7: 6 | P8: 4 | P9: 2 | P10: 1</p>
                                    <p class="pl-2 text-[10px]">P11+: 0 pts</p>
                                <?php endif; ?>
                                <p class="text-green-400 mt-2"><strong>Correct Guess Bonus:</strong> +3 pts</p>
                                <p class="text-gray-400 text-[10px] mt-1">(+3 awarded for ANY correct position guess)</p>
                            </div>
                        </div>

                         <!-- Constructor Points -->
                        <div class="mb-4">
                            <p class="font-semibold text-xs text-white mb-2">🏆 Constructor Scoring</p>
                            <div class="text-xs text-gray-300 space-y-1 pl-2">
                                <p class="text-green-400"><strong>Exact Position:</strong> +5 pts</p>
                                <p class="text-gray-400 text-[10px]">Wrong position = 0 pts</p>
                                <p class="text-gray-400 text-[10px] mt-1">Applies to all teams</p>
                            </div>
                        </div>
                        
                        <!-- Examples -->
                        <div class="mb-2 p-2 bg-white/5 rounded space-y-2">
                            <div>
                                <p class="font-semibold text-xs text-yellow-400 mb-1">Example 1 (Correct Guess):</p>
                                <div class="text-[10px] text-gray-300 space-y-0.5">
                                    <p>Predict Verstappen P1, Actual P1:</p>
                                    <p>• F1 Points: +25 | Correct Bonus: +3</p>
                                    <p class="text-green-400 font-bold">Total: 28 pts</p>
                                </div>
                            </div>
                            <div class="pt-2 border-t border-white/5">
                                <p class="font-semibold text-xs text-yellow-400 mb-1">Example 2 (Wrong Guess):</p>
                                <div class="text-[10px] text-gray-300 space-y-0.5">
                                    <p>Predict Hamilton P2, Actual P1:</p>
                                    <p>• F1 Points: +25 | Correct Bonus: 0</p>
                                    <p class="text-green-400 font-bold">Total: 25 pts</p>
                                </div>
                            </div>
                            <div class="pt-2 border-t border-white/5">
                                <p class="font-semibold text-xs text-yellow-400 mb-1">Example 3 (Outside Top 10):</p>
                                <div class="text-[10px] text-gray-300 space-y-0.5">
                                    <p>Predict Stroll P15, Actual P15:</p>
                                    <p>• F1 Points: 0 | Correct Bonus: +3</p>
                                    <p class="text-green-400 font-bold">Total: 3 pts</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- API Note -->
                        <div class="mt-3 p-2 bg-blue-500/10 border border-blue-500/20 rounded">
                            <p class="text-[10px] text-blue-300">
                                <strong>Note:</strong> Points calculated via API after race using actual results
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        const driversData = <?php echo json_encode(array_map(fn($d) => ['id' => $d['id'], 'team' => $d['team']], $drivers)); ?>;
        let draggedElement = null;

        document.addEventListener('dragstart', function(e) {
            if (e.target.closest('.prediction-item')) {
                draggedElement = e.target.closest('.prediction-item');
                draggedElement.classList.add('dragging');
                e.dataTransfer.effectAllowed = 'move';
            }
        });

        document.addEventListener('dragend', function(e) {
            if (draggedElement) {
                draggedElement.classList.remove('dragging');
                draggedElement = null;
            }
        });

        document.addEventListener('dragover', function(e) {
            if (draggedElement && e.target.closest('.prediction-item')) {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
                const item = e.target.closest('.prediction-item');
                if (item && item !== draggedElement) {
                    item.classList.add('drag-over');
                }
            }
        });

        document.addEventListener('dragleave', function(e) {
            const item = e.target.closest('.prediction-item');
            if (item) {
                item.classList.remove('drag-over');
            }
        });

        document.addEventListener('drop', function(e) {
            e.preventDefault();
            
            if (draggedElement) {
                const item = e.target.closest('.prediction-item');
                if (item && item !== draggedElement) {
                    item.classList.remove('drag-over');
                    
                    const list = document.getElementById('predictionList');
                    const allItems = [...list.querySelectorAll('.prediction-item')];
                    const draggedIndex = allItems.indexOf(draggedElement);
                    const targetIndex = allItems.indexOf(item);
                    
                    if (draggedIndex < targetIndex) {
                        item.parentNode.insertBefore(draggedElement, item.nextSibling);
                    } else {
                        item.parentNode.insertBefore(draggedElement, item);
                    }
                    
                    updatePositionNumbers();
                    updateConstructorPoints();
                }
            }
        });

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

        // Calculate team rankings based on predicted constructor order
        // Shows live F1-style points for visualization during prediction
        function calculateTeamRankings() {
            const list = document.getElementById('predictionList');
            const items = list.querySelectorAll('.prediction-item');
            
            // F1 points system for display (Dynamically set based on race type)
            const isSprint = <?php echo ($race['is_sprint'] ?? 0) ? 'true' : 'false'; ?>;
            
            const pointsSystem = isSprint ? {
                // Sprint Points (Top 8)
                1: 8, 2: 7, 3: 6, 4: 5, 5: 4, 6: 3, 7: 2, 8: 1
            } : {
                // Grand Prix Points (Top 10) - No Fastest Lap
                1: 25, 2: 18, 3: 15, 4: 12, 5: 10, 
                6: 8, 7: 6, 8: 4, 9: 2, 10: 1
            };
            
            // Calculate total points for each team (both drivers)
            const teamPoints = {};
            const teamDrivers = {};
            
            items.forEach((item, index) => {
                const position = index + 1;
                const team = item.getAttribute('data-team');
                const points = pointsSystem[position] || 0;
                
                if (!teamPoints[team]) {
                    teamPoints[team] = 0;
                    teamDrivers[team] = [];
                }
                teamPoints[team] += points;
                teamDrivers[team].push({position, points});
            });
            
            // Sort constructors by total points (highest first)
            return Object.entries(teamPoints)
                .sort((a, b) => b[1] - a[1])
                .map((entry, index) => ({
                    team: entry[0],
                    totalPoints: entry[1],
                    constructorRank: index + 1,
                    drivers: teamDrivers[entry[0]]
                }));
        }

        function updateConstructorPoints() {
            const teamRankings = calculateTeamRankings();
            
            // Update the UI - show predicted standings with F1-style points
            const container = document.getElementById('constructorPoints');
            container.innerHTML = ''; // Clear existing items
            
            teamRankings.forEach(ranking => {
                const item = document.createElement('div');
                item.className = 'constructor-item';
                item.setAttribute('data-constructor', ranking.team);
                
                // Show constructor rank and total F1-style points
                item.innerHTML = `
                    <div class="constructor-name">${ranking.team}</div>
                    <div class="constructor-points">
                        <span class="points-value">${ranking.constructorRank} (${ranking.totalPoints}pts)</span>
                    </div>
                `;
                
                container.appendChild(item);
            });
        }

        function savePredictions() {
            const list = document.getElementById('predictionList');
            const items = list.querySelectorAll('.prediction-item');
            const predictions = [];
            
            // Build driver predictions
            items.forEach((item, index) => {
                const position = index + 1;
                const driverId = item.getAttribute('data-driver-id');
                const driverName = item.getAttribute('data-driver-name');
                predictions.push({
                    driver_id: driverId,
                    driver_name: driverName,
                    predicted_position: position
                });
            });
            
            // Calculate constructor predictions using shared function
            const teamRankings = calculateTeamRankings();
            const constructorPredictions = teamRankings.map(ranking => ({
                constructor_id: ranking.team, // Note: Using team name as ID (technical debt)
                constructor_name: ranking.team,
                predicted_position: ranking.constructorRank
            }));
            
            fetch('predict.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    race_id: <?php echo $raceId; ?>,
                    predictions: predictions,
                    constructor_predictions: constructorPredictions,
                    action: 'save_predictions'
                })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    alert('✓ Predictions saved successfully!');
                    setTimeout(() => window.location.href = 'dashboard.php', 1000);
                } else {
                    alert('✗ Error saving predictions: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('✗ Error saving predictions: ' + error.message);
            });
        }

        function resetPredictions() {
            if (confirm('Reset all predictions? This cannot be undone.')) {
                location.reload();
            }
        }

        // Search and filter drivers
        document.getElementById('searchDrivers').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const items = document.querySelectorAll('.prediction-item');
            
            items.forEach(item => {
                const driverName = item.getAttribute('data-driver-name').toLowerCase();
                const team = item.getAttribute('data-team').toLowerCase();
                
                if (driverName.includes(searchTerm) || team.includes(searchTerm)) {
                    item.style.display = '';
                    item.style.opacity = '1';
                } else {
                    item.style.display = 'none';
                }
            });
        });

        // Copy predictions from previous race
        function copyFromPreviousRace() {
            fetch('predict.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    race_id: <?php echo $raceId; ?>,
                    action: 'copy_previous'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.predictions) {
                    // Get driver ID to array map
                    const driverMap = {};
                    document.querySelectorAll('.prediction-item').forEach(item => {
                        driverMap[item.getAttribute('data-driver-id')] = item;
                    });
                    
                    // Sort items by previous predictions
                    const list = document.getElementById('predictionList');
                    const sortedItems = [];
                    
                    // Create array of [driverId, position] and sort
                    const predictions = Object.entries(data.predictions);
                    predictions.sort((a, b) => a[1] - b[1]);
                    
                    // Reorder list items
                    const fragment = document.createDocumentFragment();
                    predictions.forEach(([driverId, position]) => {
                        if (driverMap[driverId]) {
                            fragment.appendChild(driverMap[driverId]);
                        }
                    });
                    
                    // Add remaining drivers (not in previous predictions)
                    document.querySelectorAll('.prediction-item').forEach(item => {
                        if (fragment.querySelector(`[data-driver-id="${item.getAttribute('data-driver-id')}"]`) === null) {
                            fragment.appendChild(item);
                        }
                    });
                    
                    list.innerHTML = '';
                    list.appendChild(fragment);
                    
                    // Update position numbers and points
                    updatePositionNumbers();
                    updateConstructorPoints();
                    
                    // Clear search
                    document.getElementById('searchDrivers').value = '';
                    
                    alert('✓ Loaded predictions from previous race!');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error loading previous predictions');
            });
        }

        document.addEventListener('DOMContentLoaded', updateConstructorPoints);
    </script>
</body>
</html>

