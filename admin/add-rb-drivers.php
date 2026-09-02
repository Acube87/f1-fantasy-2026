<?php
/**
 * Admin Tool: Ensure both Racing Bulls seats are in the roster.
 *
 * Adds Tsunoda / Lindblad if missing and normalises their team to 'Racing Bulls'.
 * Use after a driver substitute (e.g. Lawson moved up to Red Bull) so the pick list
 * always shows the full 22-car grid and scoring stays 1:1 with results.
 *
 * Safe / idempotent: no duplicates, no data loss. No other tables touched.
 *
 * CLI:
 *   php admin/add-rb-drivers.php            (preview)
 *   php admin/add-rb-drivers.php --apply    (apply)
 */
require_once __DIR__ . '/../config.php';

$isCli = (PHP_SAPI === 'cli');

if ($isCli) {
    $apply = in_array('--apply', $argv, true);
} else {
    require_once __DIR__ . '/../includes/auth.php';
    $user = getCurrentUser();
    if (!$user || empty($user['is_admin'])) {
        die('Unauthorized');
    }
    $apply = isset($_POST['apply']) || isset($_GET['apply']);
}

$db = getDB();

$targets = [
    ['tsunoda', 'Yuki Tsunoda'],
    ['lindblad', 'Arvid Lindblad'],
];

// Run the upsert only when applying.
$added = 0;
$updated = 0;
$rows = [];

if ($apply) {
    $sel = $db->prepare("SELECT team FROM drivers WHERE id = ?");
    $upd = $db->prepare("UPDATE drivers SET team = 'Racing Bulls' WHERE id = ?");
    $ins = $db->prepare("INSERT INTO drivers (id, driver_name, team) VALUES (?, ?, 'Racing Bulls')");

    foreach ($targets as $t) {
        $id = $t[0]; $name = $t[1];
        $sel->bind_param('s', $id);
        $sel->execute();
        $existing = $sel->get_result()->fetch_assoc();
        if ($existing) {
            $rows[] = "$id: already exists (team: {$existing['team']}) — skipped";
        } else {
            $ins->bind_param('ss', $id, $name);
            $ins->execute();
            $added++;
            $rows[] = "$id: added ($name)";
        }
    }
}

$total = (int)$db->query("SELECT COUNT(*) c FROM drivers")->fetch_assoc()['c'];
$pickable = (int)$db->query("SELECT COUNT(*) c FROM drivers WHERE team NOT LIKE '%Reserve%' OR team IS NULL")->fetch_assoc()['c'];
$drivers = $db->query("SELECT id, driver_name, team FROM drivers ORDER BY team, driver_name")->fetch_all(MYSQLI_ASSOC);

if ($isCli) {
    if (!$apply) {
        echo "PREVIEW: would ensure both Racing Bulls seats (Tsunoda + Lindblad).\n";
        echo "Current: roster={$total} | pickable={$pickable} (should be 22 = full grid)\n";
        echo "Run again with --apply to apply.\n";
        exit(0);
    }
    echo "APPLIED: added={$added} updated={$updated}\n";
    foreach ($rows as $r) echo "  $r\n";
    echo "Roster total: $total | Pickable: $pickable (should be 22 = full grid)\n";
    exit(0);
}

$races = $db->query("SELECT id, race_name, country, race_date, status FROM races ORDER BY race_date ASC")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix Racing Bulls Roster - Race Control</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../css/gaming-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="gaming-theme text-gray-200 min-h-screen flex items-center justify-center p-8">
    <div class="max-w-2xl w-full">
        <div class="mb-8">
            <a href="race-control.php" class="text-gray-400 hover:text-white text-sm transition">
                <i class="fas fa-arrow-left mr-2"></i>Back to Race Control
            </a>
        </div>

        <div class="g-card p-8 rounded-[2rem] border-t-4 border-t-purple-500">
            <h1 class="text-3xl font-black text-white italic uppercase mb-2">
                🏎️ Racing Bulls Seats
            </h1>
            <p class="text-gray-400 text-sm mb-6">
                Ensures both Racing Bulls seats (Tsunoda + Lindblad) are in the driver roster
                so the pick list always shows the full 22-car grid. Only adds missing drivers —
                never removes or overwrites picks. Safe to run anytime.
            </p>

            <?php if ($apply): ?>
            <div class="mb-6 p-4 rounded-xl border bg-green-500/10 border-green-500/30 text-green-400">
                <p class="font-bold text-sm">✅ Racing Bulls roster fixed.</p>
                <p class="text-xs mt-1">Added: <?php echo $added; ?> · Updated: <?php echo $updated; ?></p>
                <ul class="text-xs mt-2 space-y-1">
                    <?php foreach ($rows as $r): ?><li>• <?php echo htmlspecialchars($r); ?></li><?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <div class="grid grid-cols-2 gap-4 mb-6 text-center">
                <div class="p-4 rounded-xl bg-black/30 border border-white/10">
                    <div class="text-3xl font-black text-purple-400"><?php echo $total; ?></div>
                    <div class="text-xs text-gray-400 mt-1">Roster total</div>
                </div>
                <div class="p-4 rounded-xl bg-black/30 border border-white/10">
                    <div class="text-3xl font-black text-green-400"><?php echo $pickable; ?></div>
                    <div class="text-xs text-gray-400 mt-1">Pickable (should be 22 = full grid)</div>
                </div>
            </div>

            <p class="text-xs text-gray-400 mb-6">Current roster:<br>
                <?php foreach ($drivers as $d): ?>
                    <span class="inline-block mr-2 mt-1 px-2 py-1 rounded bg-black/20 border border-white/5 text-[11px]"><?php echo htmlspecialchars($d['driver_name']); ?> — <span class="text-purple-400"><?php echo htmlspecialchars($d['team']); ?></span></span>
                <?php endforeach; ?>
            </p>

            <a href="?apply=1" onclick="return confirm('Add the missing Racing Bulls driver(s) to the roster? Existing picks are never touched.');"
               class="block w-full text-center py-4 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white font-black text-lg rounded-2xl transition transform hover:scale-[1.02]">
                <i class="fas fa-tools"></i> FIX RACING BULLS ROSTER
            </a>
        </div>
    </div>
</body>
</html>