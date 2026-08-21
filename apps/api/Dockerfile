FROM php:8.3-cli-alpine

RUN apk add --no-cache postgresql-dev $PHPIZE_DEPS \
    && docker-php-ext-install pdo_pgsql pgsql \
    && apk del $PHPIZE_DEPS

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock* ./
RUN composer install --no-scripts --no-interaction --prefer-dist --no-autoloader

COPY . .

RUN composer dump-autoload --optimize

EXPOSE 8000

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
