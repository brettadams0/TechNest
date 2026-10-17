#!/bin/sh
# Bring the schema up to date and seed an empty database, then hand over to
# Apache. Safe to run on every start: both steps are idempotent.
set -e

php /var/www/technest/bin/technest db:setup

# The SQLite file (if used) was just created by root; Apache runs as www-data.
chown -R www-data:www-data /var/www/technest/var

exec "$@"
