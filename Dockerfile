FROM php:8.1-apache

# Install ekstensi yang dibutuhkan CodeIgniter
RUN apt-get update && apt-get install -y libicu-dev git unzip \
    && docker-php-ext-install intl pdo pdo_mysql \
    && a2enmod rewrite

# Arahkan Apache ke folder public
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Copy semua file proyek ke server
COPY . /var/www/html

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN composer install --no-dev --optimize-autoloader

# Atur izin folder agar bisa ditulis (wajib untuk CodeIgniter)
RUN chown -R www-data:www-data /var/www/html/writable
