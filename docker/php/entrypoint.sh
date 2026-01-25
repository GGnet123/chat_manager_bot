#!/bin/sh
set -e

# Wait for database to be ready (extra safety)
echo "Waiting for database..."
sleep 2

# Run migrations
echo "Running migrations..."
php artisan migrate --force

# Clear and cache config for production
if [ "$APP_ENV" = "production" ]; then
    echo "Caching configuration..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
fi

echo "Starting PHP-FPM..."
exec php-fpm
