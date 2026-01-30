# 🔧 F1 Fantasy App - Fix & Setup Guide

## What We Just Fixed

✅ **Removed hardcoded credentials** from config.php  
✅ **Updated to use Railway environment variables**  
✅ **Changed API endpoint** from 2026 to 2025 (data available)  
✅ **Added better error handling** with detailed debugging info  

---

## Next Steps to Get Your App Working

### Step 1: Get Railway MySQL Credentials

1. In Railway dashboard, click on **MySQL** service (left sidebar)
2. Click on **Variables** tab
3. You should see these variables:
   - `MYSQLHOST`
   - `MYSQLPORT`
   - `MYSQLUSER`
   - `MYSQLPASSWORD`
   - `MYSQLDATABASE`

### Step 2: Set Environment Variables in Railway

For your **f1-fantasy-2026** service (not MySQL):

1. Click on your **f1-fantasy-2026** service
2. Go to **Variables** tab
3. Click **+ New Variable**
4. Add these variables (copy values from MySQL service):

```
MYSQLHOST=<from MySQL Variables>
MYSQLPORT=<from MySQL Variables>
MYSQLUSER=<from MySQL Variables>
MYSQLPASSWORD=<from MySQL Variables>
MYSQLDATABASE=<from MySQL Variables>
```

**OR** use the Railway reference syntax:
```
MYSQLHOST=${{MySQL.MYSQLHOST}}
MYSQLPORT=${{MySQL.MYSQLPORT}}
MYSQLUSER=${{MySQL.MYSQLUSER}}
MYSQLPASSWORD=${{MySQL.MYSQLPASSWORD}}
MYSQLDATABASE=${{MySQL.MYSQLDATABASE}}
```

### Step 3: Import Database Schema

You need to create the database tables. Two options:

#### Option A: Using Railway MySQL Query Tab
1. Click on **MySQL** service
2. Go to **Query** tab
3. Copy contents of `database.sql`
4. Paste and execute

#### Option B: Using MySQL Client
1. Click on **MySQL** service → **Connect**
2. Copy the connection command
3. Run locally:
```bash
# Connect to Railway MySQL
mysql -h <MYSQLHOST> -P <MYSQLPORT> -u <MYSQLUSER> -p<MYSQLPASSWORD> <MYSQLDATABASE>

# Then paste database.sql contents
```

### Step 4: Test Locally First (Optional but Recommended)

Before deploying to Railway, test locally:

1. **Install local MySQL** (if not already):
```bash
brew install mysql
brew services start mysql
```

2. **Create local database**:
```bash
mysql -u root
CREATE DATABASE f1_fantasy;
exit;
```

3. **Import schema**:
```bash
mysql -u root f1_fantasy < database.sql
```

4. **Test connection**:
```bash
php test-db.php
```

You should see:
```
✅ SUCCESS! Connected to database.
Found 7 tables:
  - users
  - races
  - drivers
  - constructors
  - race_results
  - predictions
  - constructor_predictions
  - scores
  - user_totals
```

### Step 5: Seed Initial Data

Run these admin scripts to populate data:

#### A. Setup Races (2025 Calendar)
Visit: `http://localhost/admin/setup-races.php` (local)
Or: `https://f1-fantasy-2026-production.up.railway.app/admin/setup-races.php` (Railway)

#### B. Fetch Drivers
Visit: `http://localhost/admin/fetch-drivers.php` (local)
Or: `https://f1-fantasy-2026-production.up.railway.app/admin/fetch-drivers.php` (Railway)

### Step 6: Deploy to Railway

Once local testing works:

1. **Commit changes**:
```bash
git add .
git commit -m "Fix database connection and config"
git push origin main
```

2. **Railway auto-deploys** - wait for deployment to complete

3. **Test on Railway**:
   - Visit: `https://f1-fantasy-2026-production.up.railway.app/test-db.php`
   - Should show successful connection

### Step 7: Create First User & Test

1. Visit your app: `https://f1-fantasy-2026-production.up.railway.app`
2. Click **Sign Up**
3. Create an account
4. Try making a prediction
5. Check leaderboard

---

## Troubleshooting

### Error: "Connection failed"

**Check:**
- Railway MySQL service is running (green dot)
- Environment variables are set in f1-fantasy-2026 service
- Variables match MySQL service values
- Database name is correct

**Fix:**
```bash
# Test connection locally first
php test-db.php
```

### Error: "Table doesn't exist"

**Fix:**
- Import `database.sql` into Railway MySQL
- Use Query tab or MySQL client

### Error: "No races found"

**Fix:**
- Run `/admin/setup-races.php` to populate race calendar
- Run `/admin/fetch-drivers.php` to get drivers list

### Error: "API timeout"

**Fix:**
- Check F1 API is accessible: `http://ergast.com/api/f1/2025`
- Increase timeout in config.php if needed

---

## Quick Test Checklist

- [ ] Railway MySQL service is running
- [ ] Environment variables set in f1-fantasy-2026 service
- [ ] Database schema imported (7 tables exist)
- [ ] `test-db.php` shows success
- [ ] Races populated (run setup-races.php)
- [ ] Drivers populated (run fetch-drivers.php)
- [ ] Can sign up new user
- [ ] Can make predictions
- [ ] Leaderboard displays

---

## Files Changed

1. **config.php** - Fixed database connection, removed hardcoded credentials
2. **test-db.php** - NEW - Test database connection
3. **FIX_GUIDE.md** - This file

---

## What to Share with Me

If you need help, share:

1. **Screenshot of Railway Variables** (MySQL service)
2. **Output of test-db.php** (run locally or on Railway)
3. **Any error messages** you see
4. **Which step you're stuck on**

---

## Next: Let's Test!

**Right now, please:**

1. Share screenshot of MySQL Variables tab in Railway
2. Or tell me if you want to test locally first
3. I'll help you set up the environment variables correctly

Once we confirm the database connection works, the rest will be easy! 🚀
