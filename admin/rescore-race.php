<?php
/**
 * Quick Admin Tool: Re-run scoring for a completed race.
 * Use this after fixing the scoring engine to recalculate without re-entering results.
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/achievements.php';

$user = getCurrentUser();
if (!$user || $user['username'] !== 'Angrycube') {
    die('Unauthorized');
}

$db = getDB();

// Fetch all races for the selector
$races = $db->query("SELECT id, race_name, country, status FROM races ORDER BY race_date ASC")->fetch_all(MYSQLI_ASSOC);

$message = null;
$messageType = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['race_id'])) {
    $raceId = (int)$_POST['race_id'];

    // Verify race exists and has results
    $race = $db->query("SELECT * FROM races WHERE id = $raceId")->fetch_assoc();
    if (!$race || $race['status'] !== 'completed') {
        $message = "❌ Race not found or not completed yet.";
        $messageType = 'error';
    } else {
        // Re-run scoring
        $result = calculateRaceScores($raceId);

        // Re-check achievements
        $users = $db->query("SELECT DISTINCT user_id FROM predictions WHERE race_id = $raceId")->fetch_all(MYSQLI_ASSOC);
        foreach ($users as $u) {
            checkAndUnlockAchievements($u['user_id'], $db);
        }

        if ($result['success']) {
            $message = "✅ Re-scored " . htmlspecialchars($race['race_name']) . " successfully! Constructor bonuses now use constructor_name as fallback.";
            $messageType = 'success';
        } else {
            $message = "❌ Scoring failed: " . htmlspecialchars($result['message']);
            $messageType = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Re-Score Race - Race Control</title>
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
                ⚡ Re-Score Race
            </h1>
            <p class="text-gray-400 text-sm mb-8">
                Recalculates all user scores for a completed race <strong class="text-white">without re-entering results</strong>. 
                Use this after fixing the scoring engine.
            </p>

            <?php if ($message): ?>
                <div class="mb-6 p-4 rounded-xl border <?php echo $messageType === 'success' ? 'bg-green-500/10 border-green-500/30 text-green-400' : 'bg-red-500/10 border-red-500/30 text-red-400'; ?>">
                    <p class="font-bold text-sm"><?php echo $message; ?></p>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-6">
                    <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-3">
                        Select Race to Re-Score
                    </label>
                    <select name="race_id" class="w-full p-4 rounded-xl bg-black/30 border border-white/10 text-white font-bold focus:border-purple-500 focus:outline-none">
                        <option value="">-- Select a completed race --</option>
                        <?php foreach ($races as $race): ?>
                            <?php if ($race['status'] === 'completed'): ?>
                            <option value="<?php echo $race['id']; ?>">
                                ✅ <?php echo htmlspecialchars($race['race_name']); ?> (<?php echo htmlspecialchars($race['country']); ?>)
                            </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="bg-yellow-500/10 border border-yellow-500/30 rounded-xl p-4 mb-6">
                    <p class="text-xs text-yellow-300">
                        <strong>⚠️ What this does:</strong> Deletes and recalculates all scores for the selected race. 
                        Race results (driver positions) are <strong>not touched</strong>. 
                        Total leaderboard points will update automatically.
                    </p>
                </div>

                <button type="submit" class="w-full py-4 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white font-black text-lg rounded-2xl transition transform hover:scale-[1.02] flex items-center justify-center gap-3">
                    <i class="fas fa-calculator"></i>
                    RECALCULATE SCORES
                </button>
            </form>
        </div>
    </div>
</body>
</html>
