<?php
/**
 * Admin script to populate races for the 2026 season
 * This should be run once to set up the race calendar
 * You can also manually add races through phpMyAdmin or create an admin interface
 */

require_once __DIR__ . '/../config.php';

// 2026 F1 Race Calendar (Official - from formula1.com)
$races = [
    ['race_name' => 'Australian Grand Prix', 'circuit_name' => 'Albert Park Circuit', 'country' => 'Australia', 'race_date' => '2026-03-08', 'race_number' => 1],
    ['race_name' => 'Chinese Grand Prix', 'circuit_name' => 'Shanghai International Circuit', 'country' => 'China', 'race_date' => '2026-03-15', 'race_number' => 2],
    ['race_name' => 'Japanese Grand Prix', 'circuit_name' => 'Suzuka Circuit', 'country' => 'Japan', 'race_date' => '2026-03-29', 'race_number' => 3],
    ['race_name' => 'Bahrain Grand Prix', 'circuit_name' => 'Bahrain International Circuit', 'country' => 'Bahrain', 'race_date' => '2026-04-12', 'race_number' => 4],
    ['race_name' => 'Saudi Arabian Grand Prix', 'circuit_name' => 'Jeddah Corniche Circuit', 'country' => 'Saudi Arabia', 'race_date' => '2026-04-19', 'race_number' => 5],
    ['race_name' => 'Miami Grand Prix', 'circuit_name' => 'Miami International Autodrome', 'country' => 'United States', 'race_date' => '2026-05-03', 'race_number' => 6],
    ['race_name' => 'Canadian Grand Prix', 'circuit_name' => 'Circuit Gilles Villeneuve', 'country' => 'Canada', 'race_date' => '2026-05-24', 'race_number' => 7],
    ['race_name' => 'Monaco Grand Prix', 'circuit_name' => 'Circuit de Monaco', 'country' => 'Monaco', 'race_date' => '2026-06-07', 'race_number' => 8],
    ['race_name' => 'Spanish Grand Prix', 'circuit_name' => 'Circuit de Barcelona-Catalunya', 'country' => 'Spain', 'race_date' => '2026-06-14', 'race_number' => 9],
    ['race_name' => 'Austrian Grand Prix', 'circuit_name' => 'Red Bull Ring', 'country' => 'Austria', 'race_date' => '2026-06-28', 'race_number' => 10],
    ['race_name' => 'British Grand Prix', 'circuit_name' => 'Silverstone Circuit', 'country' => 'United Kingdom', 'race_date' => '2026-07-05', 'race_number' => 11],
    ['race_name' => 'Belgian Grand Prix', 'circuit_name' => 'Circuit de Spa-Francorchamps', 'country' => 'Belgium', 'race_date' => '2026-07-19', 'race_number' => 12],
    ['race_name' => 'Hungarian Grand Prix', 'circuit_name' => 'Hungaroring', 'country' => 'Hungary', 'race_date' => '2026-07-26', 'race_number' => 13],
    ['race_name' => 'Dutch Grand Prix', 'circuit_name' => 'Circuit Zandvoort', 'country' => 'Netherlands', 'race_date' => '2026-08-23', 'race_number' => 14],
    ['race_name' => 'Italian Grand Prix', 'circuit_name' => 'Monza Circuit', 'country' => 'Italy', 'race_date' => '2026-09-06', 'race_number' => 15],
    ['race_name' => 'Spanish Grand Prix', 'circuit_name' => 'Circuit Ricardo Tormo', 'country' => 'Spain', 'race_date' => '2026-09-13', 'race_number' => 16],
    ['race_name' => 'Azerbaijan Grand Prix', 'circuit_name' => 'Baku City Circuit', 'country' => 'Azerbaijan', 'race_date' => '2026-09-26', 'race_number' => 17],
    ['race_name' => 'Singapore Grand Prix', 'circuit_name' => 'Marina Bay Street Circuit', 'country' => 'Singapore', 'race_date' => '2026-10-11', 'race_number' => 18],
    ['race_name' => 'United States Grand Prix', 'circuit_name' => 'Circuit of the Americas', 'country' => 'United States', 'race_date' => '2026-10-25', 'race_number' => 19],
    ['race_name' => 'Mexico City Grand Prix', 'circuit_name' => 'Mexico City Circuit', 'country' => 'Mexico', 'race_date' => '2026-11-01', 'race_number' => 20],
    ['race_name' => 'Brazilian Grand Prix', 'circuit_name' => 'Autódromo José Carlos Pace', 'country' => 'Brazil', 'race_date' => '2026-11-08', 'race_number' => 21],
    ['race_name' => 'Las Vegas Grand Prix', 'circuit_name' => 'Las Vegas Street Circuit', 'country' => 'United States', 'race_date' => '2026-11-22', 'race_number' => 22],
    ['race_name' => 'Qatar Grand Prix', 'circuit_name' => 'Lusail International Circuit', 'country' => 'Qatar', 'race_date' => '2026-11-29', 'race_number' => 23],
    ['race_name' => 'Abu Dhabi Grand Prix', 'circuit_name' => 'Yas Marina Circuit', 'country' => 'United Arab Emirates', 'race_date' => '2026-12-06', 'race_number' => 24],
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

