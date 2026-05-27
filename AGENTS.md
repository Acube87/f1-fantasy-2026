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
- **Frontend**: React 18 SPA (CDN + Babel standalone, no build step)
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
| `api/data.php` | Unified JSON API for all SPA pages |
| `api/auth.php` | Login/signup/logout JSON API |
| `index.php` | **Main SPA entry point** — React 18 app with all pages |

## SPA Architecture

`index.php` is a React 18 SPA with hash-based routing. No full page reloads.

### Hash Routes

| Route | Page | Component |
|-------|------|-----------|
| `#dashboard` (or `/`) | Dashboard | `Dashboard` |
| `#predict` | Predictions | `PredictPage` |
| `#results` | Race Results | `ResultsPage` |
| `#updates` | Roundup Feed | `UpdatesPage` |
| `#leaderboard` | Standings | `LeaderboardPage` |
| `#achievements` | Badges | `AchievementsPage` |
| `#profile` | Settings | `ProfilePage` |

### Nav Links (all hash-based, no .php hrefs)

dashboard, predict, results, updates, leaderboard, achievements, profile

### API Endpoints (api/data.php)

| `type` param | Method | Description |
|--------------|--------|-------------|
| `dashboard` | GET | Personal stats, next race, recent results, standings |
| `leaderboard` | GET | Full player rankings |
| `predict` | GET | Drivers, existing predictions, deadline data |
| `results` | GET | Race results with score breakdown |
| `profile` | GET | User stats, accuracy, avatars, achievements |
| `updates` | GET | Latest race roundup and submission tracker |
| `save_predictions` | POST | Save driver/constructor predictions |
| `update_profile` | POST | Update name/username/password/avatar |

### Auth

Login/signup via modal (`LoginModal`) — uses `api/auth.php`. Session cookies. Auth gating shows a welcome screen for unauthenticated users (except leaderboard which is public).

### Design System

Dark gaming theme: `#0c0f16` bg, `#181e2c` cards, `#7c3aed` purple accent, Inter font. All pages use consistent card/bento grid layouts.

## Setup Order

1. `admin/setup-database.php` (create tables)
2. `admin/setup-races.php` (populate races)
3. `admin/fetch-drivers.php` (fetch drivers)

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