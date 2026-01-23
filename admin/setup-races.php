<?php
/**
 * Admin script to populate races for the 2026 season
 * This should be run once to set up the race calendar
 * You can also manually add races through phpMyAdmin or create an admin interface
 */

require_once __DIR__ . '/../config.php';

// 2026 F1 Race Calendar (example - update with actual dates)
$races = [
    ['race_name' => 'Bahrain Grand Prix', 'circuit_name' => 'Bahrain International Circuit', 'country' => 'Bahrain', 'race_date' => '2026-03-01', 'race_number' => 1],
    ['race_name' => 'Saudi Arabian Grand Prix', 'circuit_name' => 'Jeddah Corniche Circuit', 'country' => 'Saudi Arabia', 'race_date' => '2026-03-08', 'race_number' => 2],
    ['race_name' => 'Australian Grand Prix', 'circuit_name' => 'Albert Park Circuit', 'country' => 'Australia', 'race_date' => '2026-03-22', 'race_number' => 3],
    // Add more races as they are announced
    // You can fetch this from the F1 API or manually add them
];

$db = getDB();

echo "<h2>Setting up 2026 F1 Race Calendar</h2>";

foreach ($races as $race) {
    // Check if race already exists
    $checkStmt = $db->prepare("SELECT id FROM races WHERE race_number = ?");
    $checkStmt->bind_param("i", $race['race_number']);
    $checkStmt->execute();
    
    if ($checkStmt->get_result()->num_rows > 0) {
        echo "<p>Race #{$race['race_number']} ({$race['race_name']}) already exists. Skipping.</p>";
        continue;
    }
    
    // Insert race
    $stmt = $db->prepare("INSERT INTO races (race_name, circuit_name, country, race_date, race_number, status) VALUES (?, ?, ?, ?, ?, 'upcoming')");
    $stmt->bind_param("ssssi", $race['race_name'], $race['circuit_name'], $race['country'], $race['race_date'], $race['race_number']);
    
    if ($stmt->execute()) {
        echo "<p>✓ Added: {$race['race_name']} ({$race['race_date']})</p>";
    } else {
        echo "<p>✗ Error adding: {$race['race_name']} - " . $db->error . "</p>";
    }
}

echo "<p><strong>Done!</strong> You can now access the prediction system.</p>";
echo "<p><a href='../index.php'>Go to Homepage</a></p>";
?>

