<?php
/**
 * Avatar Configuration
 * All avatars use DiceBear API - 50+ unique styles!
 */

// All DiceBear avatar styles - Massive selection!
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
    
    // Creature & Character Styles
    'bottts-neutral-variant1' => 'Robot V1',
    'bottts-neutral-variant2' => 'Robot V2',
    'bottts-neutral-variant3' => 'Robot V3',
    'avataaars-variant1' => 'Classic V1',
    'avataaars-variant2' => 'Classic V2',
    'adventurer-variant1' => 'Adventurer V1',
    'adventurer-variant2' => 'Adventurer V2',
    'lorelei-variant1' => 'Lorelei V1',
    'lorelei-variant2' => 'Lorelei V2',
    'micah-variant1' => 'Micah V1',
    'micah-variant2' => 'Micah V2',
]);

/**
 * Get avatar URL for a user
 * @param string $avatarStyle - The avatar style identifier
 * @param string $seed - Unique seed (usually username)
 * @return string - Full URL to avatar image
 */
function getAvatarUrl($avatarStyle, $seed) {
    // Handle variants - extract base style
    $baseStyle = preg_replace('/-variant\d+$/', '', $avatarStyle);
    
    // Add variant as additional randomization to seed
    if (strpos($avatarStyle, '-variant') !== false) {
        preg_match('/-variant(\d+)$/', $avatarStyle, $matches);
        $variantNum = $matches[1] ?? 1;
        $seed = $seed . $variantNum;
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
