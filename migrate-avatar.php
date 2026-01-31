<?php
// Simple migration script to add avatar_style column
require_once 'includes/config.php';

$db = getDB();

echo "<!DOCTYPE html>";
echo "<html><head><title>Avatar Migration</title>";
echo "<style>body{font-family:monospace;padding:40px;background:#1a1a1a;color:#fff;}";
echo ".success{color:#00ff00;} .error{color:#ff0000;} .info{color:#00d2ff;}</style>";
echo "</head><body>";

echo "<h1>🔧 Avatar Column Migration</h1>";
echo "<p class='info'>Adding avatar_style column to users table...</p>";

try {
    // Check if column already exists
    $result = $db->query("SHOW COLUMNS FROM users LIKE 'avatar_style'");
    
    if ($result->num_rows > 0) {
        echo "<p class='info'>✓ Column 'avatar_style' already exists! No migration needed.</p>";
    } else {
        // Add the column
        $sql = "ALTER TABLE users ADD COLUMN avatar_style VARCHAR(50) DEFAULT 'avataaars' AFTER email";
        
        if ($db->query($sql) === TRUE) {
            echo "<p class='success'>✅ SUCCESS! Column 'avatar_style' has been added to the users table.</p>";
            echo "<p class='success'>✅ Default value set to 'avataaars'</p>";
        } else {
            echo "<p class='error'>❌ ERROR: " . $db->error . "</p>";
        }
    }
    
    // Verify the column exists now
    $verify = $db->query("SHOW COLUMNS FROM users LIKE 'avatar_style'");
    if ($verify->num_rows > 0) {
        $column = $verify->fetch_assoc();
        echo "<p class='success'>✅ Verification passed! Column details:</p>";
        echo "<pre style='background:#333;padding:10px;'>" . print_r($column, true) . "</pre>";
        echo "<p class='success'>✅ Profile page is now ready to use!</p>";
        echo "<p><a href='profile.php' style='color:#00d2ff;text-decoration:none;'>→ Go to Profile Page</a></p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Exception: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p style='color:#666;font-size:12px;'>You can safely delete this file after running it: migrate-avatar.php</p>";
echo "</body></html>";
?>
