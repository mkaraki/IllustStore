#!/bin/sh
set -e

php artisan optimize

frankenphp run
