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
    $stmt = $db->prepare("SELECT id, username, email, full_name FROM users WHERE id = ?");
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
    // Adjusted column name from password_hash to password to match database setup
    // Removed is_active check as it's not in the setup script
    $stmt = $db->prepare("SELECT id, username, email, password, full_name FROM users WHERE (username = ? OR email = ?)");
    $stmt->bind_param("ss", $username, $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        
        // Removed last_login update as the column doesn't exist in setup script
        
        return true;
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
    
    // Adjusted column name from password_hash to password
    $stmt = $db->prepare("INSERT INTO users (username, email, password, full_name) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $username, $email, $passwordHash, $fullName);
    
    if ($stmt->execute()) {
        $userId = $db->insert_id;
        
        // Removed user_totals insert as table doesn't exist in setup script
        
        return ['success' => true, 'user_id' => $userId];
    }
    
    return ['success' => false, 'message' => 'Registration failed: ' . $db->error];
}

/**
 * Logout user
 */
function logoutUser() {
    session_destroy();
    header('Location: login.php');
    exit;
}
