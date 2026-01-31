<?php
require_once 'includes/config.php';

$db = getDB();

echo "<!DOCTYPE html><html><head><title>Avatar Fix</title>";
echo "<style>body{font-family:monospace;padding:40px;background:#1a1a1a;color:#fff;}</style></head><body>";
echo "<h1>🔧 Avatar Column Check & Fix</h1>";

// Check if column exists
$result = $db->query("SHOW COLUMNS FROM users LIKE 'avatar_style'");

if ($result->num_rows > 0) {
    echo "<p style='color:#00ff00;'>✅ Column 'avatar_style' EXISTS!</p>";
    
    // Check current values
    $check = $db->query("SELECT id, username, avatar_style FROM users LIMIT 5");
    echo "<h3>Current User Data:</h3><pre>";
    while ($row = $check->fetch_assoc()) {
        echo "User: {$row['username']} | Avatar: " . ($row['avatar_style'] ?? 'NULL') . "\n";
    }
    echo "</pre>";
} else {
    echo "<p style='color:#ff0000;'>❌ Column 'avatar_style' DOES NOT EXIST</p>";
    echo "<p>Creating it now...</p>";
    
    $sql = "ALTER TABLE users ADD COLUMN avatar_style VARCHAR(100) DEFAULT 'avataaars' AFTER email";
    if ($db->query($sql)) {
        echo "<p style='color:#00ff00;'>✅ Column created successfully!</p>";
    } else {
        echo "<p style='color:#ff0000;'>❌ Error: {$db->error}</p>";
    }
}

// Test query
echo "<h3>Testing Leaderboard Query:</h3>";
$test = $db->query("SELECT u.id, u.username, u.avatar_style FROM users u LIMIT 3");
if ($test) {
    echo "<pre>";
    while ($row = $test->fetch_assoc()) {
        echo "User: {$row['username']} | Avatar: " . ($row['avatar_style'] ?? 'NULL') . "\n";
    }
    echo "</pre>";
    echo "<p style='color:#00ff00;'>✅ Query works! Avatars should display now.</p>";
} else {
    echo "<p style='color:#ff0000;'>❌ Query failed: {$db->error}</p>";
}

echo "<hr><p><a href='profile.php' style='color:#00d2ff;'>→ Go to Profile</a> | <a href='dashboard.php' style='color:#00d2ff;'>→ Go to Dashboard</a></p>";
echo "</body></html>";
?>
