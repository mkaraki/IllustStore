#!/bin/sh
set -e

php artisan optimize
php artisan config:cache

frankenphp run
