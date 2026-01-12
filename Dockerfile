# Gunakan PHP 8.1 yang stabil
FROM php:8.1-apache

# Install ekstensi sistem yang wajib buat CodeIgniter
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libicu-dev \
    libzip-dev \
    && docker-php-ext-install intl pdo pdo_mysql zip \
    && a2enmod rewrite

# Setup Apache Document Root ke folder public
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Copy file project
WORKDIR /var/www/html
COPY . /var/www/html

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# --- BAGIAN PENTING ---
# Tambahkan --ignore-platform-reqs agar tidak error exit code 2 karena versi PHP
RUN composer install --no-dev --optimize-autoloader --ignore-platform-reqs

# Fix permissions folder writable
RUN chown -R www-data:www-data /var/www/html/writable
