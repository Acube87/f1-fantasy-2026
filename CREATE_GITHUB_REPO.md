# 🚀 Create GitHub Repo - Quick Steps

## You need to create a GitHub repository first!

### Step 1: Create Repo on GitHub
1. Go to [github.com](https://github.com)
2. Click **"+"** (top right) → **"New repository"**
3. Name it: `f1-fantasy-2026` (or any name you like)
4. Make it **Public** or **Private** (your choice)
5. **DON'T** initialize with README (we already have files)
6. Click **"Create repository"**

### Step 2: Push Your Code
After creating the repo, GitHub will show you commands. Run these in your terminal:

```bash
cd /Users/angrycube/Sites/F1
git remote add origin https://github.com/YOUR_USERNAME/f1-fantasy-2026.git
git branch -M main
git push -u origin main
```

**Replace `YOUR_USERNAME` with your GitHub username!**

### Step 3: Connect to Railway
1. Go back to Railway
2. Click **"+ New Project"**
3. Select **"Deploy from GitHub repo"**
4. Choose your new `f1-fantasy-2026` repo
5. Railway will auto-detect PHP ✅

---

## 🎯 What I've Done:

✅ Initialized git repo in your F1 folder
✅ Added all files
✅ Created commit
✅ Created .gitignore (to exclude config.php with passwords)

**Now you just need to:**
1. Create GitHub repo
2. Push code
3. Connect to Railway

---

## 📝 Quick Commands (After creating GitHub repo):

```bash
# Add remote (replace YOUR_USERNAME and REPO_NAME)
git remote add origin https://github.com/YOUR_USERNAME/REPO_NAME.git

# Push to GitHub
git push -u origin main
```

Then in Railway, connect to that GitHub repo!

---

**Have you created the GitHub repo yet? If yes, what's the URL?**

