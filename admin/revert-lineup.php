<?php
/**
 * Admin Tool: Apply the required 2026 driver lineup (Monza).
 *
 * Restores the original22-car grid:
 *   - Hadjar  → Red Bull Racing
 *   - Lawson  → Racing Bulls
 *   - Tsunoda → Reserve
 * Does NOT touch predictions — existing user picks remain intact.
 * Safe to run multiple times (idempotent).
 *
 * CLI:
 *   php admin/revert-lineup.php            (preview)
 *   php admin/revert-lineup.php --apply    (apply)
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
    $apply = isset($_GET['apply']) || isset($_POST['apply']);
}

$db = getDB();

// The three drivers affected by the required lineup (Hadjar still out with a wrist
// injury; Lawson in at Red Bull for a 2nd straight event; Tsunoda at Racing Bulls).
$reverts = [
    ['lawson', 'Liam Lawson',  'Red Bull Racing'],
    ['tsunoda','Yuki Tsunoda', 'Racing Bulls'],
    ['hadjar', 'Isack Hadjar', 'Reserve'],
];

// Snapshot current state.
$snap = [];
foreach ($reverts as $rev) {
    $stmt = $db->prepare("SELECT team FROM drivers WHERE id = ?");
    $stmt->bind_param('s', $rev[0]);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $snap[] = [
        'id'      => $rev[0],
        'name'    => $rev[1],
        'before'  => $row ? $row['team'] : 'NOT IN DB',
        'after'   => $rev[2],
        'changed' => $row && $row['team'] !== $rev[2],
    ];
}

if ($apply) {
    $upd = $db->prepare("UPDATE drivers SET team = ? WHERE id = ?");
    foreach ($reverts as $rev) {
        $upd->bind_param('ss', $rev[2], $rev[0]);
        $upd->execute();
    }
}

$total    = (int)$db->query("SELECT COUNT(*) c FROM drivers")->fetch_assoc()['c'];
$pickable = (int)$db->query("SELECT COUNT(*) c FROM drivers WHERE team NOT LIKE '%Reserve%' OR team IS NULL")->fetch_assoc()['c'];
$drivers  = $db->query("SELECT id, driver_name, team FROM drivers ORDER BY team, driver_name")->fetch_all(MYSQLI_ASSOC);

if ($isCli) {
    if (!$apply) {
        echo "PREVIEW: apply required lineup.\n";
        foreach ($snap as $s) {
            $arrow = $s['changed'] ? " {$s['before']} →" : " (already)";
            echo "  {$s['name']}:{$arrow} {$s['after']}\n";
        }
        echo "Pickable: $pickable (expect 22)\n";
        echo "Run with --apply to apply.\n";
        exit(0);
    }
    echo "APPLIED: lineup updated to required state.\n";
    foreach ($snap as $s) {
        $note = $s['changed'] ? "updated" : "already correct";
        echo "  {$s['name']}: {$s['before']} → {$s['after']} ($note)\n";
    }
    echo "Roster: $total total | $pickable pickable (22 = full grid)\n";
    exit(0);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apply Lineup - Race Control</title>
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
                🔄 Apply Required Lineup
            </h1>
            <p class="text-gray-400 text-sm mb-6">
                Applies the confirmed lineup for the Italian GP (Monza).
                Existing user predictions are <strong class="text-white">never touched</strong>.
            </p>

            <?php if ($apply): ?>
            <div class="mb-6 p-4 rounded-xl border bg-green-500/10 border-green-500/30 text-green-400">
                <p class="font-bold text-sm">✅ Lineup updated to the required state.</p>
            </div>
            <?php endif; ?>

            <div class="mb-6">
                <p class="text-xs text-gray-400 uppercase tracking-widest mb-3">Changes</p>
                <table class="w-full text-sm">
                    <thead><tr class="text-gray-500 text-xs"><th class="text-left pb-2">Driver</th><th class="text-left pb-2">Before</th><th class="pb-2">→</th><th class="text-left pb-2">After</th></tr></thead>
                    <tbody>
                    <?php foreach ($snap as $s): ?>
                    <tr class="border-t border-white/5">
                        <td class="py-2 text-white font-bold"><?php echo $s['name']; ?></td>
                        <td class="py-2 text-gray-400"><?php echo $s['before']; ?></td>
                        <td class="py-2 text-center text-gray-500"><?php echo $s['changed'] ? '→' : '='; ?></td>
                        <td class="py-2 <?php echo $s['changed'] ? 'text-green-400 font-bold' : 'text-gray-500'; ?>"><?php echo $s['after']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-6 text-center">
                <div class="p-4 rounded-xl bg-black/30 border border-white/10">
                    <div class="text-3xl font-black text-purple-400"><?php echo $total; ?></div>
                    <div class="text-xs text-gray-400 mt-1">Roster total</div>
                </div>
                <div class="p-4 rounded-xl bg-black/30 border border-white/10">
                    <div class="text-3xl font-black text-green-400"><?php echo $pickable; ?></div>
                    <div class="text-xs text-gray-400 mt-1">Pickable (should be 22)</div>
                </div>
            </div>

            <p class="text-xs text-gray-400 mb-6">Full roster after revert:<br>
                <?php foreach ($drivers as $d): ?>
                    <span class="inline-block mr-2 mt-1 px-2 py-1 rounded bg-black/20 border border-white/5 text-[11px]"><?php echo htmlspecialchars($d['driver_name']); ?> — <span class="text-purple-400"><?php echo htmlspecialchars($d['team']); ?></span></span>
                <?php endforeach; ?>
            </p>

            <a href="?apply=1" onclick="return confirm('Apply the required lineup? Predictions are NOT affected.');"
               class="block w-full text-center py-4 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white font-black text-lg rounded-2xl transition transform hover:scale-[1.02]">
                <i class="fas fa-undo"></i> APPLY REQUIRED LINEUP
            </a>
        </div>
    </div>
</body>
</html>