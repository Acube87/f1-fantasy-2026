<?php
/**
 * Add 2024 F1 Drivers and Constructors manually
 * Since 2026 season hasn't started, we'll use 2024 data
 */

require_once __DIR__ . '/../config.php';

echo "<h2>Adding 2024 F1 Drivers and Constructors</h2>";

$db = getDB();

// Truncate tables to ensure fresh data
$db->query("TRUNCATE TABLE drivers");
$db->query("TRUNCATE TABLE constructors");

// 2024 F1 Drivers (actual lineup)
$drivers = [
    ['id' => 'verstappen', 'name' => 'Max Verstappen', 'team' => 'Red Bull Racing'],
    ['id' => 'perez', 'name' => 'Sergio Pérez', 'team' => 'Red Bull Racing'],
    ['id' => 'hamilton', 'name' => 'Lewis Hamilton', 'team' => 'Mercedes'],
    ['id' => 'russell', 'name' => 'George Russell', 'team' => 'Mercedes'],
    ['id' => 'leclerc', 'name' => 'Charles Leclerc', 'team' => 'Ferrari'],
    ['id' => 'sainz', 'name' => 'Carlos Sainz', 'team' => 'Ferrari'],
    ['id' => 'norris', 'name' => 'Lando Norris', 'team' => 'McLaren'],
    ['id' => 'piastri', 'name' => 'Oscar Piastri', 'team' => 'McLaren'],
    ['id' => 'alonso', 'name' => 'Fernando Alonso', 'team' => 'Aston Martin'],
    ['id' => 'stroll', 'name' => 'Lance Stroll', 'team' => 'Aston Martin'],
    ['id' => 'gasly', 'name' => 'Pierre Gasly', 'team' => 'Alpine'],
    ['id' => 'ocon', 'name' => 'Esteban Ocon', 'team' => 'Alpine'],
    ['id' => 'albon', 'name' => 'Alexander Albon', 'team' => 'Williams'],
    ['id' => 'sargeant', 'name' => 'Logan Sargeant', 'team' => 'Williams'],
    ['id' => 'bottas', 'name' => 'Valtteri Bottas', 'team' => 'Kick Sauber'],
    ['id' => 'zhou', 'name' => 'Zhou Guanyu', 'team' => 'Kick Sauber'],
    ['id' => 'hulkenberg', 'name' => 'Nico Hülkenberg', 'team' => 'Haas F1 Team'],
    ['id' => 'magnussen', 'name' => 'Kevin Magnussen', 'team' => 'Haas F1 Team'],
    ['id' => 'tsunoda', 'name' => 'Yuki Tsunoda', 'team' => 'RB'],
    ['id' => 'ricciardo', 'name' => 'Daniel Ricciardo', 'team' => 'RB'],
];

// Insert drivers
$stmt = $db->prepare("INSERT INTO drivers (id, driver_name, team) VALUES (?, ?, ?)");
$driverCount = 0;
foreach ($drivers as $driver) {
    $stmt->bind_param("sss", $driver['id'], $driver['name'], $driver['team']);
    if ($stmt->execute()) {
        echo "<p>✓ Added: {$driver['name']} ({$driver['team']})</p>";
        $driverCount++;
    }
}

echo "<p style='color: green;'><strong>Successfully inserted $driverCount drivers.</strong></p>";

// 2024 F1 Constructors
$constructors = [
    ['id' => 'red_bull', 'name' => 'Red Bull Racing'],
    ['id' => 'mercedes', 'name' => 'Mercedes'],
    ['id' => 'ferrari', 'name' => 'Ferrari'],
    ['id' => 'mclaren', 'name' => 'McLaren'],
    ['id' => 'aston_martin', 'name' => 'Aston Martin'],
    ['id' => 'alpine', 'name' => 'Alpine'],
    ['id' => 'williams', 'name' => 'Williams'],
    ['id' => 'kick_sauber', 'name' => 'Kick Sauber'],
    ['id' => 'haas', 'name' => 'Haas F1 Team'],
    ['id' => 'rb', 'name' => 'RB'],
];

// Insert constructors
$stmt = $db->prepare("INSERT INTO constructors (id, name) VALUES (?, ?)");
$constructorCount = 0;
foreach ($constructors as $constructor) {
    $stmt->bind_param("ss", $constructor['id'], $constructor['name']);
    if ($stmt->execute()) {
        echo "<p>✓ Added: {$constructor['name']}</p>";
        $constructorCount++;
    }
}

echo "<p style='color: green;'><strong>Successfully inserted $constructorCount constructors.</strong></p>";
echo "<p><strong>Drivers and constructors have been populated!</strong></p>";
echo "<p><a href='../index.php'>Back to Homepage</a></p>";
?>
