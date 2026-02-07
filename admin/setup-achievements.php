<?php
/**
 * Safe Database Setup for Achievements
 * Accessible via Browser: /setup-achievements.php
 */

require_once '../config.php';

// Simple authentication check or run in development only
// Since this is a setup script, let's just make it run
// In prod, you'd delete this file after use.

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Setup Achievements DB</title>
    <style>
        body { font-family: sans-serif; background: #1a1a1a; color: #fff; padding: 2rem; line-height: 1.6; }
        .card { background: #2a2a2a; padding: 2rem; border-radius: 8px; max-width: 800px; margin: 0 auto; box-shadow: 0 4px 6px rgba(0,0,0,0.3); }
        h1 { color: #4ade80; margin-top: 0; }
        .log { background: #111; padding: 1rem; border-radius: 4px; font-family: monospace; overflow-x: auto; margin-top: 1rem; }
        .success { color: #4ade80; }
        .error { color: #f87171; }
        .btn { display: inline-block; background: #3b82f6; color: white; padding: 0.75rem 1.5rem; text-decoration: none; border-radius: 4px; margin-top: 2rem; font-weight: bold; }
        .btn:hover { background: #2563eb; }
    </style>
</head>
<body>
<div class='card'>
    <h1>🚀 Database Setup: Achievements</h1>
    <div class='log'>
";

function logMsg($msg, $type = 'info') {
    $color = $type === 'success' ? '#4ade80' : ($type === 'error' ? '#f87171' : '#ccc');
    echo "<div style='color: {$color}; margin-bottom: 0.5rem;'>[" . date('H:i:s') . "] " . htmlspecialchars($msg) . "</div>";
    flush(); 
}

try {
    $db = getDB();
    logMsg("Connected to database successfully.", 'success');
    
    // 1. Create Achievements Table
    $sql = "CREATE TABLE IF NOT EXISTS achievements (
        id VARCHAR(50) PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        description TEXT NOT NULL,
        tier ENUM('common', 'rare', 'epic', 'legendary', 'special') NOT NULL,
        icon VARCHAR(50) DEFAULT 'fa-trophy',
        unlock_criteria TEXT NOT NULL,
        points_reward INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_tier (tier)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if ($db->query($sql)) {
        logMsg("✅ Table 'achievements' created/verified.", 'success');
    } else {
        throw new Exception("Error creating achievements table: " . $db->error);
    }
    
    // 2. Create User Achievements Table
    $sql = "CREATE TABLE IF NOT EXISTS user_achievements (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        achievement_id VARCHAR(50) NOT NULL,
        unlocked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        is_displayed BOOLEAN DEFAULT FALSE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (achievement_id) REFERENCES achievements(id) ON DELETE CASCADE,
        UNIQUE KEY unique_user_achievement (user_id, achievement_id),
        INDEX idx_user_id (user_id),
        INDEX idx_achievement_id (achievement_id),
        INDEX idx_displayed (user_id, is_displayed)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if ($db->query($sql)) {
        logMsg("✅ Table 'user_achievements' created/verified.", 'success');
    } else {
        throw new Exception("Error creating user_achievements table: " . $db->error);
    }
    
    // 3. Populate Achievements
    $achievements = [
        // COMMON (5)
        ['first_prediction', 'Rookie Driver', 'Make your first prediction', 'common', 'fa-flag', 'Submit 1 prediction', 10],
        ['welcome_aboard', 'Welcome to the Paddock', 'Complete your profile setup', 'common', 'fa-user-check', 'Add full name & avatar', 10],
        ['first_points', 'On the Board', 'Score your first points', 'common', 'fa-star', 'Earn any points', 10],
        ['participation_5', 'Racing Regular', 'Participate in 5 races', 'common', 'fa-calendar-check', 'Complete 5 predictions', 15],
        ['streak_3', 'Consistency Counts', 'Score points 3 races in a row', 'common', 'fa-fire', 'Points in 3 consecutive races', 15],

        // RARE (6)
        ['participation_10', 'Season Veteran', 'Participate in 10 races', 'rare', 'fa-medal', 'Complete 10 predictions', 25],
        ['podium_sweep_1', 'Podium Prophet', 'Get your first podium sweep', 'rare', 'fa-trophy', 'All 3 podium correct once', 30],
        ['total_500', 'Point Collector', 'Score 500 total points', 'rare', 'fa-coins', '500 total points', 25],
        ['constructor_correct_5', 'Team Tactician', 'Predict winning constructor 5 times', 'rare', 'fa-wrench', '5 correct constructors', 25],
        ['perfectionist', 'Perfectionist', 'Get 5+ exact predictions in one race', 'rare', 'fa-bullseye', '5+ exact matches in single race', 30],
        ['accuracy_20', 'Sharp Shooter', 'Achieve 20% prediction accuracy', 'rare', 'fa-crosshairs', '20% exact match rate', 25],

        // EPIC (7)
        ['big_score', 'Big Score', 'Score 150+ points in one race', 'epic', 'fa-bolt', '150+ in single race', 50],
        ['podium_sweep_3', 'Crystal Ball', 'Get 3 podium sweeps', 'epic', 'fa-eye', '3 podium sweeps total', 50],
        ['streak_10', 'Unbreakable Focus', 'Predict 10 races in a row', 'epic', 'fa-fire', '10-race streak', 50],
        ['double_points_master', 'Double Trouble', 'Score 200+ in a 2x points race', 'epic', 'fa-gem', '200+ in China/UK/Singapore', 75],
        ['accuracy_30', 'Precision Engineer', 'Achieve 30% prediction accuracy', 'epic', 'fa-bullseye', '30% exact match rate', 50],
        ['total_1000', 'Points Millionaire', 'Score 1000 total points', 'epic', 'fa-sack-dollar', '1000 total points', 50],
        ['race_winner_3', 'Hat Trick Hero', 'Win 3 individual races', 'epic', 'fa-crown', 'Rank #1 in 3 different races', 75],

        // LEGENDARY (4)
        ['legendary_performance', 'Legendary Performance', 'Score 200+ points in single race', 'legendary', 'fa-trophy', '200+ in regular race', 100],
        ['podium_sweep_5', 'Oracle of the Grid', 'Get 5 podium sweeps', 'legendary', 'fa-brain', '5 podium sweeps total', 100],
        ['accuracy_40', 'The Nostradamus', 'Achieve 40% prediction accuracy', 'legendary', 'fa-magic', '40% exact match rate', 100],
        ['total_2500', 'Point Legend', 'Score 2500 total points', 'legendary', 'fa-infinity', '2500 total points', 100],

        // SPECIAL (4)
        ['first_race_winner', 'Early Bird', 'Win the opening race', 'special', 'fa-clock', 'Rank #1 in first race', 50],
        ['constructor_sweep', 'Team Whisperer', 'Predict constructor correctly 10 times', 'special', 'fa-handshake', '10 correct constructor picks', 50],
        ['perfect_weekend', 'Perfect Weekend', 'Score 100+ points in 3 consecutive races', 'special', 'fa-check', '100+ in 3 races in a row', 75],
        ['mega_race', 'Mega Race', 'Score 250+ points in a 2x event', 'special', 'fa-rocket', '250+ in double points race', 100]
    ];
    
    $stmt = $db->prepare("INSERT INTO achievements (id, name, description, tier, icon, unlock_criteria, points_reward) VALUES (?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE name=VALUES(name), description=VALUES(description), tier=VALUES(tier), icon=VALUES(icon), unlock_criteria=VALUES(unlock_criteria), points_reward=VALUES(points_reward)");
    
    $count = 0;
    foreach ($achievements as $ach) {
        $stmt->bind_param("ssssssi", $ach[0], $ach[1], $ach[2], $ach[3], $ach[4], $ach[5], $ach[6]);
        if ($stmt->execute()) {
            $count++;
        }
    }
    
    logMsg("✅ Seeded/Updated {$count} achievements.", 'success');
    
    echo "</div>";
    echo "<a href='../achievements.php' class='btn'>Go to Achievements Page</a>";

} catch (Exception $e) {
    logMsg("❌ CRITICAL ERROR: " . $e->getMessage(), 'error');
    echo "</div>";
}

echo "</div></body></html>";
?>
