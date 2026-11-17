#!/bin/bash
set -e

# ==========================================================
# Preparación de permisos Laravel
# ==========================================================

mkdir -p /var/www/html/storage/logs
mkdir -p /var/www/html/storage/framework/cache
mkdir -p /var/www/html/storage/framework/sessions
mkdir -p /var/www/html/storage/framework/views
mkdir -p /var/www/html/bootstrap/cache

chown -R www-data:www-data \
    /var/www/html/storage \
    /var/www/html/bootstrap/cache

find /var/www/html/storage \
    -type d \
    -exec chmod 775 {} \;

find /var/www/html/storage \
    -type f \
    -exec chmod 664 {} \;

find /var/www/html/bootstrap/cache \
    -type d \
    -exec chmod 775 {} \;

find /var/www/html/bootstrap/cache \
    -type f \
    -exec chmod 664 {} \;


# ==========================================================
# APP_KEY
# ==========================================================

if [ -z "$APP_KEY" ]; then
    export APP_KEY=$(php artisan key:generate --show)

    echo "APP_KEY generada para esta ejecución."
fi

# ==========================================================
# Laravel Scheduler
# ==========================================================
#
# IMPORTANTE:
#
# El scheduler debe ejecutarse como www-data.
#
# Si se ejecuta como root puede crear archivos dentro de
# storage/ pertenecientes a root, por ejemplo laravel.log,
# impidiendo posteriormente que Apache/PHP pueda escribirlos.
#
# ==========================================================

su -s /bin/sh www-data -c \
    "cd /var/www/html && php artisan schedule:work" &

SCHEDULER_PID=$!


# ==========================================================
# Aplicación principal / Apache
# ==========================================================

"$@" &

APP_PID=$!


# ==========================================================
# Finalización limpia
# ==========================================================

terminate()
{
    kill -TERM \
        "$SCHEDULER_PID" \
        "$APP_PID" \
        2>/dev/null \
        || true
}

trap terminate TERM INT


set +e

wait -n \
    "$SCHEDULER_PID" \
    "$APP_PID"

STATUS=$?

set -e


terminate


wait "$SCHEDULER_PID" \
    2>/dev/null \
    || true

wait "$APP_PID" \
    2>/dev/null \
    || true


exit "$STATUS"