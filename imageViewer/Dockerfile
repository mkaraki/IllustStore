# syntax=docker/dockerfile:1.7-labs
FROM composer AS require-server

WORKDIR /app

COPY --exclude=vendor --exclude=node_modules . /app/

RUN composer install --ignore-platform-reqs

FROM node:22-slim AS npm

RUN apt-get update && apt-get -y install ca-certificates && \
    sed -i.bak -r 's@http://deb\.debian\.org/?@https://ftp.riken.jp/Linux/debian/@g' /etc/apt/sources.list.d/debian.sources && \
    apt-get update
RUN apt-get update && apt-get install php -y

ENV PNPM_HOME="/pnpm"
ENV PATH="$PNPM_HOME:$PATH"
ENV CI=true
RUN corepack enable

COPY --from=require-server --exclude=public/build /app /app
WORKDIR /app
RUN pnpm install --frozen-lockfile && pnpm run build

FROM php:8.4-fpm-trixie

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
RUN apt-get update && apt-get -y install ca-certificates && \
    sed -i.bak -r 's@http://deb\.debian\.org/?@https://ftp.riken.jp/Linux/debian/@g' /etc/apt/sources.list.d/debian.sources && \
    apt-get update

ENV IPE_GD_WITHOUTAVIF=1
RUN --mount=type=bind,from=ghcr.io/mlocati/php-extension-installer:latest,source=/usr/bin/install-php-extensions,target=/usr/local/bin/install-php-extensions \
    install-php-extensions imagick mysqli excimer memcached pdo_mysql
RUN docker-php-ext-install opcache
RUN <<EOF cat >> $PHP_INI_DIR/conf.d/opcache-config.ini
[opcache]
opcache.enable=1
opcache.enable_cli=1
EOF

RUN <<EOF cat >> $PHP_INI_DIR/conf.d/more-upload-size.ini
upload_max_filesize = 150M
post_max_size = 150M
EOF

WORKDIR /app/
COPY --exclude=vendor --exclude=node_modules --exclude=public/build . /app/
COPY .env.example /app/.env

COPY --from=require-server /app/vendor /app/vendor
COPY --from=npm /app/public/build /app/public/build

# Note you should re-run `php artisan key:generate` for safety.
RUN chown www-data:www-data -R /app/.env /app/storage && \
    php artisan config:clear &&\
    php artisan cache:clear && \
    php artisan view:clear && \
    php artisan route:clear && \
    php artisan optimize:clear && \
    php artisan config:cache && \
    php artisan route:cache && \
    php artisan view:cache && \
    php artisan key:generate
