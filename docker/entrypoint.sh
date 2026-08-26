#!/bin/sh
set -e

echo "==> Starting entrypoint..."

# Cache configuration for production
echo "==> Caching configuration..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Run migrations
echo "==> Running migrations..."
php artisan migrate --force --no-interaction

# Create storage symlink
php artisan storage:link --force --no-interaction 2>/dev/null || true

# Ensure permissions
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

echo "==> Entrypoint complete. Starting services..."

exec "$@"
