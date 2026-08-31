#!/usr/bin/env bash
#
# SessionStart hook for Claude Code on the web.
#
# Brings a freshly cloned container to the point where the CRM's linter
# (./vendor/bin/pint) and test suite (./vendor/bin/phpunit) actually run:
# PHP + JS dependencies, a .env with an app key, writable storage, and a local
# MariaDB server, because the Feature suite uses RefreshDatabase against MySQL
# (phpunit.xml -> DB_CONNECTION=mysql, DB_DATABASE=cms_testing) and several
# migrations use raw MySQL DDL that SQLite cannot run.
#
# Safe to run repeatedly: every step is idempotent.

set -euo pipefail

# Only set up remote (Claude Code on the web) containers; a local machine is
# assumed to be configured already.
if [ "${CLAUDE_CODE_REMOTE:-}" != "true" ]; then
    exit 0
fi

cd "${CLAUDE_PROJECT_DIR:-$(dirname "$(dirname "$(dirname "$(readlink -f "$0")")")")}"

export COMPOSER_ALLOW_SUPERUSER=1
export DEBIAN_FRONTEND=noninteractive

log() { echo "[session-start] $*"; }
warn() { echo "[session-start] WARNING: $*" >&2; }

# --------------------------------------------------------------------------
# 1. Environment file
# --------------------------------------------------------------------------
if [ ! -f .env ]; then
    log "creating .env from .env.example"
    cp .env.example .env
fi

# --------------------------------------------------------------------------
# 2. PHP dependencies
# --------------------------------------------------------------------------
log "installing composer dependencies"
composer install --no-interaction --prefer-dist --no-progress

# --------------------------------------------------------------------------
# 3. Application key
# --------------------------------------------------------------------------
if ! grep -qE '^APP_KEY=.+' .env; then
    log "generating application key"
    php artisan key:generate --force --no-interaction
fi

# --------------------------------------------------------------------------
# 4. Frontend dependencies (Vite / Tailwind / Alpine)
# --------------------------------------------------------------------------
log "installing npm dependencies"
npm install --no-audit --no-fund --loglevel=error

# --------------------------------------------------------------------------
# 5. Writable runtime directories
# --------------------------------------------------------------------------
mkdir -p \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/testing \
    storage/framework/views \
    storage/logs \
    bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache

# public/storage → storage/app/public. Uploaded files (logos, project photos)
# are served through it, so without the link they 404 in a dev session.
if [ ! -e public/storage ]; then
    php artisan storage:link >/dev/null 2>&1 || warn "could not create the public/storage link"
fi

# --------------------------------------------------------------------------
# 6. Local MariaDB (MySQL) server for the Feature test suite
# --------------------------------------------------------------------------
DB_USER="$(grep -E '^DB_USERNAME=' .env | head -1 | cut -d= -f2- | tr -d '"' || true)"
DB_USER="${DB_USER:-root}"
APP_DB="$(grep -E '^DB_DATABASE=' .env | head -1 | cut -d= -f2- | tr -d '"' || true)"
APP_DB="${APP_DB:-laravel}"
TEST_DB="$(grep -oP '(?<=name="DB_DATABASE" value=")[^"]+' phpunit.xml | head -1 || true)"
TEST_DB="${TEST_DB:-cms_testing}"

setup_database() {
    if ! command -v mariadbd >/dev/null 2>&1 && ! command -v mysqld >/dev/null 2>&1; then
        log "installing mariadb-server"
        apt-get update -qq
        apt-get install -y -qq mariadb-server
    fi

    mkdir -p /var/run/mysqld
    chown mysql:mysql /var/run/mysqld

    if ! mysqladmin ping >/dev/null 2>&1; then
        log "starting mariadb"
        nohup /usr/sbin/mariadbd --user=mysql --bind-address=127.0.0.1 --port=3306 \
            >/var/log/mariadb-session-start.log 2>&1 &

        for _ in $(seq 1 60); do
            mysqladmin ping >/dev/null 2>&1 && break
            sleep 1
        done
        mysqladmin ping >/dev/null 2>&1 || return 1
    fi

    # The app connects over TCP as DB_USERNAME with no password (see .env);
    # root authenticates through the unix socket by default, so allow both.
    mysql -u root <<SQL
CREATE DATABASE IF NOT EXISTS \`${APP_DB}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS \`${TEST_DB}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER USER 'root'@'localhost' IDENTIFIED VIA unix_socket OR mysql_native_password USING PASSWORD('');
CREATE USER IF NOT EXISTS 'root'@'127.0.0.1' IDENTIFIED BY '';
GRANT ALL PRIVILEGES ON *.* TO 'root'@'127.0.0.1' WITH GRANT OPTION;
FLUSH PRIVILEGES;
SQL

    if [ "${DB_USER}" != "root" ]; then
        mysql -u root <<SQL
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '';
CREATE USER IF NOT EXISTS '${DB_USER}'@'127.0.0.1' IDENTIFIED BY '';
GRANT ALL PRIVILEGES ON *.* TO '${DB_USER}'@'localhost' WITH GRANT OPTION;
GRANT ALL PRIVILEGES ON *.* TO '${DB_USER}'@'127.0.0.1' WITH GRANT OPTION;
FLUSH PRIVILEGES;
SQL
    fi
}

if setup_database; then
    log "mariadb ready (databases: ${APP_DB}, ${TEST_DB})"
    log "running migrations on ${APP_DB}"
    php artisan migrate --force --no-interaction >/dev/null || warn "migrations failed"
else
    warn "could not start mariadb — the Feature test suite will not run (Unit suite uses SQLite and is unaffected)"
fi

# --------------------------------------------------------------------------
# 6b. Report missing PHP extensions the app uses
# --------------------------------------------------------------------------
if ! php -m | grep -qi '^bcmath$'; then
    log "no bcmath extension here — falling back to the phpseclib/bcmath_compat polyfill"
fi

# --------------------------------------------------------------------------
# 7. Clear stale caches (compiled views/config are not in git but may be cached)
# --------------------------------------------------------------------------
php artisan config:clear >/dev/null 2>&1 || true
php artisan view:clear >/dev/null 2>&1 || true

# --------------------------------------------------------------------------
# 8. Session environment
# --------------------------------------------------------------------------
if [ -n "${CLAUDE_ENV_FILE:-}" ]; then
    echo 'export COMPOSER_ALLOW_SUPERUSER=1' >> "$CLAUDE_ENV_FILE"
fi

log "done — lint: ./vendor/bin/pint | test: ./vendor/bin/phpunit"
