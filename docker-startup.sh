#!/bin/bash
echo "Installing PHP extensions..."
docker-php-ext-install pdo_mysql gd zip

echo "Configuring Apache..."
a2enmod rewrite
echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Create directory configuration
cat > /etc/apache2/conf-available/miw.conf << 'EOF'
<Directory /var/www/html>
    Options Indexes FollowSymLinks
    AllowOverride All
    Require all granted
</Directory>
EOF

a2enconf miw

echo "Setting permissions..."
chown -R www-data:www-data /var/www/html
chmod -R 755 /var/www/html

echo "Starting Apache..."
apache2-foreground
