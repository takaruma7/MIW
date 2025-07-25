@echo off
echo MIW Travel - Simple Web Container Setup
echo ========================================
echo.

echo [*] Stopping any existing web containers...
docker stop miw-web-1 miw-web-temp 2>nul
docker rm miw-web-1 miw-web-temp 2>nul

echo [*] Starting a simple PHP-Apache container...
docker run -d --name miw-web-simple ^
  --network miw_miw-network ^
  -p 8080:80 ^
  -e DB_HOST=miw-db-1 ^
  -e DB_PORT=3306 ^
  -e DB_NAME=data_miw ^
  -e DB_USER=miw_user ^
  -e DB_PASS=miw_password ^
  -v "%CD%":/var/www/html ^
  php:8.1-apache ^
  bash -c "docker-php-ext-install pdo_mysql && a2enmod rewrite && echo 'ServerName localhost' >> /etc/apache2/apache2.conf && apache2-foreground"

echo.
echo [*] Web application starting...
echo [*] Wait 30 seconds, then access:
echo [*] - Local: http://localhost:8080
echo [*] - From other devices: http://192.168.1.7:8080
echo [*] - PHPMyAdmin: http://192.168.1.7:8081
echo.
echo ========================================
