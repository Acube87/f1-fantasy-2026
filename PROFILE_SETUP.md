# Profile Feature Setup

## Database Migration Required

To enable the new profile features (avatar customization), you need to add a new column to the `users` table.

### Option 1: Run the migration file

```bash
mysql -h [your-host] -P [port] -u [username] -p[password] [database] < migrations/add_avatar_style.sql
```

### Option 2: Run SQL directly

Execute this SQL command in your database:

```sql
ALTER TABLE users ADD COLUMN avatar_style VARCHAR(50) DEFAULT 'avataaars' AFTER email;
```

### Option 3: Via Railway Dashboard

1. Go to Railway dashboard
2. Open your MySQL database
3. Go to "Query" tab
4. Run: `ALTER TABLE users ADD COLUMN IF NOT EXISTS avatar_style VARCHAR(50) DEFAULT 'avataaars' AFTER email;`

## Features

Once the migration is complete, users can:

- Click their avatar to access profile page
- View detailed statistics (accuracy, avg position error, exact matches)
- Change avatar style (7 different styles available)
- Update username
- Change password
- View race history and best performance

##Access

Users can access the profile page by clicking their avatar in the top navigation bar.
