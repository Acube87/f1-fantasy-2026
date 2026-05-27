<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/ratelimit.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'login':
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            echo json_encode(['ok' => false, 'error' => 'Please fill in all fields']);
            exit;
        }
        
        $rateLimit = checkRateLimit('login', 5, 15);
        if (!$rateLimit['allowed']) {
            echo json_encode(['ok' => false, 'error' => 'Too many attempts. Try again later.']);
            exit;
        }
        
        if (loginUser($username, $password)) {
            resetRateLimit('login');
            echo json_encode(['ok' => true]);
        } else {
            recordFailedAttempt('login');
            echo json_encode(['ok' => false, 'error' => 'Invalid username or password']);
        }
        break;

    case 'signup':
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $fullName = trim($_POST['full_name'] ?? '');

        if (empty($username) || empty($email) || empty($password)) {
            echo json_encode(['ok' => false, 'error' => 'Please fill in all required fields']);
            exit;
        }
        if (strlen($password) < 8) {
            echo json_encode(['ok' => false, 'error' => 'Password must be at least 8 characters']);
            exit;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['ok' => false, 'error' => 'Invalid email address']);
            exit;
        }
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            echo json_encode(['ok' => false, 'error' => 'Username can only contain letters, numbers, and underscores']);
            exit;
        }

        $rateLimit = checkRateLimit('signup', 3, 15);
        if (!$rateLimit['allowed']) {
            echo json_encode(['ok' => false, 'error' => 'Too many signup attempts. Try again later.']);
            exit;
        }

        $result = registerUser($username, $email, $password, $fullName);
        if ($result['success']) {
            // Auto-login after signup
            loginUser($username, $password);
            echo json_encode(['ok' => true]);
        } else {
            recordFailedAttempt('signup');
            echo json_encode(['ok' => false, 'error' => $result['message']]);
        }
        break;

    case 'logout':
        logoutUser();
        echo json_encode(['ok' => true]);
        break;

    case 'check':
        echo json_encode(['authenticated' => isLoggedIn(), 'user' => getCurrentUser()]);
        break;

    default:
        echo json_encode(['ok' => false, 'error' => 'Unknown action']);
}
