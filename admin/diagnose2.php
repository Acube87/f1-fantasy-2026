<?php
require_once __DIR__ . '/../includes/auth.php'; // Must be logged in
require_once __DIR__ . '/../includes/functions.php';

$db = getDB();

echo "<h2>Fixing duplicate predicted positions...</h2>";

$uId = $user['id']; // Get the current logged-in user

// Clean up duplicate predictions for this user specifically
$races = $db->query("SELECT id FROM races");
while ($r = $races->fetch_assoc()) {
    $rId = $r['id'];

    // 1. Check for literal duplicate positions (e.g. they somehow have two P21s)
    $stmt = $db->query("SELECT predicted_position, COUNT(*) as c FROM predictions WHERE user_id = $uId AND race_id = $rId GROUP BY predicted_position HAVING c > 1");
    if ($stmt->num_rows > 0) {
        echo "<b style='color:orange;'>Race $rId has duplicated prediction positions!</b><br>";
        while ($row = $stmt->fetch_assoc()) {
            $pos = $row['predicted_position'];
            $q = $db->query("SELECT id FROM predictions WHERE user_id = $uId AND race_id = $rId AND predicted_position = $pos ORDER BY id ASC LIMIT 1");
            $keepId = $q->fetch_assoc()['id'];
            $db->query("DELETE FROM predictions WHERE user_id = $uId AND race_id = $rId AND predicted_position = $pos AND id != $keepId");
            echo "&nbsp;&nbsp;-> Cleaned duplicate position $pos.<br>";
        }
    }

    // 2. Check for duplicate drivers (e.g. they somehow predicted Colapinto twice at different positions)
    $stmt = $db->query("SELECT driver_id, COUNT(*) as c FROM predictions WHERE user_id = $uId AND race_id = $rId GROUP BY driver_id HAVING c > 1");
    if ($stmt->num_rows > 0) {
        echo "<b style='color:red;'>Race $rId has the same driver predicted twice!</b><br>";
        while ($row = $stmt->fetch_assoc()) {
            $dId = $db->real_escape_string($row['driver_id']);
            $q = $db->query("SELECT id FROM predictions WHERE user_id = $uId AND race_id = $rId AND driver_id = '$dId' ORDER BY id ASC LIMIT 1");
            $keepId = $q->fetch_assoc()['id'];
            $db->query("DELETE FROM predictions WHERE user_id = $uId AND race_id = $rId AND driver_id = '$dId' AND id != $keepId");
            echo "&nbsp;&nbsp;-> DELETED extra copies of Driver $dId.<br>";
        }
    }
}

echo "<h3>Re-indexing positions</h3>";
// Force re-numbering of the remaining predictions to be strictly 1-22
$races = $db->query("SELECT id FROM races");
while ($r = $races->fetch_assoc()) {
    $rId = $r['id'];
    $preds = $db->query("SELECT id FROM predictions WHERE user_id = $uId AND race_id = $rId ORDER BY predicted_position ASC");
    if ($preds->num_rows > 0) {
        $pos = 1;
        while ($p = $preds->fetch_assoc()) {
            $pid = $p['id'];
            $db->query("UPDATE predictions SET predicted_position = $pos WHERE id = $pid");
            $pos++;
        }
        echo "Re-indexed race $rId.<br>";
    }
}

echo "<hr><h2 style='color:green;'>SUCCESS!</h2>";
?>
