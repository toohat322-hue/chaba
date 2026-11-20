# Production image for apps/api, also used by the queue worker and the
# scheduler (same image, different command — see deploy/docker-compose.yml).
#
# FrankenPHP serves Laravel's public/ directly with a real multi-threaded
# server. The previous image ran `php artisan serve`, PHP's built-in dev
# server, which handles one request at a time.
FROM dunglas/frankenphp:1-php8.3-alpine

# - pdo_pgsql/pgsql: Postgres. zip: spatie/laravel-backup builds zip archives.
# - opcache: compiled-code cache, the biggest single speedup for PHP in prod.
# - pcntl: lets `queue:work` enforce --timeout and stop gracefully.
# - postgresql16-client: pg_dump, which the nightly `backup:run` needs to dump
#   the database. Must match the server's major version (postgres:16).
RUN install-php-extensions pdo_pgsql pgsql zip opcache pcntl \
    && apk add --no-cache postgresql16-client

COPY apps/api/docker/php.ini $PHP_INI_DIR/conf.d/zz-chaba.ini

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Build context is the *monorepo root* (`docker build -f apps/api/Dockerfile .`),
# not apps/api: WilayaSeeder/CommuneSeeder read packages/shared/locations/*.json
# via base_path('../../packages/shared/...'). Keeping WORKDIR at /apps/api makes
# that relative path resolve to /packages/shared inside the image, exactly like
# it does in the real monorepo checkout.
WORKDIR /apps/api

COPY apps/api/composer.json apps/api/composer.lock* ./
RUN composer install --no-dev --no-scripts --no-interaction --prefer-dist --no-autoloader

COPY apps/api/. .
COPY packages/shared /packages/shared

RUN composer dump-autoload --optimize --no-dev

EXPOSE 8000

# Honors $PORT when a host assigns one; 8000 otherwise.
CMD ["sh", "-c", "exec frankenphp php-server --root public/ --listen :${PORT:-8000}"]
