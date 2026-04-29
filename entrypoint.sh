#!/bin/bash
mkdir -p /var/www/html/data
chown -R www-data:www-data /var/www/html/data
chmod 755 /var/www/html/data
exec apache2-foreground
