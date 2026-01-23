# 🚂 Deploy to Railway (Netlify Alternative for PHP)

Railway is like Netlify but **supports PHP**! Same modern interface, same simplicity.

## Quick Setup (5 minutes)

### Step 1: Sign Up
1. Go to [railway.app](https://railway.app)
2. Click **"Start a New Project"**
3. Sign up with GitHub (free)

### Step 2: Create Project
1. Click **"New Project"**
2. Choose **"Deploy from GitHub repo"** (or upload files directly)
3. If using GitHub:
   - Connect your GitHub account
   - Select/create a repo
   - Railway will auto-detect PHP

### Step 3: Add MySQL Database
1. In your Railway project, click **"+ New"**
2. Select **"Database"** → **"Add MySQL"**
3. Railway will create a MySQL database
4. **Copy the connection details** (you'll need these)

### Step 4: Configure Environment Variables
1. In your project, go to **"Variables"** tab
2. Add these variables:
   ```
   DB_HOST=containers-us-west-XXX.railway.app
   DB_USER=root
   DB_PASS=your_password_from_railway
   DB_NAME=railway
   PORT=8000
   ```
   (Use the actual values from Railway's MySQL service)

### Step 5: Update config.php
1. In Railway, open your project files
2. Edit `config.php`
3. Update to use environment variables:
   ```php
   define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
   define('DB_USER', getenv('DB_USER') ?: 'root');
   define('DB_PASS', getenv('DB_PASS') ?: '');
   define('DB_NAME', getenv('DB_NAME') ?: 'railway');
   ```

### Step 6: Create railway.json (for Railway)
Create this file in your project root:

```json
{
  "$schema": "https://railway.app/railway.schema.json",
  "build": {
    "builder": "NIXPACKS"
  },
  "deploy": {
    "startCommand": "php -S 0.0.0.0:$PORT",
    "restartPolicyType": "ON_FAILURE",
    "restartPolicyMaxRetries": 10
  }
}
```

### Step 7: Import Database
1. Railway provides a MySQL connection string
2. Use Railway's MySQL service → **"Connect"** → **"MySQL Client"**
3. Or use a MySQL client to connect
4. Import `database.sql`

### Step 8: Deploy!
1. Railway will auto-deploy when you push to GitHub
2. Or click **"Deploy"** if you uploaded files
3. Your app will be live at: `yourproject.railway.app`

### Step 9: Set Up Races
1. Visit: `https://yourproject.railway.app/admin/setup-races.php`
2. Visit: `https://yourproject.railway.app/admin/fetch-drivers.php`
3. Update `predict.php` with drivers

## ✅ Done!

Your app is live on Railway!

**URL:** `https://yourproject.railway.app`

---

## 🎯 Why Railway?

- ✅ Modern like Netlify
- ✅ Supports PHP (unlike Netlify)
- ✅ Free tier ($5 credit/month)
- ✅ Auto-deployments
- ✅ Simple interface
- ✅ Built-in MySQL

## 💰 Pricing

- **Free tier:** $5 credit/month (usually enough for small apps)
- **Hobby:** $5/month (if you need more)

---

## 🔧 Alternative: Quick Railway Setup

If you want even simpler:

1. **Upload files to Railway** (drag & drop)
2. **Add MySQL database** (one click)
3. **Set environment variables** (copy from MySQL service)
4. **Deploy!**

That's it! Railway handles the rest.

---

Want me to help you set it up step-by-step?

