#!/bin/sh

echo "DEBUG DB_CONNECTION=$DB_CONNECTION"
echo "DEBUG DB_HOST=$DB_HOST"

php artisan migrate --force || echo "Migration failed, continuing..."
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true
php artisan storage:link --force 2>/dev/null || true

php artisan serve --host=0.0.0.0 --port="${PORT:-8000}"
