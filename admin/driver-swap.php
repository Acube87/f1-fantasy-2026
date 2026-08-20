<?php
/**
 * Admin Tool: Driver Substitution for a Race.
 *
 * Use when a driver is replaced for a race (e.g. Hadjar injured -> Lawson steps in).
 * It safely:
 *   1. Updates the `drivers` roster with the new team assignments
 *      (the outgoing driver is moved to e.g. "Reserve" so they sort last / are crossed out).
 *   2. Rewrites existing user predictions for the chosen race so a predicted driver
 *      who is no longer racing is re-pointed at their replacement, keeping the same
 *      predicted position. No scoring code needs to change — scoring matches driver_id.
 *
 * Works BEFORE or AFTER the prediction deadline.
 *
 * CLI usage:
 *   php admin/driver-swap.php --race=14 --swap=hadjar:lawson --in-team="Red Bull Racing" --out-team="Reserve"
 *   php admin/driver-swap.php --race=14 --swap=hadjar:lawson --in-team="Red Bull Racing" --out-team="Reserve" --apply
 */
require_once __DIR__ . '/../config.php';

$isCli = (PHP_SAPI === 'cli');

if ($isCli) {
    $opts = [];
    $args = array_slice($argv, 1);
    for ($i = 0; $i < count($args); $i++) {
        if (strpos($args[$i], '=') !== false) {
            list($k, $v) = explode('=', $args[$i], 2);
            $k = ltrim($k, '-');
            if ($k === 'swap') {
                $opts['swaps'][] = $v;
            } else {
                $opts[$k] = $v;
            }
        } else {
            $opts[ltrim($args[$i], '-')] = true;
        }
    }
    $raceId = isset($opts['race']) ? (int)$opts['race'] : 0;
    $swaps = $opts['swaps'] ?? [];
    $inTeam = isset($opts['in-team']) ? trim($opts['in-team']) : '';
    $outTeam = isset($opts['out-team']) && trim($opts['out-team']) !== '' ? trim($opts['out-team']) : 'Reserve';
    $apply = !empty($opts['apply']);
} else {
    require_once __DIR__ . '/../includes/auth.php';
    $user = getCurrentUser();
    if (!$user || empty($user['is_admin'])) {
        die('Unauthorized');
    }
    $raceId = isset($_POST['race_id']) ? (int)$_POST['race_id'] : 0;
    $swaps = [];
    if (!empty($_POST['swaps'])) {
        foreach (preg_split('/\r\n|\r|\n/', $_POST['swaps']) as $line) {
            $line = trim($line);
            if ($line === '') continue;
            if (strpos($line, ':') !== false) {
                $swaps[] = $line;
            } elseif (strpos($line, 'to') !== false) {
                $swaps[] = str_replace('to', ':', $line);
            }
        }
    }
    $inTeam = isset($_POST['in_team']) ? trim($_POST['in_team']) : '';
    $outTeam = isset($_POST['out_team']) && trim($_POST['out_team']) !== '' ? trim($_POST['out_team']) : 'Reserve';
    $apply = isset($_POST['apply']);
}

$db = getDB();

/** Resolve swaps into structured rows. */
function parseSwaps(array $raw) {
    $rows = [];
    foreach ($raw as $line) {
        $parts = array_map('trim', explode(':', $line));
        $outgoing = $parts[0] ?? '';
        $incoming = $parts[1] ?? '';
        if ($outgoing === '' || $incoming === '') continue;
        $rows[] = ['outgoing' => $outgoing, 'incoming' => $incoming];
    }
    return $rows;
}

function report($msg, $isCli = false) {
    if ($isCli) { echo $msg . "\n"; }
    else { echo htmlspecialchars($msg) . "\n"; }
}

/** Apply the substitution: roster updates + prediction rewrite, in one transaction. */
function applySwap($db, $raceId, $rows, $inTeam, $outTeam) {
    $summary = ['roster' => [], 'prediction_rewrites' => 0, 'conflict_expunges' => 0];
    $db->begin_transaction();
    try {
        foreach ($rows as $row) {
            $outId = $row['outgoing'];
            $inId  = $row['incoming'];

            // --- Resolve incoming driver (must exist in roster) ---
            $stmt = $db->prepare("SELECT driver_name, team FROM drivers WHERE id = ?");
            $stmt->bind_param('s', $inId);
            $stmt->execute();
            $in = $stmt->get_result()->fetch_assoc();
            if (!$in) {
                throw new Exception("Incoming driver '$inId' not found in drivers table. Add them to the roster first.");
            }
            $inName = $in['driver_name'];
            $inNewTeam = $inTeam !== '' ? $inTeam : ($in['team'] !== '' ? $in['team'] : $outTeam);

            // --- Update roster ---
            $stmt = $db->prepare("UPDATE drivers SET team = ? WHERE id = ?");
            $stmt->bind_param('ss', $inNewTeam, $inId);
            $stmt->execute();
            $summary['roster'][] = "$inId ({$inName}): {$in['team']} -> $inNewTeam";

            $stmt = $db->prepare("SELECT driver_name, team FROM drivers WHERE id = ?");
            $stmt->bind_param('s', $outId);
            $stmt->execute();
            $out = $stmt->get_result()->fetch_assoc();
            if (!$out) {
                throw new Exception("Outgoing driver '$outId' not found in drivers table.");
            }
            $stmt = $db->prepare("UPDATE drivers SET team = ? WHERE id = ?");
            $stmt->bind_param('ss', $outTeam, $outId);
            $stmt->execute();
            $summary['roster'][] = "$outId ({$out['driver_name']}): {$out['team']} -> $outTeam";

            // --- Rewrite predictions for this race ---
            // Any user who predicted the outgoing driver gets the incoming driver instead,
            // keeping their predicted_position. Prevent duplicates (unique_user_race_driver key).
            $affected = $db->prepare("SELECT DISTINCT user_id FROM predictions WHERE race_id = ? AND driver_id = ?");
            $affected->bind_param('is', $raceId, $outId);
            $affected->execute();
            $userIds = array_column($affected->get_result()->fetch_all(MYSQLI_ASSOC), 'user_id');

            if (!empty($userIds)) {
                // Drop any pre-existing incoming-driver pick for those users (avoids dup key errors).
                $place = implode(',', array_fill(0, count($userIds), '?'));
                $expunge = $db->prepare("DELETE FROM predictions WHERE race_id = ? AND driver_id = ? AND user_id IN ($place)");
                $expunge->bind_param('is' . str_repeat('i', count($userIds)), $raceId, $inId, ...$userIds);
                $summary['conflict_expunges'] += $expunge->execute() ? $db->affected_rows : 0;

                // Point outgoing predictions at the incoming driver.
                $rewrite = $db->prepare("UPDATE predictions SET driver_id = ?, driver_name = ? WHERE race_id = ? AND driver_id = ?");
                $rewrite->bind_param('ssis', $inId, $inName, $raceId, $outId);
                $rewrite->execute();
                $summary['prediction_rewrites'] += $db->affected_rows;
            }
        }
        $db->commit();
        return $summary;
    } catch (Throwable $e) {
        $db->rollback();
        throw $e;
    }
}

$race = null;
if ($raceId) {
    $stmt = $db->prepare("SELECT * FROM races WHERE id = ?");
    $stmt->bind_param('i', $raceId);
    $stmt->execute();
    $race = $stmt->get_result()->fetch_assoc();
}

$rows = parseSwaps($swaps);
$preview = ($rows && $race && !$apply);
$resultSummary = null;
$error = null;

if ($preview) {
    // Dry run: count how many predictions + users are affected.
    $previewData = ['per_user' => []];
    foreach ($rows as $row) {
        $affected = $db->prepare("SELECT DISTINCT user_id FROM predictions WHERE race_id = ? AND driver_id = ?");
        $affected->bind_param('is', $raceId, $row['outgoing']);
        $affected->execute();
        $userIds = array_column($affected->get_result()->fetch_all(MYSQLI_ASSOC), 'user_id');
        foreach ($userIds as $uid) {
            $u = $db->prepare("SELECT username FROM users WHERE id = ?");
            $u->bind_param('i', $uid);
            $u->execute();
            $uname = $u->get_result()->fetch_assoc()['username'] ?? "#$uid";
            $pred = $db->prepare("SELECT predicted_position FROM predictions WHERE race_id = ? AND driver_id = ? AND user_id = ?");
            $pred->bind_param('isi', $raceId, $row['outgoing'], $uid);
            $pred->execute();
            $pos = $pred->get_result()->fetch_assoc()['predicted_position'] ?? '?';
            $previewData['per_user'][] = "$uname: {$row['outgoing']} P$pos -> {$row['incoming']} P$pos";
        }
    }
    $previewData['total'] = count($previewData['per_user']);
} elseif ($rows && $race && $apply) {
    try {
        $resultSummary = applySwap($db, $raceId, $rows, $inTeam, $outTeam);
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

if ($isCli) {
    if (!$race) { report("Race not found (race_id=$raceId).", true); exit(1); }
    if (!$rows) { report("No valid swaps provided. Use --swap=outgoing:incoming (repeatable).", true); exit(1); }
    report("Race: #{$race['id']} {$race['race_name']} ({$race['status']})", true);
    foreach ($rows as $row) report("  Swap: {$row['outgoing']} -> {$row['incoming']}", true);
    if ($inTeam !== '') report("  Incoming team: $inTeam", true);
    if ($outTeam !== '') report("  Outgoing team: $outTeam", true);
    if ($error) { report("ERROR: $error", true); exit(1); }
    if ($preview) {
        report("PREVIEW: {$previewData['total']} prediction(s) would be rewritten.", true);
        foreach ($previewData['per_user'] as $l) report("  $l", true);
        if ($previewData['total'] === 0) report("No existing predictions to rewrite (roster update only).", true);
        report("Run again with --apply to apply.", true);
    } elseif ($resultSummary) {
        report("APPLIED:", true);
        foreach ($resultSummary['roster'] as $l) report("  ".$l, true);
        report("  Predictions rewritten: {$resultSummary['prediction_rewrites']}", true);
        report("  Conflicting picks removed: {$resultSummary['conflict_expunges']}", true);
    }
    exit(0);
}

$races = $db->query("SELECT id, race_name, country, race_date, status FROM races ORDER BY race_date ASC")->fetch_all(MYSQLI_ASSOC);
$drivers = $db->query("SELECT id, driver_name, team FROM drivers ORDER BY team, driver_name")->fetch_all(MYSQLI_ASSOC);
$defaultRaceId = $raceId ?: 14; // Dutch GP (Round 14) convenience default
$defaultSwaps = "hadjar:lawson";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Swap - Race Control</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../css/gaming-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="gaming-theme text-gray-200 min-h-screen flex items-center justify-center p-8">
    <div class="max-w-3xl w-full">
        <div class="mb-8">
            <a href="race-control.php" class="text-gray-400 hover:text-white text-sm transition">
                <i class="fas fa-arrow-left mr-2"></i>Back to Race Control
            </a>
        </div>

        <div class="g-card p-8 rounded-[2rem] border-t-4 border-t-purple-500">
            <h1 class="text-3xl font-black text-white italic uppercase mb-2">
                🔄 Driver Swap
            </h1>
            <p class="text-gray-400 text-sm mb-6">
                Re-point a driver who won't race (injury / signed out) to their replacement for a specific round.
                Existing predictions move with the same position. Works before <em>or</em> after the deadline.
            </p>

            <?php if ($error): ?>
                <div class="mb-6 p-4 rounded-xl border bg-red-500/10 border-red-500/30 text-red-400">
                    <p class="font-bold text-sm">❌ <?php echo htmlspecialchars($error); ?></p>
                </div>
            <?php endif; ?>

            <?php if ($resultSummary): ?>
                <div class="mb-6 p-4 rounded-xl border bg-green-500/10 border-green-500/30 text-green-400 space-y-1">
                    <p class="font-bold text-sm">✅ Applied successfully.</p>
                    <?php foreach ($resultSummary['roster'] as $l): ?>
                        <p class="text-xs">• <?php echo htmlspecialchars($l); ?></p>
                    <?php endforeach; ?>
                    <p class="text-xs">• Predictions rewritten: <strong><?php echo $resultSummary['prediction_rewrites']; ?></strong> (conflicts removed: <?php echo $resultSummary['conflict_expunges']; ?>)</p>
                </div>
            <?php endif; ?>

            <?php if ($preview): ?>
                <div class="mb-6 p-4 rounded-xl border bg-yellow-500/10 border-yellow-500/30 text-yellow-300">
                    <p class="font-bold text-sm">👀 Preview — <?php echo $previewData['total']; ?> prediction(s) affected</p>
                    <?php if ($previewData['total'] > 0): ?>
                        <?php foreach ($previewData['per_user'] as $l): ?>
                            <p class="text-xs mt-1">• <?php echo htmlspecialchars($l); ?></p>
                        <?php endforeach; ?>
                        <p class="text-xs mt-2">Click <strong>Apply</strong> below to finalise, or adjust first.</p>
                    <?php else: ?>
                        <p class="text-xs mt-1">No existing predictions to rewrite — only the roster will be updated.</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-6">
                    <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-3">Race</label>
                    <select name="race_id" class="w-full p-4 rounded-xl bg-black/30 border border-white/10 text-white font-bold focus:border-purple-500 focus:outline-none">
                        <?php foreach ($races as $raceRow): ?>
                            <option value="<?php echo $raceRow['id']; ?>" <?php echo $raceRow['id'] == $defaultRaceId ? 'selected' : ''; ?>>
                                <?php echo [$raceRow['status'] === 'completed' ? '✅' : ($raceRow['status'] === 'upcoming' ? '⏳' : '🏁')][0]; ?>
                                #<?php echo $raceRow['id']; ?> — <?php echo htmlspecialchars($raceRow['race_name']); ?> (<?php echo htmlspecialchars($raceRow['country']); ?>, <?php echo $raceRow['race_date']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-6">
                    <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-3">
                        Swaps <span class="text-yellow-400 normal-case font-medium">(one per line: outgoing:incoming)</span>
                    </label>
                    <textarea name="swaps" rows="3" class="w-full p-4 rounded-xl bg-black/30 border border-white/10 text-white font-mono text-sm focus:border-purple-500 focus:outline-none"><?php echo htmlspecialchars($defaultSwaps); ?></textarea>
                </div>

                <div class="grid md:grid-cols-2 gap-4 mb-6">
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-3">Incoming drivers' new team</label>
                        <input type="text" name="in_team" value="Red Bull Racing" class="w-full p-4 rounded-xl bg-black/30 border border-white/10 text-white font-bold focus:border-purple-500 focus:outline-none" placeholder="e.g. Red Bull Racing (blank = keep current)">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-3">Outgoing drivers' new team</label>
                        <input type="text" name="out_team" value="Reserve" class="w-full p-4 rounded-xl bg-black/30 border border-white/10 text-white font-bold focus:border-purple-500 focus:outline-none" placeholder="e.g. Reserve">
                    </div>
                </div>

                <p class="text-xs text-gray-400 mb-6">Current roster (for reference):<br>
                    <?php foreach ($drivers as $d): ?>
                        <span class="inline-block mr-2 mt-1 px-2 py-1 rounded bg-black/20 border border-white/5 text-[11px]"><?php echo htmlspecialchars($d['driver_name']); ?> — <span class="text-purple-400"><?php echo htmlspecialchars($d['team']); ?></span></span>
                    <?php endforeach; ?>
                </p>

                <button type="submit" class="w-full mb-3 py-4 bg-yellow-500 hover:bg-yellow-400 text-black font-black text-lg rounded-2xl transition transform hover:scale-[1.02]">
                    👀 PREVIEW
                </button>
                <button type="submit" name="apply" value="1" class="w-full py-4 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white font-black text-lg rounded-2xl transition transform hover:scale-[1.02]">
                    <i class="fas fa-check"></i> APPLY
                </button>
            </form>
        </div>
    </div>
</body>
</html>