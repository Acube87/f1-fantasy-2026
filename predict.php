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
    <style>
        /* Drag and Drop specifics that CSS file might not cover yet */
        .prediction-item {
            cursor: move; /* Fallback */
            cursor: grab;
            transition: transform 0.2s, box-shadow 0.2s, background-color 0.2s;
        }
        .prediction-item:active { cursor: grabbing; }
        .prediction-item.dragging { opacity: 0.5; background: rgba(59, 130, 246, 0.2); transform: scale(0.98); }
        .prediction-item.drag-over { border-top: 2px solid #f97316; }

        .team-badge {
            font-size: 0.7rem;
            padding: 2px 8px;
            border-radius: 12px;
            font-weight: 700;
            text-transform: uppercase;
        }
        /* Simple team colors */
        .team-ferrari { background: rgba(220, 0, 0, 0.2); color: #ff2800; border: 1px solid rgba(220,0,0,0.3); }
        .team-mercedes { background: rgba(0, 210, 190, 0.2); color: #00d2be; border: 1px solid rgba(0,210,190,0.3); }
        .team-red-bull { background: rgba(6, 0, 239, 0.2); color: #3671C6; border: 1px solid rgba(6,0,239,0.3); }
        .team-mclaren { background: rgba(255, 128, 0, 0.2); color: #ff8000; border: 1px solid rgba(255,128,0,0.3); }
        .team-aston-martin { background: rgba(0, 111, 98, 0.2); color: #006f62; border: 1px solid rgba(0,111,98,0.3); }
        /* Defaults for others */
        .team-badge:not([class*="-"]) { background: rgba(255,255,255,0.1); color: #ccc; }
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
        
        <!-- Header -->
        <div class="mb-8 flex flex-col md:flex-row justify-between items-end gap-4">
            <div>
                <span class="text-orange-500 font-bold uppercase tracking-wider text-xs mb-2 block">Make your Prediction</span>
                <h1 class="text-3xl md:text-5xl font-black text-white italic uppercase">
                    <?php echo htmlspecialchars($race['country']); ?>
                </h1>
                <p class="text-gray-400 flex items-center gap-2 mt-2">
                    <i class="fas fa-map-marker-alt text-red-500"></i> <?php echo htmlspecialchars($race['circuit_name']); ?>
                </p>
            </div>
            <div class="flex gap-3">
                 <button class="g-btn g-btn-blue px-6 py-3 flex items-center gap-2 shadow-lg hover:shadow-blue-500/20" onclick="savePredictions()">
                    <i class="fas fa-save"></i> SAVE <span class="hidden sm:inline">PREDICTIONS</span>
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            
            <!-- Main Drag List -->
            <div class="lg:col-span-3">
                <div class="g-card p-6 border-t-4 border-t-blue-500">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="font-bold text-white text-lg flex items-center gap-2">
                            <i class="fas fa-list-ol text-blue-500"></i> Driver Order
                        </h2>
                        
                        <?php if (!empty($previousPredictions)): ?>
                        <button onclick="copyFromPreviousRace()" class="text-xs bg-white/5 hover:bg-white/10 border border-white/10 px-3 py-1.5 rounded transition text-gray-300">
                            <i class="fas fa-copy mr-1"></i> Copy Previous
                        </button>
                        <?php endif; ?>
                    </div>
                    
                     <div class="mb-4">
                        <input type="text" id="searchDrivers" placeholder="🔍  Search driver..." 
                               class="w-full bg-black/20 border border-white/10 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-blue-500 transition">
                    </div>

                    <p class="text-xs text-gray-500 mb-4 bg-blue-500/10 p-2 rounded border border-blue-500/20">
                        <i class="fas fa-info-circle mr-1"></i> Drag and drop to reorder the drivers.
                    </p>

                    <div id="predictionList" class="space-y-2">
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
                            $teamClass = 'team-' . strtolower(str_replace(' ', '-', $driver['team']));
                        ?>
                        <div class="prediction-item g-card p-3 flex items-center gap-4 border-0 bg-white/5 hover:bg-white/10" 
                             draggable="true" 
                             data-driver-id="<?php echo $driver['id']; ?>" 
                             data-team="<?php echo htmlspecialchars($driver['team']); ?>" 
                             data-driver-name="<?php echo htmlspecialchars($driver['driver_name']); ?>">
                            
                            <div class="text-gray-500 cursor-grab hover:text-white px-2"><i class="fas fa-grip-vertical"></i></div>
                            
                            <div class="position-num w-8 h-8 rounded-lg bg-blue-500/10 text-blue-400 font-bold flex items-center justify-center border border-blue-500/20">
                                <?php echo $position; ?>
                            </div>
                            
                            <div class="flex-1">
                                <div class="font-bold text-white"><?php echo htmlspecialchars($driver['driver_name']); ?></div>
                                <div class="text-xs text-gray-500"><?php echo htmlspecialchars($driver['team']); ?></div>
                            </div>
                            
                            <div>
                                <span class="team-badge <?php echo $teamClass; ?>"><?php echo htmlspecialchars($driver['team']); ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Sidebar (Points & Info) -->
            <div class="lg:col-span-1 space-y-6">
                <!-- Live Points -->
                <div class="g-card p-6 border-t-4 border-t-green-500 sticky top-24">
                    <h3 class="font-bold text-white text-md mb-4 flex items-center gap-2">
                        <i class="fas fa-chart-pie text-green-500"></i> Projected Points
                    </h3>
                    <div id="constructorPoints" class="space-y-3 text-sm">
                        <!-- Populated by JS -->
                        <div class="text-gray-500 text-xs text-center py-4">Reordering...</div>
                    </div>
                </div>

                <!-- Rules -->
                <div class="g-card p-4">
                    <h4 class="font-bold text-white text-xs uppercase mb-3 text-gray-400">Rules</h4>
                    <div class="space-y-2 text-xs text-gray-400">
                        <div class="flex justify-between">
                            <span>Ex. Pos.</span>
                            <span class="text-green-400 font-bold">+10 pts</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Top 3 All Correct</span>
                            <span class="text-orange-400 font-bold">+3 pts</span>
                        </div>
                         <?php if (in_array($race['country'], ['China', 'UK', 'Singapore'])): ?>
                        <div class="bg-yellow-500/20 text-yellow-400 p-2 rounded text-center font-bold mt-2 border border-yellow-500/30">
                            DOUBLE POINTS RACE!
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>

    </main>
    
    <footer class="mt-12 border-t border-white/10 py-6 text-center">
        <p class="text-gray-500 text-sm mb-2">&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</p>
        <p class="text-gray-600 text-xs">
            Powered by <a href="https://www.scanerrific.com" target="_blank" class="text-orange-500 hover:text-orange-400 font-semibold transition">Scanerrific</a>
        </p>
    </footer>

    <!-- Scripts -->
    <script>
        // Keep Drag & Drop Logic exactly as it was, just referencing new classes if needed
        // (The logic relies on IDs and classes which I preserved: prediction-item, etc.)
        
        let draggedElement = null;
        let predictionList = document.getElementById('predictionList');

        // Drag Start
        document.addEventListener('dragstart', function(e) {
            if (e.target.closest('.prediction-item')) {
                draggedElement = e.target.closest('.prediction-item');
                draggedElement.classList.add('dragging');
                e.dataTransfer.effectAllowed = 'move';
                // Firefox requires setting data
                e.dataTransfer.setData('text/plain', '');
            }
        });

        // Drag End
        document.addEventListener('dragend', function(e) {
            if (draggedElement) {
                draggedElement.classList.remove('dragging');
                draggedElement = null;
                updatePositionNumbers();
                updateConstructorPoints();
            }
        });

        // Drag Over
        predictionList.addEventListener('dragover', function(e) {
            e.preventDefault(); // Necessary to allow dropping
            const afterElement = getDragAfterElement(predictionList, e.clientY);
            if (draggedElement) {
                if (afterElement == null) {
                    predictionList.appendChild(draggedElement);
                } else {
                    predictionList.insertBefore(draggedElement, afterElement);
                }
            }
        });
        
        // Helper to find position
        function getDragAfterElement(container, y) {
            const draggableElements = [...container.querySelectorAll('.prediction-item:not(.dragging)')];

            return draggableElements.reduce((closest, child) => {
                const box = child.getBoundingClientRect();
                const offset = y - box.top - box.height / 2;
                if (offset < 0 && offset > closest.offset) {
                    return { offset: offset, element: child };
                } else {
                    return closest;
                }
            }, { offset: Number.NEGATIVE_INFINITY }).element;
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
        
        // --- Constructor Logic ---
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
                item.className = 'flex justify-between items-center p-2 bg-white/5 rounded hover:bg-white/10 transition';
                item.innerHTML = `
                    <div class="font-medium text-gray-300 text-xs">${ranking.team}</div>
                    <div class="font-bold text-green-400 text-sm">${ranking.totalPoints} pts</div>
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
                    // Custom toast or alert styled could go here
                    alert('Predictions Locked In! 🏎️💨');
                    window.location.href = 'dashboard.php';
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(err => alert('Network error'));
        }

        // Search Filter
        document.getElementById('searchDrivers').addEventListener('input', (e) => {
            const term = e.target.value.toLowerCase();
            document.querySelectorAll('.prediction-item').forEach(item => {
                const name = item.getAttribute('data-driver-name').toLowerCase();
                if (name.includes(term)) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            });
        });
        
        // Initial init
        document.addEventListener('DOMContentLoaded', updateConstructorPoints);

    </script>

</body>
</html>
