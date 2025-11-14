# ============================================
# Agency Builder CRM - Dockerfile (FINAL FIXED)
# Works on DigitalOcean App Platform
# ============================================

FROM php:8.2-apache

# Enable rewrite
RUN a2enmod rewrite

# Install required system packages
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    curl \
    zip \
    libzip-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo pdo_mysql zip opcache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Working directory
WORKDIR /var/www/html

# Copy app files
COPY . .

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Fix permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Apache virtual host (properly escaped)
RUN printf "%s\n" \
    "<VirtualHost *:80>" \
    "    ServerName localhost" \
    "    DocumentRoot /var/www/html/public" \
    "    <Directory /var/www/html/public>" \
    "        AllowOverride All" \
    "        Require all granted" \
    "    </Directory>" \
    "</VirtualHost>" \
    > /etc/apache2/sites-available/000-default.conf

# Expose HTTP
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]
