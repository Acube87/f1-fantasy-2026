# Game-Style Landing Page

## Overview

The `index-landing.php` file is a modern, racing-themed landing page designed to showcase the F1 2026 Fantasy application with a game-style aesthetic. It combines login functionality with feature highlights in an engaging, visually appealing interface.

## Design Features

### Visual Elements

1. **Gradient Background**
   - Dark purple/black gradient base
   - Radial gradient overlays in pink and blue
   - Grid pattern overlay for depth

2. **Neon Effects**
   - Pink and blue neon text shadows
   - Glowing elements on hover
   - Racing-style color scheme (pink #ec4899, blue #3b82f6, purple #a855f7)

3. **Glass Morphism**
   - Semi-transparent cards with backdrop blur
   - Subtle borders with low opacity
   - Modern, layered appearance

4. **Animations**
   - Floating animation on login card
   - Sliding racing stripes at top and bottom
   - Hover effects on buttons and cards
   - Smooth transitions throughout

### Layout Structure

#### Left Side - Hero Content
- **Season Badge** - "Season 2026" indicator
- **Large Headlines** - "Predict The Race" with gradient text
- **Description** - Brief explanation of the application
- **Stats Grid** - 24 Races, 10 Teams, 20 Drivers
- **Feature List** - Three key features with icons

#### Right Side - Login Card
- **Floating Card** - Glass morphism design with decorative blur elements
- **Login Form** - Username/email and password fields
- **Remember Me** - Checkbox option
- **Forgot Password** - Link to recovery
- **Sign Up Link** - For new users
- **Powered By** - Branding element at bottom

#### Bottom Section - Feature Cards
Three cards highlighting:
1. **Win Prizes** 🏆 - Competition and rewards
2. **Live Stats** 📊 - Real-time tracking
3. **Quick Setup** ⚡ - Easy onboarding

### Technical Details

#### Dependencies
- **Tailwind CSS** (via CDN) - Utility-first CSS framework
- **Google Fonts** - Orbitron (racing font) and Inter (body text)

#### Responsive Design
- Desktop: Two-column layout with hero and login side-by-side
- Tablet/Mobile: Single column with stacked elements
- Breakpoints: `lg` (1024px) for major layout changes

#### Color Palette
- **Pink**: `#ec4899` (primary accent)
- **Purple**: `#a855f7` (secondary accent)
- **Blue**: `#3b82f6` (tertiary accent)
- **Dark Background**: `#0a0a0a` to `#2d1b3d` (gradients)

## Usage

### As Main Landing Page
To use this as the main landing page, you can either:
1. Rename `index.php` to `index-old.php` and rename `index-landing.php` to `index.php`
2. Update your web server configuration to serve `index-landing.php` as the default page

### Login Functionality
The page includes full login functionality:
- Form submits to itself via POST method
- Integrates with existing `includes/auth.php` authentication system
- Redirects to `dashboard.php` on successful login
- Displays error messages for invalid credentials

### Navigation
Header includes links to:
- **Home** - Links to `index.php`
- **Leaderboard** - Links to `leaderboard.php`
- **Sign Up** - Links to `signup.php`

## Customization

### Changing Colors
Update the color values in the `<style>` section:
```css
/* Pink accent */
#ec4899 → your color

/* Blue accent */
#3b82f6 → your color

/* Purple accent */
#a855f7 → your color
```

### Modifying Stats
Update the stats in the HTML:
```html
<div class="text-3xl font-bold racing-font text-pink-400">24</div>
<div class="text-xs text-gray-400 mt-1">Races</div>
```

### Changing Branding
Update the "Powered By" section:
```html
<span class="text-white font-bold text-xs">F1</span>
<span class="text-lg font-bold racing-font...">Office League</span>
```

## Browser Compatibility

- **Modern Browsers**: Full support (Chrome, Firefox, Safari, Edge)
- **Backdrop Filter**: May not work on older browsers (graceful degradation)
- **Animations**: Uses CSS animations (widely supported)

## Performance

- **CDN Resources**: Tailwind CSS and fonts loaded from CDN
- **Inline Styles**: Critical CSS inlined for faster initial render
- **Animations**: GPU-accelerated transforms for smooth performance
- **Images**: SVG for F1 car silhouettes (minimal file size)

## Accessibility

- Semantic HTML structure
- Proper heading hierarchy
- Form labels for inputs
- Keyboard navigation support
- Color contrast for readability

## Future Enhancements

Potential improvements:
1. Add animation on scroll (fade-in effects)
2. Implement dark/light theme toggle
3. Add social login options
4. Include video background
5. Add testimonials section
6. Integrate live race countdown
