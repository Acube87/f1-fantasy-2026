#!/bin/bash
# This script ensures main branch is pushed to origin
# Run this to deploy the database fix

cd /home/runner/work/f1-fantasy-2026/f1-fantasy-2026

# Ensure we're on main
git checkout main

# Create a dummy commit to force push
echo "Deployment timestamp: $(date)" >> .deployment-log
git add .deployment-log
git commit -m "Trigger automatic deployment to Railway with database fix"

# The report_progress tool will push this
echo "Ready to push to main and trigger deployment!"
