# F1 Fantasy - Automated Race Results Fetcher

This system automatically fetches race results from the Ergast F1 API and calculates user scores.

## How It Works

1. **Checks for completed races** - Looks for races where the race_date has passed
2. **Fetches results** from Ergast F1 API (http://ergast.com)
3. **Stores results** in `race_results` table
4. **Calculates scores** for every user who made predictions
5. **Updates totals** for leaderboard rankings

## Running Manually

### Via Web Browser:
```
https://your-domain.com/admin/fetch-race-results.php
```

### Via Command Line:
```bash
php /path/to/admin/fetch-race-results.php
```

## Automated Setup (Recommended)

### Option 1: Railway Cron Jobs (if supported)

Add to your `railway.toml`:

```toml
[deploy]
startCommand = "php-fpm"

[build]
builder = "nixpacks"

[cron]
# Run every Monday at 8:00 AM UTC (after typical Sunday races)
schedule = "0 8 * * 1"
command = "php /app/admin/fetch-race-results.php"
```

### Option 2: External Cron Service (EasyCron, cron-job.org)

1. Go to https://cron-job.org or https://www.easycron.com
2. Create a free account
3. Set up a cron job:
   - **URL**: `https://your-domain.com/admin/fetch-race-results.php`
   - **Schedule**: Every Monday at 8:00 AM UTC
   - **Cron Expression**: `0 8 * * 1`

### Option 3: Server Cron Job (if you have server access)

Add to crontab:

```bash
# Edit crontab
crontab -e

# Add this line (runs every Monday at 8 AM UTC)
0 8 * * 1 cd /path/to/f1-fantasy && php admin/fetch-race-results.php >> logs/cron.log 2>&1
```

### Option 4: GitHub Actions (Free, for Railway deployments)

Create `.github/workflows/fetch-results.yml`:

```yaml
name: Fetch F1 Race Results

on:
  schedule:
    # Runs every Monday at 8:00 AM UTC
    - cron: '0 8 * * 1'
  workflow_dispatch: # Allow manual trigger

jobs:
  fetch-results:
    runs-on: ubuntu-latest
    steps:
      - name: Fetch Race Results
        run: |
          curl -X GET "https://your-domain.com/admin/fetch-race-results.php"
```

## Security Recommendations

### Option A: Add Password Protection

Create `/admin/.htaccess`:

```apache
AuthType Basic
AuthName "Admin Area"
AuthUserFile /path/to/.htpasswd
Require valid-user
```

Generate password:
```bash
htpasswd -c .htpasswd admin
```

### Option B: Add IP Whitelist

Add to `/admin/.htaccess`:

```apache
Order Deny,Allow
Deny from all
Allow from 127.0.0.1
Allow from YOUR_IP_ADDRESS
```

### Option C: Use Secret Token

Modify `fetch-race-results.php` to check for a secret token:

```php
<?php
// Add at the top of fetch-race-results.php
$secretToken = getenv('FETCH_RESULTS_TOKEN'); // Set in Railway env vars
$providedToken = $_GET['token'] ?? '';

if ($providedToken !== $secretToken) {
    http_response_code(403);
    die('Unauthorized');
}
?>
```

Then call it with:
```
https://your-domain.com/admin/fetch-race-results.php?token=YOUR_SECRET_TOKEN
```

## Testing

1. **Manual Test**: Visit the URL in browser to see live output
2. **CLI Test**: Run `php admin/fetch-race-results.php` locally
3. **Check Logs**: Review output to ensure results are fetched correctly

## Monitoring

- Check `race_results` table for new entries
- Verify `races.results_fetched = TRUE` after successful fetch
- Review `scores` table for calculated points
- Monitor `user_totals` for updated rankings

## Troubleshooting

### No results found
- Check if race has actually finished
- Verify Ergast API is accessible: http://ergast.com/api/f1/current/last/results.json
- Ensure `race_number` is correctly set in races table

### Scores not calculated
- Verify users made predictions before race
- Check predictions table has entries
- Review script output for errors

### API Rate Limits
- Ergast has no strict rate limits for reasonable use
- Script processes races sequentially to avoid issues

## Logging

To enable logging:

```bash
# Create logs directory
mkdir logs

# Run with logging
php admin/fetch-race-results.php >> logs/fetch-results-$(date +%Y%m%d).log 2>&1
```

---

**Questions?** Check the main documentation or contact support.
