#!/usr/bin/env sh
set -e

# `php artisan serve`'s dev-server subprocess doesn't reliably inherit
# APP_KEY from the container's environment even when it's set (Laravel's
# ServeCommand re-parses .env for the child process), so write it into
# .env directly rather than trusting env-var propagation.
if [ -n "$APP_KEY" ]; then
    sed -i "s|^APP_KEY=.*|APP_KEY=${APP_KEY}|" .env
else
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
