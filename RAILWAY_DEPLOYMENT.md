# Railway Deployment Guide for F1 Fantasy 2026

## Prerequisites
- Railway account (https://railway.app)
- GitHub repository with your code
- MySQL database service added to your Railway project

## Step 1: Add MySQL Database Service

1. In your Railway project, click **"+ New"**
2. Select **"Database"** → **"Add MySQL"**
3. Railway will automatically provision a MySQL database

## Step 2: Configure Environment Variables

Based on your screenshot, Railway has detected the MySQL variables. Here's what you need to configure:

### Option A: Use Railway's Auto-Generated Variables (Recommended)

Railway automatically creates these variables when you add MySQL:
- `MYSQLHOST`
- `MYSQLPORT`
- `MYSQLUSER`
- `MYSQLPASSWORD`
- `MYSQLDATABASE`

Your `config.php` is already configured to read these! ✅

### Option B: Manual Configuration (if needed)

If you need to manually set variables, add these in the **Variables** tab:

```
DB_HOST=${MYSQLHOST}
DB_PORT=${MYSQLPORT}
DB_USER=${MYSQLUSER}
DB_PASS=${MYSQLPASSWORD}
DB_NAME=${MYSQLDATABASE}
```

## Step 3: Database Initialization

After deployment, you need to initialize the database. You have two options:

### Option A: Use Railway's MySQL Console

1. Click on your MySQL service in Railway
2. Go to the **"Data"** tab
3. Click **"Connect"** to open MySQL console
4. Copy and paste the contents of `/admin/setup-database.php` SQL queries

### Option B: Run Setup Scripts via Web

1. After deployment, visit: `https://your-app.railway.app/admin/setup-database.php`
2. Then visit: `https://your-app.railway.app/admin/add-drivers-manual.php`
3. Then visit: `https://your-app.railway.app/admin/setup-races.php`

**⚠️ IMPORTANT:** After running these scripts, **delete or protect the `/admin` folder** for security!

## Step 4: Deploy Your Application

### Connect GitHub Repository

1. In Railway, click **"+ New"** → **"GitHub Repo"**
2. Select your `f1-fantasy-2026` repository
3. Railway will auto-detect it's a PHP app

### Configure Build Settings

Railway should auto-detect PHP. If not, add a `railway.toml` file:

```toml
[build]
builder = "nixpacks"

[deploy]
startCommand = "php -S 0.0.0.0:$PORT -t ."
```

## Step 5: Link Services

1. In your web service settings, go to **"Settings"** → **"Service Variables"**
2. Click **"+ New Variable"** → **"Add Reference"**
3. Select your MySQL service
4. This will automatically link the MySQL variables to your web service

## Step 6: Verify Deployment

1. Once deployed, Railway will give you a public URL
2. Visit the URL to test your app
3. Try signing up and making a prediction

## Environment Variables Summary

Your app will automatically use these Railway-provided variables:

| Variable | Railway Auto-Generated | Used By |
|----------|----------------------|---------|
| `MYSQLHOST` | ✅ Yes | Database connection |
| `MYSQLPORT` | ✅ Yes | Database connection |
| `MYSQLUSER` | ✅ Yes | Database connection |
| `MYSQLPASSWORD` | ✅ Yes | Database connection |
| `MYSQLDATABASE` | ✅ Yes | Database connection |

## Troubleshooting

### Database Connection Issues

If you see "Connection failed" errors:

1. Check that MySQL service is running
2. Verify environment variables are set correctly
3. Check Railway logs: Click on your service → **"Deployments"** → Select latest deployment → **"View Logs"**

### Common Issues

**Issue:** "Table doesn't exist"
- **Solution:** Run the database setup scripts (Step 3)

**Issue:** "Access denied for user"
- **Solution:** Verify `MYSQLUSER` and `MYSQLPASSWORD` are correctly linked

**Issue:** "Unknown database"
- **Solution:** Verify `MYSQLDATABASE` is set and the database was created

## Security Recommendations

After deployment:

1. **Protect Admin Scripts:**
   ```bash
   # Add to .htaccess in /admin folder
   Order Deny,Allow
   Deny from all
   ```

2. **Or Delete Admin Folder:**
   ```bash
   rm -rf admin/
   ```

3. **Enable HTTPS:** Railway provides HTTPS by default ✅

4. **Set Strong Passwords:** Use Railway's generated passwords (already secure) ✅

## Next Steps

After successful deployment:

1. ✅ Test user registration
2. ✅ Test making predictions
3. ✅ Verify database is storing data
4. 🔄 Set up automatic backups (Railway Pro feature)
5. 🔄 Configure custom domain (optional)

## Useful Railway Commands

```bash
# Install Railway CLI
npm i -g @railway/cli

# Login to Railway
railway login

# Link to your project
railway link

# View logs
railway logs

# Open database shell
railway run mysql -h $MYSQLHOST -u $MYSQLUSER -p$MYSQLPASSWORD $MYSQLDATABASE
```

## Support

- Railway Docs: https://docs.railway.app
- Railway Discord: https://discord.gg/railway
- Your app's config: `/config.php`

---

**Your app is already configured for Railway! Just deploy and run the setup scripts.** 🚀
