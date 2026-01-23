# Formula 1 2026 Fantasy Game

A simple HTML-based Formula 1 fantasy game where users can predict race winners and standings.

## Features

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

## File Structure

```
/
├── index.php              # Homepage
├── login.php             # Login page
├── signup.php            # Registration page
├── dashboard.php          # User dashboard
├── predict.php            # Prediction interface
├── leaderboard.php        # Leaderboard
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

# f1-fantasy-2026
