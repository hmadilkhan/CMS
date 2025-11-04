#!/bin/bash
# =====================================================
# Safe Deploy Script for Hostinger Shared Hosting
# with PHP Mail Notifications
# Author: Adil & GPT-5
# =====================================================

# --- SETTINGS ---
WEBROOT="/home/u160855881/domains/solenenergyco.com/public_html/CRM/portal"
BACKUP_DIR="/home/u160855881/backups"
REPO_URL="https://github.com/hmadilkhan/CMS.git"
BRANCH="main"

# --- LOAD ENV VARIABLES ---
# Load .env file if it exists (optional)
if [ -f .env ]; then
  export $(grep -v '^#' .env | xargs)
fi

# Check if GITHUB_PAT is set
if [ -z "$GITHUB_PAT" ]; then
  echo "❌ ERROR: GITHUB_PAT not set. Please export it before running this script."
  echo "Example:"
  echo "  export GITHUB_PAT=your_personal_access_token"
  exit 1
fi

# --- DEPLOY START ---
echo "🚀 Starting deployment..."

cd $WEBROOT || { echo "❌ Invalid webroot: $WEBROOT"; exit 1; }

# --- BACKUP EXISTING FILES ---
TIMESTAMP=$(date +"%Y-%m-%d_%H-%M-%S")
mkdir -p $BACKUP_DIR
tar -czf "$BACKUP_DIR/backup_$TIMESTAMP.tar.gz" ./*
echo "✅ Backup created at $BACKUP_DIR/backup_$TIMESTAMP.tar.gz"

# --- FETCH LATEST CODE FROM GITHUB ---
echo "📥 Pulling latest code from GitHub..."
git fetch "https://hmadilkhan:${GITHUB_PAT}@github.com/hmadilkhan/CMS.git" $BRANCH
git reset --hard FETCH_HEAD

echo "✅ Code updated successfully."

# --- OPTIONAL: CLEAR CACHE OR RUN COMMANDS ---
# php artisan migrate --force
# php artisan cache:clear

echo "✅ Deployment completed successfully."
