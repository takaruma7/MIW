@echo off
echo MIW Travel - Complete Docker Fix and Restart
echo ==============================================
echo.

echo [*] Step 1: Fixing composer dependencies...
echo [*] Removing old composer.lock file...
if exist composer.lock (
    del composer.lock
    echo     composer.lock removed successfully
) else (
    echo     composer.lock not found, continuing...
)
echo.

echo [*] Clearing Composer cache...
composer clear-cache
echo.

echo [*] Step 2: Stopping any running containers...
docker-compose down
echo.

echo [*] Step 3: Removing Docker images to force rebuild...
docker-compose down --rmi all
echo.

echo [*] Step 4: Building with fresh dependencies...
docker-compose build --no-cache
echo.

echo [*] Step 5: Starting services...
docker-compose up -d
echo.

echo [*] Step 6: Showing deployment status...
docker-compose ps
echo.

echo [*] PHPMyAdmin: http://localhost:8081
echo [*] MIW Application: http://localhost:8080
echo.
echo ==============================================
