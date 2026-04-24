FROM php:8.1-apache

# Install PHP extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Set permissive upload directory permissions (intentionally misconfigured)
RUN mkdir -p /var/www/html/uploads && \
    chmod 777 /var/www/html/uploads

# Allow .htaccess overrides
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# Set working directory
WORKDIR /var/www/html

EXPOSE 80
