# syntax=docker/dockerfile:1.7

FROM node:22-bookworm-slim AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund
COPY resources ./resources
COPY public ./public
COPY vite.config.js ./
RUN npm run build

# Built on the same PHP version as the runtime stage: the `composer:2` image
# floats to the newest PHP, and phpspreadsheet caps at <8.5, so resolving there
# breaks whenever that image moves ahead of the runtime.
FROM php:8.3-cli-bookworm AS vendor

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

WORKDIR /app
COPY composer.json composer.lock ./
COPY app ./app
COPY bootstrap ./bootstrap
COPY config ./config
COPY database ./database
COPY public ./public
COPY resources ./resources
COPY routes ./routes
COPY artisan ./
# gd and zip are compiled into the runtime stage only. This stage installs from
# the lock file, so package versions are already pinned and skipping the two
# extension checks cannot change what gets downloaded.
#
# --no-scripts because post-autoload-dump boots Laravel to run package:discover,
# which needs storage/ that this stage never copies. The entrypoint runs
# package:discover at container start, where storage/ exists.
RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --no-progress \
    --no-scripts \
    --ignore-platform-req=ext-gd \
    --ignore-platform-req=ext-zip

FROM php:8.3-fpm-bookworm AS runtime

ARG APP_USER=www-data
WORKDIR /var/www/html

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        ca-certificates \
        curl \
        libfreetype6-dev \
        libicu-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        libzip-dev \
        nginx \
        supervisor \
        unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        gd \
        intl \
        opcache \
        pcntl \
        pdo_mysql \
        zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY docker/production/php.ini /usr/local/etc/php/conf.d/hezarrial.ini
COPY docker/production/nginx.conf /etc/nginx/nginx.conf
COPY docker/production/supervisord.conf /etc/supervisor/conf.d/hezarrial.conf
COPY docker/production/entrypoint.sh /usr/local/bin/hezarrial-entrypoint

COPY --from=vendor --chown=${APP_USER}:${APP_USER} /app /var/www/html
COPY --from=assets --chown=${APP_USER}:${APP_USER} /app/public/build /var/www/html/public/build

RUN mkdir -p \
        /var/www/html/bootstrap/cache \
        /var/www/html/storage/app/private \
        /var/www/html/storage/app/public \
        /var/www/html/storage/framework/cache/data \
        /var/www/html/storage/framework/sessions \
        /var/www/html/storage/framework/testing \
        /var/www/html/storage/framework/views \
        /var/www/html/storage/logs \
    && chown -R ${APP_USER}:${APP_USER} /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod +x /usr/local/bin/hezarrial-entrypoint

EXPOSE 8080

HEALTHCHECK --interval=30s --timeout=5s --start-period=30s --retries=3 \
    CMD curl -fsS http://127.0.0.1:8080/up || exit 1

ENTRYPOINT ["hezarrial-entrypoint"]
CMD ["supervisord", "-c", "/etc/supervisor/conf.d/hezarrial.conf"]
