FROM php:8.2-apache

# Enable mod_rewrite (needed for .htaccess)
RUN a2enmod rewrite

# Allow .htaccess overrides
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# Copy site files
COPY . /var/www/html/

# Ensure data dir exists and is writable
RUN mkdir -p /var/www/html/data \
    && chown -R www-data:www-data /var/www/html/data \
    && chmod 755 /var/www/html/data

EXPOSE 80
