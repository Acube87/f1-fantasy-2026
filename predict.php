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

        .team-badge-small {
            font-size: 0.65rem;
            padding: 1px 6px;
            border-radius: 4px;
            font-weight: 700;
            text-transform: uppercase;
            opacity: 0.8;
        }
        
        .driver-name-text {
            font-size: 0.9rem;
            font-weight: 600;
        }
        
        .team-ferrari { border-left: 2px solid #ff2800; }
        .team-mercedes { border-left: 2px solid #00d2be; }
        .team-red-bull { border-left: 2px solid #3671C6; }
        .team-mclaren { border-left: 2px solid #ff8000; }
        .team-aston-martin { border-left: 2px solid #006f62; }
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
                    <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=<?php echo $user['username']; ?>" alt="Avatar" class="w-full h-full"> 
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="pt-24 pb-12 px-4 md:px-8 max-w-7xl mx-auto">
        
        <!-- Compact Header -->
        <div class="mb-6 flex justify-between items-center bg-slate-900/50 p-4 rounded-xl border border-white/5 backdrop-blur-md sticky top-20 z-40 shadow-xl">
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
                 <button class="g-btn g-btn-blue px-6 py-2 text-sm shadow-lg hover:shadow-blue-500/20" onclick="savePredictions()">
                    SAVE <i class="fas fa-check ml-1"></i>
                </button>
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
                        
                        foreach ($orderedDrivers as $idx => $driver): 
                            $position = $predictions[$driver['id']] ?? ($idx + 1);
                            $teamSlug = strtolower(str_replace(' ', '-', $driver['team']));
                        ?>
                        <div class="prediction-item group team-<?php echo $teamSlug; ?>" 
                             data-driver-id="<?php echo $driver['id']; ?>" 
                             data-team="<?php echo htmlspecialchars($driver['team']); ?>" 
                             data-driver-name="<?php echo htmlspecialchars($driver['driver_name']); ?>">
                            
                            <!-- Grip Handle -->
                            <div class="text-gray-600 group-hover:text-gray-400 cursor-grab px-2">
                                <i class="fas fa-grip-lines"></i>
                            </div>
                            
                            <!-- Position (Fixed) -->
                            <div class="position-num"><?php echo $position; ?></div>
                            
                            <!-- Driver Info -->
                            <div class="flex-1 flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <span class="driver-name-text text-white"><?php echo htmlspecialchars($driver['driver_name']); ?></span>
                                    <span class="text-[10px] text-gray-500 uppercase tracking-wider"><?php echo htmlspecialchars($driver['team']); ?></span>
                                </div>
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
            </div>

        </div>

    </main>
    
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
                    alert('✓ Saved!');
                    window.location.href = 'dashboard.php';
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
