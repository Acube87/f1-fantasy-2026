<?php
/**
 * Add Official 2026 F1 Drivers and Constructors
 * Verified against User Provided Data (Step Id: 338)
 */

require_once __DIR__ . '/../config.php';

echo "<h2>Adding Official 2026 F1 Drivers and Constructors</h2>";

$db = getDB();

// Disable foreign key checks to allow truncation
$db->query("SET FOREIGN_KEY_CHECKS = 0");

// Truncate tables to ensure fresh data
$db->query("TRUNCATE TABLE drivers");
$db->query("TRUNCATE TABLE constructors");

// Re-enable foreign key checks
$db->query("SET FOREIGN_KEY_CHECKS = 1");

// 2026 F1 Drivers Lineup (Exact match to official list)
$drivers = [
    // Alpine
    ['id' => 'gasly', 'name' => 'Pierre Gasly', 'team' => 'Alpine'],
    ['id' => 'colapinto', 'name' => 'Franco Colapinto', 'team' => 'Alpine'],

    // Aston Martin
    ['id' => 'alonso', 'name' => 'Fernando Alonso', 'team' => 'Aston Martin'],
    ['id' => 'stroll', 'name' => 'Lance Stroll', 'team' => 'Aston Martin'],

    // Williams
    ['id' => 'sainz', 'name' => 'Carlos Sainz', 'team' => 'Williams'],
    ['id' => 'albon', 'name' => 'Alexander Albon', 'team' => 'Williams'],

    // Audi
    ['id' => 'hulkenberg', 'name' => 'Nico Hulkenberg', 'team' => 'Audi'],
    ['id' => 'bortoleto', 'name' => 'Gabriel Bortoleto', 'team' => 'Audi'],

    // Cadillac
    ['id' => 'perez', 'name' => 'Sergio Perez', 'team' => 'Cadillac'],
    ['id' => 'bottas', 'name' => 'Valtteri Bottas', 'team' => 'Cadillac'],

    // Ferrari
    ['id' => 'leclerc', 'name' => 'Charles Leclerc', 'team' => 'Ferrari'],
    ['id' => 'hamilton', 'name' => 'Lewis Hamilton', 'team' => 'Ferrari'],

    // Haas
    ['id' => 'ocon', 'name' => 'Esteban Ocon', 'team' => 'Haas F1 Team'],
    ['id' => 'bearman', 'name' => 'Oliver Bearman', 'team' => 'Haas F1 Team'],

    // McLaren
    ['id' => 'norris', 'name' => 'Lando Norris', 'team' => 'McLaren'],
    ['id' => 'piastri', 'name' => 'Oscar Piastri', 'team' => 'McLaren'],

    // Mercedes
    ['id' => 'russell', 'name' => 'George Russell', 'team' => 'Mercedes'],
    ['id' => 'antonelli', 'name' => 'Kimi Antonelli', 'team' => 'Mercedes'],

    // Racing Bulls
    ['id' => 'lawson', 'name' => 'Liam Lawson', 'team' => 'Racing Bulls'],
    ['id' => 'lindblad', 'name' => 'Arvid Lindblad', 'team' => 'Racing Bulls'],

    // Red Bull Racing
    ['id' => 'verstappen', 'name' => 'Max Verstappen', 'team' => 'Red Bull Racing'],
    ['id' => 'hadjar', 'name' => 'Isack Hadjar', 'team' => 'Red Bull Racing'],
];

// Insert drivers
$stmt = $db->prepare("INSERT INTO drivers (id, driver_name, team) VALUES (?, ?, ?)");
$driverCount = 0;
foreach ($drivers as $driver) {
    $stmt->bind_param("sss", $driver['id'], $driver['name'], $driver['team']);
    if ($stmt->execute()) {
        echo "<p>✓ Added: <strong>{$driver['name']}</strong> ({$driver['team']})</p>";
        $driverCount++;
    }
}

echo "<p style='color: green;'><strong>Successfully inserted $driverCount drivers.</strong></p>";

// 2026 F1 Constructors (11 Teams)
$constructors = [
    ['id' => 'alpine', 'name' => 'Alpine', 'color' => '#0090FF'],
    ['id' => 'aston_martin', 'name' => 'Aston Martin', 'color' => '#006F62'],
    ['id' => 'williams', 'name' => 'Williams', 'color' => '#005AFF'],
    ['id' => 'audi', 'name' => 'Audi', 'color' => '#F20707'],
    ['id' => 'cadillac', 'name' => 'Cadillac', 'color' => '#FCD12A'], // Gold/Yellow
    ['id' => 'ferrari', 'name' => 'Ferrari', 'color' => '#C00000'],
    ['id' => 'haas', 'name' => 'Haas F1 Team', 'color' => '#B6BABD'],
    ['id' => 'mclaren', 'name' => 'McLaren', 'color' => '#FF8000'],
    ['id' => 'mercedes', 'name' => 'Mercedes', 'color' => '#00D2BE'],
    ['id' => 'racing_bulls', 'name' => 'Racing Bulls', 'color' => '#1634CB'],
    ['id' => 'red_bull', 'name' => 'Red Bull Racing', 'color' => '#0600EF'],
];

// Insert constructors
$stmt = $db->prepare("INSERT INTO constructors (id, name, color) VALUES (?, ?, ?)");
$constructorCount = 0;
foreach ($constructors as $constructor) {
    $stmt->bind_param("sss", $constructor['id'], $constructor['name'], $constructor['color']);
    if ($stmt->execute()) {
        echo "<p>✓ Added Team: <strong>{$constructor['name']}</strong></p>";
        $constructorCount++;
    }
}

echo "<p style='color: green;'><strong>Successfully inserted $constructorCount constructors.</strong></p>";
echo "<p><a href='../index.php'>Back to Homepage</a></p>";
?>
