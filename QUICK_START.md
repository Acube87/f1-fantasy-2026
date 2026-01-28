# Quick Start - Deployment Setup

## The Problem

If you're seeing this error:
> "Cannot setup automatic deploys for Acube87/f1-fantasy-2026 because no one in the workspace has access to it"

This means your deployment platform (Railway, Netlify, Vercel, etc.) doesn't have permission to access your GitHub repository.

## The Solution (3 Steps)

### Step 1: Grant Repository Access

**This fixes 90% of deployment access issues!**

1. Go to: https://github.com/settings/installations
2. Find your deployment service (e.g., "Railway", "Netlify", "Vercel")
3. Click **Configure**
4. Under "Repository access", select:
   - **All repositories** (easiest), OR
   - **Only select repositories** → Choose `f1-fantasy-2026`
5. Click **Save**
6. Return to your deployment platform and try again

### Step 2: Deploy on Railway (Recommended for PHP)

Railway is the easiest deployment platform for PHP applications.

1. **Sign up**: Go to [railway.app](https://railway.app)
2. **Connect GitHub**: Click "Login with GitHub"
3. **Create Project**: Click "New Project" → "Deploy from GitHub repo"
4. **Select Repository**: Choose `Acube87/f1-fantasy-2026`
5. **Add Database**: Click "+ New" → "Database" → "MySQL"
6. **Deploy**: Railway will automatically deploy your app!

Your app will be live at: `yourproject.railway.app`

### Step 3: Configure Environment Variables

In Railway dashboard:

1. Click on your web service
2. Go to **Variables** tab
3. Railway automatically provides database variables, no manual setup needed!
4. The app will automatically use Railway's MySQL connection

## Alternative: GitHub Actions Deployment

If you can't connect Railway directly, use GitHub Actions:

1. **Get Railway Token**:
   - Railway → Account Settings → Tokens
   - Click "Create Token"
   - Copy the token

2. **Add GitHub Secret**:
   - GitHub → Repository Settings → Secrets → Actions
   - Click "New repository secret"
   - Name: `RAILWAY_TOKEN`
   - Value: Paste your Railway token
   - Click "Add secret"

3. **Push Code**:
   - The GitHub Actions workflow will automatically deploy
   - Check **Actions** tab to see deployment status

## Verification

✅ Can you see your repository in the deployment platform?
✅ Can you trigger a manual deployment?
✅ Does the app load without errors?

## Need More Help?

- **Full troubleshooting guide**: See [DEPLOYMENT_ACCESS_FIX.md](DEPLOYMENT_ACCESS_FIX.md)
- **GitHub secrets setup**: See [GITHUB_SECRETS_SETUP.md](GITHUB_SECRETS_SETUP.md)
- **Railway deployment**: See [RAILWAY_DEPLOY.md](RAILWAY_DEPLOY.md)

## Common Issues

### "No repositories found"
→ Grant repository access in GitHub settings (Step 1 above)

### "Permission denied"
→ Make sure you're the repository owner or have admin access

### "Railway token invalid"
→ Generate a new token and update GitHub secrets

### "Database connection failed"
→ Check that MySQL database is added in Railway

## Quick Commands

Test your setup locally:
```bash
# Check PHP syntax
find . -name "*.php" -exec php -l {} \;

# Start local server
php -S localhost:8000
```

Visit: http://localhost:8000

## What's Included

This repository now has:
- ✅ GitHub Actions CI/CD workflows
- ✅ Automatic deployment to Railway
- ✅ PHP syntax validation
- ✅ Security checks
- ✅ Environment variable configuration
- ✅ MySQL database support

## Success!

Once deployed, you'll have:
- Live F1 fantasy application
- Automatic deployments on push to main
- Built-in CI/CD pipeline
- Production-ready database connection

**Your app will be accessible at**: `https://yourproject.railway.app`

---

For detailed instructions, see the complete documentation in the repository.
