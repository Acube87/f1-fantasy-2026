# GitHub Secrets Setup for Automated Deployment

This guide explains how to set up GitHub Secrets to enable automated deployments via GitHub Actions.

## What are GitHub Secrets?

GitHub Secrets are encrypted environment variables that you can use in GitHub Actions workflows. They're perfect for storing sensitive information like API tokens, deployment keys, and credentials.

## Setting Up Secrets for Railway Deployment

### Step 1: Get Your Railway Token

1. Go to [Railway](https://railway.app)
2. Log in to your account
3. Click your profile icon (top right)
4. Go to **Account Settings**
5. Navigate to **Tokens** tab
6. Click **Create Token**
7. Give it a name: "GitHub Actions Deploy"
8. Copy the generated token (you won't see it again!)

### Step 2: Add Secret to GitHub

1. Go to your GitHub repository: https://github.com/Acube87/f1-fantasy-2026
2. Click **Settings** (repository settings, not your account)
3. In the left sidebar, click **Secrets and variables** → **Actions**
4. Click **New repository secret**
5. Add these secrets:

#### RAILWAY_TOKEN
- Name: `RAILWAY_TOKEN`
- Value: Paste the token from Railway
- Click **Add secret**

#### RAILWAY_SERVICE_ID (Optional)
- Name: `RAILWAY_SERVICE_ID`
- Value: Your Railway service ID (from Railway project URL)
- Click **Add secret**

### Step 3: Verify Setup

1. Go to **Actions** tab in your repository
2. You should see the deployment workflows
3. Make a commit to trigger a deployment
4. Watch the workflow run and verify it succeeds

## Alternative: Using Deploy Keys

If you prefer SSH-based deployment:

### Generate SSH Key Pair

```bash
# On your local machine
ssh-keygen -t ed25519 -C "github-actions-deploy" -f ~/.ssh/f1_fantasy_deploy
```

### Add Public Key to Deployment Platform

1. Copy the public key:
   ```bash
   cat ~/.ssh/f1_fantasy_deploy.pub
   ```
2. Add it to your deployment platform's SSH keys section

### Add Private Key to GitHub Secrets

1. Copy the private key:
   ```bash
   cat ~/.ssh/f1_fantasy_deploy
   ```
2. In GitHub: **Settings** → **Secrets** → **New secret**
3. Name: `DEPLOY_SSH_KEY`
4. Value: Paste the private key
5. Click **Add secret**

## Managing Secrets

### Viewing Secrets
- Secret values are **never displayed** after creation
- You can only see secret names, not their values
- To change a secret, you must delete and recreate it

### Updating Secrets
1. Go to **Settings** → **Secrets and variables** → **Actions**
2. Find the secret you want to update
3. Click **Update** or **Remove** then create a new one

### Security Best Practices
- ✅ Never commit secrets to your repository
- ✅ Rotate tokens regularly (every 90 days recommended)
- ✅ Use different tokens for different environments
- ✅ Limit token permissions to minimum required
- ✅ Revoke tokens immediately if compromised

## Secrets Reference

Here's a list of secrets you might need:

| Secret Name | Description | Where to Get It |
|-------------|-------------|-----------------|
| `RAILWAY_TOKEN` | Railway API token | Railway → Account → Tokens |
| `RAILWAY_SERVICE_ID` | Railway service ID | Railway project URL |
| `DATABASE_URL` | Database connection string | Railway MySQL service |
| `HOSTINGER_FTP_HOST` | FTP hostname | Hostinger control panel |
| `HOSTINGER_FTP_USER` | FTP username | Hostinger control panel |
| `HOSTINGER_FTP_PASS` | FTP password | Hostinger control panel |

## Testing GitHub Actions

### Manual Trigger
1. Go to **Actions** tab
2. Select a workflow
3. Click **Run workflow**
4. Choose branch and click **Run workflow**

### Viewing Logs
1. Go to **Actions** tab
2. Click on a workflow run
3. Click on the job name to see detailed logs
4. Expand steps to see individual command outputs

## Troubleshooting

### "Secret not found" Error
- Check secret name matches exactly (case-sensitive)
- Verify secret was created in the correct repository
- Ensure you're using `${{ secrets.SECRET_NAME }}` syntax

### "Permission denied" Error
- Check token has necessary permissions
- Verify token hasn't expired
- Try regenerating the token

### Deployment Fails
- Check the workflow logs for specific error messages
- Verify all required secrets are set
- Ensure deployment platform is accessible
- Check that repository has proper access configured

## Environment-Specific Secrets

For different environments (staging, production):

### Option 1: Multiple Secrets
```yaml
- name: Deploy to Production
  env:
    RAILWAY_TOKEN: ${{ secrets.RAILWAY_TOKEN_PROD }}
```

### Option 2: GitHub Environments
1. Go to **Settings** → **Environments**
2. Click **New environment**
3. Name it (e.g., "production", "staging")
4. Add environment-specific secrets
5. Reference in workflow:
   ```yaml
   jobs:
     deploy:
       environment: production
   ```

## Complete Workflow Example

Here's how secrets are used in `.github/workflows/deploy.yml`:

```yaml
name: Deploy

on:
  push:
    branches: [main]

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      
      - name: Deploy to Railway
        env:
          RAILWAY_TOKEN: ${{ secrets.RAILWAY_TOKEN }}
        run: |
          npm install -g @railway/cli
          railway up
```

## Getting Help

- **GitHub Actions Documentation**: https://docs.github.com/en/actions
- **GitHub Secrets Guide**: https://docs.github.com/en/actions/security-guides/encrypted-secrets
- **Railway Documentation**: https://docs.railway.app/

## Quick Checklist

Before enabling automated deployments:

- [ ] Railway account created and project set up
- [ ] Railway token generated
- [ ] GitHub secrets added (`RAILWAY_TOKEN`)
- [ ] Workflows committed to repository (`.github/workflows/`)
- [ ] Test workflow runs successfully
- [ ] Deployment platform shows successful deployment

---

**Note:** After setting up secrets, they will be automatically used by GitHub Actions workflows. No code changes needed!
