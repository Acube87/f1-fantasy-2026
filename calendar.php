<?php
require_once 'includes/functions.php';

// Which races to include
$raceId = isset($_GET['race_id']) ? intval($_GET['race_id']) : null;

$db = getDB();

if ($raceId) {
    $stmt = $db->prepare("SELECT * FROM races WHERE id = ? AND status != 'cancelled'");
    $stmt->bind_param("i", $raceId);
} else {
    $stmt = $db->prepare("SELECT * FROM races WHERE status != 'cancelled' ORDER BY race_date ASC");
}
$stmt->execute();
$races = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

if (empty($races)) {
    http_response_code(404);
    exit('No races found.');
}

$appUrl = 'https://f1.scanerrific.com';

// ICS timestamp format
function icsDate(DateTime $dt): string {
    return $dt->format('Ymd\THis\Z');
}

function icsEscape(string $str): string {
    return addcslashes($str, "\\,;");
}

// Build ICS
$lines = [
    'BEGIN:VCALENDAR',
    'VERSION:2.0',
    'PRODID:-//Paddock Picks//F1 2026 Schedule//EN',
    'CALSCALE:GREGORIAN',
    'METHOD:PUBLISH',
    'X-WR-CALNAME:Paddock Picks — F1 2026',
    'X-WR-CALDESC:F1 2026 prediction deadlines for Paddock Picks',
    'X-WR-TIMEZONE:UTC',
];

foreach ($races as $race) {
    // Prediction deadline = day before race at 00:00 UTC
    $deadline = getPredictionDeadline($race['race_date']);
    $deadlineEnd = clone $deadline;
    $deadlineEnd->modify('+1 hour');

    // Race start (approximate — 14:00 UTC is a reasonable default)
    $raceStart = new DateTime($race['race_date'] . ' 14:00:00', new DateTimeZone('UTC'));
    $raceEnd   = clone $raceStart;
    $raceEnd->modify('+2 hours');

    $predictUrl = $appUrl . '/predict.php?race_id=' . $race['id'];
    $uid = 'paddockpicks-deadline-' . $race['id'] . '@scanerrific.com';

    // EVENT 1 — Prediction Deadline
    $lines[] = 'BEGIN:VEVENT';
    $lines[] = 'UID:' . $uid;
    $lines[] = 'DTSTAMP:' . icsDate(new DateTime('now', new DateTimeZone('UTC')));
    $lines[] = 'DTSTART:' . icsDate($deadline);
    $lines[] = 'DTEND:' . icsDate($deadlineEnd);
    $lines[] = 'SUMMARY:' . icsEscape('🏎️ ' . $race['country'] . ' GP — Predictions Close');
    $lines[] = 'DESCRIPTION:' . icsEscape('Last chance to submit your predictions for the ' . $race['race_name'] . '!\n\nMake your picks: ' . $predictUrl);
    $lines[] = 'LOCATION:' . icsEscape($race['circuit_name']);
    $lines[] = 'URL:' . $predictUrl;
    $lines[] = 'COLOR:tomato';
    // 24h reminder alarm
    $lines[] = 'BEGIN:VALARM';
    $lines[] = 'TRIGGER:-PT24H';
    $lines[] = 'ACTION:DISPLAY';
    $lines[] = 'DESCRIPTION:' . icsEscape('⏰ 24h left to predict: ' . $race['country'] . ' GP');
    $lines[] = 'END:VALARM';
    // 1h reminder alarm
    $lines[] = 'BEGIN:VALARM';
    $lines[] = 'TRIGGER:-PT1H';
    $lines[] = 'ACTION:DISPLAY';
    $lines[] = 'DESCRIPTION:' . icsEscape('🚨 1 hour left! Submit your ' . $race['country'] . ' GP prediction');
    $lines[] = 'END:VALARM';
    $lines[] = 'END:VEVENT';

    // EVENT 2 — Race Day
    $raceUid = 'paddockpicks-race-' . $race['id'] . '@scanerrific.com';
    $lines[] = 'BEGIN:VEVENT';
    $lines[] = 'UID:' . $raceUid;
    $lines[] = 'DTSTAMP:' . icsDate(new DateTime('now', new DateTimeZone('UTC')));
    $lines[] = 'DTSTART:' . icsDate($raceStart);
    $lines[] = 'DTEND:' . icsDate($raceEnd);
    $lines[] = 'SUMMARY:' . icsEscape('🏁 ' . $race['race_name']);
    $lines[] = 'DESCRIPTION:' . icsEscape($race['race_name'] . ' at ' . $race['circuit_name'] . '\n\nPaddock Picks: ' . $appUrl);
    $lines[] = 'LOCATION:' . icsEscape($race['circuit_name']);
    $lines[] = 'URL:' . $appUrl;
    $lines[] = 'END:VEVENT';
}

$lines[] = 'END:VCALENDAR';

$filename = $raceId ? 'paddock-picks-race-' . $raceId . '.ics' : 'paddock-picks-f1-2026.ics';

header('Content-Type: text/calendar; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-cache, no-store');

echo implode("\r\n", $lines) . "\r\n";
