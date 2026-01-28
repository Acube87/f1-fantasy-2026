# Formula 1 2026 Fantasy Game

A modern, game-style Formula 1 fantasy application where users can predict race winners and standings. Features a sleek racing-themed UI with neon effects and animated elements.

## Features

- **Game-Style Landing Page** - Modern racing-themed landing page with login functionality (`index-landing.php`)
- User authentication (sign up/login)
- Real-time F1 results from official API
- Race predictions (drivers and constructors)
- Scoring system with bonus points for top 3 predictions
- Leaderboard and personal dashboard

## Tech Stack

- Frontend: HTML, CSS, JavaScript
- Backend: PHP
- Database: MySQL/MariaDB
- API: Ergast F1 API (or official F1 API)

## Quick Setup

1. Upload files to Hostinger hosting
2. Create MySQL database
3. Import `database.sql` to create tables
4. Update `config.php` with database credentials
5. Run `admin/setup-races.php` to populate race calendar
6. Run `admin/fetch-drivers.php` to get drivers/constructors list
7. Update `predict.php` with the drivers/constructors data

**For detailed setup instructions, see [SETUP.md](SETUP.md)**

## Pages

- **index-landing.php** - Modern game-style landing page with login (new!)
- **index.php** - Original homepage with race listing
- **login.php** - Standard login page
- **signup.php** - User registration
- **dashboard.php** - User dashboard with stats
- **predict.php** - Make race predictions
- **leaderboard.php** - View rankings
- **race-results.php** - View completed race results

## File Structure

```
/
├── index-landing.php      # Game-style landing page with login (NEW)
├── index.php              # Original homepage
├── login.php             # Login page
├── signup.php            # Registration page
├── dashboard.php          # User dashboard
├── predict.php            # Prediction interface
├── leaderboard.php        # Leaderboard
├── race-results.php       # Race results
├── api/                   # API endpoints
│   ├── fetch-results.php  # Fetch F1 results
│   └── calculate-scores.php # Calculate scores
├── includes/              # PHP includes
│   ├── config.php         # Database config
│   ├── auth.php           # Authentication functions
│   └── functions.php      # Helper functions
├── css/                   # Stylesheets
│   └── style.css
├── js/                    # JavaScript files
│   └── main.js
└── database.sql           # Database schema

```

## Landing Page Features

The new `index-landing.php` includes:
- **Racing-themed design** with gradient backgrounds and neon effects
- **Animated elements** including floating cards and racing stripes
- **Glass morphism UI** with backdrop blur effects
- **Login integration** directly on the landing page
- **Responsive layout** that works on all devices
- **Modern fonts** using Orbitron (racing font) and Inter
- **Feature showcase** with stats and highlights

# f1-fantasy-2026
