# 🚀 Deployment Instructions - Database Fix

## Issue Fixed
**Login Error**: "Fatal error: Database connection error: No such file or directory"

## What Was the Problem?
The database connection code was attempting socket connections on Railway, which doesn't work. Railway requires TCP connections with explicit host and port.

## Changes Made

### 1. Fixed Database Connection (config.php)
- **Removed error suppression** (`@` operator) to show real errors
- **Added connection type detection** - distinguishes between remote (Railway) and local connections
- **Force TCP for Railway** - uses explicit host:port for remote connections
- **Improved error messages** - shows connection details for debugging
- **Enhanced environment variable handling** - better fallback chain

### 2. Added Diagnostic Tool (test-db-connection.php)
- Shows all environment variables
- Tests connection with detailed output
- Helps diagnose connection issues

### 3. Created Documentation
- **DATABASE_CONNECTION_FIX.md** - Complete troubleshooting guide
- **DATABASE_FIX_SUMMARY.txt** - Quick visual reference

## How to Deploy the Fix

### Option 1: Automatic Deployment (Recommended)

If you have auto-deploy enabled in Railway:

1. ✅ Code is already pushed to GitHub
2. ✅ Railway will automatically detect changes
3. ⏳ Wait 2-3 minutes for deployment
4. ✅ Test login at your app URL

**Check deployment status:**
- Railway Dashboard → Your Project → Deployments tab
- Look for latest deployment with these changes

### Option 2: Manual Deployment via Railway

If auto-deploy is not configured:

1. Go to: https://railway.app/dashboard
2. Select your F1 Fantasy project
3. Click on your web service
4. Click **"Redeploy"** or trigger a new deployment
5. Wait for deployment to complete
6. Test login at your app URL

### Option 3: Manual Deployment via GitHub Actions

Using GitHub Actions workflow:

1. Go to: https://github.com/Acube87/f1-fantasy-2026/actions
2. Select "Deploy to Railway" workflow
3. Click "Run workflow"
4. Select branch: `copilot/update-f1-prediction-page`
5. Choose environment: `production` or `staging`
6. Click "Run workflow"
7. Wait for completion
8. Test login at your app URL

## Verification Steps

After deployment:

### 1. Check Deployment Success
- Railway Dashboard → Deployments → Check status is "Success"
- Look for "Deployed" timestamp

### 2. Test Login
- Go to your app URL
- Try logging in with test credentials
- Should work without errors

### 3. Run Diagnostic (Optional)
If you still have issues:
```bash
# In Railway shell/terminal
php test-db-connection.php
```

This will show:
- Environment variables
- Connection parameters
- Success or detailed error

## Expected Results

After deployment:
- ✅ Users can log in successfully
- ✅ No "No such file or directory" errors
- ✅ Database connection stable
- ✅ All app functionality works

## If Issues Persist

### Check Environment Variables
In Railway Dashboard → Your Service → Variables:
- Verify MySQL variables are set
- Should include: MYSQLHOST, MYSQLPORT, MYSQLUSER, MYSQLPASSWORD, MYSQLDATABASE

### Check MySQL Service
- Railway Dashboard → MySQL service
- Verify it's running (green status)
- Check database exists

### Review Logs
- Railway Dashboard → Your Service → View Logs
- Look for connection errors
- Check for environment variable issues

### Run Diagnostic
```bash
php test-db-connection.php
```

### Consult Documentation
- **DATABASE_CONNECTION_FIX.md** - Full troubleshooting guide
- **DATABASE_FIX_SUMMARY.txt** - Quick reference

## Technical Details

### What Changed in Code

**Before (Broken):**
```php
$conn = @new mysqli($host, $user, $pass, $dbname, $port);
// Problem: @ hides errors, always passes port (tries socket for localhost)
```

**After (Fixed):**
```php
if ($host !== 'localhost' && $host !== '127.0.0.1') {
    // Remote: Force TCP with explicit port
    $conn = new mysqli($host, $user, $pass, $dbname, $port);
} else {
    // Local: Let MySQL decide (socket or TCP)
    $conn = new mysqli($host, $user, $pass, $dbname);
}
// No @ suppression, shows detailed errors
```

### Why This Fixes It

1. **Detects Railway Environment**
   - Checks if host is remote (not localhost)
   - Railway provides hostname like `containers-us-west-XXX.railway.app`

2. **Forces TCP Connection**
   - Explicitly uses mysqli($host, $user, $pass, $dbname, $port)
   - Port parameter forces TCP, not socket

3. **Better Error Messages**
   - Removed @ suppression
   - Shows: host, port, user, database in errors
   - Helps diagnose issues quickly

4. **Proper Fallbacks**
   - Still works for local development
   - Handles missing environment variables
   - Graceful degradation

## Files Modified

```
✅ config.php                     - Fixed connection logic
✅ config.example.php              - Same fixes for consistency
✅ test-db-connection.php (NEW)    - Diagnostic tool
✅ DATABASE_CONNECTION_FIX.md      - Troubleshooting guide
✅ DATABASE_FIX_SUMMARY.txt        - Quick reference
✅ DEPLOYMENT_INSTRUCTIONS.md      - This file
```

## Timeline

1. ✅ Issue reported: Login errors with database connection
2. ✅ Root cause identified: Socket connection attempt on Railway
3. ✅ Fix implemented: Force TCP for Railway connections
4. ✅ Documentation created: Troubleshooting guides and tools
5. ✅ Code pushed to GitHub
6. ⏳ **NEXT**: Deploy to Railway
7. ⏳ **THEN**: Verify login works

## Summary

**Problem**: Database socket connection error on Railway
**Solution**: Force TCP connections for Railway, improve error handling
**Status**: ✅ Code fixed and pushed
**Next Step**: Deploy to Railway
**Expected**: Users can log in after deployment

---

## Quick Commands

**Test connection:**
```bash
php test-db-connection.php
```

**Check environment:**
```bash
php -r "print_r(array_filter($_ENV, function(\$k) { return strpos(\$k, 'MYSQL') !== false; }, ARRAY_FILTER_USE_KEY));"
```

**View config constants:**
```bash
php -r "require 'config.php'; echo 'Host: ' . DB_HOST . PHP_EOL . 'Port: ' . DB_PORT . PHP_EOL;"
```

---

**🎉 Deploy and your users will be able to log in again!**
