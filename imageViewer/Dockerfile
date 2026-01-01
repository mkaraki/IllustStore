FROM composer AS require-server

WORKDIR /app

COPY --exclude=vendor --exclude=node_modules --exclude=public/build --exclude=Caddyfile . /app/

RUN composer install --ignore-platform-reqs

# ---------------------------------------------------------------
FROM node:24-slim AS npm

RUN apt-get update && apt-get -y install ca-certificates && \
    sed -i.bak -r 's@http://deb\.debian\.org/?@https://ftp.riken.jp/Linux/debian/@g' /etc/apt/sources.list.d/debian.sources && \
    apt-get update
RUN apt-get update && apt-get install php -y

ENV PNPM_HOME="/pnpm"
ENV PATH="$PNPM_HOME:$PATH"
ENV CI=true
RUN corepack enable

COPY --from=require-server /app /app
COPY .env.example /app/.env
WORKDIR /app

RUN --mount=type=cache,id=pnpm,target=/pnpm/store \
    pnpm install --frozen-lockfile

RUN --mount=type=secret,id=SENTRY_ORG,env=SENTRY_ORG \
    --mount=type=secret,id=SENTRY_PROJECT,env=SENTRY_PROJECT \
    --mount=type=secret,id=SENTRY_AUTH_TOKEN,env=SENTRY_AUTH_TOKEN \
    --mount=type=secret,id=SENTRY_URL,env=SENTRY_URL \
    pnpm run build

# ---------------------------------------------------------------
FROM dunglas/frankenphp:php8.4.16-alpine

RUN install-php-extensions \
    pcntl \
    mysqli \
    pdo_mysql \
    excimer \
    memcached \
    opcache

ARG USER=www-data
RUN adduser -D ${USER}; \
    setcap -r /usr/local/bin/frankenphp; \
    chown -R ${USER}:${USER} /config/caddy /data/caddy
USER ${USER}

WORKDIR /var/www/html
COPY --from=npm --chown=${USER}:${USER} /app .
COPY --chown=${USER}:${USER} .env.example ./.env
COPY --chown=${USER}:${USER} Caddyfile .

RUN php artisan optimize:clear && \
    php artisan key:generate && \
    php artisan event:cache && \
    php artisan route:cache && \
    php artisan view:cache

CMD ["frankenphp", "run"]
