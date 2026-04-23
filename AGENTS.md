# AGENTS.md - F1 Fantasy Game

## Quick Start

```bash
# Local development (PHP built-in server)
php -S localhost:8000

# Admin setup (run in browser)
http://localhost:8000/admin/setup-database.php
http://localhost:8000/admin/setup-races.php
http://localhost:8000/admin/fetch-drivers.php
```

## Tech Stack

- **Backend**: PHP 7.4+ with MySQL/MariaDB
- **Frontend**: HTML, CSS (Tailwind via PostCSS), vanilla JS
- **Deployment**: Hostinger or Railway

## Key Files

| File | Purpose |
|------|---------|
| `config.php` | Database config, env var handling, maintenance mode |
| `database.sql` | Full schema (131 lines) |
| `admin/setup-database.php` | Creates all tables via PHP |
| `admin/setup-races.php` | Populates 2026 race calendar |
| `admin/fetch-drivers.php` | Fetches drivers/constructors from API |
| `api/fetch-results.php` | Fetches race results, calculates scores |
| `api/calculate-scores.php` | Scoring logic |

## Setup Order

1. `admin/setup-database.php` (create tables)
2. `admin/setup-races.php` (populate races)
3. `admin/fetch-drivers.php` (fetch drivers)
4. Then update `predict.php` with current driver data (not automatic)

## Railway Deployment

`config.php` uses environment variables in this priority:
1. `RAILWAY_TCP_PROXY_DOMAIN`, `RAILWAY_TCP_PROXY_PORT`
2. `MYSQLHOST`, `MYSQLUSER`, `MYSQLPASSWORD`, `MYSQLDATABASE`
3. Fallbacks to localhost/127.0.0.1

Set `MAINTENANCE_MODE = true` in `config.php` to lock the app (admin exempt).

## Admin Scripts

All admin scripts are web-accessible PHP files in `/admin/`:
- `setup-database.php` - Initialize/reset database
- `setup-races.php` - Import race calendar
- `fetch-drivers.php` - Fetch driver/constructor data
- `process-results.php` - Process completed race results
- `fetch-race-results.php` - Fetch from F1 API
- `rescore-race.php` - Recalculate scores for a race

## Scoring System

From `config.php`:
- `POINTS_PRECISION_BONUS` = 3 pts (exact position)
- `POINTS_PODIUM_SWEEP` = 10 pts (top 3 exact order)
- `POINTS_CONSTRUCTOR_BONUS` = 5 pts (constructor prediction)
- Standard F1 points: [25, 18, 15, 12, 10, 8, 6, 4, 2, 1]

## Database Tables

- `users` - User accounts
- `races` - Race calendar
- `drivers` - Driver roster
- `constructors` - Constructor roster
- `predictions` - User predictions
- `scores` - Calculated scores