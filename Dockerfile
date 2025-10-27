# Use PHP 8.2 FPM
FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libonig-dev \
    curl \
    libpq-dev \
    && docker-php-ext-install pdo_pgsql mbstring zip bcmath opcache

# Set working directory
WORKDIR /var/www/html

# Copy all project files
COPY . .

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Set permissions for Laravel
RUN chown -R www-data:www-data storage bootstrap/cache
RUN chmod -R 775 storage bootstrap/cache

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --ignore-platform-reqs

# Expose port 8000
EXPOSE 8000

# Run Laravel
# Run Laravel with migrations + seed
CMD sh -c "php artisan migrate --force --seed && php artisan serve --host 0.0.0.0 --port \$PORT"
