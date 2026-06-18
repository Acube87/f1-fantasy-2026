<?php
require_once 'config.php';

try {
    $db = getDB();
    $stmt = $db->query("SELECT email FROM users ORDER BY email");
    $emails = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($emails)) {
        echo "No active users found.\n";
    } else {
        echo "Active user emails:\n";
        foreach ($emails as $email) {
            echo $email . "\n";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>