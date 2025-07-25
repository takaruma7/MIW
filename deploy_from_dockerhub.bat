@echo off
echo MIW Travel - Deploy from Docker Hub
echo ===================================
echo.

echo [*] Pulling latest image from Docker Hub...
docker pull takaruma7/miw:latest
if %ERRORLEVEL% neq 0 (
    echo ERROR: Failed to pull image from Docker Hub
    pause
    exit /b 1
)
echo.

echo [*] Stopping current containers...
docker-compose -f docker-compose.dockerhub.yml down
echo.

echo [*] Starting services with Docker Hub image...
docker-compose -f docker-compose.dockerhub.yml up -d
if %ERRORLEVEL% neq 0 (
    echo ERROR: Failed to start services
    pause
    exit /b 1
)
echo.

echo [*] Deployment status:
docker-compose -f docker-compose.dockerhub.yml ps
echo.

echo [SUCCESS] MIW deployed from Docker Hub!
echo.
echo [*] PHPMyAdmin: http://localhost:8081
echo [*] MIW Application: http://localhost:8080
echo.
pause
