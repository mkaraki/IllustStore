FROM php:8.3 AS build-lepton

RUN apt-get update && \
    apt-get install -y \
    libclang-dev \
    curl \ 
    git && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/*

RUN curl https://sh.rustup.rs -sSf | sh -s -- -y

RUN git clone --depth 1 https://github.com/mkaraki/microsoft_lepton_jpeg_rust_php.git /ms-lepton
WORKDIR /ms-lepton

RUN . "$HOME/.cargo/env"; cargo build --release

FROM composer AS require-server

WORKDIR /app

COPY composer.* /app/

RUN composer install --no-dev --ignore-platform-reqs

FROM php:8.3-apache

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
ENV IPE_GD_WITHOUTAVIF=1
RUN install-php-extensions gd imagick mysqli
RUN pecl install apcu \
    && docker-php-ext-install opcache \
    && docker-php-ext-enable apcu

RUN <<EOF cat >> $PHP_INI_DIR/conf.d/apcu.ini
[apcu]
apc.enable=1
apc.enable_cli=1
EOF

RUN <<EOF cat >> $PHP_INI_DIR/conf.d/more-upload-size.ini
upload_max_filesize = 150M
post_max_size = 150M
EOF

RUN a2enmod rewrite

COPY --from=require-server /app/vendor /var/www/html/vendor

COPY --from=build-lepton /ms-lepton/target/release/libmicrosoft_lepton_jpeg_rust_php.so /ms-lepton.so
RUN mv /ms-lepton.so $(php-config --extension-dir)/ms-lepton.so
RUN echo extension=ms-lepton.so > $(php-config --ini-dir)/20-ms-lepton.ini

RUN mkdir /var/www/html/cache

COPY .htaccess /var/www/html/
COPY assets/* /var/www/html/assets/
COPY *.php /var/www/html/
COPY views/*.php /var/www/html/views/
