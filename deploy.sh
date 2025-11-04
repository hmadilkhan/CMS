#!/bin/bash
# =====================================================
# Safe Deploy Script for Hostinger Shared Hosting
# with PHP Mail Notifications
# Author: Adil & GPT-5
# =====================================================

# --- SETTINGS ---
WEBROOT="/home/u160855881/domains/solenenergyco.com/public_html/CRM/portal"
BACKUP_DIR="/home/u160855881/backups/crm"
LOG_FILE="/home/u160855881/logs/crm_deploy.log"

DB_NAME="u160855881_cms"
DB_USER="u160855881_cms"
DB_PASS="Aadi@149012"

BRANCH="main"
KEEP_DAYS=7
ADMIN_EMAIL="aptechadil@gmail.com"

timestamp=$(date +"%Y%m%d-%H%M%S")
backup_subdir="$BACKUP_DIR/$timestamp"
mkdir -p "$backup_subdir"
mkdir -p "$(dirname $LOG_FILE)"

echo "[$(date)] ===== Starting CRM Deploy =====" | tee -a $LOG_FILE

cd "$WEBROOT" || {
    msg="❌ CRM Deploy FAILED: cannot cd to $WEBROOT"
    echo "$msg" | tee -a $LOG_FILE
    php -r "mail('$ADMIN_EMAIL','❌ CRM Deploy Failed','$msg');"
    exit 1
}

# --- Tag current version ---
tag_name="stable-$timestamp"
git tag -a "$tag_name" -m "Stable version before update on $timestamp"
git push origin --tags
echo "[$(date)] Created Git tag: $tag_name" | tee -a $LOG_FILE

# --- Backup Database ---
echo "[$(date)] Backing up database..." | tee -a $LOG_FILE
mysqldump -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" | gzip > "$backup_subdir/db-$timestamp.sql.gz"
if [ $? -eq 0 ]; then
    echo "[$(date)] Database backup done." | tee -a $LOG_FILE
else
    msg="❌ CRM Deploy failed during database backup."
    echo "$msg" | tee -a $LOG_FILE
    php -r "mail('$ADMIN_EMAIL','❌ CRM Deploy Failed','$msg');"
    exit 1
fi

# --- Backup Source Code ---
echo "[$(date)] Backing up source code..." | tee -a $LOG_FILE
tar -czf "$backup_subdir/crm-$timestamp.tar.gz" -C "$WEBROOT" .
echo "[$(date)] Source code backup done." | tee -a $LOG_FILE

# --- Pull Latest Code ---
echo "[$(date)] Pulling latest code..." | tee -a $LOG_FILE
if git fetch origin $BRANCH && git pull origin $BRANCH; then
    echo "[$(date)] Git pull successful." | tee -a $LOG_FILE
else
    msg="❌ CRM Deploy failed during git pull."
    echo "$msg" | tee -a $LOG_FILE
    php -r "mail('$ADMIN_EMAIL','❌ CRM Deploy Failed','$msg');"
    exit 1
fi

# --- Cleanup Old Backups ---
echo "[$(date)] Cleaning backups older than $KEEP_DAYS days..." | tee -a $LOG_FILE
find "$BACKUP_DIR" -type d -mtime +$KEEP_DAYS -exec rm -rf {} \;
echo "[$(date)] Old backups cleaned." | tee -a $LOG_FILE

# --- Success ---
msg="✅ CRM Deploy completed successfully on $(hostname) at $(date)"
echo "$msg" | tee -a $LOG_FILE
php -r "mail('$ADMIN_EMAIL','✅ CRM Deploy Successful','$msg');"
