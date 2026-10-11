# syntax=docker/dockerfile:1
FROM node:22-alpine@sha256:0a7108bf6c7bf5de370ffb1a3ed6be93d405b43ff159f681a8d18c0e2bc2e402 AS assets
WORKDIR /build
COPY package.json package-lock.json ./
RUN npm ci --ignore-scripts
COPY vite.config.js tailwind.config.js ./
COPY resources ./resources
RUN npm run build

FROM php:8.5-apache@sha256:974e3a920309308e1690ed607e6cc3061d6906f90cc8d30fe519daa48bccf101 AS platform
RUN apt-get update \
    && apt-get install -y --no-install-recommends libicu-dev libzip-dev libonig-dev libsqlite3-dev mariadb-client unzip \
    && docker-php-ext-install -j2 intl pdo_mysql pdo_sqlite zip mbstring pcntl \
    && a2enmod rewrite headers \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2@sha256:af98f42dfff7c68ba8d53c2164fd9fde1087b7d449514baa38c418b1f6bc4bac /usr/bin/composer /usr/local/bin/composer
COPY apache.staging.conf /etc/apache2/sites-available/000-default.conf
COPY php.staging.ini /usr/local/etc/php/conf.d/schoolshare.ini
WORKDIR /var/www/html

FROM platform AS testing
ENV APP_ENV=testing
WORKDIR /workspace

FROM platform AS production
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-scripts --optimize-autoloader
COPY . .
COPY --from=assets /build/public/build ./public/build
RUN mkdir -p storage/app/private storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && composer dump-autoload --no-dev --optimize --no-interaction \
    && php artisan storage:link --no-interaction \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R u=rwX,g=rX,o= storage bootstrap/cache
HEALTHCHECK --interval=30s --timeout=10s --start-period=30s --retries=3 \
    CMD php -r 'exit(@file_get_contents("http://127.0.0.1/ready") === false ? 1 : 0);'
