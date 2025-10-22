FROM composer AS require-server

WORKDIR /app

COPY . /app/

RUN composer install --no-dev --ignore-platform-reqs

FROM node:22-slim AS npm

RUN apt-get update && apt-get -y install ca-certificates && \
    sed -i.bak -r 's@http://deb\.debian\.org/?@https://ftp.riken.jp/Linux/debian/@g' /etc/apt/sources.list.d/debian.sources && \
    apt-get update
RUN apt-get update && apt-get install php -y

ENV PNPM_HOME="/pnpm"
ENV PATH="$PNPM_HOME:$PATH"
ENV CI=true
RUN corepack enable

COPY --from=require-server /app /app
WORKDIR /app
RUN pnpm install --frozen-lockfile && pnpm run build

FROM php:8.4-apache-trixie

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
RUN apt-get update && apt-get -y install ca-certificates && \
    sed -i.bak -r 's@http://deb\.debian\.org/?@https://ftp.riken.jp/Linux/debian/@g' /etc/apt/sources.list.d/debian.sources && \
    apt-get update

ENV IPE_GD_WITHOUTAVIF=1
RUN --mount=type=bind,from=ghcr.io/mlocati/php-extension-installer:latest,source=/usr/bin/install-php-extensions,target=/usr/local/bin/install-php-extensions \
    install-php-extensions imagick mysqli excimer memcached pdo_mysql
RUN docker-php-ext-install opcache

RUN <<EOF cat >> $PHP_INI_DIR/conf.d/more-upload-size.ini
upload_max_filesize = 150M
post_max_size = 150M
EOF

RUN <<EOF cat > /etc/apache2/sites-available/000-default.conf
<VirtualHost *:80>
        ServerAdmin webmaster@localhost
        DocumentRoot /var/www/html/public
        ErrorLog /dev/stderr
        CustomLog /dev/stdout combined
        <Directory "/var/www/html/public">
                Options Indexes FollowSymLinks
                AllowOverride All
                Require all granted
        </Directory>
</VirtualHost>
EOF

RUN a2enmod rewrite

COPY --from=require-server /app/vendor /var/www/html/vendor

COPY . /var/www/html
COPY .env.example /var/www/html/.env

COPY --from=npm /app/public/build /var/www/html/public/build

# Note you should re-run `php artisan key:generate` for safety.
RUN chown www-data:www-data -R /var/www/html/.env && \
    php artisan key:generate
