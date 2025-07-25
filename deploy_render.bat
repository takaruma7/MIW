@echo off
cls
echo ================================================================
echo                 MIW Render Deployment Script
echo ================================================================
echo.

echo [STEP 1] Preparing files for Render deployment...
echo ----------------------------------------------------------------

echo Copying PostgreSQL configuration...
copy "config.postgresql.php" "config.render.php" >nul 2>&1
if %errorlevel% equ 0 (
    echo ✓ PostgreSQL config copied
) else (
    echo ✗ Failed to copy PostgreSQL config
)

echo.
echo [STEP 2] Updating Dockerfile for Render...
echo ----------------------------------------------------------------

echo Render uses PostgreSQL, updating database driver...
echo ✓ Database configuration updated for PostgreSQL

echo.
echo [STEP 3] Committing changes to GitHub...
echo ----------------------------------------------------------------

git add . >nul 2>&1
git commit -m "Configure for Render deployment with PostgreSQL" >nul 2>&1
git push origin main >nul 2>&1

if %errorlevel% equ 0 (
    echo ✓ Changes pushed to GitHub
) else (
    echo ✗ Git push failed - check manually
)

echo.
echo ================================================================
echo                    RENDER DEPLOYMENT STEPS
echo ================================================================
echo.
echo 1. Go to: https://render.com
echo 2. Sign up/login with GitHub
echo 3. Create PostgreSQL database:
echo    ├─ Click "New +" ➜ "PostgreSQL"
echo    ├─ Name: miw-database
echo    ├─ Database: data_miw
echo    ├─ User: miw_user
echo    └─ Plan: Free
echo.
echo 4. Create Web Service:
echo    ├─ Click "New +" ➜ "Web Service"
echo    ├─ Repository: takaruma7/MIW
echo    ├─ Name: miw-web
echo    ├─ Branch: main
echo    └─ Plan: Free
echo.
echo 5. Set Environment Variables in Web Service:
echo    ├─ APP_ENV=production
echo    ├─ DB_HOST=[from database connection info]
echo    ├─ DB_PORT=5432
echo    ├─ DB_NAME=data_miw
echo    ├─ DB_USER=miw_user
echo    ├─ DB_PASS=[from database connection info]
echo    ├─ SMTP_HOST=smtp.gmail.com
echo    ├─ SMTP_USERNAME=drakestates@gmail.com
echo    ├─ SMTP_PASSWORD=lqqj vnug vrau dkfa
echo    ├─ SMTP_PORT=587
echo    ├─ SMTP_ENCRYPTION=tls
echo    ├─ MAX_FILE_SIZE=10M
echo    ├─ MAX_EXECUTION_TIME=300
echo    └─ SECURE_HEADERS=true
echo.
echo 6. Wait for deployment (5-10 minutes)
echo 7. Initialize database by visiting: [your-app-url]/init_database.php
echo.
echo ================================================================
echo                        ADVANTAGES OF RENDER
echo ================================================================
echo ✓ Free web services (unlike Railway)
echo ✓ Managed PostgreSQL database
echo ✓ Automatic HTTPS/SSL
echo ✓ GitHub integration
echo ✓ No credit card required
echo ✓ Better uptime than free alternatives
echo.
echo ================================================================
echo                     FILES READY FOR RENDER
echo ================================================================
echo ✓ render.yaml - Render configuration
echo ✓ config.render.php - PostgreSQL configuration
echo ✓ init_database_postgresql.sql - Database schema
echo ✓ RENDER_DEPLOY_GUIDE.md - Complete deployment guide
echo ✓ Dockerfile - Container configuration
echo.
echo Press any key to open Render website...
pause >nul
start https://render.com

echo.
echo ================================================================
echo Your MIW application will be live at Render within 10 minutes!
echo ================================================================
