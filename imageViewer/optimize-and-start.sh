#!/bin/sh
set -e

pgp artisan optimize:clear

php artisan event:cache
php artisan route:cache
php artisan view:cache
php artisan config:cache

frankenphp run
