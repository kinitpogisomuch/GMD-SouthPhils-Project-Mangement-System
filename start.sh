#!/bin/sh

php artisan migrate --force || echo "Migration failed, continuing..."
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true
php artisan storage:link --force 2>/dev/null || true

php artisan serve --host=0.0.0.0 --port="${PORT:-8000}"
