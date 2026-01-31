<?php
/**
 * Rate Limiting Functions
 * Protects against brute force attacks
 */

/**
 * Check if IP is rate limited for a specific action
 * Returns array with 'allowed' boolean and 'retry_after' timestamp
 */
function checkRateLimit($action, $maxAttempts = 5, $windowMinutes = 15) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $key = "ratelimit_{$action}_{$ip}";
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = [
            'attempts' => 0,
            'first_attempt' => time(),
            'locked_until' => null
        ];
    }
    
    $data = $_SESSION[$key];
    $now = time();
    
    // Check if locked
    if ($data['locked_until'] && $now < $data['locked_until']) {
        return [
            'allowed' => false,
            'retry_after' => $data['locked_until'],
            'attempts_remaining' => 0
        ];
    }
    
    // Reset if window expired
    if ($now - $data['first_attempt'] > ($windowMinutes * 60)) {
        $_SESSION[$key] = [
            'attempts' => 0,
            'first_attempt' => $now,
            'locked_until' => null
        ];
        $data = $_SESSION[$key];
    }
    
    // Check if exceeded
    if ($data['attempts'] >= $maxAttempts) {
        $lockUntil = $now + ($windowMinutes * 60);
        $_SESSION[$key]['locked_until'] = $lockUntil;
        
        return [
            'allowed' => false,
            'retry_after' => $lockUntil,
            'attempts_remaining' => 0
        ];
    }
    
    return [
        'allowed' => true,
        'retry_after' => null,
        'attempts_remaining' => $maxAttempts - $data['attempts']
    ];
}

/**
 * Record a failed attempt
 */
function recordFailedAttempt($action) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $key = "ratelimit_{$action}_{$ip}";
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = [
            'attempts' => 0,
            'first_attempt' => time(),
            'locked_until' => null
        ];
    }
    
    $_SESSION[$key]['attempts']++;
}

/**
 * Reset rate limit on successful action
 */
function resetRateLimit($action) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $key = "ratelimit_{$action}_{$ip}";
    
    if (isset($_SESSION[$key])) {
        unset($_SESSION[$key]);
    }
}

/**
 * Get human-readable time remaining
 */
function getRetryAfterMessage($timestamp) {
    $seconds = $timestamp - time();
    if ($seconds <= 0) return "now";
    
    $minutes = ceil($seconds / 60);
    if ($minutes == 1) return "1 minute";
    return "{$minutes} minutes";
}
?>
