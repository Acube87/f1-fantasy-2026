<?php
/**
 * Setup Official 2026 F1 Race Calendar
 * Based on 24-round season starting March 6th
 */

require_once __DIR__ . '/../config.php';

echo "<h2>Setting up Official 2026 Race Calendar</h2>";

$db = getDB();

// 2026 Calendar Data
// Format: Race Name, Circuit, Country, Date, Round
$races = [
    ['Australian Grand Prix', 'Albert Park Circuit', 'Australia', '2026-03-08', 1],
    ['Chinese Grand Prix', 'Shanghai International Circuit', 'China', '2026-03-15', 2, true], // Sprint
    ['Japanese Grand Prix', 'Suzuka Circuit', 'Japan', '2026-03-29', 3],
    ['Bahrain Grand Prix', 'Bahrain International Circuit', 'Bahrain', '2026-04-12', 4],
    ['Saudi Arabian Grand Prix', 'Jeddah Corniche Circuit', 'Saudi Arabia', '2026-04-19', 5],
    ['Miami Grand Prix', 'Miami International Autodrome', 'USA', '2026-05-03', 6, true], // Sprint
    ['Canadian Grand Prix', 'Circuit Gilles Villeneuve', 'Canada', '2026-05-24', 7, true], // Sprint
    ['Monaco Grand Prix', 'Circuit de Monaco', 'Monaco', '2026-06-07', 8],
    ['Spanish Grand Prix', 'Circuit de Barcelona-Catalunya', 'Spain', '2026-06-14', 9],
    ['Austrian Grand Prix', 'Red Bull Ring', 'Austria', '2026-06-28', 10],
    ['British Grand Prix', 'Silverstone Circuit', 'UK', '2026-07-05', 11, true], // Sprint
    ['Belgian Grand Prix', 'Circuit de Spa-Francorchamps', 'Belgium', '2026-07-19', 12],
    ['Hungarian Grand Prix', 'Hungaroring', 'Hungary', '2026-07-26', 13],
    ['Dutch Grand Prix', 'Zandvoort', 'Netherlands', '2026-08-23', 14, true], // Sprint
    ['Italian Grand Prix', 'Monza', 'Italy', '2026-09-06', 15],
    ['Madrid Grand Prix', 'Madrid Street Circuit', 'Spain', '2026-09-13', 16], // NEW!
    ['Azerbaijan Grand Prix', 'Baku City Circuit', 'Azerbaijan', '2026-09-26', 17],
    ['Singapore Grand Prix', 'Marina Bay Street Circuit', 'Singapore', '2026-10-11', 18, true], // Sprint
    ['United States Grand Prix', 'Circuit of the Americas', 'USA', '2026-10-25', 19],
    ['Mexico City Grand Prix', 'Autódromo Hermanos Rodríguez', 'Mexico', '2026-11-01', 20],
    ['São Paulo Grand Prix', 'Interlagos', 'Brazil', '2026-11-08', 21],
    ['Las Vegas Grand Prix', 'Las Vegas Strip Circuit', 'USA', '2026-11-21', 22],
    ['Qatar Grand Prix', 'Lusail International Circuit', 'Qatar', '2026-11-29', 23],
    ['Abu Dhabi Grand Prix', 'Yas Marina Circuit', 'UAE', '2026-12-06', 24],
];

// Check if sprint column exists, add if not
$checkCol = $db->query("SHOW COLUMNS FROM races LIKE 'is_sprint'");
if ($checkCol && $checkCol->num_rows == 0) {
    $db->query("ALTER TABLE races ADD COLUMN is_sprint BOOLEAN DEFAULT FALSE");
    echo "<p>🔄 Added 'is_sprint' column to races table.</p>";
}

// Clear existing future races to avoid duplicates if re-running
// But keep completed ones just in case (though for setup we usually want fresh)
$db->query("TRUNCATE TABLE races");
echo "<p>🗑️ Cleared existing race calendar.</p>";

$stmt = $db->prepare("INSERT INTO races (race_name, circuit_name, country, race_date, race_number, status, is_sprint) VALUES (?, ?, ?, ?, ?, 'upcoming', ?)");

$count = 0;
foreach ($races as $race) {
    $isSprint = isset($race[5]) && $race[5] ? 1 : 0;
    
    $stmt->bind_param("ssssii", 
        $race[0], // Name
        $race[1], // Circuit
        $race[2], // Country
        $race[3], // Date
        $race[4], // Round
        $isSprint // Is Sprint
    );
    
    if ($stmt->execute()) {
        $type = $isSprint ? " (Sprint Event)" : "";
        echo "<p>✓ Added Round {$race[4]}: <strong>{$race[0]}</strong>{$type}</p>";
        $count++;
    } else {
        echo "<p style='color:red'>❌ Error adding {$race[0]}: " . $stmt->error . "</p>";
    }
}

echo "<p style='color: green;'><strong>Successfully scheduled $count races for 2026! 📅</strong></p>";
echo "<p><a href='../index.php'>Back to Homepage</a></p>";
?>
