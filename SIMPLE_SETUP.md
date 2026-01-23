# ⚡ SIMPLE & FAST Setup - 5 Minutes!

## Use 000webhost (FREE & EASIEST)

### Step 1: Sign Up (1 minute)
1. Go to: **https://www.000webhost.com**
2. Click **"Get Started Free"**
3. Enter your email and password
4. Verify your email

### Step 2: Create Website (1 minute)
1. After login, click **"Create Website"**
2. Choose a subdomain: `yourname.000webhostapp.com`
3. Click **"Create"**

### Step 3: Upload Files (2 minutes)
1. Click **"File Manager"** in dashboard
2. Go to `public_html` folder
3. Click **"Upload Files"**
4. Upload ALL files from your F1 folder:
   - All .php files
   - includes/ folder
   - api/ folder  
   - admin/ folder
   - css/ folder
   - js/ folder

### Step 4: Create Database (1 minute)
1. In dashboard, click **"MySQL Databases"**
2. Click **"Create Database"**
3. Name it: `f1_fantasy`
4. Click **"Create"**
5. **IMPORTANT:** Write down:
   - Database name: `id12345678_f1_fantasy` (they give you an ID prefix)
   - Username: `id12345678_yourname`
   - Password: (the one you set)
   - Host: `localhost` (usually)

### Step 5: Configure (30 seconds)
1. In File Manager, open `config.php`
2. Change these 4 lines:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'id12345678_yourname');  // Your actual username
   define('DB_PASS', 'your_password');         // Your actual password
   define('DB_NAME', 'id12345678_f1_fantasy'); // Your actual database name
   ```
3. Save file

### Step 6: Import Database (30 seconds)
1. In dashboard, click **"phpMyAdmin"**
2. Select your database from left sidebar
3. Click **"Import"** tab
4. Click **"Choose File"** → select `database.sql`
5. Click **"Go"**

### Step 7: Set Up Races (30 seconds)
1. Visit: `https://yourname.000webhostapp.com/admin/setup-races.php`
2. Page will show "Races added successfully"

### Step 8: Get Drivers (30 seconds)
1. Visit: `https://yourname.000webhostapp.com/admin/fetch-drivers.php`
2. Copy the driver/constructor arrays shown
3. Open `predict.php` in File Manager
4. Find lines 76-88 (the placeholder arrays)
5. Replace with copied data
6. Save

### ✅ DONE! Your app is LIVE!

Visit: **https://yourname.000webhostapp.com**

---

## 🎯 That's It!

Total time: **5 minutes**
Cost: **FREE**

Your F1 Fantasy app is now live and ready for your friends to use!

---

## 🆘 Need Help?

If something doesn't work:
1. Check `config.php` has correct database credentials
2. Make sure database was imported successfully
3. Check File Manager - all files uploaded?
4. Try visiting the homepage first

---

## 📝 Quick Checklist

- [ ] Signed up at 000webhost
- [ ] Created website/subdomain
- [ ] Uploaded all files to public_html
- [ ] Created MySQL database
- [ ] Updated config.php with database info
- [ ] Imported database.sql
- [ ] Ran setup-races.php
- [ ] Updated predict.php with drivers
- [ ] Tested homepage
- [ ] Created test account

**All done? Your app is LIVE! 🎉**

