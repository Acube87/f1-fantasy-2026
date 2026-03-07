<?php
require_once 'includes/functions.php';
$raceDateStr = '2026-03-08';
$deadline = getPredictionDeadline($raceDateStr);
$now = new DateTime('now', new DateTimeZone('UTC'));

echo "Now (UTC): " . $now->format('Y-m-d H:i:s') . "\n";
echo "Deadline: " . $deadline->format('Y-m-d H:i:s') . "\n";
echo "Now < Deadline: " . ($now < $deadline ? 'TRUE' : 'FALSE') . "\n";
echo "Now >= Deadline: " . ($now >= $deadline ? 'TRUE' : 'FALSE') . "\n";
?>
