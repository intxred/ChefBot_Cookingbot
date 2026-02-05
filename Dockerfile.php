# Use official PHP with Apache
FROM php:8.2-apache

# Enable rewrite module
RUN a2enmod rewrite

# Install PHP extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Copy PHP files only
COPY . /var/www/html/

# Remove Python files from Apache container
RUN rm -f /var/www/html/*.py

# Fix permissions
RUN chown -R www-data:www-data /var/www/html

# Expose Apache port inside container
EXPOSE 80
