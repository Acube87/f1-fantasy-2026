<?php
/**
 * Initial Database Setup Script
 * Creates ALL necessary tables for the F1 Fantasy application
 * Verified against full codebase usage.
 */

require_once __DIR__ . '/../config.php';

echo "<h2>Initializing F1 Fantasy Database (Comprehensive Setup)...</h2>";

$db = getDB();

// 1. Users Table
// Referenced in: auth.php, dashboard.php
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100),
    avatar_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($db->query($sql) === TRUE) echo "<p>✅ Table 'users' ready.</p>";
else echo "<p style='color:red'>❌ Error 'users': " . $db->error . "</p>";

// 2. Races Table
// Referenced in: setup-races.php, predict.php, functions.php
// Added missing columns: f1_race_id, results_fetched, results_fetched_at
$sql = "CREATE TABLE IF NOT EXISTS races (
    id INT AUTO_INCREMENT PRIMARY KEY,
    race_name VARCHAR(100) NOT NULL,
    circuit_name VARCHAR(100),
    country VARCHAR(100),
    race_date DATE NOT NULL,
    race_number INT NOT NULL,
    f1_race_id VARCHAR(50),
    status ENUM('upcoming', 'completed', 'cancelled') DEFAULT 'upcoming',
    results_fetched BOOLEAN DEFAULT FALSE,
    results_fetched_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($db->query($sql) === TRUE) echo "<p>✅ Table 'races' ready.</p>";
else echo "<p style='color:red'>❌ Error 'races': " . $db->error . "</p>";

// 3. Drivers Table
// Referenced in: add-drivers.php, predict.php
$sql = "CREATE TABLE IF NOT EXISTS drivers (
    id VARCHAR(50) PRIMARY KEY,
    driver_name VARCHAR(100) NOT NULL,
    team VARCHAR(100),
    image_url VARCHAR(255)
)";

if ($db->query($sql) === TRUE) echo "<p>✅ Table 'drivers' ready.</p>";
else echo "<p style='color:red'>❌ Error 'drivers': " . $db->error . "</p>";

// 4. Constructors Table
// Referenced in: add-drivers.php, predict.php
$sql = "CREATE TABLE IF NOT EXISTS constructors (
    id VARCHAR(50) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    color VARCHAR(20)
)";

if ($db->query($sql) === TRUE) echo "<p>✅ Table 'constructors' ready.</p>";
else echo "<p style='color:red'>❌ Error 'constructors': " . $db->error . "</p>";

// 5. Predictions Table (Driver Predictions)
// Referenced in: predict.php
$sql = "CREATE TABLE IF NOT EXISTS predictions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    race_id INT NOT NULL,
    driver_id VARCHAR(50) NOT NULL,
    driver_name VARCHAR(100),
    predicted_position INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (race_id) REFERENCES races(id) ON DELETE CASCADE,
    UNIQUE KEY unique_prediction (user_id, race_id, driver_id)
)";

if ($db->query($sql) === TRUE) echo "<p>✅ Table 'predictions' ready.</p>";
else echo "<p style='color:red'>❌ Error 'predictions': " . $db->error . "</p>";

// 6. Constructor Predictions Table
// Referenced in: functions.php
$sql = "CREATE TABLE IF NOT EXISTS constructor_predictions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    race_id INT NOT NULL,
    constructor_id VARCHAR(50) NOT NULL,
    constructor_name VARCHAR(100),
    predicted_position INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (race_id) REFERENCES races(id) ON DELETE CASCADE,
    UNIQUE KEY unique_const_prediction (user_id, race_id, constructor_id)
)";

if ($db->query($sql) === TRUE) echo "<p>✅ Table 'constructor_predictions' ready.</p>";
else echo "<p style='color:red'>❌ Error 'constructor_predictions': " . $db->error . "</p>";

// 7. Race Results Table
// Referenced in: functions.php
// Added missing columns: constructor_id, constructor_name, fastest_lap, status
$sql = "CREATE TABLE IF NOT EXISTS race_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    race_id INT NOT NULL,
    driver_id VARCHAR(50) NOT NULL,
    driver_name VARCHAR(100),
    constructor_id VARCHAR(50),
    constructor_name VARCHAR(100),
    position INT NOT NULL,
    points FLOAT DEFAULT 0,
    fastest_lap BOOLEAN DEFAULT FALSE,
    status VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (race_id) REFERENCES races(id) ON DELETE CASCADE,
    FOREIGN KEY (driver_id) REFERENCES drivers(id) ON DELETE CASCADE
)";

if ($db->query($sql) === TRUE) echo "<p>✅ Table 'race_results' ready.</p>";
else echo "<p style='color:red'>❌ Error 'race_results': " . $db->error . "</p>";

// 8. User Totals Table
// Referenced in: dashboard.php, functions.php
$sql = "CREATE TABLE IF NOT EXISTS user_totals (
    user_id INT PRIMARY KEY,
    total_points INT DEFAULT 0,
    races_participated INT DEFAULT 0,
    average_points FLOAT DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";

if ($db->query($sql) === TRUE) {
    echo "<p>✅ Table 'user_totals' ready.</p>";
    // Auto-populate for existing users
    $db->query("INSERT IGNORE INTO user_totals (user_id) SELECT id FROM users");
} else {
    echo "<p style='color:red'>❌ Error 'user_totals': " . $db->error . "</p>";
}

// 9. Scores Table (Per Race Summary)
// Referenced in: dashboard.php, functions.php
$sql = "CREATE TABLE IF NOT EXISTS scores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    race_id INT NOT NULL,
    driver_points INT DEFAULT 0,
    constructor_points INT DEFAULT 0,
    top3_bonus INT DEFAULT 0,
    constructor_top3_bonus INT DEFAULT 0,
    total_points INT DEFAULT 0,
    calculated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (race_id) REFERENCES races(id) ON DELETE CASCADE,
    UNIQUE KEY unique_score (user_id, race_id)
)";

if ($db->query($sql) === TRUE) echo "<p>✅ Table 'scores' ready.</p>";
else echo "<p style='color:red'>❌ Error 'scores': " . $db->error . "</p>";

// 10. Update table columns if they exist but need changes (Migration helpers)
// Check if races needs f1_race_id
$checkCol = $db->query("SHOW COLUMNS FROM races LIKE 'f1_race_id'");
if ($checkCol && $checkCol->num_rows == 0) {
    $db->query("ALTER TABLE races ADD COLUMN f1_race_id VARCHAR(50)");
    $db->query("ALTER TABLE races ADD COLUMN results_fetched BOOLEAN DEFAULT FALSE");
    $db->query("ALTER TABLE races ADD COLUMN results_fetched_at TIMESTAMP NULL");
    echo "<p>🔄 Altered 'races' table with new columns.</p>";
}

// Check if race_results needs constructor_id
$checkColResult = $db->query("SHOW COLUMNS FROM race_results LIKE 'constructor_id'");
if ($checkColResult && $checkColResult->num_rows == 0) {
    $db->query("ALTER TABLE race_results ADD COLUMN constructor_id VARCHAR(50)");
    $db->query("ALTER TABLE race_results ADD COLUMN constructor_name VARCHAR(100)");
    $db->query("ALTER TABLE race_results ADD COLUMN fastest_lap BOOLEAN DEFAULT FALSE");
    $db->query("ALTER TABLE race_results ADD COLUMN status VARCHAR(50)");
    echo "<p>🔄 Altered 'race_results' table with new columns.</p>";
}

// Check if constructor_predictions needs constructor_name
$checkColCP = $db->query("SHOW COLUMNS FROM constructor_predictions LIKE 'constructor_name'");
if ($checkColCP && $checkColCP->num_rows == 0) {
    $db->query("ALTER TABLE constructor_predictions ADD COLUMN constructor_name VARCHAR(100)");
    echo "<p>🔄 Altered 'constructor_predictions' table with new columns.</p>";
}

echo "<h3>🎉 Database setup fully complete!</h3>";
echo "<p>Next steps:</p>";
echo "<ol>";
echo "<li><a href='add-drivers-manual.php'>Populate Drivers and Teams</a></li>";
echo "<li><a href='setup-races.php'>Setup Race Calendar</a></li>";
echo "<li><a href='../dashboard.php'>Go to Dashboard</a></li>";
echo "</ol>";
?>
