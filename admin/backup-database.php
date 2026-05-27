<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';

$user = getCurrentUser();
if (!$user || empty($user['is_admin'])) {
    header('Content-Type: text/plain');
    die('Admin access required');
}

$format = $_GET['format'] ?? 'sql';

if ($format === 'sql') {
    // Export as SQL dump
    $db = getDB();
    
    // Get all tables
    $tables = [];
    $result = $db->query("SHOW TABLES");
    while ($row = $result->fetch_array()) {
        $tables[] = $row[0];
    }
    
    $output = "-- Paddock Picks Database Backup\n";
    $output .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
    $output .= "-- Database: " . DB_NAME . "\n\n";
    $output .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";
    
    foreach ($tables as $table) {
        // Create table structure
        $createResult = $db->query("SHOW CREATE TABLE `$table`");
        $createRow = $createResult->fetch_array();
        $output .= "\n\n-- Table structure for `$table`\n\n";
        $output .= "DROP TABLE IF EXISTS `$table`;\n";
        $output .= $createRow[1] . ";\n\n";
        
        // Get data
        $dataResult = $db->query("SELECT * FROM `$table`");
        $columns = [];
        $colResult = $db->query("SHOW COLUMNS FROM `$table`");
        while ($col = $colResult->fetch_array()) {
            $columns[] = $col[0];
        }
        
        if ($dataResult->num_rows > 0) {
            $output .= "-- Data for `$table`\n\n";
            while ($row = $dataResult->fetch_assoc()) {
                $values = [];
                foreach ($columns as $col) {
                    $val = $row[$col];
                    if ($val === null) {
                        $values[] = 'NULL';
                    } else {
                        $values[] = "'" . $db->real_escape_string($val) . "'";
                    }
                }
                $output .= "INSERT INTO `$table` (`" . implode('`, `', $columns) . "`) VALUES (" . implode(', ', $values) . ");\n";
            }
            $output .= "\n";
        }
    }
    
    $output .= "SET FOREIGN_KEY_CHECKS = 1;\n";
    
    $filename = 'paddock-picks-backup-' . date('Y-m-d-Hi') . '.sql';
    
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($output));
    echo $output;
    exit;
}

// HTML interface
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Database Backup - Paddock Picks</title>
    <style>
        body{background:#0c0f16;color:#f0f2f5;font-family:sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0}
        .card{background:#181e2c;border:1px solid rgba(255,255,255,0.05);border-radius:16px;padding:40px;max-width:480px;text-align:center}
        h1{font-size:24px;margin-bottom:8px}
        p{color:#8a92a8;font-size:14px;margin-bottom:24px;line-height:1.5}
        .btn{display:inline-flex;align-items:center;gap:8px;padding:14px 32px;border-radius:12px;font-weight:700;font-size:15px;border:none;cursor:pointer;text-decoration:none;transition:all 0.2s}
        .btn-primary{background:#7c3aed;color:#fff}
        .btn-primary:hover{background:#6d28d9;transform:translateY(-1px)}
        .btn-outline{background:transparent;border:1px solid rgba(255,255,255,0.1);color:#8a92a8}
        .btn-outline:hover{border-color:#7c3aed;color:#fff}
        .note{font-size:11px;color:#555b6e;margin-top:16px}
    </style>
</head>
<body>
    <div class="card">
        <div style="font-size:40px;margin-bottom:12px">💾</div>
        <h1>Database Backup</h1>
        <p>Download a complete SQL dump of all tables.<br>No data is deleted — this is a read-only export.</p>
        <a href="?format=sql" class="btn btn-primary"><span>⬇</span> Download Backup</a>
        <div class="note">⚠️ Keep this file secure — it contains all user data.</div>
    </div>
</body>
</html>
