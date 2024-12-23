# Use an official PHP image with Apache as the base image.
FROM php:8.3-apache

# Set environment variables.
ENV ACCEPT_EULA=Y
LABEL maintainer="joeluzcategui102@gmail.com"

# Install system dependencies and PHP extensions required for Laravel.
RUN apt-get update && apt-get install -y \
    build-essential \
    libpng-dev \
    vim \
    libonig-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libwebp-dev \
    libzip-dev \
    libxml2-dev \
    locales \
    zip \
    jpegoptim optipng pngquant gifsicle libpq-dev \
    curl \
    tzdata \
    git \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg

RUN docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql
RUN docker-php-ext-install -j$(nproc) gd pdo pdo_pgsql pgsql mbstring zip exif pcntl simplexml intl

# Enable Apache modules required for Laravel.
RUN a2enmod rewrite

# Configure Git to handle ownership issues in /var/www/html.
RUN git config --global --add safe.directory /var/www/html

# Set the Apache document root
ENV APACHE_DOCUMENT_ROOT /var/www/html/public

# Update the default Apache site configuration
COPY apache-config.conf /etc/apache2/sites-available/000-default.conf
COPY ./php.ini "$PHP_INI_DIR/"

# Install Composer globally.
RUN curl -sS https://getcomposer.org/installer \
    | php -- --install-dir=/usr/local/bin --filename=composer

# Create a directory for your Laravel application.
WORKDIR /var/www/html

# Copy the Laravel application files into the container.
COPY . .

# Install Laravel dependencies using Composer.
RUN composer install --optimize-autoloader --no-dev

# Set permissions for Laravel.
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

RUN touch /var/www/html/database/database.sqlite

RUN chown -R www-data:www-data /var/www/html/database
RUN chmod -R 775 /var/www/html/database

RUN php artisan migrate --force

# Expose port 80 for Apache.
EXPOSE 80

# Start Apache web server.
CMD ["apache2-foreground"]
