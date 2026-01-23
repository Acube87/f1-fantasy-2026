<?php
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();
$user = getCurrentUser();

$raceId = $_GET['race_id'] ?? null;
$db = getDB();

// Get race info
if ($raceId) {
    $stmt = $db->prepare("SELECT * FROM races WHERE id = ?");
    $stmt->bind_param("i", $raceId);
    $stmt->execute();
    $race = $stmt->get_result()->fetch_assoc();
}

if (!$raceId || !$race) {
    header('Location: index.php');
    exit;
}

// Check if race is still open for predictions
if ($race['status'] !== 'upcoming') {
    $error = 'This race is no longer accepting predictions';
}

// Get existing predictions
$existingPredictions = getUserPredictions($user['id'], $raceId);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $race['status'] === 'upcoming') {
    $driverPredictions = $_POST['driver_predictions'] ?? [];
    $constructorPredictions = $_POST['constructor_predictions'] ?? [];
    
    // Validate predictions
    $driverPositions = array_column($driverPredictions, 'position');
    $constructorPositions = array_column($constructorPredictions, 'position');
    
    if (count($driverPositions) !== count(array_unique($driverPositions))) {
        $error = 'Driver positions must be unique';
    } elseif (count($constructorPositions) !== count(array_unique($constructorPositions))) {
        $error = 'Constructor positions must be unique';
    } else {
        // Delete existing predictions
        $deleteStmt = $db->prepare("DELETE FROM predictions WHERE user_id = ? AND race_id = ?");
        $deleteStmt->bind_param("ii", $user['id'], $raceId);
        $deleteStmt->execute();
        
        $deleteConstStmt = $db->prepare("DELETE FROM constructor_predictions WHERE user_id = ? AND race_id = ?");
        $deleteConstStmt->bind_param("ii", $user['id'], $raceId);
        $deleteConstStmt->execute();
        
        // Insert driver predictions
        $insertStmt = $db->prepare("INSERT INTO predictions (user_id, race_id, driver_id, driver_name, predicted_position) VALUES (?, ?, ?, ?, ?)");
        foreach ($driverPredictions as $pred) {
            $insertStmt->bind_param("iissi", $user['id'], $raceId, $pred['driver_id'], $pred['driver_name'], $pred['position']);
            $insertStmt->execute();
        }
        
        // Insert constructor predictions
        $insertConstStmt = $db->prepare("INSERT INTO constructor_predictions (user_id, race_id, constructor_id, constructor_name, predicted_position) VALUES (?, ?, ?, ?, ?)");
        foreach ($constructorPredictions as $pred) {
            $insertConstStmt->bind_param("iissi", $user['id'], $raceId, $pred['constructor_id'], $pred['constructor_name'], $pred['position']);
            $insertConstStmt->execute();
        }
        
        $success = 'Predictions saved successfully!';
        $existingPredictions = getUserPredictions($user['id'], $raceId);
    }
}

// Get list of drivers and constructors (this would normally come from F1 API)
// For now, using placeholder data - you'll need to populate this from the API
$drivers = [
    ['id' => 'hamilton', 'name' => 'Lewis Hamilton'],
    ['id' => 'verstappen', 'name' => 'Max Verstappen'],
    ['id' => 'leclerc', 'name' => 'Charles Leclerc'],
    // Add more drivers from F1 API
];

$constructors = [
    ['id' => 'mercedes', 'name' => 'Mercedes'],
    ['id' => 'red_bull', 'name' => 'Red Bull Racing'],
    ['id' => 'ferrari', 'name' => 'Ferrari'],
    // Add more constructors from F1 API
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Make Predictions - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header>
        <nav>
            <div class="container">
                <h1>🏎️ <?php echo SITE_NAME; ?></h1>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="leaderboard.php">Leaderboard</a></li>
                    <li><a href="predict.php">Make Predictions</a></li>
                    <li><a href="logout.php">Logout (<?php echo htmlspecialchars($user['username']); ?>)</a></li>
                </ul>
            </div>
        </nav>
    </header>

    <main class="container">
        <h2>Make Predictions: <?php echo htmlspecialchars($race['race_name']); ?></h2>
        <p class="race-info"><?php echo htmlspecialchars($race['circuit_name']); ?> - <?php echo date('F j, Y', strtotime($race['race_date'])); ?></p>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <?php if ($race['status'] !== 'upcoming'): ?>
            <div class="alert alert-warning">This race is no longer accepting predictions.</div>
        <?php else: ?>
            <form method="POST" action="predict.php?race_id=<?php echo $raceId; ?>" id="prediction-form">
                <section class="prediction-section">
                    <h3>Driver Predictions - Full Grid</h3>
                    <p class="help-text">Predict the finishing positions for ALL drivers (positions 1-20 must be unique). Get 10 points for exact match, 1 point if off by 1 position. Triple points (30) bonus if you correctly predict top 3 in order!</p>
                    
                    <div class="predictions-grid">
                        <?php foreach ($drivers as $driver): 
                            $existingPred = array_filter($existingPredictions, function($p) use ($driver) {
                                return $p['driver_id'] === $driver['id'];
                            });
                            $existingPos = !empty($existingPred) ? reset($existingPred)['predicted_position'] : '';
                        ?>
                            <div class="prediction-item">
                                <label><?php echo htmlspecialchars($driver['name']); ?></label>
                                <input type="hidden" name="driver_predictions[][driver_id]" value="<?php echo htmlspecialchars($driver['id']); ?>">
                                <input type="hidden" name="driver_predictions[][driver_name]" value="<?php echo htmlspecialchars($driver['name']); ?>">
                                <input type="number" name="driver_predictions[][position]" 
                                       value="<?php echo htmlspecialchars($existingPos); ?>" 
                                       min="1" max="20" placeholder="Position" required>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
                
                <section class="prediction-section">
                    <h3>Constructor Predictions</h3>
                    <p class="help-text">Predict the finishing positions for constructors (positions must be unique). Get 10 points for exact match. Triple points (30) bonus if you correctly predict top 3 in order!</p>
                    
                    <div class="predictions-grid">
                        <?php 
                        $existingConstPreds = [];
                        if (!empty($existingPredictions)) {
                            $constStmt = $db->prepare("SELECT * FROM constructor_predictions WHERE user_id = ? AND race_id = ?");
                            $constStmt->bind_param("ii", $user['id'], $raceId);
                            $constStmt->execute();
                            $existingConstPreds = $constStmt->get_result()->fetch_all(MYSQLI_ASSOC);
                        }
                        
                        foreach ($constructors as $constructor): 
                            $existingPred = array_filter($existingConstPreds, function($p) use ($constructor) {
                                return $p['constructor_id'] === $constructor['id'];
                            });
                            $existingPos = !empty($existingPred) ? reset($existingPred)['predicted_position'] : '';
                        ?>
                            <div class="prediction-item">
                                <label><?php echo htmlspecialchars($constructor['name']); ?></label>
                                <input type="hidden" name="constructor_predictions[][constructor_id]" value="<?php echo htmlspecialchars($constructor['id']); ?>">
                                <input type="hidden" name="constructor_predictions[][constructor_name]" value="<?php echo htmlspecialchars($constructor['name']); ?>">
                                <input type="number" name="constructor_predictions[][position]" 
                                       value="<?php echo htmlspecialchars($existingPos); ?>" 
                                       min="1" max="10" placeholder="Position" required>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
                
                <button type="submit" class="btn btn-primary btn-large">Save Predictions</button>
            </form>
        <?php endif; ?>
    </main>

    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</p>
        </div>
    </footer>
    
    <script src="js/main.js"></script>
</body>
</html>

