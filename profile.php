<?php
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'includes/csrf.php';
require_once 'includes/avatars.php';

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
    // Validate CSRF token
    if (!validateCSRF()) {
        $errorMessage = 'Security validation failed. Please try again.';
    } else {
    // Avatar update
    if (isset($_POST['avatar_style'])) {
        $avatarStyle = $_POST['avatar_style'];
        
        // Get all valid avatar styles
        $validStyles = array_keys(AVATAR_STYLES);
        
        if (in_array($avatarStyle, $validStyles)) {
            try {
                $stmt = $db->prepare("UPDATE users SET avatar_style = ? WHERE id = ?");
                $stmt->bind_param("si", $avatarStyle, $userId);
                $success = $stmt->execute();
                
                if ($success && $stmt->affected_rows > 0) {
                    $_SESSION['user']['avatar_style'] = $avatarStyle;
                    $user['avatar_style'] = $avatarStyle;
                    $successMessage = "Avatar updated to: " . getAvatarName($avatarStyle);
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
    
    // Full Name update
    if (isset($_POST['full_name'])) {
        $newFullName = trim($_POST['full_name']);
        
        // Full name is optional, so allow empty
        $stmt = $db->prepare("UPDATE users SET full_name = ? WHERE id = ?");
        $stmt->bind_param("si", $newFullName, $userId);
        $stmt->execute();
        
        $user['full_name'] = $newFullName;
        
        $successMessage = 'Full name updated successfully!';
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
    }  // Close CSRF validation else block
}  // Close POST check


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

// Get actual count of individual driver predictions made
$countStmt = $db->prepare("
    SELECT COUNT(*) as total_driver_predictions
    FROM predictions p
    LEFT JOIN race_results r ON p.race_id = r.race_id AND p.driver_id = r.driver_id
    WHERE p.user_id = ? AND r.position IS NOT NULL
");
$countStmt->bind_param("i", $userId);
$countStmt->execute();
$countResult = $countStmt->get_result()->fetch_assoc();
$totalDriverPredictions = $countResult['total_driver_predictions'] ?? 0;

// Calculate accuracy: % of exact position matches
$accuracy = $totalDriverPredictions > 0 ? ($exactMatches / $totalDriverPredictions) * 100 : 0;

// Get all available avatars (grouped by type)
$allAvatars = getAllAvatars();
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

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <!-- Left Column: Avatar & Info -->
            <div class="lg:col-span-1 space-y-6">
                
                <!-- Profile Card -->
                <div class="g-card p-6 text-center">
                    <div class="w-32 h-32 rounded-full mx-auto mb-4 bg-slate-700 border-4 border-white/10 overflow-hidden">
                        <img src="<?php echo getAvatarUrl($currentAvatarStyle, $user['username']); ?>" 
                             alt="Avatar" 
                             class="w-full h-full object-cover">
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
                        <?php csrfField(); ?>
                        
                        <div class="max-h-[500px] overflow-y-auto pr-2" style="scrollbar-width: thin; scrollbar-color: #3b82f6 #1a1a1a;">
                            <?php foreach ($allAvatars as $groupKey => $group): ?>
                                <div class="mb-6">
                                    <h4 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-3 flex items-center gap-2">
                                        <?php echo $group['label']; ?>
                                        <span class="text-xs text-gray-600">(<?php echo count($group['avatars']); ?>)</span>
                                    </h4>
                                    
                                    <div class="grid grid-cols-3 gap-2">
                                        <?php foreach ($group['avatars'] as $style => $label): ?>
                                            <label class="cursor-pointer">
                                                <input type="radio" name="avatar_style" value="<?php echo $style; ?>" 
                                                       <?php echo $style === $currentAvatarStyle ? 'checked' : ''; ?>
                                                       class="hidden peer">
                                                <div class="g-card p-3 hover:bg-white/10 peer-checked:ring-2 peer-checked:ring-blue-500 peer-checked:bg-blue-500/10 transition">
                                                    <div class="w-16 h-16 rounded-full mx-auto mb-2 bg-slate-700 overflow-hidden">
                                                        <img src="<?php echo getAvatarUrl($style, $user['username']); ?>" 
                                                             alt="<?php echo $label; ?>"
                                                             class="w-full h-full object-cover">
                                                    </div>
                                                    <div class="text-xs text-center text-gray-400 leading-tight"><?php echo $label; ?></div>
                                                </div>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <button type="submit" class="g-btn g-btn-blue w-full py-3 mt-4">
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
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <!-- Change Full Name -->
                        <div>
                            <h3 class="font-bold text-white text-sm mb-3">Full Name</h3>
                            <form method="POST" action="profile.php">
                                <?php csrfField(); ?>
                                <input type="text" 
                                       name="full_name" 
                                       value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>"
                                       placeholder="Your full name (optional)"
                                       class="w-full bg-black/30 border border-white/10 rounded-lg px-4 py-2.5 text-white focus:border-blue-500 outline-none transition mb-3">
                                <button type="submit" class="w-full bg-green-500/20 hover:bg-green-500/30 border border-green-500/30 text-green-400 py-2.5 rounded-lg transition font-bold text-sm">
                                    <i class="fas fa-id-card mr-2"></i> Update Full Name
                                </button>
                            </form>
                        </div>
                        
                        <!-- Change Username -->
                        <div>
                            <h3 class="font-bold text-white text-sm mb-3">Change Username</h3>
                            <form method="POST" action="profile.php">
                                <?php csrfField(); ?>
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
                                <?php csrfField(); ?>
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
                
                <!-- Badge Display Settings -->
                <?php
                // Get user's achievements
                if (file_exists('includes/achievements.php')) {
                    require_once 'includes/achievements.php';
                    $userAchievements = getUserAchievements($userId, $db);
                } else {
                    $userAchievements = [];
                }
                
                // Handle badge toggle
                if (isset($_POST['toggle_badge'])) {
                    $achievementId = $_POST['achievement_id'] ?? '';
                    if ($achievementId && file_exists('includes/achievements.php')) {
                        toggleDisplayedBadge($userId, $achievementId, $db);
                        // Refresh user achievements
                        $userAchievements = getUserAchievements($userId, $db);
                        $successMessage = 'Badge display updated!';
                    }
                }
                ?>
                
                <?php if (!empty($userAchievements)): ?>
                <div class="g-card p-6">
                    <h2 class="font-bold text-white text-xl mb-4 flex items-center gap-2">
                        <i class="fas fa-award text-yellow-500"></i> Badge Display Settings
                    </h2>
                    <p class="text-sm text-gray-400 mb-4">
                        Control which badges appear on your leaderboard profile. Badges are shown by default.
                    </p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <?php
                        $tierColors = [
                            'common' => 'green',
                            'rare' => 'blue',
                            'epic' => 'purple',
                            'legendary' => 'red',
                            'special' => 'yellow'
                        ];
                        
                        foreach ($userAchievements as $achievement):
                            $tier = $achievement['tier'];
                            $color = $tierColors[$tier] ?? 'gray';
                            $isDisplayed = $achievement['is_displayed'];
                        ?>
                            <div class="flex items-center justify-between p-3 bg-black/20 border border-white/5 rounded-lg">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-<?php echo $color; ?>-500/20 border-2 border-<?php echo $color; ?>-500/50 flex items-center justify-center">
                                        <i class="fas <?php echo $achievement['icon']; ?> text-<?php echo $color; ?>-400"></i>
                                    </div>
                                    <div>
                                        <div class="text-sm font-bold text-white"><?php echo htmlspecialchars($achievement['name']); ?></div>
                                        <div class="text-xs text-<?php echo $color; ?>-400 uppercase"><?php echo $tier; ?></div>
                                    </div>
                                </div>
                                <form method="POST" action="profile.php" class="inline">
                                    <?php csrfField(); ?>
                                    <input type="hidden" name="achievement_id" value="<?php echo $achievement['id']; ?>">
                                    <button type="submit" 
                                            name="toggle_badge"
                                            class="px-4 py-2 rounded-lg text-xs font-bold transition <?php echo $isDisplayed ? 'bg-green-500/20 text-green-400 border border-green-500/30 hover:bg-green-500/30' : 'bg-gray-500/20 text-gray-400 border border-gray-500/30 hover:bg-gray-500/30'; ?>">
                                        <?php echo $isDisplayed ? '<i class="fas fa-eye mr-1"></i> Shown' : '<i class="fas fa-eye-slash mr-1"></i> Hidden'; ?>
                                    </button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
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
        <p class="text-gray-600 text-xs mb-3">
            Powered by <a href="https://scanerrific.com" class="text-orange-400 hover:text-orange-300 transition">Scanerrific</a>
        </p>
        <div class="flex items-center justify-center gap-2 text-gray-500 text-xs">
            <span>Follow Scanerrific on</span>
            <a href="https://www.linkedin.com/company/86236157/admin/dashboard/" target="_blank" rel="noopener noreferrer" class="inline-flex items-center text-blue-400 hover:text-blue-300 transition-colors">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="inline-block">
                    <path d="M17.303 2.25H6.69698C5.51757 2.25 4.38647 2.71852 3.5525 3.55249C2.71853 4.38646 2.25 5.51757 2.25 6.69698V17.303C2.25 18.4824 2.71853 19.6135 3.5525 20.4475C4.38647 21.2815 5.51757 21.75 6.69698 21.75H17.303C18.4824 21.75 19.6136 21.2815 20.4475 20.4475C21.2815 19.6135 21.75 18.4824 21.75 17.303V6.69698C21.75 5.51757 21.2815 4.38646 20.4475 3.55249C19.6136 2.71852 18.4824 2.25 17.303 2.25ZM8.84265 17.9923C8.84568 18.0467 8.83757 18.1011 8.81882 18.1523C8.80007 18.2035 8.77106 18.2502 8.73359 18.2898C8.69612 18.3293 8.65097 18.3608 8.6009 18.3823C8.55084 18.4038 8.49691 18.4149 8.44243 18.4148H6.66363C6.55647 18.4119 6.45468 18.3673 6.37992 18.2904C6.30517 18.2136 6.26336 18.1106 6.2634 18.0034V10.5992C6.26191 10.5457 6.27118 10.4925 6.29063 10.4426C6.31007 10.3928 6.3393 10.3473 6.37662 10.309C6.41393 10.2706 6.45857 10.2402 6.50787 10.2193C6.55716 10.1985 6.61012 10.1878 6.66363 10.1879H8.44243C8.49593 10.1878 8.54889 10.1985 8.59819 10.2193C8.64749 10.2402 8.69212 10.2706 8.72944 10.309C8.76675 10.3473 8.796 10.3928 8.81544 10.4426C8.83489 10.4925 8.84414 10.5457 8.84265 10.5992V17.9923ZM7.51968 8.63141C7.22991 8.62922 6.94729 8.54129 6.70743 8.37871C6.46757 8.21613 6.2812 7.98618 6.17183 7.71784C6.06246 7.4495 6.03499 7.15479 6.09286 6.87086C6.15073 6.58693 6.29137 6.32649 6.49704 6.12236C6.70271 5.91824 6.9642 5.77957 7.24856 5.72385C7.53292 5.66812 7.82742 5.69782 8.09492 5.80921C8.36242 5.9206 8.59096 6.10869 8.75173 6.34978C8.91249 6.59086 8.99829 6.87414 8.9983 7.16391C8.9983 7.35757 8.95998 7.54931 8.88554 7.72808C8.8111 7.90685 8.702 8.06913 8.56455 8.20554C8.4271 8.34196 8.26401 8.44982 8.08467 8.52291C7.90534 8.59601 7.71332 8.63288 7.51968 8.63141ZM18.3369 17.9812C18.337 18.0825 18.2975 18.1798 18.2269 18.2525C18.1564 18.3251 18.0602 18.3674 17.959 18.3703H16.0801C15.9788 18.3674 15.8827 18.3251 15.8121 18.2525C15.7415 18.1798 15.7021 18.0825 15.7021 17.9812V14.557C15.7021 14.0456 15.8578 12.3335 14.3458 12.3335C13.1673 12.3335 12.9339 13.5342 12.8894 14.0678V18.059C12.8894 18.1603 12.85 18.2576 12.7794 18.3303C12.7088 18.403 12.6127 18.4452 12.5114 18.4481H10.6882C10.6371 18.4481 10.5865 18.4381 10.5392 18.4185C10.492 18.3989 10.4491 18.3703 10.413 18.3342C10.3769 18.298 10.3482 18.2551 10.3287 18.2079C10.3091 18.1607 10.299 18.1101 10.299 18.059V10.5658C10.3019 10.4646 10.3442 10.3685 10.4169 10.2979C10.4895 10.2273 10.5869 10.1878 10.6882 10.1879H12.5114C12.6127 10.1878 12.71 10.2273 12.7827 10.2979C12.8554 10.3685 12.8976 10.4646 12.9005 10.5658V11.2107C13.1667 10.8212 13.5341 10.5119 13.9632 10.316C14.3922 10.12 14.8666 10.045 15.3352 10.0989C18.3703 10.0989 18.3592 12.9339 18.3592 14.5459L18.3369 17.9812Z" fill="currentColor"/>
                </svg>
                <span class="ml-1">LinkedIn</span>
            </a>
        </div>
    </footer>

</body>
</html>
