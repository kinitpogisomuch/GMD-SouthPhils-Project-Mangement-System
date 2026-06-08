#!/bin/sh

php artisan config:clear
php artisan cache:clear 2>/dev/null || true
php artisan migrate --force || echo "Migration failed, continuing..."
php artisan db:seed --class=UserSeeder --force 2>/dev/null || true
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link --force 2>/dev/null || true

php artisan serve --host=0.0.0.0 --port="${PORT:-8000}"
