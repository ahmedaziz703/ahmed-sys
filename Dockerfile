# Use PHP 8.2 FPM base image
FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libonig-dev \
    curl \
    libpq-dev \
    && docker-php-ext-install pdo_pgsql mbstring zip bcmath

# Set working directory
WORKDIR /var/www/html

# Copy all project files
COPY . .

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Set permissions for Laravel storage and cache
RUN chown -R www-data:www-data storage bootstrap/cache
RUN chmod -R 775 storage bootstrap/cache

# Expose port 8000
EXPOSE 8000

# Run Laravel
CMD php artisan serve --host 0.0.0.0 --port $PORT
