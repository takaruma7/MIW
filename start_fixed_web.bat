@echo off
echo MIW Travel - Fixed Web Container Setup
echo ========================================
echo.

echo [*] Starting web container with proper permissions...
docker run -d --name miw-web-fixed ^
  --network miw_miw-network ^
  -p 8080:80 ^
  -e DB_HOST=miw-db-1 ^
  -e DB_PORT=3306 ^
  -e DB_NAME=data_miw ^
  -e DB_USER=miw_user ^
  -e DB_PASS=miw_password ^
  -v "%CD%":/var/www/html ^
  php:8.1-apache ^
  bash -c "^
    echo 'Installing PHP extensions...' && ^
    docker-php-ext-install pdo_mysql gd zip && ^
    echo 'Configuring Apache...' && ^
    a2enmod rewrite && ^
    echo 'ServerName localhost' >> /etc/apache2/apache2.conf && ^
    echo '<Directory /var/www/html>' > /etc/apache2/conf-available/miw.conf && ^
    echo '    Options Indexes FollowSymLinks' >> /etc/apache2/conf-available/miw.conf && ^
    echo '    AllowOverride All' >> /etc/apache2/conf-available/miw.conf && ^
    echo '    Require all granted' >> /etc/apache2/conf-available/miw.conf && ^
    echo '</Directory>' >> /etc/apache2/conf-available/miw.conf && ^
    a2enconf miw && ^
    echo 'Setting permissions...' && ^
    chown -R www-data:www-data /var/www/html && ^
    chmod -R 755 /var/www/html && ^
    echo 'Starting Apache...' && ^
    apache2-foreground"

echo.
echo [*] Web container starting with fixed permissions...
echo [*] Wait 30 seconds, then access:
echo [*] - Local: http://localhost:8080
echo [*] - From other devices: http://192.168.1.7:8080
echo.
echo ========================================
