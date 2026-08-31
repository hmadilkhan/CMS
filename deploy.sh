#!/bin/bash
# Simple Deploy Script
#
# Pulls the latest code and then does the follow-up work that a Laravel deploy
# needs on this host. Everything after the pull is conditional on what actually
# changed, so a code-only deploy stays as quick as it always was.
#
# Overridable from the environment, for testing or a second checkout:
#   DEPLOY_WEBROOT, DEPLOY_BRANCH, PHP_BIN, COMPOSER_BIN

WEBROOT="${DEPLOY_WEBROOT:-/home/u160855881/domains/solenenergyco.com/public_html/CRM/portal}"
BRANCH="${DEPLOY_BRANCH:-main}"
PHP_BIN="${PHP_BIN:-php}"
COMPOSER_BIN="${COMPOSER_BIN:-composer}"

cd "$WEBROOT" || { echo "❌ Invalid webroot"; exit 1; }

echo "🚀 Deploying latest code..."
git fetch origin "$BRANCH" || { echo "❌ git fetch failed"; exit 1; }

BEFORE="$(git rev-parse HEAD)"
git pull origin "$BRANCH" || { echo "❌ git pull failed"; exit 1; }
AFTER="$(git rev-parse HEAD)"

CHANGED=""
if [ "$BEFORE" = "$AFTER" ]; then
    echo "ℹ️  No new commits."
else
    CHANGED="$(git diff --name-only "$BEFORE" "$AFTER")"
    echo "✅ Code updated successfully."
fi

# --------------------------------------------------------------------------
# Decide what still needs doing.
#
# The composer check looks at the installed state rather than at what this pull
# changed, because the two do not always line up: the release that first brought
# these steps into deploy.sh was itself pulled by the OLD script, so its
# composer install never ran. Comparing composer.lock against what is actually
# installed catches that, and any hand-made pull, on the next deploy.
# --------------------------------------------------------------------------
NEEDS_COMPOSER=0
if [ ! -f vendor/autoload.php ] || [ composer.lock -nt vendor/composer/installed.json ]; then
    NEEDS_COMPOSER=1
fi

NEEDS_ASSETS=0
if echo "$CHANGED" | grep -qE '^(resources/(js|css)/|package(-lock)?\.json$|vite\.config\.js$|tailwind\.config\.js$|postcss\.config\.js$)'; then
    NEEDS_ASSETS=1
fi
if [ ! -d public/build ]; then
    NEEDS_ASSETS=1
fi

if [ "$BEFORE" = "$AFTER" ] && [ "$NEEDS_COMPOSER" -eq 0 ] && [ "$NEEDS_ASSETS" -eq 0 ]; then
    echo "✅ Already up to date — nothing to rebuild."
    exit 0
fi

# --------------------------------------------------------------------------
# PHP dependencies
# --------------------------------------------------------------------------
if [ "$NEEDS_COMPOSER" -eq 1 ]; then
    if command -v "$COMPOSER_BIN" >/dev/null 2>&1; then
        echo "📦 Installing PHP dependencies..."
        "$COMPOSER_BIN" install --no-interaction --no-dev --optimize-autoloader \
            || echo "⚠️  composer install failed — run it by hand before trusting this deploy"
    else
        echo "⚠️  composer not found (set COMPOSER_BIN) — run 'composer install --no-dev --optimize-autoloader' by hand"
    fi
fi

# --------------------------------------------------------------------------
# Frontend build — when the Vite inputs moved, or nothing has been built yet
# --------------------------------------------------------------------------
if [ "$NEEDS_ASSETS" -eq 1 ]; then
    if command -v npm >/dev/null 2>&1; then
        echo "🎨 Building frontend assets..."
        npm install --no-audit --no-fund && npm run build \
            || echo "⚠️  asset build failed — the site will keep serving the previous build"
    else
        echo "⚠️  npm not found — run 'npm install && npm run build' by hand"
    fi
fi

# --------------------------------------------------------------------------
# Caches — compiled Blade views and cached config outlive a git pull, so the
# server keeps serving the old UI until they are cleared
# --------------------------------------------------------------------------
if command -v "$PHP_BIN" >/dev/null 2>&1; then
    echo "🧹 Clearing caches..."
    "$PHP_BIN" artisan view:clear
    "$PHP_BIN" artisan cache:clear
    "$PHP_BIN" artisan config:clear
else
    echo "⚠️  php not found (set PHP_BIN) — run 'php artisan view:clear && php artisan cache:clear && php artisan config:clear' by hand"
fi

echo "✅ Deploy complete."
