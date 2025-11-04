#!/bin/bash
# Simple Rollback Script

WEBROOT="/home/u160855881/domains/solenenergyco.com/public_html/CRM/portal"

cd "$WEBROOT" || { echo "❌ Invalid webroot"; exit 1; }

echo "🔁 Rolling back to previous commit..."
git reset --hard HEAD~1

echo "✅ Rollback completed. Reverted one commit."
