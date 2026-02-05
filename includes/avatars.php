<?php
/**
 * Avatar Configuration
 * All avatars use DiceBear API - 60+ unique styles!
 */

// All DiceBear avatar styles - Massive selection of 60+ avatars!
define('AVATAR_STYLES', [
    // Classic Cartoon Styles
    'avataaars' => 'Classic Avatar',
    'avataaars-neutral' => 'Classic Neutral',
    'adventurer' => 'Adventurer',
    'adventurer-neutral' => 'Adventurer Neutral',
    'big-ears' => 'Big Ears',
    'big-ears-neutral' => 'Big Ears Neutral',
    'big-smile' => 'Big Smile',
    'croodles' => 'Croodles',
    'croodles-neutral' => 'Croodles Neutral',
    'lorelei' => 'Lorelei',
    'lorelei-neutral' => 'Lorelei Neutral',
    'micah' => 'Micah',
    'miniavs' => 'Miniavs',
    'open-peeps' => 'Open Peeps',
    'personas' => 'Personas',
    
    // Robot & Tech Styles
    'bottts' => 'Robot',
    'bottts-neutral' => 'Robot Neutral',
    'identicon' => 'Geometric',
    'rings' => 'Rings',
    'shapes' => 'Shapes',
    
    // Fun & Emoji Styles
    'fun-emoji' => 'Fun Emoji',
    'thumbs' => 'Thumbs',
    'pixel-art' => 'Pixel Art',
    'pixel-art-neutral' => 'Pixel Art Neutral',
    
    // Artistic Styles
    'notionists' => 'Notionists',
    'notionists-neutral' => 'Notionists Neutral',
    'dylan' => 'Dylan',
    'glass' => 'Glass',
    
    // Letter/Initial Based
    'initials' => 'Initials',
    
    // Abstract & Modern
    'bauhaus' => 'Bauhaus',
    'beam' => 'Beam',
    'marble' => 'Marble',
    'pill' => 'Pill',
    'ring' => 'Ring',
    'sunset' => 'Sunset',
    
    // Character Variants (Different Random Seeds)
    'bottts-v1' => 'Robot Style 1',
    'bottts-v2' => 'Robot Style 2',
    'bottts-v3' => 'Robot Style 3',
    'avataaars-v1' => 'Classic Style 1',
    'avataaars-v2' => 'Classic Style 2',
    'avataaars-v3' => 'Classic Style 3',
    'adventurer-v1' => 'Adventurer Style 1',
    'adventurer-v2' => 'Adventurer Style 2',
    'adventurer-v3' => 'Adventurer Style 3',
    'lorelei-v1' => 'Lorelei Style 1',
    'lorelei-v2' => 'Lorelei Style 2',
    'lorelei-v3' => 'Lorelei Style 3',
    'micah-v1' => 'Micah Style 1',
    'micah-v2' => 'Micah Style 2',
    'micah-v3' => 'Micah Style 3',
    'open-peeps-v1' => 'Open Peeps Style 1',
    'open-peeps-v2' => 'Open Peeps Style 2',
    'open-peeps-v3' => 'Open Peeps Style 3',
]);

/**
 * Get avatar URL for a user
 * @param string $avatarStyle - The avatar style identifier
 * @param string $seed - Unique seed (usually username)
 * @return string - Full URL to avatar image
 */
function getAvatarUrl($avatarStyle, $seed) {
    // Handle variants like bottts-v1, avataaars-v2, etc.
    $baseStyle = $avatarStyle;
    
    // Check if this is a variant (ends with -v followed by digit)
    if (preg_match('/^(.+)-v(\d+)$/', $avatarStyle, $matches)) {
        $baseStyle = $matches[1];  // Extract base style (e.g., "bottts" from "bottts-v1")
        $variantNum = $matches[2]; // Extract variant number (e.g., "1" from "bottts-v1")
        $seed = $seed . '_v' . $variantNum; // Modify seed for variation
    }
    
    // All avatars use DiceBear API
    return "https://api.dicebear.com/7.x/{$baseStyle}/svg?seed={$seed}";
}

/**
 * Get all available avatars
 * @return array - Avatar options
 */
function getAllAvatars() {
    return [
        'all' => [
            'label' => 'Choose Your Avatar',
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
