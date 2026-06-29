<?php
require_once __DIR__ . '/../config.php';

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get current user ID
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current user data
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    $db = getDB();
    
    // AUTO-MIGRATE: Ensure avatar_style, is_admin, is_active, last_login exist before selecting them
    // This protects the live app from crashing if the migration hasn't run yet
    static $migrationsChecked = false;
    if (!$migrationsChecked) {
        $tryCol = function($col, $def) use ($db) {
            $r = $db->query("SHOW COLUMNS FROM users LIKE '$col'");
            if ($r && $r->num_rows == 0) {
                $db->query("ALTER TABLE users ADD COLUMN $col $def");
            }
        };
        try {
            $tryCol('avatar_style', "VARCHAR(50) DEFAULT 'avataaars' AFTER email");
            $tryCol('is_admin', 'TINYINT(1) DEFAULT 0');
            $tryCol('is_active', 'BOOLEAN DEFAULT TRUE');
            $tryCol('last_login', 'TIMESTAMP NULL');
            $migrationsChecked = true;
        } catch (Exception $e) {
            // Silently continue, the query below might still fail if ALTER failed
        }
    }

    $stmt = $db->prepare("SELECT id, username, email, full_name, avatar_style, is_admin FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

/**
 * Require login - redirect to login page if not logged in
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Login user
 */
function loginUser($username, $password) {
    $db = getDB();
    
    // Check which password column name exists (for live sync)
    $passCol = 'password_hash';
    $check = $db->query("SHOW COLUMNS FROM users LIKE 'password'");
    if ($check && $check->num_rows > 0) {
        $passCol = 'password';
        // Attempt to RENAME to password_hash for future consistency (auto-migration)
        @$db->query("ALTER TABLE users CHANGE COLUMN password password_hash VARCHAR(255) NOT NULL");
        // If rename succeeded, use the new name
        $checkAgain = $db->query("SHOW COLUMNS FROM users LIKE 'password_hash'");
        if ($checkAgain && $checkAgain->num_rows > 0) $passCol = 'password_hash';
    }

    $stmt = $db->prepare("SELECT id, username, email, $passCol, full_name FROM users WHERE (username = ? OR email = ?)");
    $stmt->bind_param("ss", $username, $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if ($user) {
        // Use the dynamic column name for verification
        $storedHash = $user[$passCol];
        
        if (password_verify($password, $storedHash)) {
            // Regenerate session ID to prevent session fixation
            session_regenerate_id(true);
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            return true;
        }
    }
    
    return false;
}

/**
 * Register new user
 */
function registerUser($username, $email, $password, $fullName = '') {
    $db = getDB();
    
    // Check if username or email already exists
    $checkStmt = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $checkStmt->bind_param("ss", $username, $email);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if ($result->num_rows > 0) {
        return ['success' => false, 'message' => 'Username or email already exists'];
    }
    
    // Create user
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $db->prepare("INSERT INTO users (username, email, password_hash, full_name) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $username, $email, $passwordHash, $fullName);
    
    if ($stmt->execute()) {
        $userId = $db->insert_id;
        
        // Ensure user_totals entry exists for the new user
        $db->query("INSERT IGNORE INTO user_totals (user_id, total_points, races_participated) VALUES ($userId, 0, 0)");
        
        return ['success' => true, 'user_id' => $userId];
    }
    
    return ['success' => false, 'message' => 'Registration failed: ' . $db->error];
}

/**
 * Check if current user is admin
 */
function isAdmin() {
    $user = getCurrentUser();
    return $user && isset($user['is_admin']) && $user['is_admin'] == 1;
}

/**
 * Require admin access - redirect to home if not admin
 */
function requireAdmin() {
    if (!isAdmin()) {
        header('Location: ../index.php#dashboard');
        exit;
    }
}

/**
 * Logout user
 */
function logoutUser() {
    session_destroy();
    header('Location: login.php');
    exit;
}
