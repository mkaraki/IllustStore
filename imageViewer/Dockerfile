FROM composer AS require-server

WORKDIR /app

COPY composer.* /app/

RUN composer install --no-dev --ignore-platform-reqs

FROM php:8.3-apache

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
ENV IPE_GD_WITHOUTAVIF=1
RUN install-php-extensions gd mysqli
RUN pecl install apcu \
    && docker-php-ext-install opcache \
    && docker-php-ext-enable apcu

RUN <<EOF cat >> $PHP_INI_DIR/conf.d/apcu.ini
[apcu]
apc.enable=1
apc.enable_cli=1
EOF

RUN a2enmod rewrite

COPY --from=require-server /app/vendor /var/www/html/vendor

RUN export PHP_EXTENSION_DIR=$(php-config --extension-dir)
RUN export PHP_INI_DIR=$(php-config --ini-dir)

COPY --from=ghcr.io/mkaraki/microsoft_lepton_jpeg_rust_php:latest /pack/ms-lepton.so $PHP_EXTENSION_DIR/ms-lepton.so
COPY --from=ghcr.io/mkaraki/microsoft_lepton_jpeg_rust_php:latest /pack/20-ms-lepton.ini $PHP_INI_DIR/conf.d/20-ms-lepton.ini

RUN mkdir /var/www/html/cache

COPY .htaccess /var/www/html/
COPY assets/* /var/www/html/assets/
COPY *.php /var/www/html/
COPY views/*.php /var/www/html/views/
