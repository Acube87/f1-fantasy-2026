<!DOCTYPE html>
<html>
<head>
    <title>Avatar Test</title>
    <style>
        body { background: #1a1a1a; color: white; padding: 20px; font-family: sans-serif; }
        .avatar { width: 100px; height: 100px; margin: 10px; display: inline-block; border: 2px solid #3b82f6; }
        .test { margin: 20px 0; }
    </style>
</head>
<body>
    <h1>🎮 Avatar Image Test</h1
    
    <div class="test">
        <h2>Test 1: Direct Path</h2>
        <img src="assets/avatars/pixel-char-1.png" class="avatar" alt="Test 1">
        <img src="assets/avatars/pixel-char-2.png" class="avatar" alt="Test 2">
        <img src="assets/avatars/pixel-char-3.png" class="avatar" alt="Test 3">
    </div>
    
    <div class="test">
        <h2>Test 2: Root Path</h2>
        <img src="/assets/avatars/pixel-char-1.png" class="avatar" alt="Test 1">
        <img src="/assets/avatars/pixel-char-2.png" class="avatar" alt="Test 2">
        <img src="/assets/avatars/pixel-char-3.png" class="avatar" alt="Test 3">
    </div>
    
    <div class="test">
        <h2>Test 3: PHP Check</h2>
        <?php
        require_once 'includes/avatars.php';
        
        echo "<p>Avatar URL for pixel-char-1: <code>" . getAvatarUrl('pixel-char-1', 'test') . "</code></p>";
        echo "<p>Avatar URL for avataaars: <code>" . getAvatarUrl('avataaars', 'test') . "</code></p>";
        
        echo '<img src="' . getAvatarUrl('pixel-char-1', 'test') . '" class="avatar" alt="PHP Test">';
        ?>
    </div>
    
    <div class="test">
        <h2>File Check</h2>
        <?php
        $file = 'assets/avatars/pixel-char-1.png';
        echo "<p>File exists: " . (file_exists($file) ? "✅ YES" : "❌ NO") . "</p>";
        echo "<p>Full path: <code>" . realpath($file) . "</code></p>";
        ?>
    </div>
</body>
</html>
