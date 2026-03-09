<?php
require_once 'config.php';
$db = getDB();

// Check Angrycube's admin status
$result = $db->query("SELECT username, is_admin FROM users WHERE username = 'Angrycube'");
$user = $result->fetch_assoc();

echo "<h1>Admin Status Check</h1>";
echo "<p>Angrycube admin status: " . ($user['is_admin'] ? '<strong style="color: green;">YES</strong>' : '<strong style="color: red;">NO</strong>') . "</p>";

// If not admin, make them admin
if (!$user['is_admin']) {
    $db->query("UPDATE users SET is_admin = 1 WHERE username = 'Angrycube'");
    echo "<p style='color: green;'>✅ Set Angrycube as admin!</p>";
}

// Show all users
echo "<h2>All Users:</h2>";
$all = $db->query('SELECT username, is_admin FROM users');
while ($row = $all->fetch_assoc()) {
    $status = $row['is_admin'] ? 'ADMIN' : 'USER';
    echo "<p>{$row['username']}: {$status}</p>";
}

echo "<hr>";
echo "<p><a href='../dashboard.php'>← Back to Dashboard</a></p>";
?>