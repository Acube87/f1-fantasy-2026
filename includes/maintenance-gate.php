<?php
/**
 * Maintenance Mode Gate
 * Safe to include at the very top of any public page.
 * Admin pages (in /admin/) bypass this automatically.
 */
if (!defined('MAINTENANCE_MODE')) {
    require_once __DIR__ . '/../config.php';
}

if (defined('MAINTENANCE_MODE') && MAINTENANCE_MODE === true) {
    $currentFile = $_SERVER['SCRIPT_FILENAME'] ?? '';
    $isAdminPage = strpos($currentFile, DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR) !== false;
    
    if (!$isAdminPage) {
        include __DIR__ . '/../maintenance.php';
        exit;
    }
}
?>
