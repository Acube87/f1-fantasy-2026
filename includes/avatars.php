<?php
/**
 * Avatar Configuration
 * Defines all available avatar options for the app
 */

// Custom pixel art avatars (stored locally in assets/avatars/)
define('PIXEL_AVATARS', [
    'pixel-char-1' => 'Pixel Warrior 1',
    'pixel-char-2' => 'Pixel Warrior 2',
    'pixel-char-3' => 'Pixel Mage',
    'pixel-char-4' => 'Pixel Knight',
    'pixel-char-5' => 'Pixel Archer',
    'pixel-char-6' => 'Pixel Rogue',
    'pixel-char-7' => 'Pixel Wizard',
    'pixel-char-8' => 'Pixel Paladin',
    'pixel-char-9' => 'Pixel Ranger',
    'pixel-char-10' => 'Pixel Samurai',
    'pixel-char-11' => 'Pixel Ninja',
    'pixel-char-12' => 'Pixel Monk',
    'pixel-char-13' => 'Pixel Cleric',
    'pixel-char-14' => 'Pixel Barbarian',
    'pixel-char-15' => 'Pixel Druid',
    'pixel-char-16' => 'Pixel Sorcerer',
    'pixel-char-17' => 'Pixel Bard',
    'pixel-char-18' => 'Pixel Assassin',
]);

// DiceBear avatar styles (API-based)
define('DICEBEAR_STYLES', [
    'avataaars' => 'Avataaars',
    'bottts' => 'Bottts (Robot)',
    'pixel-art' => 'Pixel Art',
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
    // Check if it's a pixel avatar (local file)
    if (strpos($avatarStyle, 'pixel-char-') === 0) {
        // Return relative path to local pixel avatar
        return 'assets/avatars/' . $avatarStyle . '.png';
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
        'pixel' => [
            'label' => '🎮 Pixel Characters',
            'avatars' => PIXEL_AVATARS
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
    if (isset(PIXEL_AVATARS[$avatarStyle])) {
        return PIXEL_AVATARS[$avatarStyle];
    }
    
    if (isset(DICEBEAR_STYLES[$avatarStyle])) {
        return DICEBEAR_STYLES[$avatarStyle];
    }
    
    return 'Unknown Avatar';
}
?>
