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
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1400px; margin: 0 auto; }
        h1 { color: #333; margin: 0 0 5px 0; }
        .race-info { color: #666; font-size: 14px; margin: 0 0 20px 0; }
        .content { display: grid; grid-template-columns: 1fr 350px; gap: 20px; }
        
        .prediction-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .drivers-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: 10px;
            margin-bottom: 20px;
            padding: 15px;
            background: #fafafa;
            border-radius: 6px;
            border: 2px dashed #ddd;
        }
        
        .driver-card {
            background: white;
            border: 2px solid #ddd;
            padding: 12px;
            border-radius: 6px;
            cursor: move;
            transition: all 0.3s;
            text-align: center;
            font-size: 13px;
            user-select: none;
        }
        
        .driver-card:hover {
            background: #fff;
            border-color: #e8003e;
            box-shadow: 0 4px 8px rgba(232, 0, 62, 0.2);
        }
        
        .driver-card.dragging { opacity: 0.5; }
        
        .position-list {
            background: #f0f0f0;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            max-height: 600px;
            overflow-y: auto;
            border: 2px dashed #ccc;
            min-height: 400px;
        }
        
        .position-item {
            background: white;
            padding: 10px;
            margin: 5px 0;
            border-radius: 4px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-left: 4px solid #e8003e;
            cursor: grab;
        }
        
        .position-item.top3 { border-left-color: #FFD700; background: #fffbf0; }
        .position-item.top10 { border-left-color: #90EE90; }
        
        .position-number {
            font-weight: bold;
            min-width: 30px;
            text-align: center;
            color: #666;
        }
        
        .driver-name { flex: 1; margin: 0 10px; font-size: 14px; font-weight: bold; }
        .driver-team { font-size: 11px; color: #999; background: #f0f0f0; padding: 2px 6px; border-radius: 3px; }
        
        .sidebar {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            height: fit-content;
            position: sticky;
            top: 20px;
        }
        
        .sidebar h3 { margin-top: 0; }
        .constructor-item { background: #f9f9f9; padding: 10px; margin: 8px 0; border-radius: 4px; border-left: 3px solid #e8003e; font-size: 13px; }
        .constructor-name { font-weight: bold; }
        .constructor-points { color: #e8003e; font-weight: bold; }
        
        .scoring-info {
            background: #e8f5e9;
            padding: 12px;
            border-radius: 4px;
            margin-top: 20px;
            font-size: 12px;
            border-left: 3px solid #4caf50;
        }
        
        .scoring-info h4 { margin: 8px 0; }
        .scoring-info p { margin: 4px 0; }
        
        .buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .btn {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            font-size: 14px;
        }
        
        .btn-save { background: #4caf50; color: white; }
        .btn-save:hover { background: #45a049; }
        .btn-reset { background: #f44336; color: white; }
        .btn-reset:hover { background: #da190b; }
        
        .message {
            padding: 12px;
            border-radius: 4px;
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
            <h2>Drag Drivers to Predict Finishing Order (1-20)</h2>
            
            <h3>Available Drivers</h3>
            <div class="drivers-grid" id="availableDrivers">
                <?php foreach ($drivers as $driver): 
                    $predicted = isset($predictions[$driver['id']]);
                ?>
                <div class="driver-card" draggable="true" data-driver-id="<?php echo $driver['id']; ?>" data-driver-name="<?php echo htmlspecialchars($driver['driver_name']); ?>" data-team="<?php echo htmlspecialchars($driver['team']); ?>" <?php echo $predicted ? 'style="display:none;"' : ''; ?>>
                    <div><?php echo htmlspecialchars($driver['driver_name']); ?></div>
                    <div style="font-size: 11px; color: #666;"><?php echo htmlspecialchars($driver['team']); ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <h3>Your Predictions (Drag drivers here)</h3>
            <div class="position-list" id="positionList">
                <?php for ($i = 1; $i <= 20; $i++): ?>
                    <div class="position-item" data-position="<?php echo $i; ?>">
                        <span class="position-number"><?php echo $i; ?></span>
                        <span class="driver-info" style="flex: 1; color: #999;">Drop driver here</span>
                    </div>
                <?php endfor; ?>
            </div>
            
            <div class="buttons">
                <button class="btn btn-save" onclick="savePredictions()">✓ Save Predictions</button>
                <button class="btn btn-reset" onclick="resetPredictions()">↻ Reset</button>
            </div>
        </div>
        
        <div class="sidebar">
            <h3>📊 Predicted Points</h3>
            <div id="constructorPoints">
                <?php foreach ($constructors as $const): ?>
                    <div class="constructor-item" data-constructor="<?php echo htmlspecialchars($const['team']); ?>">
                        <div class="constructor-name"><?php echo htmlspecialchars($const['team']); ?></div>
                        <div class="constructor-points">Points: <span class="points-value">0</span></div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="scoring-info">
                <h4>🎯 Scoring System</h4>
                <p><strong>Drivers & Constructors:</strong></p>
                <p>+<?php echo $pointsSystem['exact']; ?> pts - Exact position</p>
                <p>+<?php echo $pointsSystem['top3PodiumBonus']; ?> pts - Top 3 Podium</p>
                <p style="margin-top: 10px; font-size: 11px; color: #666;">Constructors points = Top driver from team</p>
            </div>
        </div>
    </div>
</div>

<script>
const drivers_data = <?php echo json_encode(array_map(fn($d) => ['id' => $d['id'], 'team' => $d['team']], $drivers)); ?>;
let draggedElement = null;

document.querySelectorAll('.driver-card').forEach(card => {
    card.addEventListener('dragstart', e => {
        draggedElement = card;
        card.classList.add('dragging');
        e.dataTransfer.effectAllowed = 'move';
    });
    card.addEventListener('dragend', e => card.classList.remove('dragging'));
});

document.querySelectorAll('.position-item').forEach(pos => {
    pos.addEventListener('dragover', e => {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
        pos.style.background = '#fff5e6';
    });
    pos.addEventListener('dragleave', e => pos.style.background = '');
    pos.addEventListener('drop', e => {
        e.preventDefault();
        pos.style.background = '';
        if (!draggedElement) return;
        
        const driverId = draggedElement.dataset.driverId;
        const driverName = draggedElement.dataset.driverName;
        const team = draggedElement.dataset.team;
        const position = pos.dataset.position;
        
        document.querySelectorAll('.position-item').forEach(item => {
            if (item.dataset.position !== position && item.querySelector('[data-driver-id="' + driverId + '"]')) {
                item.querySelector('.driver-info').innerHTML = 'Drop driver here';
                item.querySelector('.driver-info').removeAttribute('data-driver-id');
            }
        });
        
        let html = `<strong>${driverName}</strong><br><span style="font-size: 11px; color: #666;">${team}</span>`;
        pos.querySelector('.driver-info').innerHTML = html;
        pos.querySelector('.driver-info').setAttribute('data-driver-id', driverId);
        
        pos.classList.remove('top3', 'top10');
        if (position <= 3) pos.classList.add('top3');
        else if (position <= 10) pos.classList.add('top10');
        
        draggedElement.style.display = 'none';
        updateConstructorPoints();
        draggedElement = null;
    });
});

function updateConstructorPoints() {
    const predictions = {};
    document.querySelectorAll('.position-item').forEach(item => {
        const driverId = item.querySelector('.driver-info').getAttribute('data-driver-id');
        if (driverId) {
            const team = drivers_data.find(d => d.id == driverId).team;
            predictions[team] = item.dataset.position;
        }
    });
    
    document.querySelectorAll('.constructor-item').forEach(item => {
        const team = item.dataset.constructor;
        const position = predictions[team];
        let points = 0;
        if (position) {
            points = <?php echo $pointsSystem['exact']; ?>;
            if (position <= 3) points += <?php echo $pointsSystem['top3PodiumBonus']; ?>;
        }
        item.querySelector('.points-value').textContent = points;
    });
}

function savePredictions() {
    const predictions = [];
    document.querySelectorAll('.position-item').forEach(item => {
        const driverId = item.querySelector('.driver-info').getAttribute('data-driver-id');
        if (driverId) predictions.push(driverId);
    });
    
    if (predictions.length === 0) {
        alert('Please make at least one prediction!');
        return;
    }
    
    const formData = new FormData();
    formData.append('predictions', JSON.stringify(predictions));
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    }).then(r => r.json()).then(data => {
        if (data.success) {
            document.getElementById('message').textContent = '✓ Predictions saved successfully!';
            document.getElementById('message').classList.add('success');
            setTimeout(() => location.href = 'index.php', 2000);
        }
    });
}

function resetPredictions() {
    if (confirm('Reset all predictions?')) {
        document.querySelectorAll('.position-item').forEach(item => {
            item.querySelector('.driver-info').innerHTML = 'Drop driver here';
            item.querySelector('.driver-info').removeAttribute('data-driver-id');
            item.classList.remove('top3', 'top10');
        });
        document.querySelectorAll('.driver-card').forEach(card => card.style.display = 'block');
        updateConstructorPoints();
    }
}

updateConstructorPoints();
</script>
</body>
</html>

