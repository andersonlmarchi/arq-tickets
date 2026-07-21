#!/bin/sh
set -e

if [ -z "$APP_KEY" ]; then
  export APP_KEY="base64:1d2pasCVUUKQNjy3J3sUhtKffvSDYmxk1+bK3jLPFzk="
fi

echo "Aguardando PostgreSQL (vendas)..."
until pg_isready -h "${DB_HOST:-postgres-sales}" -U "${DB_USERNAME:-sales}" -d "${DB_DATABASE:-sales_db}" >/dev/null 2>&1; do
  sleep 1
done
echo "PostgreSQL disponivel."

php artisan migrate --force --no-interaction

exec php artisan serve --host=0.0.0.0 --port=8080
