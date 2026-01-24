<?php
require_once __DIR__ . '/../config.php';

$db = getDB();

echo "<h2>Adding 2026 F1 Drivers</h2>";

// 2026 F1 Grid (known drivers)
$drivers = [
    ['name' => 'Max Verstappen', 'constructor' => 'Red Bull Racing'],
    ['name' => 'Sergio Perez', 'constructor' => 'Red Bull Racing'],
    ['name' => 'Lewis Hamilton', 'constructor' => 'Ferrari'],
    ['name' => 'Charles Leclerc', 'constructor' => 'Ferrari'],
    ['name' => 'Lando Norris', 'constructor' => 'McLaren'],
    ['name' => 'Oscar Piastri', 'constructor' => 'McLaren'],
    ['name' => 'Fernando Alonso', 'constructor' => 'Aston Martin'],
    ['name' => 'Lance Stroll', 'constructor' => 'Aston Martin'],
    ['name' => 'George Russell', 'constructor' => 'Mercedes'],
    ['name' => 'Andrea Kimi Antonelli', 'constructor' => 'Mercedes'],
    ['name' => 'Yuki Tsunoda', 'constructor' => 'Racing Bulls'],
    ['name' => 'Isack Hadjar', 'constructor' => 'Racing Bulls'],
    ['name' => 'Alexander Albon', 'constructor' => 'Williams'],
    ['name' => 'Carlos Sainz', 'constructor' => 'Williams'],
    ['name' => 'Nico Hulkenberg', 'constructor' => 'Haas'],
    ['name' => 'Esteban Ocon', 'constructor' => 'Haas'],
    ['name' => 'Pierre Gasly', 'constructor' => 'Alpine'],
    ['name' => 'Jack Doohan', 'constructor' => 'Alpine'],
    ['name' => 'Zhou Guanyu', 'constructor' => 'Sauber'],
    ['name' => 'Nico Rosberg', 'constructor' => 'Sauber'],
];

foreach ($drivers as $driver) {
    $checkStmt = $db->prepare("SELECT id FROM drivers WHERE driver_name = ?");
    $checkStmt->bind_param("s", $driver['name']);
    $checkStmt->execute();
    
    if ($checkStmt->get_result()->num_rows > 0) {
        echo "<p>Driver {$driver['name']} already exists. Skipping.</p>";
        continue;
    }
    
    $stmt = $db->prepare("INSERT INTO drivers (driver_name, team) VALUES (?, ?)");
    $stmt->bind_param("ss", $driver['name'], $driver['constructor']);
    
    if ($stmt->execute()) {
        echo "<p>✓ Added: {$driver['name']} ({$driver['constructor']})</p>";
    } else {
        echo "<p>✗ Error: " . $db->error . "</p>";
    }
}

echo "<p><strong>Done!</strong></p>";
echo "<p><a href='../index.php'>Go to Homepage</a></p>";
?>