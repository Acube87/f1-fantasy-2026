<?php
require_once __DIR__ . '/../includes/auth.php'; // Must be logged in
require_once __DIR__ . '/../includes/functions.php';

if (!isAdmin()) { die("Admin only"); }

$db = getDB();

echo "<h2>F1 Fantasy Diagnostic & Cleanup Tool</h2>";
echo "<p>Running comprehensive check on drivers, predictions, and race results...</p><hr>";

// 1. CLEAN UP DUPLICATE DRIVERS
echo "<h3>1. Drivers Table</h3>";
$stmt = $db->query("SELECT driver_name, COUNT(*) as c FROM drivers GROUP BY driver_name HAVING c > 1");
if ($stmt->num_rows > 0) {
    echo "<b style='color:red;'>FOUND DUPLICATE DRIVERS!</b><br>";
    while ($row = $stmt->fetch_assoc()) {
        $name = $db->real_escape_string($row['driver_name']);
        
        // Find the one to keep
        $q = $db->query("SELECT id FROM drivers WHERE driver_name = '$name' ORDER BY id ASC LIMIT 1");
        $keepId = $q->fetch_assoc()['id'];
        
        // Delete all others
        $db->query("DELETE FROM drivers WHERE driver_name = '$name' AND id != '$keepId'");
        echo "&nbsp;&nbsp;-> DELETED extra copies of $name. Kept ID: $keepId<br>";
    }
} else {
    echo "<span style='color:green;'>No duplicate drivers found in DB.</span><br>";
}

// 2. CLEAN UP DUPLICATE ACTUAL POSITIONS IN RACE_RESULTS
echo "<h3>2. Race Results (Duplicate Positions)</h3>";
$races = $db->query("SELECT id FROM races WHERE status = 'completed'");
while ($race = $races->fetch_assoc()) {
    $rId = $race['id'];
    $stmt = $db->query("SELECT position, COUNT(*) as c FROM race_results WHERE race_id = $rId GROUP BY position HAVING c > 1");
    if ($stmt->num_rows > 0) {
        echo "<b style='color:red;'>Race $rId has duplicated positions!</b><br>";
        while ($row = $stmt->fetch_assoc()) {
            $pos = $row['position'];
            // Keep the first one it finds
            $q = $db->query("SELECT id FROM race_results WHERE race_id = $rId AND position = $pos ORDER BY id ASC LIMIT 1");
            $keepId = $q->fetch_assoc()['id'];
            
            $db->query("DELETE FROM race_results WHERE race_id = $rId AND position = $pos AND id != $keepId");
            echo "&nbsp;&nbsp;-> Cleaned up duplicate Position $pos for Race $rId.<br>";
        }
    } else {
        echo "<span style='color:green;'>Race $rId results are clean (no duplicate positions).</span><br>";
    }
}

// 3. CLEAN UP DUPLICATE ACTUAL DRIVERS IN RACE_RESULTS
$races = $db->query("SELECT id FROM races WHERE status = 'completed'");
while ($race = $races->fetch_assoc()) {
    $rId = $race['id'];
    $stmt = $db->query("SELECT driver_name, COUNT(*) as c FROM race_results WHERE race_id = $rId GROUP BY driver_name HAVING c > 1");
    if ($stmt->num_rows > 0) {
        echo "<b style='color:red;'>Race $rId has duplicated DRIVERS!</b><br>";
        while ($row = $stmt->fetch_assoc()) {
            $dName = $db->real_escape_string($row['driver_name']);
            // Keep one
            $q = $db->query("SELECT id FROM race_results WHERE race_id = $rId AND driver_name = '$dName' ORDER BY id ASC LIMIT 1");
            $keepId = $q->fetch_assoc()['id'];
            
            $db->query("DELETE FROM race_results WHERE race_id = $rId AND driver_name = '$dName' AND id != $keepId");
            echo "&nbsp;&nbsp;-> Cleaned up extra copies of $dName in Race $rId.<br>";
        }
    }
}

// 4. CLEAN UP PREDICTIONS
echo "<h3>3. Predictions (Duplicate Positions)</h3>";
$users = $db->query("SELECT id, username FROM users");
while ($u = $users->fetch_assoc()) {
    $uId = $u['id'];
    $uname = $u['username'];
    
    $races = $db->query("SELECT id FROM races");
    while ($r = $races->fetch_assoc()) {
        $rId = $r['id'];
        
        $stmt = $db->query("SELECT predicted_position, COUNT(*) as c FROM predictions WHERE user_id = $uId AND race_id = $rId GROUP BY predicted_position HAVING c > 1");
        if ($stmt->num_rows > 0) {
            echo "<b style='color:orange;'>User: $uname, Race: $rId has duplicated prediction positions!</b><br>";
            while ($row = $stmt->fetch_assoc()) {
                $pos = $row['predicted_position'];
                
                $q = $db->query("SELECT id FROM predictions WHERE user_id = $uId AND race_id = $rId AND predicted_position = $pos ORDER BY id ASC LIMIT 1");
                $keepId = $q->fetch_assoc()['id'];
                
                $db->query("DELETE FROM predictions WHERE user_id = $uId AND race_id = $rId AND predicted_position = $pos AND id != $keepId");
                echo "&nbsp;&nbsp;-> Cleaned duplicate Prediction $pos for $uname in Race $rId.<br>";
            }
        }
    }
}

echo "<hr><h2 style='color:green;'>SUCCESS! All duplicates have been purged.</h2>";
echo "<p>Please visit <a href='../race-results.php?race_id=2'>race-results.php</a> to verify.</p>";
?>
