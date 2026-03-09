<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$user = getCurrentUser();
if (!$user) {
    header('Location: ../login.php');
    exit;
}

$db = getDB();

// Ensure Angrycube is always admin (special case for main user)
if ($user['username'] === 'Angrycube') {
    // Make sure they're marked as admin
    $db->query("UPDATE users SET is_admin = 1 WHERE username = 'Angrycube'");
    $user['is_admin'] = 1;
} else {
    // For other users, require admin access
    requireAdmin();
}

$message = '';
$success = false;

// Handle manual debrief generation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['race_id'])) {
    $raceId = (int)$_POST['race_id'];
    
    // Verify race exists and is completed
    $raceStmt = $db->prepare("SELECT id, race_name, status FROM races WHERE id = ?");
    $raceStmt->bind_param("i", $raceId);
    $raceStmt->execute();
    $race = $raceStmt->get_result()->fetch_assoc();
    
    if (!$race) {
        $message = "Race not found!";
    } elseif ($race['status'] !== 'completed') {
        $message = "Race must be completed before creating debrief!";
    } else {
        // Check if debrief already exists
        $existsStmt = $db->prepare("SELECT id FROM posts WHERE race_id = ?");
        $existsStmt->bind_param("i", $raceId);
        $existsStmt->execute();
        $existing = $existsStmt->get_result()->fetch_assoc();
        
        if ($existing) {
            $message = "Debrief already exists for this race! Removing old one and creating new...";
            $db->query("DELETE FROM posts WHERE race_id = $raceId");
        }
        
        // Create the debrief
        if (createPostRaceDebrief($raceId, $db)) {
            $message = "✅ Post-race debrief created successfully for " . htmlspecialchars($race['race_name']) . "!";
            $success = true;
        } else {
            $message = "❌ Error creating debrief. Please check the database.";
        }
    }
}

// Get completed races without debriefs
$completedRaces = $db->query("
    SELECT r.id, r.race_name, r.country, r.race_date, 
           CASE WHEN p.id IS NOT NULL THEN 'Yes' ELSE 'No' END as has_debrief
    FROM races r
    LEFT JOIN posts p ON r.id = p.race_id
    WHERE r.status = 'completed'
    ORDER BY r.race_date DESC
");
$races = $completedRaces->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Race Debriefs - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../css/gaming-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-slate-950 text-white font-sans">
    <div class="min-h-screen">
        <!-- Navigation -->
        <nav class="border-b border-white/10 sticky top-0 z-50 bg-slate-950/95 backdrop-blur-sm">
            <div class="max-w-7xl mx-auto px-4 py-3 flex items-center justify-between">
                <a href="race-control.php" class="flex items-center gap-2 hover:opacity-80 transition">
                    <i class="fas fa-chevron-left"></i>
                    <span class="font-bold text-orange-500">Back to Race Control</span>
                </a>
                <h1 class="text-2xl font-bold text-orange-500 flex items-center gap-2">
                    <i class="fas fa-wand-magic-sparkles"></i> Generate Race Debriefs
                </h1>
                <div class="w-32"></div>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="max-w-4xl mx-auto px-4 py-8">
            <!-- Message -->
            <?php if ($message): ?>
                <div class="mb-6 p-4 rounded-lg border <?php echo $success ? 'bg-emerald-900/20 border-emerald-500/30 text-emerald-300' : 'bg-red-900/20 border-red-500/30 text-red-300'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <!-- Info Card -->
            <div class="bg-gradient-to-br from-slate-900 to-slate-950 border border-orange-500/20 rounded-lg p-6 mb-8">
                <h2 class="text-xl font-bold text-orange-400 mb-2 flex items-center gap-2">
                    <i class="fas fa-info-circle"></i> How it Works
                </h2>
                <p class="text-gray-300 text-sm">
                    This tool generates automated post-race debrief posts for completed races. 
                    Each debrief includes:
                </p>
                <ul class="text-gray-400 text-sm mt-3 space-y-1 ml-4">
                    <li>✅ Top 3 scoring users (leaderboard)</li>
                    <li>✅ Race winner and constructor champion</li>
                    <li>✅ Participation statistics</li>
                    <li>✅ User achievements and highlights</li>
                    <li>✅ Next upcoming race info</li>
                    <li>✅ F1 humor and community engagement</li>
                </ul>
            </div>

            <!-- Races List -->
            <div class="space-y-4">
                <h3 class="text-lg font-bold text-white flex items-center gap-2">
                    <i class="fas fa-flag-checkered"></i> Completed Races
                </h3>

                <?php if (empty($races)): ?>
                    <div class="text-center py-8 bg-slate-900/50 rounded-lg border border-white/10">
                        <p class="text-gray-400">No completed races found</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-3">
                        <?php foreach ($races as $race): ?>
                            <div class="bg-gradient-to-r from-slate-900 to-slate-950 border border-orange-500/20 rounded-lg p-4 flex items-center justify-between hover:border-orange-500/40 transition">
                                <div class="flex-1">
                                    <h4 class="font-bold text-orange-400">
                                        <?php echo htmlspecialchars($race['race_name']); ?>
                                    </h4>
                                    <p class="text-sm text-gray-400">
                                        <i class="fas fa-map-marker-alt"></i> 
                                        <?php echo htmlspecialchars($race['country']); ?> • 
                                        <?php echo date('M d, Y', strtotime($race['race_date'])); ?>
                                    </p>
                                    <div class="mt-2">
                                        <span class="inline-block px-2 py-1 text-xs rounded-full <?php echo $race['has_debrief'] === 'Yes' ? 'bg-emerald-900/30 text-emerald-300 border border-emerald-500/30' : 'bg-amber-900/30 text-amber-300 border border-amber-500/30'; ?>">
                                            Debrief: <?php echo $race['has_debrief']; ?>
                                        </span>
                                    </div>
                                </div>
                                <form method="POST" class="ml-4">
                                    <input type="hidden" name="race_id" value="<?php echo $race['id']; ?>">
                                    <button type="submit" class="px-6 py-2 bg-orange-600 hover:bg-orange-500 rounded-lg font-bold text-sm transition transform hover:scale-105 active:scale-95">
                                        <?php echo $race['has_debrief'] === 'Yes' ? 'Regenerate' : 'Generate'; ?>
                                    </button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
