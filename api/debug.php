<?php
require_once __DIR__ . '/../includes/functions.php';
$db = getDB();
$r = $db->query('SELECT position, driver_name FROM race_results WHERE race_id = 2 ORDER BY position ASC;');
while($row = $r->fetch_assoc()) echo $row['position'] . ' - ' . $row['driver_name'] . "\n";
