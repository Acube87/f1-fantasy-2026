<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$user = getCurrentUser();
if (!$user) {
    header('Location: login.php');
    exit;
}

$db = getDB();
$race_id = $_GET['race_id'] ?? null;

if (!$race_id) {
    header('Location: index.php');
    exit;
}

// Get race info
$raceStmt = $db->prepare("SELECT * FROM races WHERE id = ?");
$raceStmt->bind_param("i", $race_id);
$raceStmt->execute();
$race = $raceStmt->get_result()->fetch_assoc();

if (!$race) {
    die("Race not found");
}

// Get all drivers
$driversStmt = $db->prepare("SELECT id, driver_name, team FROM drivers ORDER BY driver_name");
$driversStmt->execute();
$drivers = $driversStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get existing predictions for this race
$predStmt = $db->prepare("SELECT driver_id, predicted_position FROM race_predictions WHERE user_id = ? AND race_id = ? ORDER BY predicted_position");
$predStmt->bind_param("ii", $user['id'], $race_id);
$predStmt->execute();
$predictions = [];
while ($pred = $predStmt->get_result()->fetch_assoc()) {
    $predictions[$pred['driver_id']] = $pred['predicted_position'];
}

// Get constructors
$constructorsStmt = $db->prepare("SELECT DISTINCT team FROM drivers ORDER BY team");
$constructorsStmt->execute();
$constructors = $constructorsStmt->get_result()->fetch_all(MYSQLI_ASSOC);

$pointsSystem = [
    'exact' => 10,
    'offByOne' => 1,
    'top3Exact' => 30,
    'top3PodiumBonus' => 3
];

// Handle save
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $predictions_json = $_POST['predictions'] ?? '[]';
    $predictions_array = json_decode($predictions_json, true);
    
    // Clear existing predictions
    $delStmt = $db->prepare("DELETE FROM race_predictions WHERE user_id = ? AND race_id = ?");
    $delStmt->bind_param("ii", $user['id'], $race_id);
    $delStmt->execute();
    
    // Insert new predictions
    $insStmt = $db->prepare("INSERT INTO race_predictions (user_id, race_id, driver_id, predicted_position) VALUES (?, ?, ?, ?)");
    foreach ($predictions_array as $position => $driver_id) {
        $pos = $position + 1;
        $insStmt->bind_param("iiii", $user['id'], $race_id, $driver_id, $pos);
        $insStmt->execute();
    }
    
    echo json_encode(['success' => true]);
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Predict - F1 Fantasy</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        * { box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; }
        h1 { color: #333; margin: 0 0 5px 0; }
        .race-info { color: #666; font-size: 14px; margin: 0 0 20px 0; }
        .content { display: grid; grid-template-columns: 1fr 320px; gap: 20px; }
        
        .prediction-section {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .prediction-list {
            list-style: none;
            padding: 0;
            margin: 0;
            border: 2px solid #e5e5e5;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .prediction-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            border-bottom: 1px solid #e5e5e5;
            background: white;
            cursor: grab;
            transition: background 0.2s, box-shadow 0.2s;
            user-select: none;
        }
        
        .prediction-item:last-child { border-bottom: none; }
        .prediction-item:hover { background: #f9f9f9; }
        .prediction-item.dragging { opacity: 0.5; background: #f0f0f0; }
        .prediction-item.drag-over { background: #e8f4f8; border-top: 2px solid #0066cc; }
        
        .drag-handle {
            display: flex;
            align-items: center;
            gap: 4px;
            color: #ccc;
            font-size: 20px;
            cursor: grab;
            flex-shrink: 0;
        }
        
        .drag-handle:active { cursor: grabbing; }
        
        .position-num {
            min-width: 32px;
            font-weight: bold;
            color: #666;
            text-align: center;
            font-size: 14px;
            background: #f0f0f0;
            padding: 4px 8px;
            border-radius: 4px;
            flex-shrink: 0;
        }
        
        .driver-info { flex: 1; min-width: 0; }
        .driver-name { font-weight: 600; color: #333; font-size: 14px; }
        .driver-team { font-size: 12px; color: #999; margin-top: 2px; }
        
        .team-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            white-space: nowrap;
            flex-shrink: 0;
        }
        
        .team-alpine { background: #0082FA; color: white; }
        .team-aston { background: #006F62; color: white; }
        .team-audi { background: #00A19A; color: white; }
        .team-cadillac { background: #FD1E1E; color: white; }
        .team-ferrari { background: #DC0000; color: white; }
        .team-haas { background: #FFFFFF; color: #000; border: 1px solid #ccc; }
        .team-mclaren { background: #FF8700; color: white; }
        .team-mercedes { background: #00D2BE; color: black; }
        .team-red-bull { background: #0600EF; color: white; }
        .team-racing-bulls { background: #2D826D; color: white; }
        .team-williams { background: #005AFF; color: white; }
        
        .sidebar {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            height: fit-content;
            position: sticky;
            top: 20px;
        }
        
        .sidebar h3 { margin-top: 0; margin-bottom: 16px; color: #333; font-size: 16px; }
        
        .constructor-item { 
            background: #f9f9f9; 
            padding: 12px; 
            margin: 8px 0; 
            border-radius: 6px; 
            border-left: 3px solid #e8003e; 
            font-size: 13px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .constructor-name { font-weight: 600; color: #333; }
        .constructor-points { 
            color: #e8003e; 
            font-weight: bold;
            font-size: 18px;
        }
        
        .scoring-info {
            background: #e8f5e9;
            padding: 12px;
            border-radius: 6px;
            margin-top: 20px;
            font-size: 12px;
            border-left: 3px solid #4caf50;
        }
        
        .scoring-info h4 { margin: 0 0 8px 0; font-size: 13px; }
        .scoring-info p { margin: 4px 0; line-height: 1.4; }
        
        .buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .btn {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.2s;
        }
        
        .btn-save { background: #4caf50; color: white; }
        .btn-save:hover { background: #45a049; }
        .btn-reset { background: #f44336; color: white; }
        .btn-reset:hover { background: #da190b; }
        
        .message {
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 15px;
            display: none;
        }
        
        .message.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; display: block; }
    </style>
</head>
<body>
<div class="container">
    <h1>🏎️ Predict: <?php echo htmlspecialchars($race['race_name']); ?></h1>
    <p class="race-info">Race Date: <strong><?php echo date('M d, Y', strtotime($race['race_date'])); ?></strong></p>
    
    <div id="message" class="message"></div>
    
    <div class="content">
        <div class="prediction-section">
            <h2>Drag to Reorder Drivers (1-22)</h2>
            <p style="color: #666; font-size: 13px; margin: 0 0 16px 0;">Drag drivers up/down to set your finishing predictions. Points calculated in real-time on the right.</p>
            
            <ul class="prediction-list" id="predictionList">
                <?php 
                $orderedDrivers = $drivers;
                // Sort by predicted position if predictions exist
                usort($orderedDrivers, function($a, $b) use ($predictions) {
                    $posA = $predictions[$a['id']] ?? 999;
                    $posB = $predictions[$b['id']] ?? 999;
                    return $posA - $posB;
                });
                
                foreach ($orderedDrivers as $idx => $driver): 
                    $position = $predictions[$driver['id']] ?? ($idx + 1);
                    $teamClass = strtolower(str_replace([' ', '-'], '-', $driver['team']));
                ?>
                <li class="prediction-item" draggable="true" data-driver-id="<?php echo $driver['id']; ?>" data-team="<?php echo htmlspecialchars($driver['team']); ?>">
                    <div class="drag-handle">⋮⋮</div>
                    <div class="position-num" id="pos-<?php echo $driver['id']; ?>"><?php echo $position; ?></div>
                    <div class="driver-info">
                        <div class="driver-name"><?php echo htmlspecialchars($driver['driver_name']); ?></div>
                        <div class="driver-team"><?php echo htmlspecialchars($driver['team']); ?></div>
                    </div>
                    <div class="team-badge team-<?php echo $teamClass; ?>"><?php echo htmlspecialchars($driver['team']); ?></div>
                </li>
                <?php endforeach; ?>
            </ul>
            
            <div class="buttons">
                <button class="btn btn-save" onclick="savePredictions()">✓ Save Predictions</button>
                <button class="btn btn-reset" onclick="resetPredictions()">↻ Reset</button>
            </div>
        </div>
        
        <div class="sidebar">
            <h3>📊 Constructor Points</h3>
            <div id="constructorPoints">
                <?php foreach ($constructors as $const): ?>
                    <div class="constructor-item" data-constructor="<?php echo htmlspecialchars($const['team']); ?>">
                        <div class="constructor-name"><?php echo htmlspecialchars($const['team']); ?></div>
                        <div class="constructor-points"><span class="points-value">0</span></div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="scoring-info">
                <h4>🎯 Scoring System</h4>
                <p>+<?php echo $pointsSystem['exact']; ?> pts - Exact position</p>
                <p>+<?php echo $pointsSystem['top3PodiumBonus']; ?> pts - Top 3 Podium</p>
                <p style="margin-top: 8px; color: #666;">Constructors = Top driver from team</p>
            </div>
        </div>
    </div>
</div>

<script>
const driversData = <?php echo json_encode(array_map(fn($d) => ['id' => $d['id'], 'team' => $d['team']], $drivers)); ?>;
let draggedElement = null;

document.addEventListener('dragstart', function(e) {
    if (e.target.classList.contains('prediction-item')) {
        draggedElement = e.target;
        e.target.classList.add('dragging');
        e.dataTransfer.effectAllowed = 'move';
    }
});

document.addEventListener('dragend', function(e) {
    if (e.target.classList.contains('prediction-item')) {
        e.target.classList.remove('dragging');
        draggedElement = null;
    }
});

document.addEventListener('dragover', function(e) {
    if (draggedElement && e.target.classList.contains('prediction-item')) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
        e.target.classList.add('drag-over');
    }
});

document.addEventListener('dragleave', function(e) {
    if (e.target.classList.contains('prediction-item')) {
        e.target.classList.remove('drag-over');
    }
});

document.addEventListener('drop', function(e) {
    e.preventDefault();
    
    if (draggedElement && e.target.classList.contains('prediction-item')) {
        e.target.classList.remove('drag-over');
        
        // Reorder items
        const list = document.getElementById('predictionList');
        const allItems = [...list.querySelectorAll('.prediction-item')];
        const draggedIndex = allItems.indexOf(draggedElement);
        const targetIndex = allItems.indexOf(e.target);
        
        if (draggedIndex < targetIndex) {
            e.target.parentNode.insertBefore(draggedElement, e.target.nextSibling);
        } else {
            e.target.parentNode.insertBefore(draggedElement, e.target);
        }
        
        updatePositionNumbers();
        updateConstructorPoints();
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

function updateConstructorPoints() {
    const list = document.getElementById('predictionList');
    const items = list.querySelectorAll('.prediction-item');
    const teamTopPositions = {};
    
    items.forEach((item, index) => {
        const position = index + 1;
        const team = item.getAttribute('data-team');
        
        if (!teamTopPositions[team] || position < teamTopPositions[team]) {
            teamTopPositions[team] = position;
        }
    });
    
    document.querySelectorAll('.constructor-item').forEach(item => {
        const team = item.getAttribute('data-constructor');
        const position = teamTopPositions[team];
        let points = 0;
        
        if (position) {
            points = <?php echo $pointsSystem['exact']; ?>;
            if (position <= 3) points += <?php echo $pointsSystem['top3PodiumBonus']; ?>;
        }
        
        item.querySelector('.points-value').textContent = points;
    });
}

function savePredictions() {
    const list = document.getElementById('predictionList');
    const items = list.querySelectorAll('.prediction-item');
    const predictions = [];
    
    items.forEach((item, index) => {
        const position = index + 1;
        const driverId = item.getAttribute('data-driver-id');
        predictions.push({
            driver_id: parseInt(driverId),
            predicted_position: position
        });
    });
    
    fetch('predict.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            race_id: <?php echo $raceId; ?>,
            predictions: predictions,
            action: 'save_predictions'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const msg = document.getElementById('message');
            msg.textContent = '✓ Predictions saved successfully!';
            msg.classList.add('success');
            setTimeout(() => { msg.classList.remove('success'); }, 3000);
        } else {
            alert('✗ Error saving predictions: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('✗ Error saving predictions');
    });
}

function resetPredictions() {
    if (confirm('Reset all predictions? This cannot be undone.')) {
        location.reload();
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    updateConstructorPoints();
});
</script>
</body>
</html>

