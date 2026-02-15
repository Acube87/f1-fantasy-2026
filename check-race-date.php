<?php
require 'includes/functions.php';
$db = getDB();
$stmt = $db->prepare('SELECT id, race_name, country, race_date, race_time FROM races WHERE race_date >= CURDATE() ORDER BY race_date LIMIT 1');
$stmt->execute();
$race = $stmt->get_result()->fetch_assoc();
echo 'Next Race: ' . $race['race_name'] . PHP_EOL;
echo 'Date: ' . $race['race_date'] . ' ' . ($race['race_time'] ?? '14:00:00') . PHP_EOL;
echo 'Race ID: ' . $race['id'] . PHP_EOL;

$raceDateTime = new DateTime($race['race_date'] . ' ' . ($race['race_time'] ?? '14:00:00'));
$now = new DateTime('now', new DateTimeZone('UTC'));
$interval = $now->diff($raceDateTime);
echo PHP_EOL . 'Time until race:' . PHP_EOL;
echo 'Days: ' . $interval->days . PHP_EOL;
echo 'Hours: ' . $interval->h . PHP_EOL;
echo 'Minutes: ' . $interval->i . PHP_EOL;
?>
