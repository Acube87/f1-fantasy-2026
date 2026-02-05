<?php
require_once 'includes/avatars.php';

echo "<h1>Debug Avatar System</h1>";
echo "<h2>AVATAR_STYLES constant:</h2>";
echo "<pre>";
print_r(AVATAR_STYLES);
echo "</pre>";

echo "<h2>getAllAvatars():</h2>";
echo "<pre>";
print_r(getAllAvatars());
echo "</pre>";

echo "<h2>Test Avatar URL:</h2>";
$testUrl = getAvatarUrl('avataaars', 'testuser');
echo "<p>URL: $testUrl</p>";
echo "<img src='$testUrl' width='100' alt='test'>";

echo "<h2>All 8 Avatars:</h2>";
foreach (AVATAR_STYLES as $style => $label) {
    echo "<div style='display:inline-block; margin:10px; text-align:center;'>";
    echo "<img src='" . getAvatarUrl($style, 'demo') . "' width='80' style='border-radius:50%; background:#333;'><br>";
    echo "<small>$label</small>";
    echo "</div>";
}
?>
