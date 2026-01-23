# 🚂 Railway Deployment - Step by Step

## You're logged in! Let's deploy your F1 Fantasy app.

### Step 1: Create New Project (1 minute)
1. In Railway dashboard, click **"+ New Project"**
2. Choose **"Deploy from GitHub repo"** (if you have GitHub) OR
3. Choose **"Empty Project"** (if uploading files directly)

**If using GitHub:**
- Connect your GitHub account
- Select/create a repository
- Railway will auto-detect PHP

**If using Empty Project:**
- Click "Empty Project"
- We'll upload files next

---

### Step 2: Add MySQL Database (1 minute)
1. In your project, click **"+ New"** button
2. Select **"Database"**
3. Choose **"Add MySQL"**
4. Railway will create the database automatically
5. **IMPORTANT:** Click on the MySQL service
6. Go to **"Variables"** tab
7. **Copy these values** (you'll need them):
   - `MYSQLHOST`
   - `MYSQLUSER`
   - `MYSQLPASSWORD`
   - `MYSQLDATABASE`
   - `MYSQLPORT`

---

### Step 3: Update config.php for Railway (2 minutes)

The config needs to use Railway's environment variables.

**Option A: Use the Railway-ready config**
- I've created `config.railway.php` - just rename it to `config.php`

**Option B: Update manually**
- Open `config.php`
- Replace the database section with:
```php
// Railway database configuration
define('DB_HOST', getenv('MYSQLHOST') ?: 'localhost');
define('DB_USER', getenv('MYSQLUSER') ?: 'root');
define('DB_PASS', getenv('MYSQLPASSWORD') ?: '');
define('DB_NAME', getenv('MYSQLDATABASE') ?: 'railway');
define('DB_PORT', getenv('MYSQLPORT') ?: '3306');
```

---

### Step 4: Upload Files (2 minutes)

**If using GitHub:**
- Push your code to GitHub
- Railway will auto-deploy

**If using Empty Project:**
1. In Railway project, go to **"Settings"**
2. Click **"Source"** → **"Connect GitHub"** OR
3. Use Railway CLI to upload:
   ```bash
   npm install -g @railway/cli
   railway login
   railway link
   railway up
   ```

**Or drag & drop:**
- Railway doesn't have direct file upload
- Best to use GitHub or CLI

---

### Step 5: Set Environment Variables (1 minute)
1. In your project, go to **"Variables"** tab
2. Railway should auto-add MySQL variables
3. Verify these exist:
   - `MYSQLHOST`
   - `MYSQLUSER`
   - `MYSQLPASSWORD`
   - `MYSQLDATABASE`
   - `MYSQLPORT`
4. If missing, add them manually from MySQL service

---

### Step 6: Import Database Schema (2 minutes)
1. In Railway, click on your **MySQL service**
2. Click **"Connect"** → **"MySQL Client"**
3. Or use Railway's web terminal
4. Run this command:
   ```bash
   mysql -h $MYSQLHOST -u $MYSQLUSER -p$MYSQLPASSWORD $MYSQLDATABASE < database.sql
   ```
5. Or use a MySQL client (like TablePlus, MySQL Workbench)
   - Connect using Railway's connection string
   - Import `database.sql` file

**Easier way:**
- Use Railway's web terminal
- Copy contents of `database.sql`
- Paste and run in MySQL client

---

### Step 7: Deploy! (1 minute)
1. Railway will auto-deploy when you push to GitHub
2. Or click **"Deploy"** button
3. Wait for deployment to complete
4. Your app will be live at: `yourproject.railway.app`

---

### Step 8: Set Up Races (1 minute)
1. Visit: `https://yourproject.railway.app/admin/setup-races.php`
2. This populates the race calendar
3. Visit: `https://yourproject.railway.app/admin/fetch-drivers.php`
4. Copy the drivers/constructors
5. Update `predict.php` with the data

---

### Step 9: Test! (1 minute)
1. Visit your app: `https://yourproject.railway.app`
2. Create a test account
3. Try making a prediction
4. Check the leaderboard

---

## ✅ Done!

Your F1 Fantasy app is live on Railway!

**URL:** `https://yourproject.railway.app`

---

## 🆘 Troubleshooting

**Deployment fails?**
- Check Railway logs
- Verify `config.php` uses environment variables
- Make sure `railway.json` exists

**Database connection error?**
- Verify environment variables are set
- Check MySQL service is running
- Test connection in Railway's MySQL client

**Can't access admin pages?**
- Make sure files uploaded correctly
- Check file permissions
- Verify paths are correct

---

## 📝 Quick Checklist

- [ ] Created Railway project
- [ ] Added MySQL database
- [ ] Updated config.php for Railway
- [ ] Uploaded all files
- [ ] Set environment variables
- [ ] Imported database.sql
- [ ] Deployed app
- [ ] Set up races
- [ ] Tested login/signup
- [ ] Tested predictions

---

**Need help with any step? Let me know!**

