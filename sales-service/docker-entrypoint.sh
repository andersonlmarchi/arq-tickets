#!/bin/sh
set -e

if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "base64:" ]; then
  php artisan key:generate --force --no-interaction
fi

echo "Aguardando PostgreSQL (vendas)..."
until pg_isready -h "${DB_HOST:-postgres-sales}" -U "${DB_USERNAME:-sales}" -d "${DB_DATABASE:-sales_db}" >/dev/null 2>&1; do
  sleep 1
done
echo "PostgreSQL disponivel."

php artisan migrate --force --no-interaction

exec php artisan serve --host=0.0.0.0 --port=8080
