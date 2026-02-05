<?php
/**
 * Avatar Configuration
 * All avatars use DiceBear API - reliable and work everywhere
 */

// DiceBear avatar styles - All via API (no local files needed)
define('AVATAR_STYLES', [
    'avataaars' => '😊 Classic Avatar',
    'big-smile' => '😁 Big Smile',
    'bottts' => '🤖 Robot',
    'fun-emoji' => '😎 Fun Emoji',
    'lorelei' => '👩 Lorelei',
    'adventurer' => '🧑 Adventurer',
    'thumbs' => '👍 Thumbs', 
    'initials' => '🔤 Initials',
]);

/**
 * Get avatar URL for a user
 * @param string $avatarStyle - The avatar style identifier
 * @param string $seed - Unique seed (usually username)
 * @return string - Full URL to avatar image
 */
function getAvatarUrl($avatarStyle, $seed) {
    // All avatars use DiceBear API
    return "https://api.dicebear.com/7.x/{$avatarStyle}/svg?seed={$seed}";
}

/**
 * Get all available avatars
 * @return array - Avatar options
 */
function getAllAvatars() {
    return [
        'all' => [
            'label' => '🎨 Choose Your Avatar',
            'avatars' => AVATAR_STYLES
        ]
    ];
}

/**
 * Get avatar name/label
 * @param string $avatarStyle
 * @return string
 */
function getAvatarName($avatarStyle) {
    return AVATAR_STYLES[$avatarStyle] ?? 'Classic Avatar';
}
?>
