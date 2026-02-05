# ===============================
# Dockerfile for ChefBot PHP App
# ===============================

# Use official PHP 8.2 with Apache
FROM php:8.2-apache

# Enable Apache rewrite module (for .htaccess / clean URLs)
RUN a2enmod rewrite

# Install PHP extensions: mysqli, PDO, and PDO MySQL
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Copy all project files into Apache web root
COPY . /var/www/html/

# Optional: remove any leftover Python files
RUN rm -f /var/www/html/*.py

# Fix permissions so Apache can read/write files
RUN chown -R www-data:www-data /var/www/html

# Expose port 80 for the web server
EXPOSE 80

# Default command runs Apache in the foreground
CMD ["apache2-foreground"]
