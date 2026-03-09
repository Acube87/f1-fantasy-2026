<?php
session_start();
require_once 'includes/auth.php';

$user = getCurrentUser();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Debug Session</title>
</head>
<body>
    <h1>Session Debug</h1>
    <p>Session ID: <?php echo session_id(); ?></p>
    <p>User ID in session: <?php echo $_SESSION['user_id'] ?? 'NONE'; ?></p>
    <p>Current user: <?php echo $user ? $user['username'] : 'NONE'; ?></p>
    <p>Is admin: <?php echo isAdmin() ? 'YES' : 'NO'; ?></p>
    <p>Admin field value: <?php echo $user ? $user['is_admin'] : 'N/A'; ?></p>

    <?php if ($user && isAdmin()): ?>
        <p><a href="admin/race-control.php" style="color: orange; font-weight: bold;">GO TO RACE CONTROL</a></p>
    <?php else: ?>
        <p style="color: red;">You are not an admin or not logged in properly!</p>
    <?php endif; ?>
</body>
</html>
