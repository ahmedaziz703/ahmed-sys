FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libonig-dev \
    curl \
    libpq-dev \
    libxml2-dev \
    libssl-dev \
    && docker-php-ext-install pdo_pgsql mbstring zip bcmath opcache

WORKDIR /var/www/html

COPY . .

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Set permissions
RUN chown -R www-data:www-data storage bootstrap/cache
RUN chmod -R 775 storage bootstrap/cache

# Install dependencies including require-dev temporarily
RUN composer install --ignore-platform-reqs --optimize-autoloader

EXPOSE 8000

CMD sh -c "php artisan migrate --force --seed && php artisan serve --host 0.0.0.0 --port \$PORT"
