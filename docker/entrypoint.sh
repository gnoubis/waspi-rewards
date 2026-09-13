#!/usr/bin/env sh
set -e

# Generate an APP_KEY on first boot if one isn't already baked in via env.
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "base64:" ]; then
    php artisan key:generate --force
fi

# SQLite lives on the container's ephemeral filesystem in this simple
# setup (see README "Deployment" / "Assumptions"): data resets on every
# redeploy, which is fine for a reviewable demo but not for production.
mkdir -p database
touch database/database.sqlite

php artisan migrate --force
php artisan db:seed --force

exec php artisan serve --host=0.0.0.0 --port="${PORT:-8080}"
