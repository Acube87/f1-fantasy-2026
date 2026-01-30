# Railway Manual Redeploy Instructions

## The Issue
Railway is showing old code. Even though code is pushed to GitHub, Railway hasn't picked up the changes.

## Solution: Manual Redeploy

### Option 1: Redeploy Button (FASTEST - 2 minutes)

1. Go to: https://railway.app/dashboard
2. Find your F1 Fantasy project
3. Click on the service
4. Click the **3 dots menu** (⋮) in the top right
5. Click **"Redeploy"**
6. Wait 1-2 minutes for rebuild
7. Test login - should work!

### Option 2: Trigger New Deployment

1. Go to: https://railway.app/dashboard
2. Find your F1 Fantasy project
3. Click on the service
4. Go to **"Deployments"** tab
5. Click **"Deploy"** button
6. Select latest commit
7. Wait 1-2 minutes for rebuild
8. Test login - should work!

### Option 3: Environment Variable Refresh

Sometimes Railway needs environment variables refreshed:

1. Go to: https://railway.app/dashboard
2. Find your F1 Fantasy project
3. Click on the service
4. Go to **"Variables"** tab
5. Verify these exist:
   - `RAILWAY_TCP_PROXY_DOMAIN`
   - `RAILWAY_TCP_PROXY_PORT`
   - `MYSQLUSER`
   - `MYSQL_ROOT_PASSWORD`
   - `MYSQL_DATABASE`
6. If missing, Railway should auto-add them
7. Click **"Redeploy"** to apply changes

## Verification

After redeploying:

1. Check Railway logs for startup
2. Look for "PHP 8.3.15 Development Server started"
3. Try logging in
4. Should NOT see "No such file or directory" error
5. Login should work!

## What Changed

The config.php file now:
- Uses Railway environment variables automatically
- Forces TCP connection for remote databases
- Prevents socket connection errors
- Has better error messages

## If Still Not Working

1. Check Railway logs for actual error message
2. Verify database service is running
3. Check environment variables are set
4. Try restarting the database service
5. Contact Railway support if needed
