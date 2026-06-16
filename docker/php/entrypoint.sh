#!/bin/sh
set -e

# Garante permissões corretas toda vez que o container sobe
# (necessário porque o volume é montado depois do build)
if [ -d "/var/www/api/storage" ]; then
    chmod -R 775 /var/www/api/storage
    chmod -R 775 /var/www/api/bootstrap/cache
fi

exec "$@"