@echo off
setlocal enabledelayedexpansion

echo =====================================================
echo   MIW Travel - Docker Deployment Script for Windows
echo =====================================================
echo.

REM Check if docker is installed
where docker >nul 2>&1
if %errorlevel% neq 0 (
    echo [X] Docker is not installed. Please install Docker first.
    exit /b 1
)

REM Check if docker-compose is installed
where docker-compose >nul 2>&1
if %errorlevel% neq 0 (
    echo [X] Docker Compose is not installed. Please install Docker Compose first.
    exit /b 1
)

REM Parse arguments
set ENVIRONMENT=development
if "%1"=="production" (
    set ENVIRONMENT=production
)

echo [*] Starting deployment in %ENVIRONMENT% mode...

REM Check for environment file
if not exist .env (
    echo [!] No .env file found. Creating from example...
    copy .env.example .env
    echo Please update your .env file with proper credentials.
    echo Then run this script again.
    exit /b 1
)

REM Choose the right docker-compose file
set COMPOSE_FILE=docker-compose.yml
if "%ENVIRONMENT%"=="production" (
    set COMPOSE_FILE=docker-compose.production.yml
)

REM Pull the latest images
echo [*] Pulling latest Docker images...
docker-compose -f %COMPOSE_FILE% pull

REM Build the containers
echo [*] Building containers...
docker-compose -f %COMPOSE_FILE% build

REM Start the containers
echo [*] Starting services...
docker-compose -f %COMPOSE_FILE% up -d

REM Display status
echo [✓] Deployment completed successfully!
echo.
if "%ENVIRONMENT%"=="development" (
    echo [*] PHPMyAdmin: http://localhost:8081
    echo [*] MIW Application: http://localhost:8080
) else (
    echo [*] MIW Application is now running in production mode!
)

echo.
echo ====================================================

endlocal
