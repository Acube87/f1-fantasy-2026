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
    $isLoginPage = strpos($currentFile, 'login.php') !== false;
    
    // Whitelist the admin account so you can see behind the maintenance page
    $isWhitelistedUser = isset($_SESSION['username']) && strtolower($_SESSION['username']) === 'angrycube';
    
    if (!$isAdminPage && !$isWhitelistedUser && !$isLoginPage) {
        include __DIR__ . '/../maintenance.php';
        exit;
    }
}
?>
