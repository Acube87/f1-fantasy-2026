# Deployment Guide - Hostinger

## Step-by-Step Deployment Instructions

### 1. Get Hostinger Account
- Sign up at [hostinger.com](https://www.hostinger.com)
- Choose a hosting plan (Shared Hosting is sufficient)
- Complete registration and verify your account

### 2. Access Your Hosting Control Panel
- Log into Hostinger hPanel
- Navigate to **File Manager** or use **FTP**

### 3. Upload Files

#### Option A: Using File Manager (Easiest)
1. Go to **File Manager** in hPanel
2. Navigate to `public_html` folder
3. Upload all files from your local `F1` folder
4. Maintain the folder structure:
   ```
   public_html/
   ├── index.php
   ├── config.php
   ├── login.php
   ├── signup.php
   ├── dashboard.php
   ├── leaderboard.php
   ├── predict.php
   ├── race-results.php
   ├── logout.php
   ├── includes/
   │   ├── auth.php
   │   └── functions.php
   ├── api/
   │   ├── fetch-results.php
   │   └── calculate-scores.php
   ├── admin/
   │   ├── setup-races.php
   │   └── fetch-drivers.php
   ├── css/
   │   └── style.css
   └── js/
       └── main.js
   ```

#### Option B: Using FTP
1. Get FTP credentials from Hostinger hPanel
2. Use FTP client (FileZilla, Cyberduck, etc.)
3. Connect to your server
4. Upload all files to `public_html` directory

### 4. Create MySQL Database

1. In hPanel, go to **MySQL Databases**
2. Create a new database:
   - Database name: `f1_fantasy` (or your choice)
   - Click **Create**
3. Create a database user:
   - Username: `f1_user` (or your choice)
   - Password: (create a strong password)
   - Click **Create User**
4. Add user to database:
   - Select the user and database
   - Grant **ALL PRIVILEGES**
   - Click **Add**

**IMPORTANT:** Note down these details:
- Database name: `your_db_name`
- Database username: `your_db_user`
- Database password: `your_db_password`
- Database host: Usually `localhost` (check in Hostinger)

### 5. Configure Database Connection

1. In File Manager, open `config.php`
2. Update these lines:
   ```php
   define('DB_HOST', 'localhost');  // Usually 'localhost'
   define('DB_USER', 'your_db_user');  // Your database username
   define('DB_PASS', 'your_db_password');  // Your database password
   define('DB_NAME', 'your_db_name');  // Your database name
   ```
3. Save the file

### 6. Import Database Schema

1. In hPanel, go to **phpMyAdmin**
2. Select your database from the left sidebar
3. Click on **Import** tab
4. Click **Choose File** and select `database.sql` from your local files
5. Click **Go** to import
6. You should see "Import has been successfully finished"

### 7. Set Up Races

1. Visit: `https://yourdomain.com/admin/setup-races.php`
2. This will populate the 2026 race calendar
3. **Important:** Update the race dates in `admin/setup-races.php` with actual 2026 F1 calendar dates before running

### 8. Get Drivers and Constructors

1. Visit: `https://yourdomain.com/admin/fetch-drivers.php`
2. Copy the drivers and constructors arrays shown
3. Open `predict.php` in File Manager
4. Replace the placeholder arrays (around line 76-88) with the copied data
5. Save the file

### 9. Test Your Application

1. Visit: `https://yourdomain.com`
2. You should see the homepage
3. Try signing up a test account
4. Test login functionality
5. Check if you can make predictions

### 10. Security Setup (Important!)

1. **Protect admin files:**
   - In File Manager, go to `admin/` folder
   - Create `.htaccess` file with:
     ```apache
     AuthType Basic
     AuthName "Admin Area"
     AuthUserFile /path/to/.htpasswd
     Require valid-user
     ```
   - Or restrict access via IP in Hostinger hPanel

2. **Remove error display in production:**
   - In `config.php`, remove or comment out:
     ```php
     error_reporting(E_ALL);
     ini_set('display_errors', 1);
     ```

3. **Set proper file permissions:**
   - Folders: 755
   - Files: 644
   - `config.php`: 600 (more secure)

### 11. Set Up Cron Job (Optional - for automatic results)

1. In hPanel, go to **Cron Jobs**
2. Add a new cron job:
   - Command: `curl https://yourdomain.com/api/fetch-results.php?race_id=1`
   - Schedule: Daily at 10 PM (adjust as needed)
   - Or run manually after each race

## Troubleshooting

### Database Connection Error
- Verify database credentials in `config.php`
- Check database user has proper permissions
- Ensure database exists in phpMyAdmin

### Page Shows Blank/Error
- Check PHP error logs in Hostinger hPanel
- Verify all files uploaded correctly
- Check file permissions

### Can't Access Admin Pages
- Verify files are in correct location
- Check `.htaccess` isn't blocking access
- Try accessing via direct URL

### API Not Working
- Check if `curl` is enabled (usually is)
- Verify F1 API endpoint is accessible
- Check error logs

## Post-Deployment Checklist

- [ ] All files uploaded
- [ ] Database created and configured
- [ ] `config.php` updated with correct credentials
- [ ] Database schema imported
- [ ] Races set up
- [ ] Drivers/constructors populated
- [ ] Test account created
- [ ] Login/logout works
- [ ] Predictions can be made
- [ ] Leaderboard displays correctly
- [ ] Security measures in place
- [ ] Error display disabled in production

## Support

- Hostinger Support: Available 24/7 in hPanel
- Check Hostinger documentation for specific hosting features
- Review PHP error logs in hPanel for debugging

## Domain Setup

If you have a custom domain:
1. Point domain to Hostinger nameservers
2. Add domain in Hostinger hPanel
3. Wait for DNS propagation (24-48 hours)

If using Hostinger subdomain:
- Your site will be at: `yourname.hostingersite.com`
- Works immediately after setup

---

**Your app will be live at:** `https://yourdomain.com` or `https://yourname.hostingersite.com`

