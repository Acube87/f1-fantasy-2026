<?php
/**
 * Initial Database Setup Script
 * Creates all necessary tables for the F1 Fantasy application
 */

require_once __DIR__ . '/../config.php';

echo "<h2>Initializing F1 Fantasy Database...</h2>";

$db = getDB();

// 1. Users Table
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100),
    avatar_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($db->query($sql) === TRUE) {
    echo "<p>✅ Table 'users' created or checks out.</p>";
} else {
    echo "<p style='color:red'>❌ Error creating table 'users': " . $db->error . "</p>";
}

// 2. Races Table
$sql = "CREATE TABLE IF NOT EXISTS races (
    id INT AUTO_INCREMENT PRIMARY KEY,
    race_name VARCHAR(100) NOT NULL,
    circuit_name VARCHAR(100),
    country VARCHAR(100),
    race_date DATE NOT NULL,
    race_number INT NOT NULL,
    status ENUM('upcoming', 'completed', 'cancelled') DEFAULT 'upcoming',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($db->query($sql) === TRUE) {
    echo "<p>✅ Table 'races' created or checks out.</p>";
} else {
    echo "<p style='color:red'>❌ Error creating table 'races': " . $db->error . "</p>";
}

// 3. Drivers Table
$sql = "CREATE TABLE IF NOT EXISTS drivers (
    id VARCHAR(50) PRIMARY KEY,
    driver_name VARCHAR(100) NOT NULL,
    team VARCHAR(100),
    image_url VARCHAR(255)
)";

if ($db->query($sql) === TRUE) {
    echo "<p>✅ Table 'drivers' created or checks out.</p>";
} else {
    echo "<p style='color:red'>❌ Error creating table 'drivers': " . $db->error . "</p>";
}

// 4. Constructors Table
$sql = "CREATE TABLE IF NOT EXISTS constructors (
    id VARCHAR(50) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    color VARCHAR(20)
)";

if ($db->query($sql) === TRUE) {
    echo "<p>✅ Table 'constructors' created or checks out.</p>";
} else {
    echo "<p style='color:red'>❌ Error creating table 'constructors': " . $db->error . "</p>";
}

// 5. Predictions Table
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

if ($db->query($sql) === TRUE) {
    echo "<p>✅ Table 'predictions' created or checks out.</p>";
} else {
    echo "<p style='color:red'>❌ Error creating table 'predictions': " . $db->error . "</p>";
}

// 6. Race Results Table
$sql = "CREATE TABLE IF NOT EXISTS race_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    race_id INT NOT NULL,
    driver_id VARCHAR(50) NOT NULL,
    position INT NOT NULL,
    points INT DEFAULT 0,
    FOREIGN KEY (race_id) REFERENCES races(id) ON DELETE CASCADE,
    FOREIGN KEY (driver_id) REFERENCES drivers(id) ON DELETE CASCADE
)";

if ($db->query($sql) === TRUE) {
    echo "<p>✅ Table 'race_results' created or checks out.</p>";
} else {
    echo "<p style='color:red'>❌ Error creating table 'race_results': " . $db->error . "</p>";
}

// 7. User Totals Table
$sql = "CREATE TABLE IF NOT EXISTS user_totals (
    user_id INT PRIMARY KEY,
    total_points INT DEFAULT 0,
    races_participated INT DEFAULT 0,
    average_points FLOAT DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";

if ($db->query($sql) === TRUE) {
    echo "<p>✅ Table 'user_totals' created or checks out.</p>";
    
    // Populate existing users into user_totals if missing
    $db->query("INSERT IGNORE INTO user_totals (user_id) SELECT id FROM users");
} else {
    echo "<p style='color:red'>❌ Error creating table 'user_totals': " . $db->error . "</p>";
}

echo "<h3>Database setup complete! 🚀</h3>";
echo "<p>Next steps:</p>";
echo "<ol>";
echo "<li><a href='add-drivers-manual.php'>Populate Drivers and Teams</a></li>";
echo "<li><a href='setup-races.php'>Setup Race Calendar</a></li>";
echo "<li><a href='../signup.php'>Go to Sign Up</a></li>";
echo "</ol>";
?>
