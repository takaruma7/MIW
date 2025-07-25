@echo off
REM Railway Deployment Script for MIW Travel
REM Project ID: 2725c7e0-071b-43ea-9be7-33142b967d77

echo 🚀 Deploying MIW to Railway...
echo Project ID: 2725c7e0-071b-43ea-9be7-33142b967d77
echo.

REM Check if Railway CLI is installed
where railway >nul 2>&1
if %errorlevel% neq 0 (
    echo 📦 Installing Railway CLI...
    npm install -g @railway/cli
    if %errorlevel% neq 0 (
        echo ❌ Failed to install Railway CLI. Please install Node.js first.
        echo Download from: https://nodejs.org
        pause
        exit /b 1
    )
)

REM Login to Railway
echo 🔐 Logging in to Railway...
railway login
if %errorlevel% neq 0 (
    echo ❌ Railway login failed
    pause
    exit /b 1
)

REM Link to existing project
echo 🔗 Linking to Railway project...
railway link 2725c7e0-071b-43ea-9be7-33142b967d77
if %errorlevel% neq 0 (
    echo ❌ Failed to link project
    pause
    exit /b 1
)

REM Deploy the application
echo 🚀 Deploying application...
railway up
if %errorlevel% neq 0 (
    echo ❌ Deployment failed
    pause
    exit /b 1
)

echo.
echo ✅ Deployment initiated!
echo 📊 Check deployment status at: https://railway.app/project/2725c7e0-071b-43ea-9be7-33142b967d77
echo.
pause
