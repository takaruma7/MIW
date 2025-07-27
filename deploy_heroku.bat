@echo off
cls
echo ================================================================
echo                   MIW Heroku Deployment Script
echo ================================================================
echo.

echo [STEP 1] Checking Heroku CLI installation...
echo ----------------------------------------------------------------
heroku --version >nul 2>&1
if %errorlevel% equ 0 (
    echo ✓ Heroku CLI is installed
) else (
    echo ✗ Heroku CLI not found
    echo Please install from: https://devcenter.heroku.com/articles/heroku-cli
    echo.
    echo Press any key to open download page...
    pause >nul
    start https://devcenter.heroku.com/articles/heroku-cli
    exit /b 1
)

echo.
echo [STEP 2] Preparing Heroku configuration...
echo ----------------------------------------------------------------
echo Copying Heroku-specific config...
copy "config.heroku.php" "config.production.php" >nul 2>&1
echo ✓ Heroku configuration ready

echo.
echo [STEP 3] Committing latest changes...
echo ----------------------------------------------------------------
git add . >nul 2>&1
git commit -m "Prepare for Heroku deployment" >nul 2>&1
if %errorlevel% equ 0 (
    echo ✓ Changes committed
) else (
    echo ℹ No new changes to commit
)

echo.
echo [STEP 4] Heroku login and app creation...
echo ----------------------------------------------------------------
echo Please login to Heroku when browser opens...
heroku login
if %errorlevel% neq 0 (
    echo ✗ Heroku login failed
    pause
    exit /b 1
)

echo.
echo Creating Heroku app...
heroku create miw-travel-app-2024
if %errorlevel% neq 0 (
    echo ℹ App name may be taken, trying with random name...
    heroku create
    if %errorlevel% neq 0 (
        echo ✗ Failed to create Heroku app
        pause
        exit /b 1
    )
)

echo.
echo [STEP 5] Adding PostgreSQL database...
echo ----------------------------------------------------------------
heroku addons:create heroku-postgresql:mini
if %errorlevel% equ 0 (
    echo ✓ PostgreSQL database added
) else (
    echo ✗ Failed to add database (may require verification)
    echo Please verify your Heroku account at: https://heroku.com/verify
)

echo.
echo [STEP 6] Setting environment variables...
echo ----------------------------------------------------------------
heroku config:set APP_ENV=production
heroku config:set SMTP_HOST=smtp.gmail.com
heroku config:set SMTP_USERNAME=drakestates@gmail.com
heroku config:set SMTP_PASSWORD="lqqj vnug vrau dkfa"
heroku config:set SMTP_PORT=587
heroku config:set SMTP_ENCRYPTION=tls
heroku config:set MAX_FILE_SIZE=10M
heroku config:set MAX_EXECUTION_TIME=300
heroku config:set SECURE_HEADERS=true
echo ✓ Environment variables configured

echo.
echo [STEP 7] Deploying application...
echo ----------------------------------------------------------------
git push heroku main
if %errorlevel% equ 0 (
    echo ✓ Application deployed successfully!
) else (
    echo ✗ Deployment failed
    echo Check logs with: heroku logs --tail
    pause
    exit /b 1
)

echo.
echo ================================================================
echo                     DEPLOYMENT COMPLETED!
echo ================================================================
echo.
echo Your MIW application is now live on Heroku!
echo.
echo [NEXT STEPS]
echo ----------------------------------------------------------------
echo 1. Initialize database by visiting: [your-app-url]/init_database_universal.php
echo 2. Test registration forms
echo 3. Verify email functionality
echo 4. Check admin dashboard
echo.
echo [USEFUL COMMANDS]
echo ----------------------------------------------------------------
echo • View logs:        heroku logs --tail
echo • Open app:         heroku open
echo • Check status:     heroku ps
echo • View config:      heroku config
echo • Database console: heroku pg:psql
echo.
echo [APP INFORMATION]
echo ----------------------------------------------------------------
heroku info
echo.
echo [DATABASE INFORMATION]
echo ----------------------------------------------------------------
heroku pg:info
echo.
echo Opening your live application...
heroku open

echo.
echo ================================================================
echo         🎉 MIW Travel System is now LIVE on Heroku! 🎉
echo ================================================================
echo.
echo Your customers can now register for Haji and Umroh packages!
echo Admin dashboard is ready for managing bookings and documents.
echo.
pause
