#!/bin/bash
# =====================================================
# Safe Deploy Script for Hostinger Shared Hosting
# with PHP Mail Notifications
# Author: Adil & GPT-5
# =====================================================

# --- Load Laravel .env variables ---
LARAVEL_ENV="/home/u160855881/domains/solenenergyco.com/public_html/CRM/portal/.env"

if [ -f "$LARAVEL_ENV" ]; then
    export $(grep -v '^#' "$LARAVEL_ENV" | xargs)
    echo "✅ Loaded Laravel environment variables."
else
    echo "⚠️ Warning: Laravel .env file not found, cannot load variables."
fi

# --- SETTINGS ---
WEBROOT="/home/u160855881/domains/solenenergyco.com/public_html/CRM/portal"
BACKUP_DIR="/home/u160855881/backups"
BRANCH="main"
ADMIN_EMAIL="aptechadil@gmail.com"

# --- Validate required variables ---
if [ -z "$GITHUB_PAT" ]; then
    echo "❌ ERROR: GITHUB_PAT not set in .env"
    php -r "mail('$ADMIN_EMAIL', '❌ CRM Deploy Failed', 'GITHUB_PAT missing in .env file.');"
    exit 1
fi

# --- Start Deployment ---
echo "🚀 Starting deployment..."
cd "$WEBROOT" || { echo "❌ Invalid webroot: $WEBROOT"; exit 1; }

# --- Backup existing project ---
TIMESTAMP=$(date +"%Y-%m-%d_%H-%M-%S")
mkdir -p "$BACKUP_DIR"
tar -czf "$BACKUP_DIR/backup_$TIMESTAMP.tar.gz" ./*
echo "✅ Backup created at $BACKUP_DIR/backup_$TIMESTAMP.tar.gz"

# --- Configure authenticated remote temporarily ---
echo "🔑 Setting GitHub remote with token..."
git remote set-url origin "https://hmadilkhan:${GITHUB_PAT}@github.com/hmadilkhan/CMS.git"

# --- Pull latest code ---
echo "📥 Pulling latest code from GitHub..."
if git fetch origin "$BRANCH" && git reset --hard "origin/$BRANCH"; then
    echo "✅ Code updated successfully."
else
    msg="❌ Git pull failed during deploy"
    echo "$msg"
    php -r "mail('$ADMIN_EMAIL','❌ CRM Deploy Failed','$msg');"
    exit 1
fi

# --- Optional Laravel maintenance steps ---
echo "⚙️ Running Laravel optimizations..."
php artisan migrate --force
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# --- Deployment completed ---
msg="✅ CRM Deployment completed successfully at $(date)"
echo "$msg"
php -r "mail('$ADMIN_EMAIL','✅ CRM Deploy Successful','$msg');"
