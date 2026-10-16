FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts

FROM node:22-alpine AS assets

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY resources resources
COPY public public
COPY vite.config.js ./
RUN npm run build

FROM php:8.5-fpm-alpine AS application

WORKDIR /var/www/html

RUN apk add --no-cache icu-libs libzip libxml2 oniguruma \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS icu-dev libzip-dev libxml2-dev oniguruma-dev \
    && docker-php-ext-install -j"$(nproc)" bcmath intl mbstring pdo_mysql zip \
    && apk del .build-deps

COPY . .
COPY --from=vendor /app/vendor vendor
COPY --from=assets /app/public/build public/build
COPY docker/entrypoint.sh /usr/local/bin/start-app

RUN chmod +x /usr/local/bin/start-app \
    && mkdir -p storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && ln -sfn ../storage/app/public public/storage \
    && chown -R www-data:www-data storage bootstrap/cache

ENTRYPOINT ["start-app"]
CMD ["php-fpm"]

FROM nginx:1.27-alpine AS web

COPY docker/nginx.conf /etc/nginx/conf.d/default.conf
COPY --from=application /var/www/html/public /var/www/html/public
