# Setup Instructions for F1 2026 Fantasy Game

## Prerequisites

- Hostinger hosting account with PHP 7.4+ and MySQL/MariaDB
- FTP access or File Manager access to upload files
- Database credentials from Hostinger

## Step 1: Upload Files

1. Upload all files to your Hostinger hosting directory (usually `public_html` or `www`)
2. Maintain the folder structure as provided

## Step 2: Create Database

1. Log into your Hostinger control panel
2. Go to MySQL Databases
3. Create a new database (e.g., `f1_fantasy`)
4. Create a database user and grant full privileges
5. Note down:
   - Database name
   - Database username
   - Database password
   - Database host (usually `localhost`)

## Step 3: Configure Database

1. Open `config.php` in a text editor
2. Update the database credentials:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'your_db_username');
   define('DB_PASS', 'your_db_password');
   define('DB_NAME', 'f1_fantasy');
   ```
3. Save and upload the file

## Step 4: Import Database Schema

1. Log into phpMyAdmin (usually available in Hostinger control panel)
2. Select your database
3. Go to the "Import" tab
4. Choose the `database.sql` file
5. Click "Go" to import

Alternatively, you can run the SQL commands directly in phpMyAdmin's SQL tab.

## Step 5: Set Up Races

1. Access `admin/setup-races.php` in your browser
   - Example: `https://yourdomain.com/admin/setup-races.php`
2. This will populate the 2026 race calendar
3. **Important**: Update the race dates in `admin/setup-races.php` with actual 2026 F1 calendar dates

## Step 6: Populate Drivers and Constructors

The prediction page currently has placeholder drivers/constructors. You need to:

1. Fetch the actual 2026 driver and constructor list from the F1 API
2. Update the arrays in `predict.php` with real data
3. Or create an admin script to fetch and store them in the database

## Step 7: Test the Application

1. Visit your website homepage
2. Create a test account
3. Try making predictions
4. Test the login/logout functionality

## Step 8: Fetch Race Results (After Each Race)

After each F1 race:

1. Go to: `https://yourdomain.com/api/fetch-results.php?race_id=X`
   - Replace X with the race ID
2. This will:
   - Fetch results from the F1 API
   - Calculate scores for all users
   - Update the leaderboard

You can automate this with a cron job or run it manually after each race.

## Cron Job Setup (Optional)

To automatically fetch results after races, set up a cron job in Hostinger:

1. Go to Cron Jobs in your Hostinger control panel
2. Add a cron job that runs daily:
   ```
   0 22 * * * curl https://yourdomain.com/api/fetch-results.php?race_id=1
   ```
   (Adjust time and race IDs as needed)

## Security Notes

1. **Change default passwords** - Use strong passwords for database
2. **Protect admin files** - Consider password-protecting the `admin/` directory
3. **Update API endpoints** - If using official F1 API, add authentication
4. **Regular backups** - Set up database backups in Hostinger

## Troubleshooting

### Database Connection Error
- Verify database credentials in `config.php`
- Check database user has proper permissions
- Ensure database exists

### API Not Working
- Check if `curl` is enabled on your hosting
- Verify F1 API endpoint is accessible
- Check error logs in Hostinger

### Predictions Not Saving
- Check database tables exist
- Verify user is logged in
- Check browser console for JavaScript errors

## Next Steps

1. Customize the design (colors, logo, etc.)
2. Add more features (email notifications, etc.)
3. Set up regular backups
4. Monitor usage and performance

## Support

For issues or questions, check:
- Hostinger support documentation
- PHP error logs in Hostinger control panel
- Database error logs

