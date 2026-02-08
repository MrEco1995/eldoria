FROM php:8.3-fpm

ARG UID=1000
ARG GID=1000

# System dependencies and PHP extensions commonly needed by Laravel
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        curl \
        zip \
        unzip \
        libpng-dev \
        libonig-dev \
        libxml2-dev \
        libzip-dev \
        libicu-dev \
        libssl-dev \
        libpq-dev \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd intl \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Copy Composer from official image
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Create non-root user matching typical host UID/GID to avoid permission issues
RUN groupadd -g ${GID} laravel || true \
    && useradd -m -u ${UID} -g ${GID} laravel || true

WORKDIR /var/www/html

# Install PHP dependencies (expects composer.lock present for reproducibility)
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader --no-scripts

# Copy application code
COPY . .
RUN composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader

# Ensure storage/bootstrap dirs are writable
RUN chown -R laravel:laravel storage bootstrap/cache \
    && find storage -type d -exec chmod 775 {} \; \
    && find storage -type f -exec chmod 664 {} \; \
    && chmod -R 775 bootstrap/cache

USER laravel

CMD ["php-fpm"]
