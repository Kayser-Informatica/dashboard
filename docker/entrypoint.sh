#!/bin/sh
set -e

echo "==> Starting entrypoint..."

# Prepare .env from environment variables with proper quoting
echo "==> Preparing environment configuration..."
php -r '
    $prefixes = [
        "APP_", "DB_", "REDIS_", "MAIL_", "CACHE_", "QUEUE_",
        "SESSION_", "LOG_", "BROADCAST_", "FILESYSTEM_", "BCRYPT_",
        "VITE_", "MONITORING_", "AWS_", "MEMCACHED_"
    ];

    $env = getenv();
    $existingEnv = [];

    if (file_exists("/var/www/html/.env")) {
        $lines = file("/var/www/html/.env", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (preg_match("/^([A-Za-z0-9_]+)=(.*)$/", trim($line), $m)) {
                $existingEnv[$m[1]] = trim($m[2], "\"'\t ");
            }
        }
    }

    $output = [];
    foreach ($env as $key => $val) {
        foreach ($prefixes as $prefix) {
            if (str_starts_with($key, $prefix)) {
                if ($val === "null") {
                    $val = "";
                }
                $escaped = str_replace(["\\", "\""], ["\\\\", "\\\""], $val);
                $output[$key] = "{$key}=\"{$escaped}\"";
                break;
            }
        }
    }

    if (empty($output["APP_KEY"]) && !empty($existingEnv["APP_KEY"])) {
        $escaped = str_replace(["\\", "\""], ["\\\\", "\\\""], $existingEnv["APP_KEY"]);
        $output["APP_KEY"] = "APP_KEY=\"{$escaped}\"";
    }

    file_put_contents("/var/www/html/.env", implode("\n", $output) . "\n");
'

# Generate app key if not set
if ! grep -q '^APP_KEY="base64:' /var/www/html/.env 2>/dev/null && [ -z "$APP_KEY" ]; then
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
