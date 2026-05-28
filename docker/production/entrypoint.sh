#!/usr/bin/env sh
set -eu

cd /var/www/html

mkdir -p \
    bootstrap/cache \
    storage/app/private \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/testing \
    storage/framework/views \
    storage/logs

chown -R www-data:www-data storage bootstrap/cache

if [ "${RUN_LARAVEL_OPTIMIZE:-true}" = "true" ]; then
    php artisan package:discover --ansi --no-interaction
    php artisan storage:link --force --ansi --no-interaction
    php artisan config:cache --ansi --no-interaction
    php artisan route:cache --ansi --no-interaction
    php artisan view:cache --ansi --no-interaction
fi

exec "$@"
