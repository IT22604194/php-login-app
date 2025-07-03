FROM php:8.2-apache

# Enable mod_rewrite and install mysqli
RUN apt-get update && apt-get install -y \
    libzip-dev zip unzip && \
    docker-php-ext-install mysqli && \
    a2enmod rewrite

# Copy project files
COPY ./public/ /var/www/html/
COPY ./config.php /var/www/html/config.php

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html
