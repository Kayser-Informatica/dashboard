# ==============================================================================
# Stage 1: Build frontend assets (Vite + Tailwind)
# ==============================================================================
FROM node:22-alpine AS frontend

WORKDIR /app

COPY package.json package-lock.json .npmrc ./
RUN npm ci --ignore-scripts

COPY vite.config.js ./
COPY resources/ ./resources/

RUN npm run build

# ==============================================================================
# Stage 2: Install PHP dependencies
# ==============================================================================
FROM composer:2 AS composer

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-scripts \
    --no-autoloader \
    --prefer-dist

COPY . .
RUN composer dump-autoload --optimize --no-dev

# ==============================================================================
# Stage 3: Production image (PHP-FPM + Nginx)
# ==============================================================================
FROM php:8.3-fpm-alpine AS production

# Install system dependencies
RUN apk add --no-cache \
    nginx \
    supervisor \
    curl \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    linux-headers \
    postgresql-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        pdo_pgsql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        intl \
        opcache \
    && rm -rf /var/cache/apk/*

# PHP production config
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

COPY docker/php.ini "$PHP_INI_DIR/conf.d/99-custom.ini"
COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisord.conf

WORKDIR /var/www/html

# Copy application code
COPY --from=composer /app /var/www/html
COPY --from=frontend /app/public/build /var/www/html/public/build

# Remove dev files
RUN rm -rf \
    node_modules \
    tests \
    docker \
    .env \
    .env.example \
    .editorconfig \
    .gitignore \
    .gitattributes \
    phpunit.xml \
    vite.config.js \
    package.json \
    package-lock.json \
    .npmrc

# Create required directories and set permissions
RUN mkdir -p \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 storage bootstrap/cache

# Copy entrypoint
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80

ENTRYPOINT ["entrypoint.sh"]
CMD ["supervisord", "-c", "/etc/supervisord.conf", "-n"]
