@echo off
echo MIW Travel - Docker Hub Push Script
echo ====================================
echo.

echo [*] Building the latest MIW image...
docker build -t miw-web:latest .
if %ERRORLEVEL% neq 0 (
    echo ERROR: Failed to build image
    pause
    exit /b 1
)
echo.

echo [*] Tagging image for Docker Hub (takaruma7/miw)...
docker tag miw-web:latest takaruma7/miw:latest
docker tag miw-web:latest takaruma7/miw:v1.0
if %ERRORLEVEL% neq 0 (
    echo ERROR: Failed to tag image
    pause
    exit /b 1
)
echo.

echo [*] Pushing to Docker Hub...
docker push takaruma7/miw:latest
docker push takaruma7/miw:v1.0
if %ERRORLEVEL% neq 0 (
    echo ERROR: Failed to push to Docker Hub
    pause
    exit /b 1
)
echo.

echo [SUCCESS] MIW image successfully pushed to Docker Hub!
echo.
echo Repository: https://hub.docker.com/r/takaruma7/miw
echo Latest tag: takaruma7/miw:latest
echo Version tag: takaruma7/miw:v1.0
echo.
echo To use this image in production, update your docker-compose.yml:
echo   web:
echo     image: takaruma7/miw:latest
echo     # Remove the 'build: .' line
echo.
pause
