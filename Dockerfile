# Single-container image (see README "Deployment"). Builds the Vue/SCSS
# assets at image build time, then serves the whole app (API + SPA) with
# `php artisan serve`. Not a production-grade PHP-FPM/Nginx setup - it's
# the simplest thing that reliably deploys on a free host (Railway,
# Render, Fly.io) from a single Dockerfile.

FROM node:20-slim AS assets
WORKDIR /app
COPY package.json package-lock.json* ./
RUN npm install
COPY resources resources
COPY vite.config.js ./
RUN npm run build

FROM php:8.2-cli
RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip libsqlite3-dev libzip-dev \
    && docker-php-ext-install pdo pdo_sqlite zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .
COPY --from=assets /app/public/build ./public/build

# Not --no-dev: the seeder uses fakerphp/faker (a dev dependency) to
# generate the demo dataset, and this image's whole point is running
# that seeder for a reviewable demo.
RUN composer install --optimize-autoloader --no-interaction --no-progress \
    && cp .env.example .env \
    && chmod +x docker/entrypoint.sh

EXPOSE 8080
CMD ["docker/entrypoint.sh"]
