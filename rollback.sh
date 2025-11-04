#!/bin/bash
# =====================================================
# CRM Rollback Script for Hostinger Shared Hosting
# with PHP Mail Notifications
# Author: Adil & GPT-5
# =====================================================

WEBROOT="/home/u160855881/domains/solenenergyco.com/public_html/CRM/portal"
LOG_FILE="/home/u160855881/logs/crm_rollback.log"
ADMIN_EMAIL="aptechadil@gmail.com"

timestamp=$(date +"%Y%m%d-%H%M%S")
mkdir -p "$(dirname $LOG_FILE)"

cd "$WEBROOT" || {
    msg="❌ CRM Rollback FAILED: cannot cd to $WEBROOT"
    echo "$msg" | tee -a $LOG_FILE
    php -r "mail('$ADMIN_EMAIL','❌ CRM Rollback Failed','$msg');"
    exit 1
}

echo "[$(date)] ===== Starting CRM Rollback =====" | tee -a $LOG_FILE

git fetch --tags

# Find latest stable tag
last_tag=$(git tag --sort=-creatordate | grep "^stable-" | head -n 1)

if [ -z "$last_tag" ]; then
    msg="❌ CRM Rollback FAILED: No stable tag found."
    echo "$msg" | tee -a $LOG_FILE
    php -r "mail('$ADMIN_EMAIL','❌ CRM Rollback Failed','$msg');"
    exit 1
fi

echo "[$(date)] Rolling back to tag: $last_tag" | tee -a $LOG_FILE
git checkout $last_tag -f

msg="✅ CRM Rollback to $last_tag completed successfully at $(date)"
echo "$msg" | tee -a $LOG_FILE
php -r "mail('$ADMIN_EMAIL','✅ CRM Rollback Successful','$msg');"
