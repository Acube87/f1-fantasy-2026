<?php
require_once __DIR__ . '/../config.php';

$isCli = (PHP_SAPI === 'cli');

$states = [
    'standard' => [
        'label' => 'Standard grid — Hadjar fit and racing',
        'teams' => ['hadjar' => 'Red Bull Racing', 'lawson' => 'Racing Bulls', 'tsunoda' => 'Reserve'],
    ],
    'substitute' => [
        'label' => 'Substitute grid — Hadjar injured, Lawson filling in',
        'teams' => ['hadjar' => 'Reserve', 'lawson' => 'Red Bull Racing', 'tsunoda' => 'Racing Bulls'],
    ],
];

$substitutions = [
    'standard' => ['from' => 'tsunoda', 'to' => 'hadjar'],
    'substitute' => ['from' => 'hadjar', 'to' => 'lawson'],
];

$displayOrder = ['hadjar', 'lawson', 'tsunoda'];

if ($isCli) {
    $apply = false;
    $to = null;
    $raceId = 0;
    foreach (array_slice($argv, 1) as $arg) {
        if ($arg === '--apply') { $apply = true; }
        elseif (strpos($arg, '--to=') === 0) { $to = substr($arg, 5); }
        elseif (strpos($arg, '--race=') === 0) { $raceId = (int)substr($arg, 7); }
    }
} else {
    require_once __DIR__ . '/../includes/auth.php';
    $user = getCurrentUser();
    if (!$user || empty($user['is_admin'])) {
        die('Unauthorized');
    }
    $apply = isset($_GET['apply']) || isset($_POST['apply']);
    $to = isset($_GET['to']) ? trim($_GET['to']) : null;
    $raceId = isset($_GET['race_id']) ? (int)$_GET['race_id'] : 0;
}

$db = getDB();

$stmt = $db->prepare("SELECT id, driver_name, team FROM drivers WHERE id IN ('hadjar','lawson','tsunoda','lindblad')");
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$current = [];
foreach ($rows as $r) {
    $current[$r['id']] = ['name' => $r['driver_name'], 'team' => $r['team']];
}

foreach ($displayOrder as $id) {
    if (!isset($current[$id])) {
        die("Roster incomplete: '$id' is missing from the drivers table. Run admin/add-rb-drivers.php first.");
    }
}

$currentState = null;
foreach ($states as $key => $state) {
    $match = true;
    foreach ($displayOrder as $id) {
        if ($current[$id]['team'] !== $state['teams'][$id]) { $match = false; break; }
    }
    if ($match) { $currentState = $key; break; }
}

if ($currentState === null) {
    $currentState = 'inconsistent';
}

if (!$raceId) {
    $next = $db->query("SELECT id, race_name FROM races WHERE status = 'upcoming' AND race_date >= CURDATE() ORDER BY race_date ASC LIMIT 1")->fetch_assoc();
    if (!$next) {
        $next = $db->query("SELECT id, race_name FROM races WHERE status = 'upcoming' ORDER BY race_date ASC LIMIT 1")->fetch_assoc();
    }
    $raceId = $next ? (int)$next['id'] : 0;
    $raceName = $next ? $next['race_name'] : 'unknown';
} else {
    $stmt = $db->prepare("SELECT race_name FROM races WHERE id = ?");
    $stmt->bind_param('i', $raceId);
    $stmt->execute();
    $raceName = $stmt->get_result()->fetch_assoc()['race_name'] ?? ('race #' . $raceId);
}

$races = $db->query("SELECT id, race_name, race_date, status FROM races ORDER BY race_date DESC")->fetch_all(MYSQLI_ASSOC);

$rowsOut = [];
$totalChanges = 0;
$targetState = ($to !== null && isset($states[$to])) ? $states[$to] : null;

if ($targetState) {
    foreach ($displayOrder as $id) {
        $from = $current[$id]['team'];
        $dest = $targetState['teams'][$id];
        $rowsOut[] = [
            'id' => $id,
            'name' => $current[$id]['name'],
            'from' => $from,
            'to' => $dest,
            'changed' => $from !== $dest,
        ];
        if ($from !== $dest) { $totalChanges++; }
    }
}

$affected = 0;
$sub = $targetState ? $substitutions[$to] : null;
if ($sub && $raceId) {
    $stmt = $db->prepare("SELECT COUNT(*) c FROM predictions WHERE race_id = ? AND driver_id = ?");
    $stmt->bind_param('is', $raceId, $sub['from']);
    $stmt->execute();
    $affected = (int)$stmt->get_result()->fetch_assoc()['c'];
}

$pickable = function () use ($db) {
    return (int)$db->query("SELECT COUNT(*) c FROM drivers WHERE team NOT LIKE '%Reserve%' OR team IS NULL")->fetch_assoc()['c'];
};
$pickableNow = $pickable();

$result = null;
$error = null;

if ($apply && $targetState) {
    $db->begin_transaction();
    try {
        foreach ($displayOrder as $id) {
            $dest = $targetState['teams'][$id];
            if ($current[$id]['team'] === $dest) { continue; }
            $stmt = $db->prepare("UPDATE drivers SET team = ? WHERE id = ?");
            $stmt->bind_param('ss', $dest, $id);
            $stmt->execute();
        }

        $sub = $substitutions[$to];
        $stmt = $db->prepare("SELECT driver_name FROM drivers WHERE id = ?");
        $stmt->bind_param('s', $sub['to']);
        $stmt->execute();
        $subName = $stmt->get_result()->fetch_assoc()['driver_name'];

        $expunged = 0;
        $rewritten = 0;

        if ($raceId) {
            $del = $db->prepare("DELETE p FROM predictions p
                JOIN predictions inp ON inp.race_id = p.race_id AND inp.user_id = p.user_id AND inp.driver_id = ?
                WHERE p.race_id = ? AND p.driver_id = ?");
            $del->bind_param('sis', $sub['to'], $raceId, $sub['from']);
            $del->execute();
            $expunged = $db->affected_rows;

            $upd = $db->prepare("UPDATE predictions SET driver_id = ?, driver_name = ? WHERE race_id = ? AND driver_id = ?");
            $upd->bind_param('ssis', $sub['to'], $subName, $raceId, $sub['from']);
            $upd->execute();
            $rewritten = $db->affected_rows;
        }

        $db->commit();
        $result = [
            'state' => $to,
            'label' => $targetState['label'],
            'expunged' => $expunged,
            'rewritten' => $rewritten,
            'pickable' => $pickable(),
        ];
        $currentState = $to;
    } catch (Throwable $e) {
        $db->rollback();
        $error = $e->getMessage();
    }
}

if ($isCli) {
    if ($error) {
        echo "ERROR: $error\n";
        exit(1);
    }
    if ($result) {
        echo "APPLIED ({$result['state']}): {$result['label']}\n";
        foreach ($displayOrder as $id) {
            echo "  {$current[$id]['name']}: -> {$states[$result['state']]['teams'][$id]}\n";
        }
        echo "Predictions for {$raceName}: {$result['rewritten']} rewritten, {$result['expunged']} duplicate(s) removed\n";
        echo "Pickable: {$result['pickable']}\n";
        exit(0);
    }

    echo "Current state: " . ($currentState === 'inconsistent' ? 'INCONSISTENT (mixed)' : $states[$currentState]['label']) . "\n";
    foreach ($displayOrder as $id) {
        echo "  {$current[$id]['name']}: {$current[$id]['team']}\n";
    }
    echo "Target race for prediction fixes: #{$raceId} ({$raceName})\n";
    echo "Pickable: {$pickableNow}\n\n";
    if ($currentState === 'inconsistent') {
        echo "Lineup does not match a known state. Choose an explicit target:\n";
    }
    foreach ($states as $key => $state) {
        $sub = $substitutions[$key];
        echo "  --to={$key}  " . ($key === $currentState ? '[CURRENT] ' : '') . $state['label'] . "\n";
        echo "             would rewrite {$sub['from']} picks -> {$sub['to']} for race #{$raceId}\n";
    }
    echo "\nPreview only. Add --apply to execute.\n";
    exit(0);
}

function h($s) { return htmlspecialchars($s); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lineup Switch &mdash; Race Control</title>
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
            <i class="fas fa-right-left"></i> Lineup Switch
        </h1>
        <p class="text-gray-400 text-sm mb-6">
            One control for the Hadjar / Lawson / Tsunoda seats. Picks a target lineup and repairs
            any picks for the driver who drops to reserve. Existing picks are never deleted when you
            already chose the incoming driver yourself.
        </p>

        <?php if ($error): ?>
        <div class="mb-6 p-4 rounded-xl border bg-red-500/10 border-red-500/30 text-red-400">
            <p class="font-bold text-sm"><?= h($error) ?></p>
        </div>
        <?php endif; ?>

        <?php if ($result): ?>
        <div class="mb-6 p-4 rounded-xl border bg-green-500/10 border-green-500/30 text-green-400">
            <p class="font-bold text-sm"><?= h(ucfirst($result['state'])) ?> lineup applied.</p>
            <p class="text-xs mt-1 text-green-300/80">
                <?= $result['rewritten'] ?> pick(s) rewritten &middot;
                <?= $result['expunged'] ?> duplicate(s) removed &middot;
                <?= $result['pickable'] ?> pickable drivers
            </p>
        </div>
        <?php endif; ?>

        <div class="mb-6 p-4 rounded-xl bg-black/30 border border-white/10">
            <p class="text-xs text-gray-400 uppercase tracking-widest mb-3">Current lineup</p>
            <?php if ($currentState === 'inconsistent'): ?>
                <p class="text-sm text-yellow-400 font-bold mb-3">Mixed state &mdash; not matching a known grid. Pick an explicit target below.</p>
            <?php else: ?>
                <p class="text-sm text-white font-bold mb-3"><?= h($states[$currentState]['label']) ?></p>
            <?php endif; ?>
            <?php foreach ($displayOrder as $id): ?>
            <div class="flex items-center justify-between py-1.5 border-t border-white/5">
                <span class="text-sm text-gray-200"><?= h($current[$id]['name']) ?></span>
                <span class="text-xs font-mono <?= $current[$id]['team'] === 'Reserve' ? 'text-gray-500' : 'text-purple-400' ?>">
                    <?= h($current[$id]['team']) ?>
                </span>
            </div>
            <?php endforeach; ?>
            <div class="flex items-center justify-between py-1.5 border-t border-white/5">
                <span class="text-sm text-gray-200">Arvid Lindblad</span>
                <span class="text-xs font-mono text-gray-500"><?= h($current['lindblad']['team'] ?? 'Racing Bulls') ?></span>
            </div>
        </div>

        <form method="get" class="mb-6">
            <label class="block text-xs text-gray-400 uppercase tracking-widest mb-2">
                Target race for pick repairs
            </label>
            <select name="race_id" class="w-full mb-2" onchange="this.form.submit()">
                <?php foreach ($races as $r): ?>
                <option value="<?= (int)$r['id'] ?>" <?= (int)$r['id'] === $raceId ? 'selected' : '' ?>>
                    <?= h($r['race_name']) ?> &mdash; <?= h($r['race_date']) ?> (<?= h($r['status']) ?>)
                </option>
                <?php endforeach; ?>
            </select>
        </form>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <?php foreach ($states as $key => $state): ?>
            <?php $isCurrent = $key === $currentState; ?>
            <div class="p-5 rounded-2xl border <?= $isCurrent ? 'border-green-500/40 bg-green-500/5' : 'border-white/10 bg-black/20' ?>">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-sm font-black uppercase tracking-wide <?= $isCurrent ? 'text-green-400' : 'text-white' ?>">
                        <?= h($key) ?>
                    </span>
                    <?php if ($isCurrent): ?>
                    <span class="text-[10px] font-bold uppercase tracking-widest text-green-400">Current</span>
                    <?php endif; ?>
                </div>
                <p class="text-xs text-gray-400 mb-4 leading-relaxed"><?= h($state['label']) ?></p>

                <div class="text-xs font-mono mb-4">
                    <?php foreach ($displayOrder as $id): ?>
                    <div class="flex justify-between py-1 border-t border-white/5">
                        <span class="text-gray-400"><?= h($current[$id]['name']) ?></span>
                        <span class="<?= $state['teams'][$id] === 'Reserve' ? 'text-gray-500' : 'text-gray-300' ?>">
                            <?= h($state['teams'][$id]) ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>

                <?php $sub = $substitutions[$key]; ?>
                <?php
                $stmt = $db->prepare("SELECT COUNT(*) c FROM predictions WHERE race_id = ? AND driver_id = ?");
                $stmt->bind_param('is', $raceId, $sub['from']);
                $stmt->execute();
                $count = (int)$stmt->get_result()->fetch_assoc()['c'];
                ?>
                <p class="text-[11px] text-gray-500 mb-4">
                    <?= $count ?> existing pick(s) for <?= h($sub['from']) ?> &rarr; would move to <?= h($sub['to']) ?>
                </p>

                <?php if ($isCurrent): ?>
                <div class="text-center py-3 rounded-xl border border-green-500/30 text-green-400 text-xs font-bold uppercase tracking-widest">
                    Already active
                </div>
                <?php else: ?>
                <a href="?to=<?= h($key) ?>&race_id=<?= (int)$raceId ?>"
                   class="block text-center py-3 rounded-xl bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white font-black text-xs uppercase tracking-widest transition">
                    Switch to <?= h($key) ?>
                </a>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if ($targetState && $currentState !== $to): ?>
        <div class="mt-6 p-5 rounded-2xl border border-purple-500/30 bg-purple-500/5">
            <p class="text-xs text-gray-400 uppercase tracking-widest mb-3">Pending change &rarr; <?= h($to) ?></p>
            <table class="w-full text-sm mb-4">
                <thead><tr class="text-gray-500 text-[10px] uppercase tracking-widest">
                    <th class="text-left pb-2">Driver</th><th class="text-left pb-2">From</th><th class="text-left pb-2">To</th>
                </tr></thead>
                <tbody>
                <?php foreach ($rowsOut as $r): ?>
                <tr class="border-t border-white/5">
                    <td class="py-1.5 text-white font-bold"><?= h($r['name']) ?></td>
                    <td class="py-1.5 <?= $r['changed'] ? 'text-gray-300' : 'text-gray-600' ?>"><?= h($r['from']) ?></td>
                    <td class="py-1.5 <?= $r['changed'] ? 'text-purple-400 font-bold' : 'text-gray-600' ?>"><?= h($r['to']) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <p class="text-[11px] text-gray-400 mb-4">
                <?= $totalChanges ?> roster change(s). <?= $affected ?> pick(s) on
                <strong class="text-gray-200"><?= h($raceName) ?></strong> will be moved off
                <strong class="text-gray-200"><?= h($sub['from']) ?></strong>.
                <?php if ($affected === 0): ?>
                <span class="text-green-400">No picks at risk.</span>
                <?php endif; ?>
            </p>
            <a href="?to=<?= h($to) ?>&race_id=<?= (int)$raceId ?>&apply=1"
               onclick="return confirm('Apply the <?= h($to) ?> lineup to <?= h($raceName) ?>?\n\n<?= $affected ?> pick(s) will be repaired.');"
               class="block w-full text-center py-4 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white font-black text-lg rounded-2xl transition">
                <i class="fas fa-bolt"></i> APPLY <?= strtoupper(h($to)) ?> LINEUP
            </a>
        </div>
        <?php endif; ?>

        <p class="text-[11px] text-gray-500 mt-6 leading-relaxed">
            Scoring is matched on <span class="font-mono">driver_id</span>, so completed race results
            are never affected by a lineup switch. Only the pick list for the target race is repaired.
        </p>
    </div>
</div>
</body>
</html>