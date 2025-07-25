@echo off
echo MIW Travel - Docker Deployment Clean Restart Script for Windows
echo =====================================================
echo.

echo [*] Stopping any running containers...
docker-compose down
echo.

echo [*] Removing old containers, images, and volumes for a clean build...
docker-compose rm -f
echo.

echo [*] Clearing Docker build cache...
docker builder prune -f
docker system prune -f --volumes
echo.

echo [*] Rebuilding containers with the fixed configuration...
docker-compose build --no-cache
echo.

echo [*] Starting services...
docker-compose up -d
echo.

echo [*] Deployment status:
docker-compose ps
echo.

echo [*] PHPMyAdmin: http://localhost:8081
echo [*] MIW Application: http://localhost:8080
echo.
echo ====================================================
