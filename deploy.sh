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

cd "$WEBROOT" || { echo "❌ Invalid webroot"; exit 1; }

# Triggered from the admin deploy page this runs as the web user, which often
# has no HOME — and composer and npm both refuse to start without one.
export HOME="${HOME:-$WEBROOT/storage/app/.deploy-home}"
export COMPOSER_HOME="${COMPOSER_HOME:-$HOME/.composer}"
mkdir -p "$COMPOSER_HOME"

# Shared hosting rarely puts the binaries on PATH under the names you expect.
# Hostinger ships Composer 2 as `composer2` (a plain `composer` there can still
# be v1, which cannot read a v2 composer.lock), and `php` is often an older
# build than this app supports. Pick deliberately; PHP_BIN / COMPOSER_BIN still
# win when they are set.
find_composer() {
    if [ -n "${COMPOSER_BIN:-}" ]; then
        command -v "$COMPOSER_BIN" >/dev/null 2>&1 && echo "$COMPOSER_BIN"
        return
    fi

    for candidate in composer2 composer composer.phar; do
        if command -v "$candidate" >/dev/null 2>&1; then
            echo "$candidate"
            return
        fi
    done
}

find_php() {
    if [ -n "${PHP_BIN:-}" ]; then
        command -v "$PHP_BIN" >/dev/null 2>&1 && echo "$PHP_BIN"
        return
    fi

    for candidate in php php8.3 php8.2 php8.1; do
        command -v "$candidate" >/dev/null 2>&1 || continue

        if "$candidate" -r 'exit(PHP_VERSION_ID >= 80100 ? 0 : 1);' >/dev/null 2>&1; then
            echo "$candidate"
            return
        fi
    done
}

COMPOSER_BIN="$(find_composer)"
PHP_BIN="$(find_php)"

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

FAILED=0

# --------------------------------------------------------------------------
# PHP dependencies
#
# --prefer-dist and a small parallel-HTTP budget matter on shared hosting:
# without a zip archive to unpack composer falls back to `git clone`, and
# cloning a repo the size of google/apiclient-services runs the account out of
# processes ("unable to create thread: Resource temporarily unavailable").
# --------------------------------------------------------------------------
if [ "$NEEDS_COMPOSER" -eq 1 ]; then
    if [ -n "$COMPOSER_BIN" ]; then
        echo "📦 Installing PHP dependencies with $COMPOSER_BIN..."

        if COMPOSER_MAX_PARALLEL_HTTP="${COMPOSER_MAX_PARALLEL_HTTP:-4}" \
            "$COMPOSER_BIN" install --no-interaction --no-progress --prefer-dist \
                --no-dev --optimize-autoloader; then
            :
        else
            FAILED=1
            echo "⚠️  composer install failed — the vendor directory is NOT up to date"
            echo "    composer : $("$COMPOSER_BIN" --version 2>&1 | head -1)"
            echo "    php      : ${PHP_BIN:-none} $([ -n "$PHP_BIN" ] && "$PHP_BIN" -r 'echo PHP_VERSION;' 2>/dev/null)"
            echo "    zip ext  : $([ -n "$PHP_BIN" ] && "$PHP_BIN" -r 'echo extension_loaded("zip") ? "yes" : "no";' 2>/dev/null)"
            echo "    unzip    : $(command -v unzip || echo 'not found')"
            echo "    (no zip extension and no unzip binary is what forces composer to clone from source)"
        fi
    else
        FAILED=1
        echo "⚠️  no composer found (tried composer2, composer, composer.phar; set COMPOSER_BIN) — run 'composer install --no-dev --optimize-autoloader' by hand"
    fi
fi

# --------------------------------------------------------------------------
# Frontend build — when the Vite inputs moved, or nothing has been built yet
# --------------------------------------------------------------------------
if [ "$NEEDS_ASSETS" -eq 1 ]; then
    if command -v npm >/dev/null 2>&1; then
        echo "🎨 Building frontend assets..."
        if npm install --no-audit --no-fund && npm run build; then
            :
        else
            FAILED=1
            echo "⚠️  asset build failed — the site will keep serving the previous build"
        fi
    else
        FAILED=1
        echo "⚠️  npm not found — run 'npm install && npm run build' by hand"
    fi
fi

# --------------------------------------------------------------------------
# Caches — compiled Blade views and cached config outlive a git pull, so the
# server keeps serving the old UI until they are cleared
# --------------------------------------------------------------------------
if [ -n "$PHP_BIN" ]; then
    echo "🧹 Clearing caches with $PHP_BIN..."
    "$PHP_BIN" artisan view:clear
    "$PHP_BIN" artisan cache:clear
    "$PHP_BIN" artisan config:clear
else
    FAILED=1
    echo "⚠️  no PHP 8.1+ binary found (set PHP_BIN) — run 'php artisan view:clear && php artisan cache:clear && php artisan config:clear' by hand"
fi

if [ "$FAILED" -ne 0 ]; then
    echo "❌ Deploy finished with errors — see the warnings above. The code was pulled, but a step did not complete."
    exit 1
fi

echo "✅ Deploy complete."
