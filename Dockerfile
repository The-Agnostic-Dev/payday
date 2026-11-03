FROM php:8.3-cli-bookworm

# pdo_sqlite ships with the official image; composer needs git and unzip.
RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --no-interaction --prefer-dist

COPY . .
RUN composer dump-autoload --optimize --no-dev \
    && cp .env.example .env \
    && php artisan key:generate --force \
    && chmod +x docker-entrypoint.sh

ENV PHP_CLI_SERVER_WORKERS=4
EXPOSE 5119

ENTRYPOINT ["./docker-entrypoint.sh"]
