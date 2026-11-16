FROM php:8.3-cli AS base

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip \
    && docker-php-ext-install pdo_mysql \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

FROM base AS dev

CMD ["php", "-S", "0.0.0.0:8080", "-t", "public"]

FROM base AS prod

ENV APP_ENV=production
ENV PHP_CLI_SERVER_WORKERS=4
RUN echo "expose_php = Off" > "$PHP_INI_DIR/conf.d/security.ini"

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --no-autoloader

COPY . .
RUN composer dump-autoload --no-dev --optimize

CMD ["sh", "-c", "exec php -S 0.0.0.0:${PORT:-8080} -t public"]