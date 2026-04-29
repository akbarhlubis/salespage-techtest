# Use official PHP 8.3 FPM image (matching Laragon)
FROM php:8.3.16-fpm

# Install system dependencies & PHP extensions
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip

# Install common PHP extensions for Laravel
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Install composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www