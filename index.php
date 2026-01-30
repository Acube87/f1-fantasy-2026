<?php
// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/auth.php';
require_once 'includes/functions.php';

$user = getCurrentUser();

// Handle login form submission
$loginError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_submit'])) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $loginError = 'Please fill in all fields';
    } else {
        if (loginUser($username, $password)) {
            header('Location: dashboard.php');
            exit;
        } else {
            $loginError = 'Invalid username or password';
        }
    }
}

// Redirect if already logged in
if ($user) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>F1 2026 Prediction - Office Racing League</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #0a0e27;
            color: #fff;
            overflow-x: hidden;
        }

        /* ==================== HEADER ==================== */
        .header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: rgba(10, 14, 39, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding: 20px 0;
            z-index: 1000;
        }

        .header-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .logo-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #e94560, #c72845);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .logo-text {
            font-size: 20px;
            font-weight: 800;
            color: #fff;
        }

        .nav {
            display: flex;
            gap: 40px;
            align-items: center;
        }

        .nav a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            font-weight: 600;
            font-size: 15px;
            transition: color 0.3s;
        }

        .nav a:hover {
            color: #e94560;
        }

        .btn-header {
            padding: 10px 24px;
            background: #e94560;
            border: none;
            border-radius: 8px;
            color: #fff;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-header:hover {
            background: #ff5573;
            transform: translateY(-2px);
        }

        .btn-header-outline {
            padding: 10px 24px;
            background: transparent;
            border: 2px solid #e94560;
            border-radius: 8px;
            color: #fff;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-header-outline:hover {
            background: #e94560;
        }

        /* ==================== HERO SECTION ==================== */
        .hero {
            margin-top: 90px;
            min-height: 100vh;
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
        }

        /* Decorative Dots */
        .dots-left, .dots-right {
            position: absolute;
            width: 200px;
            height: 400px;
            opacity: 0.3;
        }

        .dots-left {
            left: 0;
            top: 20%;
            background-image: radial-gradient(circle, rgba(233, 69, 96, 0.3) 2px, transparent 2px);
            background-size: 15px 15px;
        }

        .dots-right {
            right: 0;
            bottom: 20%;
            background-image: radial-gradient(circle, rgba(233, 69, 96, 0.3) 2px, transparent 2px);
            background-size: 15px 15px;
        }

        /* Decorative Shapes */
        .shape {
            position: absolute;
            border-radius: 50%;
        }

        .shape-red {
            width: 40px;
            height: 40px;
            background: #e94560;
            top: 15%;
            left: 15%;
            animation: float 6s ease-in-out infinite;
        }

        .shape-small {
            width: 20px;
            height: 20px;
            background: #e94560;
            bottom: 30%;
            right: 20%;
            animation: float 4s ease-in-out infinite 1s;
        }

        .shape-cross {
            width: 30px;
            height: 30px;
            position: absolute;
            top: 60%;
            left: 10%;
        }

        .shape-cross::before,
        .shape-cross::after {
            content: '';
            position: absolute;
            background: #e94560;
        }

        .shape-cross::before {
            width: 30px;
            height: 3px;
            top: 50%;
            left: 0;
        }

        .shape-cross::after {
            width: 3px;
            height: 30px;
            left: 50%;
            top: 0;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }

        .hero-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 80px 40px;
            position: relative;
            z-index: 2;
        }

        .hero-badge {
            display: inline-block;
            padding: 8px 20px;
            background: rgba(233, 69, 96, 0.15);
            border: 1px solid #e94560;
            border-radius: 20px;
            color: #e94560;
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 30px;
            letter-spacing: 1px;
        }

        .hero-title-small {
            font-size: 20px;
            color: #e94560;
            font-weight: 600;
            margin-bottom: 15px;
            letter-spacing: 2px;
        }

        .hero-title {
            font-size: 72px;
            font-weight: 900;
            line-height: 1.1;
            margin-bottom: 20px;
            text-transform: uppercase;
            max-width: 800px;
        }

        .hero-title .highlight {
            color: #e94560;
            display: block;
        }

        .hero-subtitle {
            font-size: 18px;
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 40px;
            max-width: 600px;
            line-height: 1.8;
        }

        .btn-primary {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 18px 40px;
            background: #e94560;
            border: none;
            border-radius: 12px;
            color: #fff;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
        }

        .btn-primary:hover {
            background: #ff5573;
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(233, 69, 96, 0.4);
        }

        /* Hero Stats */
        .hero-stats {
            display: flex;
            gap: 50px;
            margin-top: 60px;
        }

        .stat {
            text-align: center;
        }

        .stat-value {
            font-size: 48px;
            font-weight: 900;
            color: #e94560;
            line-height: 1;
        }

        .stat-label {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.6);
            margin-top: 5px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* ==================== FEATURED TEAMS/DRIVERS SECTION ==================== */
        .featured-section {
            padding: 120px 40px;
            position: relative;
        }

        .section-header {
            text-align: center;
            margin-bottom: 80px;
        }

        .section-title-small {
            font-size: 16px;
            color: #e94560;
            font-weight: 700;
            letter-spacing: 2px;
            margin-bottom: 15px;
        }

        .section-title {
            font-size: 48px;
            font-weight: 900;
            margin-bottom: 15px;
        }

        .section-title .highlight {
            color: #e94560;
        }

        .section-subtitle {
            font-size: 16px;
            color: rgba(255, 255, 255, 0.6);
        }

        /* Teams Grid */
        .teams-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            max-width: 1400px;
            margin: 0 auto;
        }

        .team-card {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 40px 30px;
            text-align: center;
            transition: all 0.4s;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .team-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #e94560, #ff6b9d);
            transform: scaleX(0);
            transition: transform 0.4s;
        }

        .team-card:hover {
            background: rgba(255, 255, 255, 0.08);
            border-color: #e94560;
            transform: translateY(-10px);
        }

        .team-card:hover::before {
            transform: scaleX(1);
        }

        .team-icon {
            font-size: 64px;
            margin-bottom: 20px;
            display: inline-block;
            animation: float 3s ease-in-out infinite;
        }

        .team-card:nth-child(2) .team-icon { animation-delay: 0.5s; }
        .team-card:nth-child(3) .team-icon { animation-delay: 1s; }
        .team-card:nth-child(4) .team-icon { animation-delay: 1.5s; }

        .team-name {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 8px;
            color: #fff;
        }

        .team-info {
            font-size: 13px;
            color: rgba(255, 255, 255, 0.6);
            margin-bottom: 20px;
        }

        .team-btn {
            display: inline-block;
            padding: 8px 20px;
            background: rgba(233, 69, 96, 0.15);
            border: 1px solid #e94560;
            border-radius: 8px;
            color: #e94560;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.3s;
        }

        .team-btn:hover {
            background: #e94560;
            color: #fff;
        }

        /* Pagination Dots */
        .pagination-dots {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 60px;
        }

        .dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            cursor: pointer;
            transition: all 0.3s;
        }

        .dot.active {
            background: #e94560;
            width: 30px;
            border-radius: 6px;
        }

        /* ==================== LOGIN MODAL (Overlay) ==================== */
        .login-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(10, 14, 39, 0.95);
            backdrop-filter: blur(10px);
            z-index: 2000;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-overlay.active {
            display: flex;
        }

        .login-modal {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            padding: 50px;
            max-width: 500px;
            width: 100%;
            position: relative;
        }

        .close-modal {
            position: absolute;
            top: 20px;
            right: 20px;
            background: transparent;
            border: none;
            color: rgba(255, 255, 255, 0.6);
            font-size: 28px;
            cursor: pointer;
            transition: color 0.3s;
        }

        .close-modal:hover {
            color: #fff;
        }

        .login-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .login-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #e94560, #c72845);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            margin: 0 auto 20px;
        }

        .login-title {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .login-subtitle {
            color: rgba(255, 255, 255, 0.6);
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            font-size: 14px;
            color: rgba(255, 255, 255, 0.9);
        }

        .form-input {
            width: 100%;
            padding: 14px 16px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            color: #fff;
            font-size: 15px;
            transition: all 0.3s;
        }

        .form-input:focus {
            outline: none;
            border-color: #e94560;
            background: rgba(255, 255, 255, 0.08);
        }

        .form-input::placeholder {
            color: rgba(255, 255, 255, 0.3);
        }

        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            font-size: 13px;
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 8px;
            color: rgba(255, 255, 255, 0.7);
        }

        .forgot-link {
            color: #e94560;
            text-decoration: none;
        }

        .btn-login {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #e94560, #c72845);
            border: none;
            border-radius: 10px;
            color: #fff;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(233, 69, 96, 0.4);
        }

        .signup-text {
            text-align: center;
            margin-top: 20px;
            color: rgba(255, 255, 255, 0.7);
            font-size: 14px;
        }

        .signup-text a {
            color: #e94560;
            text-decoration: none;
            font-weight: 600;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        /* ==================== RESPONSIVE ==================== */
        @media (max-width: 768px) {
            .header-content {
                padding: 0 20px;
            }

            .nav {
                gap: 20px;
            }

            .hero-title {
                font-size: 48px;
            }

            .hero-stats {
                gap: 30px;
            }

            .teams-grid {
                grid-template-columns: 1fr;
            }

            .section-title {
                font-size: 36px;
            }

            .login-modal {
                padding: 40px 30px;
            }
        }
    </style>
</head>
<body>

    <!-- HEADER -->
    <header class="header">
        <div class="header-content">
            <div class="logo">
                <div class="logo-icon">🏁</div>
                <span class="logo-text">PADDOCK PICKS</span>
            </div>
            <nav class="nav">
                <a href="#explore">Explore</a>
                <a href="#races">Races</a>
                <a href="#discover">Discover New Season</a>
                <button class="btn-header-outline" onclick="openLogin()">Log In</button>
                <button class="btn-header" onclick="openSignup()">Sign Up</button>
            </nav>
        </div>
    </header>

    <!-- HERO SECTION -->
    <section class="hero">
        <!-- Decorative Elements -->
        <div class="dots-left"></div>
        <div class="dots-right"></div>
        <div class="shape shape-red"></div>
        <div class="shape shape-small"></div>
        <div class="shape-cross"></div>

        <div class="hero-container">
            <div class="hero-badge">🏁 SEASON STARTS MARCH 6 • MELBOURNE</div>
            <h2 class="hero-title-small">Predict F1 Races You Will Love</h2>
            <h1 class="hero-title">
                BE THE BEST<br>
                <span class="highlight">PREDICTOR AND GET</span>
                <span class="highlight">TO THE TOP</span>
            </h1>
            <p class="hero-subtitle">
                Experience the all-new F1 2026 season with revolutionary regulations. 
                Join the ultimate office prediction league and compete with 22 drivers across 24 epic races.
            </p>
            <a href="#" class="btn-primary" onclick="openLogin(); return false;">
                Start Predicting →
            </a>

            <div class="hero-stats">
                <div class="stat">
                    <div class="stat-value">24</div>
                    <div class="stat-label">Races</div>
                </div>
                <div class="stat">
                    <div class="stat-value">11</div>
                    <div class="stat-label">Teams</div>
                </div>
                <div class="stat">
                    <div class="stat-value">22</div>
                    <div class="stat-label">Drivers</div>
                </div>
            </div>
        </div>
    </section>

    <!-- FEATURED TEAMS SECTION -->
    <section class="featured-section">
        <div class="section-header">
            <div class="section-title-small">OUR <span class="highlight">TEAMS</span></div>
            <h2 class="section-title">The 2026 <span class="highlight">Grid</span></h2>
            <p class="section-subtitle">11 legendary teams, 22 incredible drivers</p>
        </div>

        <div class="teams-grid">
            <div class="team-card">
                <div class="team-icon">🏎️</div>
                <div class="team-name">McLaren</div>
                <div class="team-info">Norris • Piastri</div>
                <a href="#" class="team-btn">Learn More</a>
            </div>

            <div class="team-card">
                <div class="team-icon">🏎️</div>
                <div class="team-name">Ferrari</div>
                <div class="team-info">Leclerc • Hamilton</div>
                <a href="#" class="team-btn">Learn More</a>
            </div>

            <div class="team-card">
                <div class="team-icon">🏎️</div>
                <div class="team-name">Red Bull</div>
                <div class="team-info">Verstappen • Hadjar</div>
                <a href="#" class="team-btn">Learn More</a>
            </div>

            <div class="team-card">
                <div class="team-icon">🏎️</div>
                <div class="team-name">Mercedes</div>
                <div class="team-info">Russell • Antonelli</div>
                <a href="#" class="team-btn">Learn More</a>
            </div>
        </div>

        <div class="pagination-dots">
            <div class="dot active"></div>
            <div class="dot"></div>
            <div class="dot"></div>
        </div>
    </section>
    
    <footer style="border-top: 1px solid rgba(255,255,255,0.1); padding: 40px; text-align: center; background: rgba(10, 14, 39, 0.95);">
        <p style="color: rgba(255,255,255,0.6); margin-bottom: 10px;">&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</p>
        <p style="font-size: 14px; color: rgba(255,255,255,0.5);">
            Powered by <a href="https://www.scanerrific.com" target="_blank" style="color: #e94560; font-weight: 600; text-decoration: none;">Scanerrific</a>
        </p>
    </footer>

    <!-- LOGIN MODAL -->
    <div class="login-overlay" id="loginOverlay"<?php if ($loginError): ?> style="display: flex;"<?php endif; ?>>
        <div class="login-modal">
            <button class="close-modal" onclick="closeLogin()">×</button>
            
            <div class="login-header">
                <div class="login-icon">🏁</div>
                <h2 class="login-title">Login to Race</h2>
                <p class="login-subtitle">Enter your credentials to start predicting</p>
            </div>

            <form method="POST" action="index.php">
                <?php if ($loginError): ?>
                    <div class="alert-error"><?php echo htmlspecialchars($loginError); ?></div>
                <?php endif; ?>
                
                <div class="form-group">
                    <label class="form-label">Email or Username</label>
                    <input type="text" name="username" class="form-input" placeholder="your@email.com" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-input" placeholder="••••••••" required>
                </div>

                <div class="form-options">
                    <label class="remember-me">
                        <input type="checkbox" name="remember">
                        <span>Remember me</span>
                    </label>
                    <a href="#" class="forgot-link">Forgot password?</a>
                </div>

                <input type="hidden" name="login_submit" value="1">
                <button type="submit" class="btn-login">START RACING</button>
            </form>

            <p class="signup-text">
                Don't have an account? <a href="signup.php">Sign up here</a>
            </p>
        </div>
    </div>

    <script>
        function openLogin() {
            document.getElementById('loginOverlay').classList.add('active');
        }

        function closeLogin() {
            document.getElementById('loginOverlay').classList.remove('active');
        }

        function openSignup() {
            window.location.href = 'signup.php';
        }

        // Close modal when clicking outside
        document.getElementById('loginOverlay').addEventListener('click', function(e) {
            if (e.target === this) {
                closeLogin();
            }
        });
    </script>

</body>
</html>
