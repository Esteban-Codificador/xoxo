#!/bin/bash
# SessionStart hook for Claude Code on the web: leaves the container ready to
# run tests, linters and the app. Idempotent and non-interactive.
set -euo pipefail

if [ "${CLAUDE_CODE_REMOTE:-}" != "true" ]; then
    exit 0
fi

cd "${CLAUDE_PROJECT_DIR:-$(pwd)}"
export COMPOSER_ALLOW_SUPERUSER=1

log() { echo "[session-start] $*" >&2; }

# 1. Services: PostgreSQL 16 and Redis are installed but not running.
log "Starting PostgreSQL and Redis"
service postgresql start >/dev/null 2>&1 || true
redis-cli ping >/dev/null 2>&1 || redis-server --daemonize yes >/dev/null
for _ in $(seq 1 30); do
    pg_isready -h 127.0.0.1 -q && break
    sleep 1
done

# 2. Development and test databases (credentials match .env.example / phpunit.xml).
log "Ensuring databases"
su postgres -c "psql -v ON_ERROR_STOP=1 -q" <<'SQL'
DO $$ BEGIN
    IF NOT EXISTS (SELECT FROM pg_roles WHERE rolname = 'ai_roadmap') THEN
        CREATE ROLE ai_roadmap LOGIN PASSWORD 'secret';
    END IF;
END $$;
SELECT 'CREATE DATABASE ai_roadmap OWNER ai_roadmap' WHERE NOT EXISTS (SELECT FROM pg_database WHERE datname = 'ai_roadmap')\gexec
SELECT 'CREATE DATABASE ai_roadmap_testing OWNER ai_roadmap' WHERE NOT EXISTS (SELECT FROM pg_database WHERE datname = 'ai_roadmap_testing')\gexec
SQL

# 3. PHP dependencies. The GitHub API is blocked here, so Composer clones
#    sources with git. Packages published only as GitHub zipballs (phpstan)
#    are rebuilt from their git repository into Composer's cache first.
log "Installing PHP dependencies"
composer config -g use-github-api false
composer config -g github-protocols https
cache_dir="$(composer config -g cache-files-dir)"

php -r '
    $lock = json_decode(file_get_contents("composer.lock"), true);
    foreach (array_merge($lock["packages"], $lock["packages-dev"] ?? []) as $package) {
        $url = $package["dist"]["url"] ?? "";
        if (empty($package["source"]) && preg_match("#^https://api\.github\.com/repos/([^/]+/[^/]+)/zipball/([0-9a-f]+)$#", $url, $m)) {
            echo $package["name"], " ", $m[1], " ", $m[2], " ", sha1($url), PHP_EOL;
        }
    }
' | while read -r name repository reference key; do
    target="${cache_dir}/${name}/${key}.zip"
    [ -f "$target" ] && continue
    log "Rebuilding ${name} from git"
    workdir="$(mktemp -d)"
    git -C "$workdir" init -q
    git -C "$workdir" fetch -q --depth 1 "https://github.com/${repository}.git" "$reference"
    mkdir -p "$(dirname "$target")"
    git -C "$workdir" archive --format=zip --prefix="${repository//\//-}-${reference:0:7}/" -o "$target" FETCH_HEAD
    rm -rf "$workdir"
done

composer install --no-interaction --prefer-source --no-progress 2>&1 | grep -v "Ambiguous class resolution" >&2 || true
test -f vendor/autoload.php

# 4. JavaScript dependencies.
log "Installing JS dependencies"
npm install --no-audit --no-fund >&2

# 5. Environment file (mail goes to the log: there is no SMTP server here).
if [ ! -f .env ]; then
    log "Creating .env"
    cp .env.example .env
    sed -i 's/^MAIL_MAILER=smtp$/MAIL_MAILER=log/' .env
    php artisan key:generate --ansi --no-interaction >&2
fi

# 6. Schema, generated routes/types and the Vite manifest that feature tests need.
log "Migrating and building assets"
php artisan migrate --force --no-interaction >&2
npm run build >&2

log "Ready"
