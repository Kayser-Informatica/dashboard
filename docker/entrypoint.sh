#!/bin/sh
set -e

echo "==> Starting entrypoint..."

# Copy .env from environment if not present
if [ ! -f /var/www/html/.env ]; then
    echo "==> .env not found, creating from environment variables..."
    env | grep -E '^(APP_|DB_|REDIS_|MAIL_|CACHE_|QUEUE_|SESSION_|LOG_|BROADCAST_|FILESYSTEM_|BCRYPT_|VITE_|MONITORING_|AWS_|MEMCACHED_)' | while read -r line; do
        echo "$line"
    done > /var/www/html/.env
fi

# Generate app key if not set
if [ -z "$APP_KEY" ]; then
    echo "==> Generating application key..."
    php artisan key:generate --force --no-interaction
fi

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
