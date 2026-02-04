# Quick Setup Guide - Automated Race Results

## ✅ What's Included

Your F1 Fantasy app now has **automatic race results** via the Ergast F1 API!

## 🚀 Setup Steps

### 1. Add GitHub Secret

Go to your GitHub repository:
1. Click **Settings** → **Secrets and variables** → **Actions**
2. Click **New repository secret**
3. Name: `APP_DOMAIN`
4. Value: Your Railway app domain (e.g., `f1-fantasy-2026.up.railway.app`)
5. Click **Add secret**

### 2. Enable GitHub Actions

GitHub Actions is **automatically enabled** when you push. The workflow will:
- Run **every Monday at 8:00 AM UTC** (after Sunday races)
- Run **every Monday at 8:00 PM UTC** (backup)
- Can be triggered **manually** from the Actions tab

### 3. Test It Manually

#### Option A: Via GitHub Actions
1. Go to **Actions** tab in your repo
2. Click **"Fetch F1 Race Results"** workflow
3. Click **"Run workflow"** → **"Run workflow"**
4. Watch it fetch results!

#### Option B: Via Web Browser
Visit: `https://your-domain.com/admin/fetch-race-results.php`

#### Option C: Via Command Line (Local)
```bash
php admin/fetch-race-results.php
```

## 📋 How It Works

1. **Every Monday** (after race weekend):
   - GitHub Actions calls your `/admin/fetch-race-results.php`
   - Script checks for races with past dates
   - Fetches results from Ergast API
   - Stores results in `race_results` table
   - Calculates points for all users
   - Updates leaderboards

2. **Users see results**:
   - Dashboard shows race in "Recent Results"
   - Click to view detailed breakdown
   - See predictions vs actual positions
   - View points earned

## 🔒 Security (Optional)

Currently, the endpoint is public. To secure it:

### Option 1: Add Token Authentication

1. Add to Railway environment variables:
   ```
   FETCH_RESULTS_TOKEN=your_random_secret_token_here
   ```

2. Update `admin/fetch-race-results.php` (add at top):
   ```php
   $secretToken = getenv('FETCH_RESULTS_TOKEN');
   $providedToken = $_GET['token'] ?? '';
   
   if ($secretToken && $providedToken !== $secretToken) {
       http_response_code(403);
       die('Unauthorized');
   }
   ```

3. Update GitHub Actions workflow URL:
   ```yaml
   curl "https://${{ secrets.APP_DOMAIN }}/admin/fetch-race-results.php?token=${{ secrets.FETCH_RESULTS_TOKEN }}"
   ```

4. Add `FETCH_RESULTS_TOKEN` secret to GitHub

## 📊 What Gets Updated

After each race, the system automatically:

✅ Fetches official F1 results  
✅ Stores driver positions  
✅ Calculates user scores  
✅ Awards exact match points (+10)  
✅ Awards top 3 bonus points (+3)  
✅ Updates leaderboard rankings  
✅ Shows results on race results page  

## 🧪 Testing

To test with a past race:

1. Find a race in your database with past date
2. Set `results_fetched = FALSE`
3. Run the script manually
4. Check the output

## 📅 Race Schedule

F1 races typically occur on **Sundays**. The automation runs on **Mondays** to ensure results are available.

If you need results faster, you can:
- Run manually from GitHub Actions
- Call the endpoint directly
- Adjust the cron schedule

## 🐛 Troubleshooting

### No results showing
- Check if race date has passed
- Verify Ergast API is working: http://ergast.com/api/f1/current/last/results.json
- Run script manually to see errors

### GitHub Actions not running
- Check Actions are enabled in repo settings
- Verify `APP_DOMAIN` secret is set
- Check workflow file is in `.github/workflows/`

### Scores not calculating
- Verify users made predictions before race
- Check `predictions` table has entries
- Review script output in GitHub Actions logs

## 📖 API Reference

**Ergast F1 API**: http://ergast.com/mrd/

Example endpoints:
- Current season: `http://ergast.com/api/f1/current.json`
- Last race results: `http://ergast.com/api/f1/current/last/results.json`
- Specific race: `http://ergast.com/api/f1/2026/1/results.json`

---

**Ready!** Your app will now automatically fetch results every Monday! 🏁
