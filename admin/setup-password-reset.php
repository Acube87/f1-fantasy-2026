<?php
/**
 * Setup Password Reset Table
 * Run this once to add password reset functionality
 */

require_once __DIR__ . '/../config.php';

echo "<h2>Setting up Password Reset Table...</h2>";

$db = getDB();

// Create password_reset_tokens table
$sql = "CREATE TABLE IF NOT EXISTS password_reset_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    used BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_expires (expires_at)
)";

if ($db->query($sql) === TRUE) {
    echo "<p>✅ Table 'password_reset_tokens' created successfully.</p>";
} else {
    echo "<p style='color:red'>❌ Error creating table: " . $db->error . "</p>";
}

echo "<h3>🎉 Password Reset setup complete!</h3>";
echo "<p>Users can now reset their passwords via email.</p>";
echo "<p><a href='../login.php'>Go to Login Page</a></p>";
?>
