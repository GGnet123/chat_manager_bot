FROM php:8.2-fpm-alpine

ARG UID=1000
ARG GID=1000

# Install system dependencies
RUN apk add --no-cache \
    git \
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
    linux-headers \
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
RUN pecl install redis && docker-php-ext-enable redis

# Create system user
RUN addgroup -g ${GID} appgroup \
    && adduser -u ${UID} -G appgroup -s /bin/sh -D appuser

# Set working directory
WORKDIR /var/www

# Copy PHP configuration
COPY php.ini /usr/local/etc/php/conf.d/custom.ini

# Copy entrypoint script
COPY entrypoint.sh /usr/local/bin/entrypoint.sh

# Set ownership and permissions
RUN chmod +x /usr/local/bin/entrypoint.sh \
    && chown -R appuser:appgroup /var/www

USER appuser

EXPOSE 9000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
