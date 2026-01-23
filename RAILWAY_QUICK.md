# ⚡ Quick Railway Deployment (5 Minutes)

## Why Railway?
- ✅ **Like Netlify but supports PHP!**
- ✅ Modern, simple interface
- ✅ Free tier ($5 credit/month)
- ✅ Auto-deployments
- ✅ Built-in MySQL

## Super Quick Steps:

### 1. Sign Up (1 min)
- Go to [railway.app](https://railway.app)
- Sign up with GitHub (free)

### 2. Create Project (1 min)
- Click **"New Project"**
- Choose **"Deploy from GitHub repo"** OR **"Empty Project"**
- If empty: Upload your F1 folder files

### 3. Add MySQL (1 min)
- Click **"+ New"** → **"Database"** → **"Add MySQL"**
- Railway creates database automatically
- **Copy the connection details shown**

### 4. Set Environment Variables (1 min)
- Go to **"Variables"** tab
- Railway auto-adds MySQL variables:
  - `MYSQLHOST`
  - `MYSQLUSER`
  - `MYSQLPASSWORD`
  - `MYSQLDATABASE`
  - `MYSQLPORT`
- These are already set! ✅

### 5. Update config.php (30 sec)
- Replace `config.php` with `config.railway.php` content
- Or rename: `config.railway.php` → `config.php`
- The Railway version uses environment variables automatically

### 6. Deploy! (30 sec)
- Railway auto-deploys
- Or click **"Deploy"**
- Your app is live! 🎉

### 7. Import Database (1 min)
- In Railway MySQL service, click **"Connect"**
- Use MySQL client or Railway's web terminal
- Import `database.sql`

### 8. Set Up Races (30 sec)
- Visit: `https://yourproject.railway.app/admin/setup-races.php`
- Visit: `https://yourproject.railway.app/admin/fetch-drivers.php`
- Update `predict.php` with drivers

## ✅ Done!

**Your app:** `https://yourproject.railway.app`

---

## 🎯 Pro Tips:

1. **Railway auto-detects PHP** - no configuration needed!
2. **MySQL is included** - just add it as a service
3. **Environment variables** - Railway sets them automatically
4. **Custom domain** - add in Railway settings (free)

---

## 📝 Files You Need:

- ✅ All your PHP files
- ✅ `railway.json` (already created)
- ✅ `config.php` (use Railway version)
- ✅ `database.sql` (import this)

That's it! Railway handles the rest automatically.

---

## 🆘 Need Help?

Railway has great docs: [docs.railway.app](https://docs.railway.app)

Or ask me! I can help with any step.

