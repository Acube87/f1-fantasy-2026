# Railway Manual Environment Variables Setup

## When To Use This Guide

Use this guide if you want to:
- Connect to an **external MySQL database** (not Railway's MySQL)
- Use a database from PlanetScale, AWS RDS, DigitalOcean, etc.
- Manually configure database connection

**Note**: If you want to use Railway's built-in MySQL, use `RAILWAY_DATABASE_SETUP.md` instead (easier).

---

## Required Environment Variables

Your Railway web service needs these environment variables set:

### Option A: Standard MySQL Variables (Recommended)

```
MYSQLHOST = your-database-host.com
MYSQLPORT = 3306
MYSQLUSER = your_username
MYSQLPASSWORD = your_password
MYSQLDATABASE = your_database_name
```

### Option B: Railway TCP Proxy Variables

```
RAILWAY_TCP_PROXY_DOMAIN = your-database-host.com
RAILWAY_TCP_PROXY_PORT = 3306
MYSQLUSER = your_username
MYSQL_ROOT_PASSWORD = your_password
MYSQL_DATABASE = your_database_name
```

### Option C: Generic DB Variables

```
DB_HOST = your-database-host.com
DB_PORT = 3306
DB_USER = your_username
DB_PASS = your_password
DB_NAME = your_database_name
```

**The code checks all three options in order!**

---

## How To Set Variables in Railway

1. **Go to Railway Dashboard**
   - Visit: https://railway.app/dashboard
   - Select your F1 Fantasy project

2. **Click on your web service**

3. **Go to "Variables" tab**

4. **Click "New Variable"**

5. **Add each variable**:
   - Variable name: `MYSQLHOST`
   - Value: `your-database-host.com`
   - Click "Add"

6. **Repeat for all variables**:
   - MYSQLPORT
   - MYSQLUSER
   - MYSQLPASSWORD
   - MYSQLDATABASE

7. **Redeploy** your web service

---

## Variable Examples

### PlanetScale Example

```
MYSQLHOST = us-east.connect.psdb.cloud
MYSQLPORT = 3306
MYSQLUSER = abcdefghijkl
MYSQLPASSWORD = pscale_pw_1234567890
MYSQLDATABASE = f1_fantasy
```

**Note**: PlanetScale might require SSL connection parameters (not yet supported in current code).

### AWS RDS Example

```
MYSQLHOST = mydb.c1234567890.us-east-1.rds.amazonaws.com
MYSQLPORT = 3306
MYSQLUSER = admin
MYSQLPASSWORD = MySecretPassword123
MYSQLDATABASE = f1_fantasy
```

### DigitalOcean Example

```
MYSQLHOST = db-mysql-nyc3-12345-do-user-1234567-0.b.db.ondigitalocean.com
MYSQLPORT = 25060
MYSQLUSER = doadmin
MYSQLPASSWORD = AVNS_1234567890
MYSQLDATABASE = defaultdb
```

### Local Development Example

```
MYSQLHOST = localhost
MYSQLPORT = 3306
MYSQLUSER = root
MYSQLPASSWORD = 
MYSQLDATABASE = f1_fantasy
```

---

## Connection String Format

Some services provide a full connection URL. Railway doesn't use this directly, but you can extract values:

**Connection URL format:**
```
mysql://username:password@hostname:port/database
```

**Example:**
```
mysql://root:MyPass123@db.example.com:3306/f1_fantasy
```

**Extract to variables:**
```
MYSQLHOST = db.example.com
MYSQLPORT = 3306
MYSQLUSER = root
MYSQLPASSWORD = MyPass123
MYSQLDATABASE = f1_fantasy
```

---

## Verification

After setting variables and redeploying:

1. **Visit diagnostic page**:
   ```
   https://f1.scanerrific.com/check-env.php
   ```

2. **Check output shows your variables**:
   ```
   MYSQLHOST = db.example.com
   MYSQLPORT = 3306
   MYSQLUSER = root
   MYSQLPASSWORD = [SET]
   MYSQLDATABASE = f1_fantasy
   
   Host: db.example.com
   Type: Remote (TCP)
   ```

3. **Check connection test**:
   ```
   ✅ Connection SUCCESSFUL!
   ```
   OR
   ```
   ❌ Connection FAILED: [error message]
   ```

4. **If connection successful**, try logging in to your app

---

## Common Issues

### Connection times out

**Possible causes:**
- Database firewall blocking Railway IP addresses
- Database not accepting external connections
- Wrong hostname/port

**Solutions:**
- Check database firewall settings
- Allow Railway IP ranges (check Railway docs)
- Verify hostname and port are correct

### Access denied for user

**Possible causes:**
- Wrong username or password
- User doesn't have remote access permissions
- Database requires specific host pattern

**Solutions:**
- Double-check username and password
- Grant remote access to database user
- Check database user host permissions

### SSL/TLS connection required

**Possible causes:**
- Database requires encrypted connection
- Current code doesn't configure SSL

**Solutions:**
- Use database that doesn't require SSL, OR
- Modify config.php to add SSL options (advanced)

### Variables not showing in check-env.php

**Possible causes:**
- Variables not saved in Railway
- Web service not redeployed after adding variables

**Solutions:**
- Check Railway web service Variables tab
- Verify variables are listed
- Redeploy web service

---

## External Database Providers

### PlanetScale
- Free tier available
- Serverless MySQL
- Good performance
- May require SSL configuration

### AWS RDS
- Paid service
- Fully managed MySQL
- High availability
- Requires AWS account

### DigitalOcean Managed Databases
- Paid service (starts $15/month)
- Simple setup
- Good for small-medium apps

### Railway MySQL (Recommended)
- Integrated with Railway
- Automatic variable management
- Easier setup
- See RAILWAY_DATABASE_SETUP.md

---

## Security Notes

- **Never commit credentials** to git
- **Use Railway's Variables** (encrypted at rest)
- **Use strong passwords**
- **Restrict database access** by IP if possible
- **Regular backups** of database

---

## Database Schema

After connection is working, import the schema:

1. Connect to your external database using provided tool
2. Run the SQL from `database.sql` file in this repo
3. Creates necessary tables: users, races, predictions, etc.

---

## Summary

1. Get external MySQL database credentials
2. Set environment variables in Railway web service
3. Redeploy web service
4. Visit check-env.php to verify
5. Import database schema
6. Test login

---

## Need Help?

- Check check-env.php for connection status
- Read RAILWAY_DATABASE_SETUP.md for Railway's built-in MySQL (easier)
- Verify all variables are set correctly in Railway dashboard
