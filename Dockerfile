# =============================================================================
# Multi-stage production Dockerfile for Laravel
# Build: docker build -t <ECR_URI>/ai-chat-bot:latest .
# Run modes:
#   - Web server (default): docker run -p 80:80 <image>
#   - Queue worker: docker run <image> queue
#   - Scheduler: docker run <image> scheduler
# =============================================================================

# -----------------------------------------------------------------------------
# Stage 1: Composer dependencies
# -----------------------------------------------------------------------------
FROM composer:2 AS composer

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --ignore-platform-reqs \
    --prefer-dist

COPY . .

# Clear any cached dev service providers and rebuild autoload
RUN rm -rf bootstrap/cache/*.php \
    && composer dump-autoload --optimize --no-dev

# -----------------------------------------------------------------------------
# Stage 2: Production image
# -----------------------------------------------------------------------------
FROM php:8.2-fpm-alpine

LABEL maintainer="ServiceBot"
LABEL description="AI Chat Bot - Laravel Application"

# Install system dependencies
RUN apk add --no-cache \
    nginx \
    supervisor \
    curl \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    zip \
    unzip \
    icu-dev \
    oniguruma-dev \
    libpq-dev \
    $PHPIZE_DEPS

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_pgsql \
        pgsql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        intl \
        opcache

# Install Redis extension
RUN pecl install redis \
    && docker-php-ext-enable redis \
    && rm -rf /tmp/pear

# Clean up build dependencies
RUN apk del $PHPIZE_DEPS \
    && rm -rf /var/cache/apk/*

# Configure PHP for production
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# PHP configuration
COPY docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini

# Production PHP optimizations
RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.memory_consumption=256" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.interned_strings_buffer=16" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.max_accelerated_files=20000" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.validate_timestamps=0" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.save_comments=1" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "realpath_cache_size=4096K" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "realpath_cache_ttl=600" >> /usr/local/etc/php/conf.d/opcache.ini

# Configure PHP-FPM to use TCP instead of socket (more reliable)
RUN sed -i 's/listen = 127.0.0.1:9000/listen = 9000/' /usr/local/etc/php-fpm.d/zz-docker.conf 2>/dev/null || true \
    && echo "[www]" > /usr/local/etc/php-fpm.d/zzz-custom.conf \
    && echo "listen = 9000" >> /usr/local/etc/php-fpm.d/zzz-custom.conf \
    && echo "pm = dynamic" >> /usr/local/etc/php-fpm.d/zzz-custom.conf \
    && echo "pm.max_children = 20" >> /usr/local/etc/php-fpm.d/zzz-custom.conf \
    && echo "pm.start_servers = 2" >> /usr/local/etc/php-fpm.d/zzz-custom.conf \
    && echo "pm.min_spare_servers = 1" >> /usr/local/etc/php-fpm.d/zzz-custom.conf \
    && echo "pm.max_spare_servers = 3" >> /usr/local/etc/php-fpm.d/zzz-custom.conf \
    && echo "clear_env = no" >> /usr/local/etc/php-fpm.d/zzz-custom.conf

# Nginx configuration
COPY <<'EOF' /etc/nginx/http.d/default.conf
server {
    listen 80 default_server;
    listen [::]:80 default_server;
    server_name _;
    root /var/www/public;
    index index.php;

    charset utf-8;
    client_max_body_size 64M;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;

    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_proxied any;
    gzip_comp_level 6;
    gzip_types text/plain text/css text/xml application/json application/javascript application/rss+xml application/atom+xml image/svg+xml;

    # Health check endpoint
    location /health {
        access_log off;
        return 200 'OK';
        add_header Content-Type text/plain;
    }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
        fastcgi_buffer_size 128k;
        fastcgi_buffers 4 256k;
        fastcgi_busy_buffers_size 256k;
        fastcgi_read_timeout 300;
        fastcgi_connect_timeout 60;
    }

    # Deny access to hidden files
    location ~ /\.(?!well-known).* {
        deny all;
    }
}
EOF

# Supervisor configuration
COPY <<'EOF' /etc/supervisor/conf.d/supervisord.conf
[supervisord]
nodaemon=true
user=root
logfile=/var/log/supervisor/supervisord.log
pidfile=/var/run/supervisord.pid
loglevel=info

[program:php-fpm]
command=/usr/local/sbin/php-fpm -F
stdout_logfile=/dev/stdout
stdout_logfile_maxbytes=0
stderr_logfile=/dev/stderr
stderr_logfile_maxbytes=0
autorestart=true
priority=5
startsecs=5

[program:nginx]
command=/usr/sbin/nginx -g 'daemon off;'
stdout_logfile=/dev/stdout
stdout_logfile_maxbytes=0
stderr_logfile=/dev/stderr
stderr_logfile_maxbytes=0
autorestart=true
priority=10
startsecs=0
depends_on=php-fpm
EOF

# Create required directories
RUN mkdir -p /var/log/supervisor \
    && mkdir -p /var/run \
    && mkdir -p /var/www

# Set working directory
WORKDIR /var/www

# Copy application from composer stage
COPY --from=composer /app/vendor ./vendor
COPY . .

# Clear bootstrap cache and set permissions
RUN rm -rf bootstrap/cache/*.php \
    && chown -R nginx:nginx /var/www \
    && chmod -R 755 /var/www/storage \
    && chmod -R 755 /var/www/bootstrap/cache

# Create entrypoint script
COPY <<'ENTRYPOINT' /usr/local/bin/entrypoint.sh
#!/bin/sh
set -e

# Run mode (web, queue, scheduler)
MODE=${1:-web}

echo "=== ServiceBot Container Starting ==="
echo "Mode: $MODE"
echo "APP_ENV: $APP_ENV"
echo "PHP Version: $(php -v | head -n1)"

# Ensure storage directories are writable
echo "Setting up storage directories..."
mkdir -p /var/www/storage/logs
mkdir -p /var/www/storage/framework/cache
mkdir -p /var/www/storage/framework/sessions
mkdir -p /var/www/storage/framework/views
mkdir -p /var/www/bootstrap/cache
chmod -R 777 /var/www/storage /var/www/bootstrap/cache
chown -R nobody:nobody /var/www/storage /var/www/bootstrap/cache 2>/dev/null || true

# Generate app key if not set (for initial setup)
if [ -z "$APP_KEY" ]; then
    echo "WARNING: APP_KEY not set!"
fi

# Discover packages (rebuilds bootstrap/cache/packages.php)
echo "Discovering packages..."
php artisan package:discover --ansi || echo "Package discover failed, continuing..."

# Run migrations (only in web mode to avoid race conditions)
if [ "$MODE" = "web" ] && [ "$RUN_MIGRATIONS" = "true" ]; then
    echo "Running migrations..."
    php artisan migrate --force || echo "Migration failed, continuing..."
fi

# Cache configuration for production
if [ "$APP_ENV" = "production" ]; then
    echo "Caching configuration..."
    php artisan config:cache || echo "Config cache failed, continuing..."
    php artisan route:cache || echo "Route cache failed, continuing..."
    php artisan view:cache || echo "View cache failed, continuing..."
fi

case "$MODE" in
    web)
        echo "Starting web server (nginx + php-fpm)..."
        exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
        ;;
    queue)
        echo "Starting queue worker..."
        exec php artisan queue:work --sleep=3 --tries=3 --max-time=3600 --memory=256
        ;;
    scheduler)
        echo "Starting scheduler..."
        while true; do
            php artisan schedule:run --verbose --no-interaction
            sleep 60
        done
        ;;
    *)
        exec "$@"
        ;;
esac
ENTRYPOINT

RUN chmod +x /usr/local/bin/entrypoint.sh

# Expose port
EXPOSE 80

# Health check
HEALTHCHECK --interval=30s --timeout=5s --start-period=5s --retries=3 \
    CMD curl -f http://localhost/health || exit 1

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["web"]
