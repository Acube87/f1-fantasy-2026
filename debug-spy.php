<?php
/**
 * DEBUG: Spy feature race selection
 * Visit this page on production to see exactly what race_id is being picked
 * DELETE after debugging.
 */
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$user = getCurrentUser();
if (!$user) { header('Location: login.php'); exit; }

$db = getDB();
$now = new DateTime('now', new DateTimeZone('UTC'));

echo "<style>body{font-family:monospace;background:#0f172a;color:#e2e8f0;padding:2rem;}
table{border-collapse:collapse;width:100%;}
th,td{border:1px solid #334155;padding:8px 12px;text-align:left;}
th{background:#1e293b;}
.passed{background:#064e3b;color:#34d399;}
.future{background:#1e293b;color:#94a3b8;}
.cancelled{background:#450a0a;color:#f87171;}
.selected{background:#78350f;color:#fbbf24;font-weight:bold;}
</style>";

echo "<h2>🔍 Spy Feature Debug</h2>";
echo "<p>Server UTC time: <strong>" . $now->format('Y-m-d H:i:s') . " UTC</strong></p>";

// Get all races
$allRaces = $db->query("SELECT id, race_name, country, race_date, status FROM races ORDER BY race_date DESC LIMIT 24")->fetch_all(MYSQLI_ASSOC);

$selectedId = 1;
$found = false;

echo "<table>
<tr><th>ID</th><th>Country</th><th>Race Date</th><th>Status</th><th>Deadline (Sat 00:00 UTC)</th><th>Deadline Passed?</th><th>Selected?</th></tr>";

foreach ($allRaces as $r) {
    $deadline = getPredictionDeadline($r['race_date']);
    $passed = $now >= $deadline;
    $isSelected = false;
    
    if ($passed && !$found) {
        $selectedId = $r['id'];
        $found = true;
        $isSelected = true;
    }
    
    $rowClass = $r['status'] === 'cancelled' ? 'cancelled' : ($passed ? 'passed' : 'future');
    if ($isSelected) $rowClass = 'selected';
    
    echo "<tr class='$rowClass'>
        <td>{$r['id']}</td>
        <td>{$r['country']}</td>
        <td>{$r['race_date']}</td>
        <td>{$r['status']}</td>
        <td>" . $deadline->format('Y-m-d H:i:s') . " UTC</td>
        <td>" . ($passed ? '✅ YES' : '❌ NO') . "</td>
        <td>" . ($isSelected ? '👉 THIS ONE' : '') . "</td>
    </tr>";
}

echo "</table>";

echo "<br><h3>Result</h3>";
echo "<p>The 'Peek' button will link to <strong>race_id = $selectedId</strong></p>";

// Show the race
$raceInfo = $db->query("SELECT * FROM races WHERE id = $selectedId")->fetch_assoc();
echo "<p>Race: <strong>" . htmlspecialchars($raceInfo['race_name'] ?? 'Not found') . "</strong> (" . ($raceInfo['country'] ?? '') . ")</p>";

// Test if view-predictions gate would pass for this race
$testDeadline = getPredictionDeadline($raceInfo['race_date'] ?? date('Y-m-d'));
$gateWouldPass = $now >= $testDeadline;
echo "<p>view-predictions.php gate would: <strong>" . ($gateWouldPass ? '✅ ALLOW viewing' : '❌ BLOCK viewing (still before deadline)') . "</strong></p>";

echo "<br><p style='color:#f87171'>Delete this file after debugging: <code>debug-spy.php</code></p>";
?>
