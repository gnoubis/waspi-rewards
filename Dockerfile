# Single-container image for a demo/review deployment (see README
# "Deployment"). Builds the Vue/SCSS assets at image build time, then
# serves the whole app (API + SPA) with `php artisan serve`. This is not
# a production-grade PHP-FPM/Nginx setup - it's the simplest thing that
# reliably deploys this task on a free host (Railway, Render, Fly.io) in
# a single Dockerfile, which is what the brief actually needs.

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

RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress \
    && cp .env.example .env \
    && chmod +x docker/entrypoint.sh

EXPOSE 8080
CMD ["docker/entrypoint.sh"]
