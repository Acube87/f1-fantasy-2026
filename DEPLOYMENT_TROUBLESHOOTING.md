# Branch Deployment Troubleshooting Checklist

## Quick Diagnosis

Check these in order:

### ✅ 1. Is your branch `main` or `master`?

```bash
git branch --show-current
```

- **If YES**: Should auto-deploy on push
- **If NO**: Feature branch - requires manual deployment

**Fix**: Use manual deployment (see below)

---

### ✅ 2. Are you pushing to the correct remote?

```bash
git remote -v
```

Should show:
```
origin  https://github.com/Acube87/f1-fantasy-2026 (fetch)
origin  https://github.com/Acube87/f1-fantasy-2026 (push)
```

**Fix**: If wrong, update remote:
```bash
git remote set-url origin https://github.com/Acube87/f1-fantasy-2026
```

---

### ✅ 3. Did your push succeed?

```bash
git push origin your-branch-name
```

Should see:
```
To https://github.com/Acube87/f1-fantasy-2026
   abc1234..def5678  your-branch -> your-branch
```

**Fix**: If failed, check:
- GitHub authentication
- Branch permissions
- Network connection

---

### ✅ 4. Are GitHub Actions enabled?

Visit: https://github.com/Acube87/f1-fantasy-2026/actions

- **If you see workflows**: Actions are enabled ✅
- **If you see "Actions disabled"**: Enable them in Settings

**Fix**: Settings → Actions → Allow all actions

---

### ✅ 5. Does the deploy workflow exist?

Check: https://github.com/Acube87/f1-fantasy-2026/actions/workflows/deploy.yml

- **If exists**: Workflow is present ✅
- **If 404**: Workflow missing

**Fix**: Ensure `.github/workflows/deploy.yml` is committed

---

### ✅ 6. Is Railway token configured?

For automatic deployments, you need:
- `RAILWAY_TOKEN` secret
- `RAILWAY_SERVICE_ID` secret (optional)

**Check**: Repository → Settings → Secrets → Actions

**Fix**: See [GITHUB_SECRETS_SETUP.md](GITHUB_SECRETS_SETUP.md)

---

## Solutions by Situation

### Situation A: "My feature branch doesn't deploy"

**This is normal!** Feature branches don't auto-deploy.

**Solution**: Deploy manually via GitHub Actions

1. Go to **Actions** tab
2. Select **Deploy to Railway**
3. Click **Run workflow**
4. Choose your branch
5. Select "staging" environment
6. Click **Run workflow**

See: [DEPLOY_FEATURE_BRANCH.md](DEPLOY_FEATURE_BRANCH.md)

---

### Situation B: "Main branch doesn't deploy"

**Check**:
1. GitHub Actions enabled? ✅
2. Workflow file exists? `.github/workflows/deploy.yml` ✅
3. Railway token configured? (optional for direct Railway integration)

**Solution**:
- Check workflow run in Actions tab for errors
- If token missing, configure it or use Railway's direct GitHub integration

---

### Situation C: "Deployment fails with errors"

**Common errors**:

#### "RAILWAY_TOKEN not set"
- **Solution**: Add token to GitHub Secrets OR use Railway's GitHub integration
- See: [GITHUB_SECRETS_SETUP.md](GITHUB_SECRETS_SETUP.md)

#### "PHP syntax error"
- **Solution**: Fix PHP syntax errors
- Check workflow logs for specific file/line
- Test locally: `php -l yourfile.php`

#### "Railway CLI error"
- **Solution**: Check Railway service ID is correct
- Ensure Railway project exists
- Verify token has correct permissions

#### "Cannot access repository"
- **Solution**: Grant Railway access to repository
- See: [DEPLOYMENT_ACCESS_FIX.md](DEPLOYMENT_ACCESS_FIX.md)

---

### Situation D: "Used to work, now doesn't"

**What changed?**

1. **New branch created?**
   - Feature branches require manual deployment
   - See: [BRANCH_DEPLOYMENT_GUIDE.md](BRANCH_DEPLOYMENT_GUIDE.md)

2. **Railway token expired?**
   - Generate new token
   - Update GitHub Secrets

3. **Railway service changed?**
   - Update `RAILWAY_SERVICE_ID` secret

4. **Repository permissions changed?**
   - Re-grant Railway access
   - See: [DEPLOYMENT_ACCESS_FIX.md](DEPLOYMENT_ACCESS_FIX.md)

---

## Deployment Status Check

### Check 1: Workflow Runs
https://github.com/Acube87/f1-fantasy-2026/actions

Look for:
- ✅ Green checkmark = Success
- ❌ Red X = Failed (click for logs)
- 🟡 Yellow dot = Running
- ⚪ Gray circle = Queued

### Check 2: Railway Dashboard
https://railway.app/dashboard

Look for:
- Latest deployment timestamp
- Deployment status (Active/Failed)
- Deployment logs

### Check 3: Live Site
Visit your Railway URL to verify deployment worked.

---

## Quick Fixes

### Manual Deployment (Works 100% of time)

```
1. Go to: https://github.com/Acube87/f1-fantasy-2026/actions
2. Click: Deploy to Railway
3. Click: Run workflow
4. Select: Your branch
5. Choose: staging
6. Click: Run workflow
```

**Takes 2 minutes, always works!**

---

### Railway Preview Deployments (Set once, works forever)

```
1. Railway Dashboard → Your Project
2. Settings → Deploy
3. Enable Preview Deployments
4. Save
```

Now every branch gets automatic preview deployment!

---

## Still Stuck?

### Check These Resources

1. **Branch deployment**: [BRANCH_DEPLOYMENT_GUIDE.md](BRANCH_DEPLOYMENT_GUIDE.md)
2. **Quick start**: [DEPLOY_FEATURE_BRANCH.md](DEPLOY_FEATURE_BRANCH.md)
3. **Access issues**: [DEPLOYMENT_ACCESS_FIX.md](DEPLOYMENT_ACCESS_FIX.md)
4. **Railway setup**: [RAILWAY_DEPLOY.md](RAILWAY_DEPLOY.md)
5. **Secrets setup**: [GITHUB_SECRETS_SETUP.md](GITHUB_SECRETS_SETUP.md)

### Get Help

1. **Check workflow logs**: Actions tab → Failed run → Click job → View logs
2. **Check Railway logs**: Railway Dashboard → Deployments → View logs
3. **Test locally**: `php -S localhost:8000` → Visit http://localhost:8000

---

## Deployment Flow Diagram

```
Your Code Changes
        ↓
    Git Commit
        ↓
    Git Push
        ↓
┌───────────────────┐
│  GitHub receives  │
│  your push        │
└───────────────────┘
        ↓
  Is branch main/master?
    /           \
   YES          NO (feature branch)
    ↓            ↓
Auto-deploy   Manual deployment
    ↓         required
Railway           ↓
    ↓         Actions tab
    ↓         Run workflow
    ↓             ↓
    └─────────────┘
           ↓
    Deployment!
```

---

## Prevention Tips

### For Future Branches

1. **Document branch strategy**: Know which branches auto-deploy
2. **Use manual deployment**: Safe, predictable, always works
3. **Enable Railway previews**: Automatic for all branches
4. **Test locally first**: Catch errors before deploying

### Best Practices

```
Feature Development:
1. Create feature branch
2. Make changes
3. Test locally
4. Commit and push
5. Deploy manually to staging
6. Test staging deployment
7. Merge to main
8. Auto-deploys to production ✅
```

---

## Summary

**Most Common Issue**: Feature branches don't auto-deploy (by design)

**Fastest Solution**: Manual deployment via Actions tab (2 minutes)

**Best Long-term Solution**: Enable Railway preview deployments (5 minutes setup, automatic forever)

**Remember**: This is a safety feature, not a bug! It prevents accidental production deployments from development branches.
