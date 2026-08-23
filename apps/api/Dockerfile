FROM php:8.3-cli-alpine

# libzip-dev + the zip extension: spatie/laravel-backup (composer.json)
# requires ext-zip to build its backup archives — without it, `composer
# install` fails outright (verified by actually building this image; it
# never had succeeded before this fix).
RUN apk add --no-cache postgresql-dev libzip-dev $PHPIZE_DEPS \
    && docker-php-ext-install pdo_pgsql pgsql zip \
    && apk del $PHPIZE_DEPS

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Build context is the *monorepo root* (see dockerContext in render.yaml /
# `docker build -f apps/api/Dockerfile .` from the repo root), not
# apps/api — WilayaSeeder/CommuneSeeder read packages/shared/locations/*.json
# via base_path('../../packages/shared/...'), a path outside apps/api
# entirely. Keeping /apps/api as the container's WORKDIR (not flattening to
# /app) is what makes that relative "../../" still resolve correctly to
# /packages/shared inside the image, exactly like it does from apps/api/ in
# the real monorepo checkout.
WORKDIR /apps/api

COPY apps/api/composer.json apps/api/composer.lock* ./
RUN composer install --no-scripts --no-interaction --prefer-dist --no-autoloader

COPY apps/api/. .
COPY packages/shared /packages/shared

RUN composer dump-autoload --optimize

EXPOSE 8000

# Render (and most PaaS hosts) assign the actual listen port via $PORT at
# runtime — a hardcoded --port=8000 works for docker-compose's local infra
# (which maps 8000 explicitly) but silently fails health checks on a host
# that expects the container to honor $PORT. Falls back to 8000 so
# docker-compose's local usage is unaffected.
CMD ["sh", "-c", "php artisan serve --host=0.0.0.0 --port=${PORT:-8000}"]
