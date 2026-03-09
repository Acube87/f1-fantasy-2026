<?php
/**
 * CSRF Protection Functions
 */

/**
 * Generate a CSRF token and store in session
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Get the current CSRF token
 */
function getCSRFToken() {
    return $_SESSION['csrf_token'] ?? generateCSRFToken();
}

/**
 * Verify CSRF token from request
 */
function verifyCSRFToken($token) {
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Output a hidden CSRF token field for forms
 */
function csrfField() {
    $token = getCSRFToken();
    echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}

/**
 * Validate CSRF token from POST request or from parameter
 * Returns true if valid, false otherwise
 */
function validateCSRF($token = null) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return true; // Only validate POST requests
    }
    
    // If token is provided as parameter, use it (for JSON/AJAX requests)
    if ($token !== null) {
        return verifyCSRFToken($token);
    }
    
    // Otherwise, read from POST data (for form submissions)
    $token = $_POST['csrf_token'] ?? '';
    return verifyCSRFToken($token);
}
?>
