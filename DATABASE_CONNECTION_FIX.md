# Database Connection Troubleshooting Guide

## Common Issue: "No such file or directory"

If you're seeing this error:
```
Fatal error: Uncaught Exception: Database connection error: No such file or directory
```

This means MySQL is trying to use a socket connection but can't find the socket file.

## Quick Fix

This issue has been fixed in the latest version. The solution:
1. **Update your code** - Pull the latest changes from the repository
2. **Redeploy** - Deploy the updated code to Railway
3. **Test** - Try logging in again

## What Was Fixed

The database connection code now:
- ✅ Detects Railway environment variables
- ✅ Forces TCP connection for Railway (not socket)
- ✅ Provides better error messages
- ✅ Shows connection details for debugging

## Testing Your Connection

Use the included test script to diagnose connection issues:

```bash
php test-db-connection.php
```

This will show:
- All environment variables
- Connection parameters being used
- Whether connection succeeds or fails
- Detailed error messages if it fails

## Railway Deployment Checklist

### 1. Verify Environment Variables

In Railway dashboard, check your MySQL service has these variables:
- `MYSQLHOST` or `RAILWAY_TCP_PROXY_DOMAIN`
- `MYSQLPORT` or `RAILWAY_TCP_PROXY_PORT`
- `MYSQLUSER`
- `MYSQLPASSWORD`
- `MYSQLDATABASE` or `MYSQL_DATABASE`

### 2. Check Database Service

Ensure:
- ✅ MySQL service is running
- ✅ Database is created
- ✅ Tables are imported (from database.sql)
- ✅ Service is linked to your web app

### 3. Verify Connection Settings

The app now automatically:
- Uses Railway's TCP proxy domain/port
- Falls back to standard MySQL environment variables
- Uses localhost for local development

## Environment Variable Priority

The code checks environment variables in this order:

**For Host:**
1. `RAILWAY_TCP_PROXY_DOMAIN` (Railway specific)
2. `MYSQLHOST` (Standard Railway)
3. `DB_HOST` (Custom)
4. `localhost` (Fallback)

**For Port:**
1. `RAILWAY_TCP_PROXY_PORT` (Railway specific)
2. `MYSQLPORT` (Standard Railway)
3. `DB_PORT` (Custom)
4. `3306` (Default)

## Connection Logic

### Remote Connection (Railway)
When hostname is NOT `localhost` or `127.0.0.1`:
```php
new mysqli($host, $user, $pass, $dbname, $port);
```
This forces TCP connection with explicit port.

### Local Connection (Development)
When hostname IS `localhost` or `127.0.0.1`:
```php
new mysqli($host, $user, $pass, $dbname);
```
This lets MySQL decide whether to use socket or TCP.

## Common Issues and Solutions

### Issue 1: Wrong Database Name
**Error:** `Unknown database 'railway'`
**Solution:** 
- Check `MYSQLDATABASE` or `MYSQL_DATABASE` environment variable
- Default changed from 'railway' to 'f1_fantasy'
- Update environment variable or import schema to correct database

### Issue 2: Authentication Failed
**Error:** `Access denied for user`
**Solution:**
- Verify `MYSQLUSER` and `MYSQLPASSWORD` environment variables
- Check Railway MySQL service for correct credentials
- Ensure password doesn't have special characters that need escaping

### Issue 3: Can't Connect to Host
**Error:** `Connection refused` or `Can't connect to MySQL server`
**Solution:**
- Check `MYSQLHOST` is set correctly
- For Railway, should be something like `containers-us-west-XXX.railway.app`
- Verify MySQL service is running in Railway dashboard

### Issue 4: Socket File Not Found
**Error:** `No such file or directory`
**Solution:**
- ✅ **FIXED** - Latest code forces TCP connection for Railway
- Update your code and redeploy
- This was the main issue that's now resolved

## Debugging Steps

1. **Check Environment Variables**
   ```bash
   php -r "echo getenv('MYSQLHOST');"
   php -r "echo getenv('MYSQLPORT');"
   ```

2. **Run Connection Test**
   ```bash
   php test-db-connection.php
   ```

3. **Check Railway Logs**
   - Go to Railway dashboard
   - Click on your service
   - View "Deployments" → Click latest → "View Logs"
   - Look for database connection errors

4. **Test MySQL Connection Directly**
   In Railway MySQL service:
   - Click "Connect"
   - Use provided connection string
   - Test with MySQL client

## Railway-Specific Notes

### TCP Proxy Variables
Railway provides two sets of variables:

**Internal (for services in same project):**
- `MYSQLHOST`
- `MYSQLPORT`

**External (TCP proxy for outside connections):**
- `RAILWAY_TCP_PROXY_DOMAIN`
- `RAILWAY_TCP_PROXY_PORT`

The app uses TCP proxy first for better reliability.

### Database Service Setup

1. **Add MySQL Service**
   - Railway project → "+ New" → "Database" → "Add MySQL"

2. **Link to Web Service**
   - Variables automatically available in web service
   - No manual configuration needed

3. **Import Schema**
   ```bash
   # Get connection string from Railway
   mysql -h <host> -P <port> -u <user> -p<password> <database> < database.sql
   ```

## Local Development

For local development, the app works with:
- XAMPP
- MAMP
- Docker MySQL
- Native MySQL installation

Just ensure:
- MySQL is running
- Database 'f1_fantasy' exists
- User has proper permissions
- Tables are imported from database.sql

## Still Having Issues?

If the issue persists after updating:

1. **Check your Railway environment variables**
   - All MySQL variables should be set
   - Values should match MySQL service

2. **Verify database exists and has tables**
   - Connect to MySQL via Railway
   - Check database and tables exist

3. **Run the test script**
   ```bash
   php test-db-connection.php
   ```

4. **Check error logs**
   - Railway dashboard → Logs
   - Look for specific error messages

5. **Try manual connection**
   - Use MySQL client with Railway credentials
   - Verify you can connect from outside Railway

## Summary

The database connection issue has been fixed by:
- ✅ Forcing TCP connections for Railway
- ✅ Removing error suppression
- ✅ Adding detailed error messages
- ✅ Improving environment variable handling
- ✅ Providing diagnostic tools

**Update your code and redeploy to fix the login issue!**
