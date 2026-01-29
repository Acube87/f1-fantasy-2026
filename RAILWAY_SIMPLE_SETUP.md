# Railway Setup - Simple Instructions

## What Railway Does Automatically

When you add MySQL to your Railway project:
✅ Creates a MySQL database  
✅ Sets up all environment variables automatically  
✅ Connects your app to the database  

**You don't need to do anything manually!**

## How This App Works with Railway

1. **Add MySQL Plugin**
   - In Railway dashboard, click "New" → "Database" → "Add MySQL"
   - Railway provisions everything automatically

2. **Deploy Your App**
   - Railway automatically deploys from your GitHub repo
   - App will connect to MySQL using Railway's environment variables

3. **Tables Auto-Create**
   - First time the app runs, it automatically creates all needed tables
   - No manual schema import needed!

## That's It!

The app should just work. Visit your Railway URL and you'll see the login page.

## Environment Variables (Automatic)

Railway sets these automatically when you add MySQL:
- `MYSQLHOST` - Database host
- `MYSQLPORT` - Database port (usually 3306)
- `MYSQLUSER` - Username (usually 'root')
- `MYSQLPASSWORD` - Password
- `MYSQLDATABASE` - Database name

The app reads these automatically.

## Troubleshooting

**If you see database connection errors:**

1. Make sure MySQL is added in Railway (should see it in your project)
2. Check that your app service is linked to the MySQL service
3. Check Railway logs for specific error messages

**If tables aren't created:**
- The app auto-creates tables on first database connection
- Check Railway logs for any SQL errors
- Make sure `database.sql` file exists in your repo

## Testing Locally

To test locally without Railway:
1. Install MySQL locally
2. The app will use default values (localhost, root, no password)
3. Database and tables will auto-create

## Support

Having issues? Check Railway's documentation or the app logs in Railway dashboard.
