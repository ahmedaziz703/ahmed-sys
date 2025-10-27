FROM php:8.2-fpm

# Install system dependencies + build tools
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libonig-dev \
    curl \
    libpq-dev \
    libxml2-dev \
    libssl-dev \
    libicu-dev \
    zlib1g-dev \
    g++ \
    make \
    pkg-config \
    libcurl4-openssl-dev \
    && docker-php-ext-install pdo_pgsql mbstring zip bcmath opcache intl

WORKDIR /var/www/html

COPY . .

# Copy .env.example to .env
RUN cp .env.example .env

# Set permissions
RUN chown -R www-data:www-data storage bootstrap/cache
RUN chmod -R 775 storage bootstrap/cache

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install all PHP dependencies
RUN composer install --no-interaction --ignore-platform-reqs --optimize-autoloader

# Generate Laravel key
RUN php artisan key:generate

EXPOSE 8000

# Run Laravel with migrations + seed
CMD php artisan serve --host 0.0.0.0 --port 8000
