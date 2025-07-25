@echo off
echo MIW Travel - Quick Fix for Web Container
echo ==========================================
echo.

echo [*] Stopping web container...
docker stop miw-web-1 2>nul

echo [*] Creating a simple temporary fix for Apache MPM...
docker run -d --name miw-web-temp ^
  --network miw_miw-network ^
  -p 8080:80 ^
  -e DB_HOST=miw-db-1 ^
  -e DB_PORT=3306 ^
  -e DB_NAME=data_miw ^
  -e DB_USER=miw_user ^
  -e DB_PASS=miw_password ^
  -e APP_ENV=development ^
  -v "%CD%":/var/www/html ^
  php:8.1-apache ^
  bash -c "a2enmod rewrite headers && docker-php-ext-install pdo_mysql gd zip && apache2-foreground"

echo.
echo [*] Web container started as miw-web-temp
echo [*] Access your application at: http://localhost:8080
echo [*] PHPMyAdmin: http://localhost:8081
echo.
echo ==========================================
