<?php
/**
 * Reconcile the races table with the OFFICIAL 2026 F1 calendar.
 * Source of truth: https://www.formula1.com/en/racing/2026
 *
 * What was wrong before this tool:
 *   - Round 16 Bahrain (02-04 Oct) was MISSING from the DB entirely.
 *     getNextRace() therefore skipped it and reported Singapore as the next race.
 *   - "Madrid Grand Prix" / "Circuit Ricardo Tormo" (id 16) was not a real 2026 race.
 *     The real round 14 is the Spanish GP at Barcelona-Catalunya.
 *   - Saudi Arabian GP (id 5) is not on the 2026 calendar.
 *   - Every race_number from round 4 onward was off by two.
 *   - Rounds 10-15 were still flagged 'upcoming' although they have been run.
 *
 * Rows are updated IN PLACE by id so existing predictions, scores and posts
 * (which key off races.id) are never re-pointed or orphaned.
 *
 * Usage:
 *   php admin/update-calendar.php            # dry run, prints before/after diff
 *   php admin/update-calendar.php --apply    # commit (backs up first)
 *   or visit /admin/update-calendar.php?apply=1 in the browser (admin only)
 */

require_once __DIR__ . '/../config.php';

$isCli = (PHP_SAPI === 'cli');
if ($isCli) {
    $apply = in_array('--apply', array_slice($argv, 1), true);
} else {
    require_once __DIR__ . '/../includes/auth.php';
    $user = getCurrentUser();
    if (!$user || empty($user['is_admin'])) {
        die('Unauthorized');
    }
    $apply = isset($_GET['apply']) || isset($_POST['apply']);
}

/**
 * OFFICIAL 2026 calendar, expressed against the EXISTING race ids.
 * id, race_name, circuit_name, country, race_date (race day = Sunday), race_number, status
 *
 * Note: round 16 is titled "Bahrain Grand Prix in Malaysia" on the official
 * calendar, so it is run at Sepang rather than Bahrain International.
 */
$calendar = [
    ['id' =>  1, 'race_name' => 'Australian Grand Prix',      'circuit_name' => 'Albert Park Circuit',           'country' => 'Australia',      'race_date' => '2026-03-08', 'race_number' =>  1, 'status' => 'completed'],
    ['id' =>  2, 'race_name' => 'Chinese Grand Prix',         'circuit_name' => 'Shanghai International Circuit', 'country' => 'China',          'race_date' => '2026-03-15', 'race_number' =>  2, 'status' => 'completed'],
    ['id' =>  3, 'race_name' => 'Japanese Grand Prix',        'circuit_name' => 'Suzuka Circuit',                'country' => 'Japan',          'race_date' => '2026-03-29', 'race_number' =>  3, 'status' => 'completed'],
    ['id' =>  6, 'race_name' => 'Miami Grand Prix',           'circuit_name' => 'Miami International Autodrome',  'country' => 'USA',            'race_date' => '2026-05-03', 'race_number' =>  4, 'status' => 'completed'],
    ['id' =>  7, 'race_name' => 'Canadian Grand Prix',        'circuit_name' => 'Circuit Gilles Villeneuve',      'country' => 'Canada',         'race_date' => '2026-05-24', 'race_number' =>  5, 'status' => 'completed'],
    ['id' =>  8, 'race_name' => 'Monaco Grand Prix',          'circuit_name' => 'Circuit de Monaco',             'country' => 'Monaco',         'race_date' => '2026-06-07', 'race_number' =>  6, 'status' => 'completed'],
    ['id' =>  9, 'race_name' => 'Spanish Grand Prix',         'circuit_name' => 'Circuit de Barcelona-Catalunya','country' => 'Spain',          'race_date' => '2026-06-14', 'race_number' =>  7, 'status' => 'completed'],
    ['id' => 10, 'race_name' => 'Austrian Grand Prix',        'circuit_name' => 'Red Bull Ring',                 'country' => 'Austria',        'race_date' => '2026-06-28', 'race_number' =>  8, 'status' => 'completed'],
    ['id' => 11, 'race_name' => 'British Grand Prix',         'circuit_name' => 'Silverstone Circuit',           'country' => 'UK',             'race_date' => '2026-07-05', 'race_number' =>  9, 'status' => 'completed'],
    ['id' => 12, 'race_name' => 'Belgian Grand Prix',         'circuit_name' => 'Circuit de Spa-Francorchamps',  'country' => 'Belgium',        'race_date' => '2026-07-19', 'race_number' => 10, 'status' => 'completed'],
    ['id' => 13, 'race_name' => 'Hungarian Grand Prix',       'circuit_name' => 'Hungaroring',                   'country' => 'Hungary',        'race_date' => '2026-07-26', 'race_number' => 11, 'status' => 'completed'],
    ['id' => 14, 'race_name' => 'Dutch Grand Prix',           'circuit_name' => 'Zandvoort',                     'country' => 'Netherlands',    'race_date' => '2026-08-23', 'race_number' => 12, 'status' => 'completed'],
    ['id' => 15, 'race_name' => 'Italian Grand Prix',         'circuit_name' => 'Monza',                         'country' => 'Italy',          'race_date' => '2026-09-06', 'race_number' => 13, 'status' => 'completed'],
    ['id' => 16, 'race_name' => 'Spanish Grand Prix',         'circuit_name' => 'Circuit de Barcelona-Catalunya','country' => 'Spain',          'race_date' => '2026-09-13', 'race_number' => 14, 'status' => 'completed'],
    ['id' => 17, 'race_name' => 'Azerbaijan Grand Prix',      'circuit_name' => 'Baku City Circuit',             'country' => 'Azerbaijan',     'race_date' => '2026-09-26', 'race_number' => 15, 'status' => 'completed'],
    ['id' =>  4, 'race_name' => 'Bahrain Grand Prix',         'circuit_name' => 'Sepang International Circuit',  'country' => 'Bahrain',        'race_date' => '2026-10-04', 'race_number' => 16, 'status' => 'upcoming'],
    ['id' => 18, 'race_name' => 'Singapore Grand Prix',       'circuit_name' => 'Marina Bay Street Circuit',     'country' => 'Singapore',      'race_date' => '2026-10-11', 'race_number' => 17, 'status' => 'upcoming'],
    ['id' => 19, 'race_name' => 'United States Grand Prix',   'circuit_name' => 'Circuit of the Americas',       'country' => 'USA',            'race_date' => '2026-10-25', 'race_number' => 18, 'status' => 'upcoming'],
    ['id' => 20, 'race_name' => 'Mexico City Grand Prix',     'circuit_name' => 'Autódromo Hermanos Rodríguez',  'country' => 'Mexico',         'race_date' => '2026-11-01', 'race_number' => 19, 'status' => 'upcoming'],
    ['id' => 21, 'race_name' => 'Brazilian Grand Prix',       'circuit_name' => 'Interlagos',                    'country' => 'Brazil',         'race_date' => '2026-11-08', 'race_number' => 20, 'status' => 'upcoming'],
    ['id' => 22, 'race_name' => 'Las Vegas Grand Prix',       'circuit_name' => 'Las Vegas Strip Circuit',       'country' => 'USA',            'race_date' => '2026-11-21', 'race_number' => 21, 'status' => 'upcoming'],
    ['id' => 23, 'race_name' => 'Qatar Grand Prix',           'circuit_name' => 'Lusail International Circuit',  'country' => 'Qatar',          'race_date' => '2026-11-29', 'race_number' => 22, 'status' => 'upcoming'],
    ['id' => 24, 'race_name' => 'Abu Dhabi Grand Prix',       'circuit_name' => 'Yas Marina Circuit',            'country' => 'Abu Dhabi',      'race_date' => '2026-12-06', 'race_number' => 23, 'status' => 'upcoming'],
];

/** Races that are not on the official 2026 calendar and should be removed. */
$remove = [5]; // Saudi Arabian GP - not run in 2026

$db = getDB();

function out($s) { echo $s . "\n"; }

function fetchRaces($db) {
    $rows = array();
    $r = $db->query("SELECT id, race_name, circuit_name, country, race_date, race_number, status FROM races ORDER BY race_date ASC");
    while ($x = $r->fetch_assoc()) { $rows[(int)$x['id']] = $x; }
    return $rows;
}

function dependentCounts($db, $raceId) {
    $out = array();
    foreach (array('predictions', 'constructor_predictions', 'race_results', 'scores', 'posts') as $t) {
        $s = $db->prepare("SELECT COUNT(*) AS n FROM `$t` WHERE race_id = ?");
        $s->bind_param('i', $raceId);
        $s->execute();
        $out[$t] = (int)$s->get_result()->fetch_assoc()['n'];
    }
    return $out;
}

$before = fetchRaces($db);

// ---------------------------------------------------------------- safety checks
$blocking = array();
foreach ($remove as $id) {
    if (!isset($before[$id])) { continue; } // already gone -> idempotent
    $counts = dependentCounts($db, $id);
    $total = array_sum($counts);
    if ($total > 0) {
        $blocking[] = "race id $id ({$before[$id]['race_name']}) still has dependent rows: "
            . json_encode($counts) . " - refusing to delete. Clear them manually first.";
    }
}
$knownIds = array();
foreach ($calendar as $c) { $knownIds[] = (int)$c['id']; }
foreach ($knownIds as $id) {
    if (!isset($before[$id])) {
        $blocking[] = "expected race id $id does not exist in the DB - schema differs from local, aborting.";
    }
}

out("=== OFFICIAL 2026 CALENDAR RECONCILIATION ===");
out("Mode: " . ($apply ? 'APPLY' : 'DRY RUN (nothing written)'));
out("");

if ($blocking) {
    out("!! ABORTING - unresolved safety checks:");
    foreach ($blocking as $b) { out("   - $b"); }
    exit(1);
}

out("Planned changes:");
$changed = 0;
foreach ($calendar as $c) {
    $id = (int)$c['id'];
    $cur = $before[$id];
    $diffs = array();
    foreach (array('race_name', 'circuit_name', 'country', 'race_date', 'race_number', 'status') as $f) {
        if ((string)$cur[$f] !== (string)$c[$f]) {
            $diffs[] = "$f: '{$cur[$f]}' -> '{$c[$f]}'";
        }
    }
    if ($diffs) {
        $changed++;
        out("  id $id ({$c['race_name']})");
        foreach ($diffs as $d) { out("      $d"); }
    }
}
foreach ($remove as $id) {
    if (isset($before[$id])) {
        $changed++;
        out("  id $id ({$before[$id]['race_name']}) -> DELETE (not on the official 2026 calendar)");
    }
}
out("");
out("Rows needing change: $changed");
out("");

if (!$apply) {
    out("Re-run with --apply (or ?apply=1 in the browser) to write these changes.");
    exit(0);
}

// ---------------------------------------------------------------------- backup
// Introspect the real schema instead of assuming columns: the live `races`
// table may not have every column the local one does (e.g. updated_at).
$schema = array();
$r = $db->query("SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_DEFAULT
                 FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'races'");
while ($x = $r->fetch_assoc()) { $schema[$x['COLUMN_NAME']] = $x; }

$db->query("CREATE TABLE IF NOT EXISTS races_bak_official2026 LIKE races");
$have = array();
$r = $db->query("SELECT id FROM races_bak_official2026");
while ($x = $r->fetch_assoc()) { $have[(int)$x['id']] = true; }

$backedUp = 0;
foreach ($before as $id => $row) {
    if (isset($have[$id])) { continue; }

    $bNow = date('Y-m-d H:i:s');
    $wanted = array(
        'id' => (int)$row['id'],
        'race_name' => (string)$row['race_name'],
        'circuit_name' => (string)$row['circuit_name'],
        'country' => (string)$row['country'],
        'race_date' => (string)$row['race_date'],
        'race_number' => (int)$row['race_number'],
        'status' => (string)$row['status'],
        'f1_race_id' => null,
        'results_fetched' => 0,
        'results_fetched_at' => null,
        'created_at' => $bNow,
        'updated_at' => $bNow,
    );

    $useCols = array();
    $useVals = array();
    $types = '';
    foreach ($schema as $col => $meta) {
        if (!array_key_exists($col, $wanted)) { continue; }
        $v = $wanted[$col];
        if ($v === null) {
            if ($meta['IS_NULLABLE'] === 'NO') {
                if ($meta['COLUMN_DEFAULT'] !== null) {
                    $v = $meta['COLUMN_DEFAULT'];
                } else {
                    $v = (strpos($meta['DATA_TYPE'], 'int') !== false) ? 0 : '';
                }
            }
        }
        if ($v === null) { $types .= 's'; } else { $types .= (is_int($v) ? 'i' : 's'); }
        $useCols[] = $col;
        $useVals[] = $v;
    }

    $sql = "INSERT INTO races_bak_official2026 (" . implode(',', $useCols) . ") VALUES ("
        . implode(',', array_fill(0, count($useCols), '?')) . ")";
    $ins = $db->prepare($sql);
    $refs = array();
    foreach ($useVals as $k => $v) { $refs[$k] = &$useVals[$k]; }
    array_unshift($refs, $types);
    call_user_func_array(array($ins, 'bind_param'), $refs);
    if (!$ins->execute()) {
        out("!! BACKUP FAILED for race id $id: " . $ins->error);
        out("!! No changes have been written. Aborting so nothing is lost.");
        exit(1);
    }
    $backedUp++;
}
out("Backed up $backedUp row(s) into races_bak_official2026.");

// --------------------------------------------------------------------- deletes
foreach ($remove as $id) {
    if (isset($before[$id])) {
        $db->query("DELETE FROM races WHERE id = " . (int)$id);
        out("Deleted race id $id ({$before[$id]['race_name']}).");
    }
}

// ------------------------------------- phase 1: park race_number (UNIQUE index)
$db->query("UPDATE races SET race_number = -race_number");

// ------------------------------------------------- phase 2: write final values
$up = $db->prepare("UPDATE races
    SET race_name = ?, circuit_name = ?, country = ?, race_date = ?, race_number = ?, status = ?
    WHERE id = ?");
$written = 0;
foreach ($calendar as $c) {
    $id = (int)$c['id'];
    $num = (int)$c['race_number'];
    $up->bind_param('ssssisi', $c['race_name'], $c['circuit_name'], $c['country'],
        $c['race_date'], $num, $c['status'], $id);
    if (!$up->execute()) {
        out("!! FAILED to write race id $id: " . $db->error);
        exit(1);
    }
    $written += $up->affected_rows;
}
out("Wrote $written race row(s).");

// ------------------------------------------------------------------ verify
out("");
out("=== VERIFY ===");
$r = $db->query("SELECT id, race_number, country, race_name, circuit_name, race_date, status FROM races ORDER BY race_date ASC");
printf("%-4s %-5s %-14s %-24s %-30s %-12s %s\n", 'id', 'rnd', 'country', 'race_name', 'circuit', 'date', 'status');
$count = 0;
while ($x = $r->fetch_assoc()) {
    $count++;
    printf("%-4s %-5s %-14s %-24s %-30s %-12s %s\n",
        $x['id'], $x['race_number'], $x['country'],
        substr($x['race_name'], 0, 23), substr($x['circuit_name'], 0, 29),
        $x['race_date'], $x['status']);
}
out("Total races: $count (official 2026 season has 23)");

$next = null;
if (!function_exists('getNextRace')) {
    require_once __DIR__ . '/../includes/functions.php';
}
$next = getNextRace();
out("");
if ($next) {
    out("getNextRace() now returns: id {$next['id']} | Round {$next['race_number']} | "
        . "{$next['race_name']} | {$next['circuit_name']} | {$next['race_date']}");
} else {
    out("getNextRace() returned NULL - check statuses/dates.");
}
