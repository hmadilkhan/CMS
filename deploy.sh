#!/bin/bash
# Simple Deploy Script

WEBROOT="/home/u160855881/domains/solenenergyco.com/public_html/CRM/portal"
BRANCH="main"

cd "$WEBROOT" || { echo "❌ Invalid webroot"; exit 1; }

echo "🚀 Deploying latest code..."
git fetch origin "$BRANCH"
git pull origin "$BRANCH"

echo "✅ Code updated successfully."
