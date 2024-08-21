FROM composer AS require-server

WORKDIR /app

COPY composer.* /app/

RUN composer install --no-dev --ignore-platform-reqs

FROM php:8.3-apache

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
ENV IPE_GD_WITHOUTAVIF=1
RUN install-php-extensions imagick mysqli
RUN docker-php-ext-install opcache

RUN <<EOF cat >> $PHP_INI_DIR/conf.d/more-upload-size.ini
upload_max_filesize = 150M
post_max_size = 150M
EOF

RUN a2enmod rewrite

COPY --from=require-server /app/vendor /var/www/html/vendor

RUN mkdir /var/www/html/cache

COPY .htaccess /var/www/html/
COPY ImageHash/*.php /var/www/html/ImageHash/
COPY assets/* /var/www/html/assets/
COPY *.php /var/www/html/
COPY views/*.php /var/www/html/views/
COPY routes/*.php /var/www/html/routes/
