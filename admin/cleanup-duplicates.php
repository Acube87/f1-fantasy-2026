<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

if (!isAdmin()) { die("Admin only"); }

$db = getDB();
echo "<h2>Duplicate Driver Cleanup</h2><hr>";

// Clean race_results table
echo "<h3>Cleaning race_results table...</h3>";
$races = $db->query("SELECT id FROM races")->fetch_all(MYSQLI_ASSOC);
foreach ($races as $race) {
    $rId = $race['id'];
    
    // Find duplicates
    $dupes = $db->query("
        SELECT driver_id, COUNT(*) as cnt, GROUP_CONCAT(id) as ids
        FROM race_results 
        WHERE race_id = $rId 
        GROUP BY driver_id 
        HAVING COUNT(*) > 1
    ")->fetch_all(MYSQLI_ASSOC);
    
    if (!empty($dupes)) {
        foreach ($dupes as $d) {
            $driverId = $d['driver_id'];
            $ids = explode(',', $d['ids']);
            $keepId = array_shift($ids); // Keep first
            
            foreach ($ids as $delId) {
                $db->query("DELETE FROM race_results WHERE id = $delId");
            }
            echo "Race $rId: Deleted duplicate driver_id $driverId (kept id $keepId)<br>";
        }
    }
}

// Clean predictions table  
echo "<h3>Cleaning predictions table...</h3>";
$users = $db->query("SELECT id FROM users")->fetch_all(MYSQLI_ASSOC);
foreach ($users as $u) {
    $uId = $u['id'];
    $races = $db->query("SELECT id FROM races")->fetch_all(MYSQLI_ASSOC);
    foreach ($races as $race) {
        $rId = $race['id'];
        
        $dupes = $db->query("
            SELECT driver_id, COUNT(*) as cnt, GROUP_CONCAT(id) as ids
            FROM predictions 
            WHERE race_id = $rId AND user_id = $uId
            GROUP BY driver_id 
            HAVING COUNT(*) > 1
        ")->fetch_all(MYSQLI_ASSOC);
        
        if (!empty($dupes)) {
            foreach ($dupes as $d) {
                $driverId = $d['driver_id'];
                $ids = explode(',', $d['ids']);
                $keepId = array_shift($ids);
                
                foreach ($ids as $delId) {
                    $db->query("DELETE FROM predictions WHERE id = $delId");
                }
                echo "User $uId Race $rId: Deleted duplicate driver_id $driverId (kept id $keepId)<br>";
            }
        }
    }
}

echo "<hr><h3 style='color:green'>Done! All duplicates cleaned.</h3>";
echo "<p><a href='../dashboard.php'>Go to Dashboard</a></p>";
?>