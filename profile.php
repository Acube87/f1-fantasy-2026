<?php
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$user = getCurrentUser();
if (!$user) {
    header('Location: index.php');
    exit;
}

$db = getDB();
$userId = $user['id'];

// Auto-migrate: Add avatar_style column if it doesn't exist
try {
    $checkColumn = $db->query("SHOW COLUMNS FROM users LIKE 'avatar_style'");
    if ($checkColumn->num_rows == 0) {
        $db->query("ALTER TABLE users ADD COLUMN avatar_style VARCHAR(50) DEFAULT 'avataaars' AFTER email");
    }
} catch (Exception $e) {
    // Column might already exist, continue
}

$successMessage = '';
$errorMessage = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Avatar update
    if (isset($_POST['avatar_style'])) {
        $avatarStyle = $_POST['avatar_style'];
        
        $validStyles = [
            'avataaars', 'adventurer', 'adventurer-neutral', 'avataaars-neutral', 
            'big-ears', 'big-ears-neutral', 'big-smile', 'bottts', 'bottts-neutral', 
            'croodles', 'croodles-neutral', 'fun-emoji', 'identicon', 'initials', 
            'lorelei', 'lorelei-neutral', 'micah', 'miniavs', 'notionists', 
            'notionists-neutral', 'open-peeps', 'personas', 'pixel-art', 
            'pixel-art-neutral', 'rings', 'shapes', 'thumbs'
        ];
        
        if (in_array($avatarStyle, $validStyles)) {
            try {
                $stmt = $db->prepare("UPDATE users SET avatar_style = ? WHERE id = ?");
                $stmt->bind_param("si", $avatarStyle, $userId);
                $success = $stmt->execute();
                
                if ($success && $stmt->affected_rows > 0) {
                    $_SESSION['user']['avatar_style'] = $avatarStyle;
                    $user['avatar_style'] = $avatarStyle;
                    $successMessage = "Avatar updated to: {$avatarStyle}";
                } else {
                    $errorMessage = "Database update failed. Column might not exist. Rows affected: {$stmt->affected_rows}";
                }
            } catch (Exception $e) {
                $errorMessage = "Error saving avatar: " . $e->getMessage();
            }
        } else {
            $errorMessage = "Invalid avatar style selected: {$avatarStyle}";
        }
    }
    
    // Username update
    if (isset($_POST['new_username'])) {
        $newUsername = trim($_POST['new_username']);
        
        if (strlen($newUsername) < 3) {
            $errorMessage = 'Username must be at least 3 characters long.';
        } else {
            // Check if username already exists
            $stmt = $db->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $stmt->bind_param("si", $newUsername, $userId);
            $stmt->execute();
            $existing = $stmt->get_result()->fetch_assoc();
            
            if ($existing) {
                $errorMessage = 'Username already taken. Please choose another.';
            } else {
                $stmt = $db->prepare("UPDATE users SET username = ? WHERE id = ?");
                $stmt->bind_param("si", $newUsername, $userId);
                $stmt->execute();
                
                $_SESSION['user']['username'] = $newUsername;
                $user['username'] = $newUsername;
                
                $successMessage = 'Username updated successfully!';
            }
        }
    }
    
    // Password update
    if (isset($_POST['current_password']) && isset($_POST['new_password'])) {
        $currentPassword = $_POST['current_password'];
        $newPassword = $_POST['new_password'];
        $confirmPassword = $_POST['confirm_password'];
        
        // Verify current password
        $stmt = $db->prepare("SELECT password_hash FROM users WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        if (!password_verify($currentPassword, $result['password_hash'])) {
            $errorMessage = 'Current password is incorrect.';
        } elseif (strlen($newPassword) < 6) {
            $errorMessage = 'New password must be at least 6 characters long.';
        } elseif ($newPassword !== $confirmPassword) {
            $errorMessage = 'New passwords do not match.';
        } else {
            $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            $stmt->bind_param("si", $newPasswordHash, $userId);
            $stmt->execute();
            
            $successMessage = 'Password updated successfully!';
        }
    }
}

// Get current avatar style directly from database to verify
$checkStmt = $db->prepare("SELECT avatar_style FROM users WHERE id = ?");
$checkStmt->bind_param("i", $userId);
$checkStmt->execute();
$checkResult = $checkStmt->get_result()->fetch_assoc();
$dbAvatarStyle = $checkResult['avatar_style'] ?? null;

// Get current avatar style
$currentAvatarStyle = $dbAvatarStyle ?? $user['avatar_style'] ?? 'avataaars';

// Get User Statistics
$stats = getUserStats($userId);
$totalPoints = $stats['total_points'] ?? 0;
$racesParticipated = $stats['races_participated'] ?? 0;
$rank = $stats['rank'] ?? '-';

// Calculate detailed accuracy stats
$accuracyStats = [];
$stmt = $db->prepare("
    SELECT 
        COUNT(DISTINCT p.race_id) as total_predictions,
        AVG(ABS(p.predicted_position - r.position)) as avg_position_error,
        SUM(CASE WHEN p.predicted_position = r.position THEN 1 ELSE 0 END) as exact_matches,
        MIN(ABS(p.predicted_position - r.position)) as best_prediction_error
    FROM predictions p
    LEFT JOIN race_results r ON p.race_id = r.race_id AND p.driver_id = r.driver_id
    WHERE p.user_id = ? AND r.position IS NOT NULL
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$accuracyStats = $stmt->get_result()->fetch_assoc();

// Get best race performance
$bestRace = null;
$stmt = $db->prepare("
    SELECT r.country, r.race_name, s.total_points, r.race_date
    FROM scores s
    JOIN races r ON s.race_id = r.id
    WHERE s.user_id = ?
    ORDER BY s.total_points DESC
    LIMIT 1
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$bestRace = $stmt->get_result()->fetch_assoc();

$avgError = $accuracyStats['avg_position_error'] ?? 0;
$exactMatches = $accuracyStats['exact_matches'] ?? 0;
$totalPredictions = $accuracyStats['total_predictions'] ?? 0;
$accuracy = $totalPredictions > 0 ? ($exactMatches / ($totalPredictions * 10)) * 100 : 0; // Assuming 10 predictions per race

// Available avatar styles (27 solid working options)
$avatarStyles = [
    'avataaars' => 'Classic Avatar',
    'adventurer' => 'Adventurer',
    'adventurer-neutral' => 'Adventurer Neutral',
    'avataaars-neutral' => 'Classic Neutral',
    'big-ears' => 'Big Ears',
    'big-ears-neutral' => 'Big Ears Neutral',
    'big-smile' => 'Big Smile',
    'bottts' => 'Robot',
    'bottts-neutral' => 'Robot Neutral',
    'croodles' => 'Croodles',
    'croodles-neutral' => 'Croodles Neutral',
    'fun-emoji' => 'Fun Emoji',
    'identicon' => 'Geometric',
    'initials' => 'Initials',
    'lorelei' => 'Illustrated',
    'lorelei-neutral' => 'Illustrated Neutral',
    'micah' => 'Modern',
    'miniavs' => 'Miniavs',
    'notionists' => 'Notionists',
    'notionists-neutral' => 'Notionists Neutral',
    'open-peeps' => 'Open Peeps',
    'personas' => 'Artistic',
    'pixel-art' => 'Pixel Art',
    'pixel-art-neutral' => 'Pixel Art Neutral',
    'rings' => 'Rings',
    'shapes' => 'Shapes',
    'thumbs' => 'Thumbs'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile & Settings - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="css/gaming-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="gaming-theme text-gray-200">

    <!-- Navbar -->
    <nav class="g-nav fixed w-full z-50 px-6 py-4 flex justify-between items-center">
        <div class="flex items-center gap-4">
            <a href="index.php" class="flex items-center gap-4 hover:opacity-80 transition">
                <div class="w-10 h-10 bg-gradient-to-br from-red-600 to-orange-500 rounded-xl flex items-center justify-center shadow-lg shadow-orange-500/20">
                    <i class="fas fa-flag-checkered text-white text-lg"></i>
                </div>
                <span class="font-bold text-xl tracking-wide text-white">PADDOCK PICKS</span>
            </a>
        </div>
        
        <div class="flex items-center gap-6">
            <a href="dashboard.php" class="text-gray-300 hover:text-white font-bold text-sm">← Back to Dashboard</a>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="pt-24 pb-12 px-4 md:px-8 max-w-6xl mx-auto">
        
        <?php if ($successMessage): ?>
            <div class="mb-6 bg-green-500/10 border border-green-500/30 rounded-lg p-4 text-green-400">
                <i class="fas fa-check-circle mr-2"></i> <?php echo $successMessage; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($errorMessage): ?>
            <div class="mb-6 bg-red-500/10 border border-red-500/30 rounded-lg p-4 text-red-400">
                <i class="fas fa-exclamation-circle mr-2"></i> <?php echo $errorMessage; ?>
            </div>
        <?php endif; ?>

        <!-- DEBUG INFO -->
        <div class="mb-6 bg-blue-500/10 border border-blue-500/30 rounded-lg p-4 text-blue-300 text-sm">
            <strong>🔍 Debug Info:</strong><br>
            Current Avatar in DB: <code class="bg-black/30 px-2 py-1 rounded"><?php echo $dbAvatarStyle ?? 'NULL (column might not exist)'; ?></code><br>
            Session Avatar: <code class="bg-black/30 px-2 py-1 rounded"><?php echo $user['avatar_style'] ?? 'NULL'; ?></code><br>
            Using: <code class="bg-black/30 px-2 py-1 rounded"><?php echo $currentAvatarStyle; ?></code>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <!-- Left Column: Avatar & Info -->
            <div class="lg:col-span-1 space-y-6">
                
                <!-- Profile Card -->
                <div class="g-card p-6 text-center">
                    <div class="w-32 h-32 rounded-full mx-auto mb-4 bg-slate-700 border-4 border-white/10 overflow-hidden">
                        <img src="https://api.dicebear.com/7.x/<?php echo $currentAvatarStyle; ?>/svg?seed=<?php echo $user['username']; ?>" 
                             alt="Avatar" 
                             class="w-full h-full">
                    </div>
                    <h1 class="text-2xl font-black text-white mb-1"><?php echo htmlspecialchars($user['username']); ?></h1>
                    <div class="text-sm text-gray-400 mb-4"><?php echo htmlspecialchars($user['email']); ?></div>
                    
                    <div class="flex justify-center gap-4 mt-6">
                        <div class="text-center">
                            <div class="text-2xl font-black text-blue-400"><?php echo $racesParticipated; ?></div>
                            <div class="text-xs text-gray-500 uppercase">Level</div>
                        </div>
                        <div class="border-l border-white/10"></div>
                        <div class="text-center">
                            <div class="text-2xl font-black text-green-400"><?php echo number_format($totalPoints); ?></div>
                            <div class="text-xs text-gray-500 uppercase">Points</div>
                        </div>
                        <div class="border-l border-white/10"></div>
                        <div class="text-center">
                            <div class="text-2xl font-black text-orange-400">#<?php echo $rank; ?></div>
                            <div class="text-xs text-gray-500 uppercase">Rank</div>
                        </div>
                    </div>
                </div>

                <!-- Avatar Selector -->
                <div class="g-card p-6">
                    <h3 class="font-bold text-white text-lg mb-4 flex items-center gap-2">
                        <i class="fas fa-user-circle text-blue-500"></i> Change Avatar
                    </h3>
                    
                    <form method="POST" action="profile.php">
                        <div class="grid grid-cols-3 gap-2 mb-4 max-h-96 overflow-y-auto pr-2" style="scrollbar-width: thin; scrollbar-color: #3b82f6 #1a1a1a;">
                            <?php foreach ($avatarStyles as $style => $label): ?>
                                <label class="cursor-pointer">
                                    <input type="radio" name="avatar_style" value="<?php echo $style; ?>" 
                                           <?php echo $style === $currentAvatarStyle ? 'checked' : ''; ?>
                                           class="hidden peer">
                                    <div class="g-card p-3 hover:bg-white/10 peer-checked:ring-2 peer-checked:ring-blue-500 transition">
                                        <div class="w-16 h-16 rounded-full mx-auto mb-2 bg-slate-700 overflow-hidden">
                                            <img src="https://api.dicebear.com/7.x/<?php echo $style; ?>/svg?seed=<?php echo $user['username']; ?>" 
                                                 alt="<?php echo $label; ?>"
                                                 class="w-full h-full">
                                        </div>
                                        <div class="text-xs text-center text-gray-400"><?php echo $label; ?></div>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        
                        <button type="submit" class="g-btn g-btn-blue w-full py-3">
                            <i class="fas fa-save mr-2"></i> Save Avatar
                        </button>
                    </form>
                </div>

            </div>

            <!-- Right Column: Statistics & Settings -->
            <div class="lg:col-span-2 space-y-6">
                
                <!-- Account Settings -->
                <div class="g-card p-6">
                    <h2 class="font-bold text-white text-xl mb-6 flex items-center gap-2">
                        <i class="fas fa-cog text-purple-500"></i> Account Settings
                    </h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Change Username -->
                        <div>
                            <h3 class="font-bold text-white text-sm mb-3">Change Username</h3>
                            <form method="POST" action="profile.php">
                                <input type="text" 
                                       name="new_username" 
                                       value="<?php echo htmlspecialchars($user['username']); ?>"
                                       placeholder="New username"
                                       class="w-full bg-black/30 border border-white/10 rounded-lg px-4 py-2.5 text-white focus:border-blue-500 outline-none transition mb-3"
                                       required>
                                <button type="submit" class="w-full bg-blue-500/20 hover:bg-blue-500/30 border border-blue-500/30 text-blue-400 py-2.5 rounded-lg transition font-bold text-sm">
                                    <i class="fas fa-user mr-2"></i> Update Username
                                </button>
                            </form>
                        </div>
                        
                        <!-- Change Password -->
                        <div>
                            <h3 class="font-bold text-white text-sm mb-3">Change Password</h3>
                            <form method="POST" action="profile.php">
                                <input type="password" 
                                       name="current_password" 
                                       placeholder="Current password"
                                       class="w-full bg-black/30 border border-white/10 rounded-lg px-4 py-2.5 text-white focus:border-blue-500 outline-none transition mb-2"
                                       required>
                                <input type="password" 
                                       name="new_password" 
                                       placeholder="New password"
                                       class="w-full bg-black/30 border border-white/10 rounded-lg px-4 py-2.5 text-white focus:border-blue-500 outline-none transition mb-2"
                                       required>
                                <input type="password" 
                                       name="confirm_password" 
                                       placeholder="Confirm new password"
                                       class="w-full bg-black/30 border border-white/10 rounded-lg px-4 py-2.5 text-white focus:border-blue-500 outline-none transition mb-3"
                                       required>
                                <button type="submit" class="w-full bg-green-500/20 hover:bg-green-500/30 border border-green-500/30 text-green-400 py-2.5 rounded-lg transition font-bold text-sm">
                                    <i class="fas fa-lock mr-2"></i> Update Password
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Accuracy Stats -->
                <div class="g-card p-6">
                    <h2 class="font-bold text-white text-xl mb-6 flex items-center gap-2">
                        <i class="fas fa-chart-line text-green-500"></i> Prediction Accuracy
                    </h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                        <div class="bg-black/20 rounded-lg p-4 border border-white/5">
                            <div class="text-3xl font-black text-green-400 mb-1"><?php echo number_format($accuracy, 1); ?>%</div>
                            <div class="text-xs text-gray-500 uppercase">Overall Accuracy</div>
                        </div>
                        
                        <div class="bg-black/20 rounded-lg p-4 border border-white/5">
                            <div class="text-3xl font-black text-blue-400 mb-1"><?php echo number_format($avgError, 1); ?></div>
                            <div class="text-xs text-gray-500 uppercase">Avg Position Error</div>
                        </div>
                        
                        <div class="bg-black/20 rounded-lg p-4 border border-white/5">
                            <div class="text-3xl font-black text-orange-400 mb-1"><?php echo $exactMatches; ?></div>
                            <div class="text-xs text-gray-500 uppercase">Exact Matches</div>
                        </div>
                    </div>
                    
                    <div class="bg-gradient-to-r from-green-500/10 to-blue-500/10 border border-green-500/20 rounded-lg p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-sm font-bold text-white mb-1">Prediction Accuracy Explained</div>
                                <div class="text-xs text-gray-400">Lower position error = better predictions!</div>
                            </div>
                            <i class="fas fa-info-circle text-blue-400 text-2xl"></i>
                        </div>
                    </div>
                </div>

                <!-- Best Performance -->
                <?php if ($bestRace): ?>
                <div class="g-card p-6">
                    <h2 class="font-bold text-white text-xl mb-6 flex items-center gap-2">
                        <i class="fas fa-trophy text-yellow-500"></i> Best Performance
                    </h2>
                    
                    <div class="bg-gradient-to-r from-yellow-500/10 to-orange-500/10 border border-yellow-500/20 rounded-lg p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-2xl font-black text-white mb-2"><?php echo htmlspecialchars($bestRace['country']); ?></div>
                                <div class="text-sm text-gray-400"><?php echo date('M d, Y', strtotime($bestRace['race_date'])); ?></div>
                            </div>
                            <div class="text-right">
                                <div class="text-4xl font-black text-yellow-400"><?php echo number_format($bestRace['total_points']); ?></div>
                                <div class="text-xs text-gray-500 uppercase">Points</div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Recent Activity -->
                <div class="g-card p-6">
                    <h2 class="font-bold text-white text-xl mb-6 flex items-center gap-2">
                        <i class="fas fa-history text-purple-500"></i> Race History
                    </h2>
                    
                    <?php
                    $stmt = $db->prepare("
                        SELECT r.country, r.race_date, s.total_points
                        FROM scores s
                        JOIN races r ON s.race_id = r.id
                        WHERE s.user_id = ?
                        ORDER BY r.race_date DESC
                        LIMIT 5
                    ");
                    $stmt->bind_param("i", $userId);
                    $stmt->execute();
                    $recentRaces = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                    ?>
                    
                    <?php if (!empty($recentRaces)): ?>
                        <div class="space-y-2">
                            <?php foreach ($recentRaces as $race): ?>
                                <div class="flex items-center justify-between p-3 bg-black/20 rounded-lg hover:bg-white/5 transition">
                                    <div>
                                        <div class="text-sm font-bold text-white"><?php echo htmlspecialchars($race['country']); ?></div>
                                        <div class="text-xs text-gray-500"><?php echo date('M d, Y', strtotime($race['race_date'])); ?></div>
                                    </div>
                                    <div class="text-lg font-bold text-green-400">+<?php echo number_format($race['total_points']); ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-8 text-gray-500">
                            <i class="fas fa-inbox text-4xl mb-2 opacity-20"></i>
                            <p>No race history yet. Start predicting!</p>
                        </div>
                    <?php endif; ?>
                </div>

            </div>

        </div>

    </main>

    <footer class="mt-12 border-t border-white/10 py-6 text-center">
        <p class="text-gray-500 text-sm mb-2">&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</p>
        <p class="text-gray-600 text-xs">
            Powered by <a href="https://scanerrific.com" class="text-orange-400 hover:text-orange-300 transition">Scanerrific</a>
        </p>
    </footer>

</body>
</html>
