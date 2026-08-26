#!/bin/sh
set -e

echo "==> Starting entrypoint..."

if [ "$APP_ENV" = "production" ]; then
    echo "==> Caching configuration for production..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    php artisan event:cache
else
    echo "==> Clearing caches for local development..."
    php artisan config:clear || true
    php artisan route:clear || true
    php artisan view:clear || true
fi

# Run migrations
echo "==> Running migrations..."
php artisan migrate --force --no-interaction

# Create storage symlink
php artisan storage:link --force --no-interaction 2>/dev/null || true

# Ensure permissions
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

echo "==> Entrypoint complete. Starting services..."

exec "$@"
