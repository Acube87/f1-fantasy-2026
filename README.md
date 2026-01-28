# Formula 1 2026 Fantasy Game

[![CI Status](https://github.com/Acube87/f1-fantasy-2026/actions/workflows/ci.yml/badge.svg)](https://github.com/Acube87/f1-fantasy-2026/actions/workflows/ci.yml)
[![Deploy to Railway](https://github.com/Acube87/f1-fantasy-2026/actions/workflows/deploy.yml/badge.svg)](https://github.com/Acube87/f1-fantasy-2026/actions/workflows/deploy.yml)

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

### Automated Deployment (Recommended)

**Deploy to Railway (Free Tier Available):**
1. Fork this repository to your GitHub account
2. Sign up at [Railway](https://railway.app) using your GitHub account
3. Click "New Project" → "Deploy from GitHub repo"
4. Select your forked repository
5. Railway will auto-detect PHP and deploy automatically
6. Add MySQL database from Railway dashboard
7. Configure environment variables (see [RAILWAY_DEPLOY.md](RAILWAY_DEPLOY.md))

**Having deployment access issues?** See [DEPLOYMENT_ACCESS_FIX.md](DEPLOYMENT_ACCESS_FIX.md) for troubleshooting.

**⚠️ Feature branch not deploying?** See [WHY_BRANCH_NOT_DEPLOYING.md](WHY_BRANCH_NOT_DEPLOYING.md) for quick fix (2 minutes!)

**Working on a feature branch?** See [BRANCH_DEPLOYMENT_GUIDE.md](BRANCH_DEPLOYMENT_GUIDE.md) to deploy non-main branches.

### Manual Deployment

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

## Deployment

### Automated Deployments
This repository includes GitHub Actions workflows for:
- **Continuous Integration** - Validates PHP syntax and checks for security issues
- **Automated Deployment** - Deploys to Railway on push to main/master branches
- **Manual Deployment** - Deploy any branch via Actions tab → "Run workflow"

### Branch Deployment Workflow
- **Main/Master branches**: Auto-deploy to production on every push
- **Feature branches**: Use manual deployment or Railway preview deployments
- **See**: [BRANCH_DEPLOYMENT_GUIDE.md](BRANCH_DEPLOYMENT_GUIDE.md) for deploying feature branches

### Deployment Options
- **Railway** - Recommended for PHP applications (see [RAILWAY_DEPLOY.md](RAILWAY_DEPLOY.md))
- **Hostinger** - Traditional hosting (see [DEPLOYMENT.md](DEPLOYMENT.md))
- **Other Platforms** - See deployment documentation in repository root

### Troubleshooting Deployment
If you encounter deployment issues:
- **Access denied**: See [DEPLOYMENT_ACCESS_FIX.md](DEPLOYMENT_ACCESS_FIX.md)
- **Branch not deploying**: See [BRANCH_DEPLOYMENT_GUIDE.md](BRANCH_DEPLOYMENT_GUIDE.md)
- **General setup**: See [QUICK_START.md](QUICK_START.md)

# f1-fantasy-2026
