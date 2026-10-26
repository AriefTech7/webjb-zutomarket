#!/bin/sh
set -eu

umask 022
mkdir -p storage/app/public storage/app/private storage/framework/cache/data \
    storage/framework/sessions storage/framework/views storage/logs bootstrap/cache

if [ "$1" = "php-fpm" ]; then
    : "${APP_KEY:?Set a persistent APP_KEY before starting the application}"
    php artisan config:cache --no-interaction
    php artisan route:cache --no-interaction
fi

exec docker-php-entrypoint "$@"
