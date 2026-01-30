# Railway MySQL Database Setup Guide

## Problem Identified

The diagnostic page (check-env.php) shows that **NO MySQL environment variables are set** in your Railway project.

This means your Railway project either:
1. Has NO MySQL database service at all
2. Has a MySQL service but it's not linked to your web service
3. Variables aren't being shared between services

## Solution: Add MySQL Database to Railway

This takes about **5 minutes** and Railway handles everything automatically.

---

## Step 1: Add MySQL Service to Railway

1. **Go to Railway Dashboard**
   - Visit: https://railway.app/dashboard
   - Select your F1 Fantasy project

2. **Add MySQL Database**
   - Click the **"New"** button
   - Select **"Database"**
   - Choose **"Add MySQL"**

3. **Wait for Deployment**
   - Railway will create a new MySQL service
   - Takes about 30 seconds
   - You'll see it appear in your project

---

## Step 2: Verify MySQL Service Created

**Check that MySQL service exists:**

1. In your Railway project, you should now see TWO services:
   - Your web service (f1-fantasy-2026)
   - MySQL database service

2. Click on the MySQL service

3. Go to the **"Variables"** tab

4. You should see these variables automatically created:
   - `MYSQLHOST` - (hostname like: containers-us-west-123.railway.app)
   - `MYSQLPORT` - (port number like: 6789)
   - `MYSQLUSER` - (usually: root)
   - `MYSQLPASSWORD` - (auto-generated password)
   - `MYSQLDATABASE` - (usually: railway)

**These variables are automatically created by Railway!**

---

## Step 3: Link Web Service to MySQL Service

Railway should automatically link services, but let's verify:

1. **Click on your web service** (f1-fantasy-2026)

2. **Go to "Settings"** or **"Variables"** tab

3. **Check for shared variables**:
   - You should see the MySQL variables from Step 2
   - They appear with a link icon (🔗) indicating they're from MySQL service

4. **If variables are NOT showing:**
   - Go to web service Settings
   - Look for "Service References" or "Variable References"
   - Add reference to MySQL service
   - This shares MySQL variables with web service

---

## Step 4: Redeploy Web Service

After MySQL service is added and linked:

1. **Click on your web service** (f1-fantasy-2026)

2. **Click the "Redeploy" button** (or 3-dot menu → Redeploy)

3. **Wait for deployment** (about 90 seconds)

4. Railway will rebuild your app with MySQL environment variables

---

## Step 5: Verify It Works

After redeployment completes:

1. **Visit your diagnostic page**:
   ```
   https://f1.scanerrific.com/check-env.php
   ```

2. **Check the output** - You should now see:
   ```
   MYSQLHOST = containers-us-west-123.railway.app
   MYSQLPORT = 6789
   MYSQLUSER = root
   MYSQLPASSWORD = [SET]
   
   ✅ Connection SUCCESSFUL!
   ```

3. **Try logging in**:
   - Go to your app homepage
   - Try logging in
   - Should work now! ✅

---

## Expected Results

**Before adding MySQL:**
```
Environment Variables:
  MYSQLHOST = [NOT SET]
  MYSQLPORT = [NOT SET]
  MYSQLUSER = [NOT SET]
  MYSQLPASSWORD = [NOT SET]

Host: localhost
❌ Connection Failed: No such file or directory
```

**After adding MySQL:**
```
Environment Variables:
  MYSQLHOST = containers-us-west-123.railway.app
  MYSQLPORT = 6789
  MYSQLUSER = root
  MYSQLPASSWORD = abc123xyz...

Host: containers-us-west-123.railway.app
✅ Connection SUCCESSFUL!
```

---

## Troubleshooting

### MySQL service added but variables still not showing?

**Solution:**
1. Go to web service → Settings
2. Find "Service References" or "Variable References"
3. Add reference to MySQL service
4. Redeploy web service

### Variables show but connection still fails?

**Check:**
1. MySQL service is running (not stopped/crashed)
2. Web service redeployed AFTER MySQL was added
3. MYSQLHOST is a Railway domain (not 'localhost')
4. Visit check-env.php to see exact error message

### Want to see MySQL database content?

**Options:**
1. Use Railway's built-in MySQL client (in MySQL service page)
2. Use external tool (TablePlus, MySQL Workbench)
3. Connect using credentials from MySQL service variables

---

## Database Schema

After MySQL is running, you need to import the database schema:

1. **Get database credentials** from Railway MySQL service variables

2. **Import the schema**:
   - Use Railway's built-in SQL client, OR
   - Connect with external tool, OR
   - Run the database.sql file from this repo

3. **Create test user** (optional):
   ```sql
   INSERT INTO users (username, email, password) 
   VALUES ('testuser', 'test@example.com', '$2y$10$...');
   ```

---

## Summary

1. **Add MySQL service** to Railway project (30 seconds)
2. **Verify variables** are created (auto by Railway)
3. **Link web service** to MySQL service (auto or manual)
4. **Redeploy web service** (90 seconds)
5. **Test login** - Should work! ✅

**Total time: About 5 minutes**

---

## Need Help?

- Check check-env.php for current status
- Read RAILWAY_MANUAL_ENV.md for external database option
- All code is already configured correctly
- The only missing piece is the MySQL service in Railway
