<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isAdmin()) { die("Admin only"); }

$db = getDB();
echo "<h2>Cleaning Duplicate Predictions</h2><hr>";

// For each user and race, keep only ONE prediction per driver
$users = $db->query("SELECT id FROM users")->fetch_all(MYSQLI_ASSOC);
$totalDeleted = 0;

foreach ($users as $u) {
    $uId = $u['id'];
    $races = $db->query("SELECT id FROM races")->fetch_all(MYSQLI_ASSOC);
    
    foreach ($races as $r) {
        $rId = $r['id'];
        
        // Find duplicates: same user, race, and driver_name
        $dupes = $db->query("
            SELECT driver_name, COUNT(*) as cnt, GROUP_CONCAT(id ORDER BY id) as ids
            FROM predictions 
            WHERE race_id = $rId AND user_id = $uId
            GROUP BY driver_name 
            HAVING COUNT(*) > 1
        ")->fetch_all(MYSQLI_ASSOC);
        
        if (!empty($dupes)) {
            foreach ($dupes as $d) {
                $driverName = $d['driver_name'];
                $ids = explode(',', $d['ids']);
                $keepId = array_shift($ids); // Keep first
                
                foreach ($ids as $delId) {
                    $db->query("DELETE FROM predictions WHERE id = $delId");
                    $totalDeleted++;
                }
                echo "User $uId Race $rId: Deleted duplicate '$driverName' (kept id $keepId)<br>";
            }
        }
    }
}

echo "<hr><h3 style='color:green'>Done! Deleted $totalDeleted duplicate predictions.</h3>";
echo "<p><a href='../dashboard.php'>Go to Dashboard</a></p>";
?>