# Emergency Restoration Guide 🏁

If your app crashes or data is lost, follow these steps to restore from your backups.

## 1. Restoring the Codebase

If you need to revert the code to a previous version:

1.  **Locate your backup**: Check the `backups/` directory for `f1_fantasy_code_YYYYMMDD_HHMMSS.tar.gz`.
2.  **Clear current files**: (Optional but recommended) Delete the current files to ensure a clean restore.
3.  **Extract the backup**:
    ```bash
    tar -xzf backups/f1_fantasy_code_TIMESTAMP.tar.gz -C /path/to/your/app
    ```
4.  **Verify configuration**: Ensure your `config.php` still has the correct database credentials.

## 2. Restoring the Database

If your data is corrupted or lost:

1.  **Locate your SQL backup**: Check `backups/` for `f1_fantasy_db_YYYYMMDD_HHMMSS.sql.gz`.
2.  **Decompress the file**:
    ```bash
    gunzip backups/f1_fantasy_db_TIMESTAMP.sql.gz
    ```
3.  **Import to MySQL**:
    - **Local/Manual**:
      ```bash
      mysql -u root -p f1_fantasy < backups/f1_fantasy_db_TIMESTAMP.sql
      ```
    - **Railway**:
      Use the Railway CLI or the "Connect" tab in your Railway dashboard to run the import command provided by Railway.
      ```bash
      railway connect mysql -- < backups/f1_fantasy_db_TIMESTAMP.sql
      ```

## 3. Post-Restoration Checklist

- [ ] Check `config.php` database connection.
- [ ] Log in with an existing user to verify authentication.
- [ ] Check `Predict` page to see if previous predictions are restored.
- [ ] Verify `Leaderboard` totals match expectations.

## 4. Automating Backups (Recommended)

To ensure you always have fresh backups, set up a **Cron Job**:

1.  Open your crontab: `crontab -e`
2.  Add these lines to backup every night at 3 AM:
    ```bash
    0 3 * * * /Users/angrycube/Sites/F1/scripts/backup_code.sh >> /Users/angrycube/Sites/F1/backups/backup_log.txt 2>&1
    0 3 * * * /Users/angrycube/Sites/F1/scripts/backup_db.sh >> /Users/angrycube/Sites/F1/backups/backup_log.txt 2>&1
    ```

---

> [!IMPORTANT]
> **Always test your backups!** Try restoring a backup to a test environment occasionally to ensure the files are valid.
