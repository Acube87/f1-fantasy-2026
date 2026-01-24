# Authenticate GitHub Push (Private Repo)

Since your repo is **PRIVATE**, you need to authenticate. Here are your options:

## Option 1: Personal Access Token (Easiest)

1. **Create Token:**
   - Go to: https://github.com/settings/tokens
   - Click **"Generate new token"** → **"Generate new token (classic)"**
   - Name: "F1 Fantasy"
   - Expiration: 90 days (or your choice)
   - Check **"repo"** scope (full control of private repositories)
   - Click **"Generate token"**
   - **COPY THE TOKEN** (you won't see it again!)

2. **Push with Token:**
   ```bash
   cd /Users/angrycube/Sites/F1
   git push -u origin main
   ```
   When prompted:
   - Username: `Acube87`
   - Password: **Paste your token** (not your GitHub password!)

## Option 2: Use SSH (If you have SSH keys)

```bash
cd /Users/angrycube/Sites/F1
git remote set-url origin git@github.com:Acube87/f1-fantasy-2026.git
git push -u origin main
```

## Option 3: GitHub CLI (if installed)

```bash
gh auth login
git push -u origin main
```

---

**Try Option 1 first - it's the easiest!**

After you get the token, run:
```bash
git push -u origin main
```
And use the token as the password.

