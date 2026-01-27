<?php
require_once 'includes/auth.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        if (loginUser($username, $password)) {
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Invalid username or password';
        }
    }
}

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header>
        <nav>
            <div class="container">
                <div>
                    <h1>🏎️ <?php echo SITE_NAME; ?></h1>
                    <p style="font-size: 0.75rem; color: rgba(255,255,255,0.6); margin-top: 0.25rem;">Powered by Scanerrific</p>
                </div>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="signup.php">Sign Up</a></li>
                </ul>
            </div>
        </nav>
    </header>

    <main class="container">
        <div class="auth-container">
            <h2>Login</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="login.php">
                <div class="form-group">
                    <label for="username">Username or Email</label>
                    <input type="text" id="username" name="username" required autofocus>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Login</button>
            </form>
            
            <p class="auth-link">Don't have an account? <a href="signup.php">Sign up here</a></p>
        </div>
    </main>

    <footer style="text-align: center; padding: 2rem 0; color: #999; border-top: 1px solid rgba(255,255,255,0.1); margin-top: 2rem;">
        <p>Powered by <a href="https://scanerrific.com" target="_blank" style="color: #e10600;">Scanerrific</a></p>
    </footer>
</body>
</html>

