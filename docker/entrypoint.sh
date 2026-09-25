#!/bin/bash
set -e

: "${PORT:=80}"

sed -ri "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s/:80>/:${PORT}>/" /etc/apache2/sites-available/000-default.conf

if [ -n "$DB_DIR" ]; then
    mkdir -p "$DB_DIR"
    chown -R www-data:www-data "$DB_DIR"
fi

a2dismod mpm_event mpm_worker 2>/dev/null || true
rm -f /etc/apache2/mods-enabled/mpm_event.* /etc/apache2/mods-enabled/mpm_worker.* 2>/dev/null || true
a2enmod mpm_prefork 2>/dev/null || true

exec "$@"