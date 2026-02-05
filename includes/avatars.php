<?php
/**
 * Avatar Configuration
 * Defines all available avatar options for the app
 */

// Custom emoji avatars (SVG files stored locally in assets/avatars/)
define('EMOJI_AVATARS', [
    'emoji-boy' => '👦 Boy',
    'emoji-girl' => '👧 Girl',
    'emoji-man' => '👨 Man',
    'emoji-woman' => '👩 Woman',
    'emoji-cool' => '😎 Cool',
    'emoji-nerd' => '🤓 Nerd',
    'emoji-happy' => '😊 Happy',
    'emoji-laugh' => '😄 Laugh',
    'emoji-wink' => '😉 Wink',
    'emoji-smile' => '😃 Smile',
    'emoji-love' => '😍 Love',
    'emoji-star' => '⭐ Star',
]);

// DiceBear avatar styles (API-based)
define('DICEBEAR_STYLES', [
    'avataaars' => 'Avataaars',
    'bottts' => 'Bottts (Robot)',
    'lorelei' => 'Lorelei',
    'adventurer' => 'Adventurer',
    'big-smile' => 'Big Smile',
    'fun-emoji' => 'Fun Emoji',
    'thumbs' => 'Thumbs',
]);

/**
 * Get avatar URL for a user
 * @param string $avatarStyle - The avatar style identifier
 * @param string $seed - Unique seed (usually username)
 * @return string - Full URL to avatar image
 */
function getAvatarUrl($avatarStyle, $seed) {
    // Check if it's an emoji avatar (local SVG file)
    if (strpos($avatarStyle, 'emoji-') === 0) {
        // Return absolute path to local emoji avatar (with leading slash for web root)
        return '/assets/avatars/' . $avatarStyle . '.svg';
    }
    
    // Otherwise, use DiceBear API
    return "https://api.dicebear.com/7.x/{$avatarStyle}/svg?seed={$seed}";
}

/**
 * Get all available avatars grouped by type
 * @return array - Grouped avatar options
 */
function getAllAvatars() {
    return [
        'emoji' => [
            'label' => '😊 Emoji Avatars',
            'avatars' => EMOJI_AVATARS
        ],
        'dicebear' => [
            'label' => '🎨 Generated Styles',
            'avatars' => DICEBEAR_STYLES
        ]
    ];
}

/**
 * Get avatar name/label
 * @param string $avatarStyle
 * @return string
 */
function getAvatarName($avatarStyle) {
    if (isset(EMOJI_AVATARS[$avatarStyle])) {
        return EMOJI_AVATARS[$avatarStyle];
    }
    
    if (isset(DICEBEAR_STYLES[$avatarStyle])) {
        return DICEBEAR_STYLES[$avatarStyle];
    }
    
    return 'Unknown Avatar';
}
?>
