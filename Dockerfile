# syntax=docker/dockerfile:1
ARG PHP_IMAGE=php:8.5.12-fpm-bookworm
ARG NODE_IMAGE=node:24.22.1-bookworm-slim
ARG COMPOSER_IMAGE=composer:2.10.3
ARG NGINX_IMAGE=nginxinc/nginx-unprivileged:stable-alpine

FROM ${COMPOSER_IMAGE} AS composer

FROM ${PHP_IMAGE} AS php-base
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libcurl4-openssl-dev libfreetype6-dev libicu-dev libjpeg62-turbo-dev \
        libonig-dev libpng-dev libzip-dev unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" curl gd intl mbstring pcntl pdo_mysql zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*
RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY docker-php.ini /usr/local/etc/php/conf.d/zz-production.ini
COPY docker-fpm.conf /usr/local/etc/php-fpm.d/zz-app.conf
WORKDIR /var/www/html

FROM php-base AS dependencies
COPY --from=composer /usr/bin/composer /usr/local/bin/composer
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress --no-scripts --no-autoloader
COPY app ./app
COPY bootstrap ./bootstrap
COPY config ./config
COPY database ./database
COPY routes ./routes
COPY artisan ./
RUN mkdir -p bootstrap/cache storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs \
    && composer dump-autoload --no-dev --classmap-authoritative --no-scripts \
    && php artisan package:discover --no-interaction \
    && composer check-platform-reqs --no-dev

FROM ${NODE_IMAGE} AS assets
WORKDIR /app
COPY package.json package-lock.json .npmrc ./
RUN npm ci --ignore-scripts
COPY --from=dependencies /var/www/html/vendor/laravel/framework/src/Illuminate/Pagination/resources/views ./vendor/laravel/framework/src/Illuminate/Pagination/resources/views
COPY resources ./resources
COPY public ./public
COPY vite.config.js ./
RUN npm run build

FROM php-base AS app
COPY --from=dependencies /var/www/html /var/www/html
COPY --from=assets /app/public ./public
COPY resources/views ./resources/views
COPY --chmod=755 docker-entrypoint.sh /usr/local/bin/game-market-entrypoint
RUN mkdir -p storage/app/public storage/app/private storage/framework/cache/data \
        storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && ln -s /var/www/html/storage/app/public public/storage \
    && chown -R www-data:www-data storage bootstrap/cache
USER www-data
ENTRYPOINT ["game-market-entrypoint"]
CMD ["php-fpm", "-F"]

FROM ${NGINX_IMAGE} AS web
COPY nginx.conf /etc/nginx/conf.d/default.conf
COPY --from=app /var/www/html/public /var/www/html/public
EXPOSE 8080
