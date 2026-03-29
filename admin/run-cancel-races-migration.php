<?php
/**
 * One-time admin migration: Cancel Bahrain & Saudi Arabia GPs 2026
 * DELETE THIS FILE after running it on production.
 */
require_once __DIR__ . '/../config.php';

// Basic protection — only allow admin or direct server access
session_start();
$token = $_GET['token'] ?? '';
$expectedToken = 'cancel-me-2026'; // simple secret

if ($token !== $expectedToken) {
    http_response_code(403);
    echo "Forbidden. Pass ?token=cancel-me-2026";
    exit;
}

$db = getDB();

// Run the migration
$result = $db->query("UPDATE races SET status = 'cancelled' WHERE id IN (4, 5)");
$affected = $db->affected_rows;

// Show verification
$check = $db->query("SELECT id, race_name, country, race_date, status FROM races WHERE id IN (4, 5, 6) ORDER BY race_date ASC");

echo "<h2>Migration: Cancel Bahrain & Saudi Arabia GPs</h2>";
echo "<p>Rows updated: <strong>$affected</strong></p>";
echo "<table border='1' cellpadding='6'>";
echo "<tr><th>ID</th><th>Race</th><th>Country</th><th>Date</th><th>Status</th></tr>";
while ($row = $check->fetch_assoc()) {
    $color = $row['status'] === 'cancelled' ? '#ffdddd' : '#ddffdd';
    echo "<tr style='background:{$color}'>";
    echo "<td>{$row['id']}</td><td>{$row['race_name']}</td><td>{$row['country']}</td><td>{$row['race_date']}</td><td><strong>{$row['status']}</strong></td>";
    echo "</tr>";
}
echo "</table>";
echo "<br><p style='color:red'><strong>⚠️ DELETE this file from the server after confirming!</strong></p>";
echo "<p>Next race should now be Miami GP (id=6).</p>";
?>
