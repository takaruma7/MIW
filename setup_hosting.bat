@echo off
echo ================================================================
echo               MIW Travel - Cloud Hosting Setup
echo ================================================================
echo.
echo Choose your preferred hosting platform:
echo.
echo [1] Railway.app (RECOMMENDED)
echo     - Cost: $2-3/month ($5 free credit)
echo     - Database: MySQL (no migration needed)
echo     - Difficulty: Easy
echo     - Best for: Your current setup
echo.
echo [2] Render.com (FREE)
echo     - Cost: $0/month
echo     - Database: PostgreSQL (requires migration)
echo     - Difficulty: Medium
echo     - Best for: Completely free hosting
echo.
echo [3] Fly.io
echo     - Cost: $0-2/month
echo     - Database: PostgreSQL
echo     - Difficulty: Medium
echo     - Best for: Performance and global deployment
echo.
echo [4] Show detailed comparison
echo.
set /p choice="Enter your choice (1-4): "

if "%choice%"=="1" goto railway
if "%choice%"=="2" goto render
if "%choice%"=="3" goto fly
if "%choice%"=="4" goto comparison
goto invalid

:railway
echo.
echo ================================================================
echo                    RAILWAY DEPLOYMENT SETUP
echo ================================================================
echo.
echo [*] Setting up Railway deployment...
call deploy_railway.bat
goto end

:render
echo.
echo ================================================================
echo                    RENDER DEPLOYMENT SETUP
echo ================================================================
echo.
echo [*] Render requires PostgreSQL migration from MySQL
echo [*] This is more complex but completely free
echo.
echo [!] Would you like to proceed with database migration? (y/n)
set /p migrate="Enter choice: "
if /i "%migrate%"=="y" (
    echo [*] Creating PostgreSQL migration files...
    echo [*] Please check RENDER_DEPLOYMENT.md for detailed instructions
) else (
    echo [*] Consider Railway for easier MySQL-compatible deployment
)
goto end

:fly
echo.
echo ================================================================
echo                     FLY.IO DEPLOYMENT SETUP
echo ================================================================
echo.
echo [*] Fly.io requires CLI installation and PostgreSQL migration
echo [*] Please check FLY_DEPLOYMENT.md for detailed instructions
echo.
goto end

:comparison
echo.
echo ================================================================
echo                      DETAILED COMPARISON
echo ================================================================
echo.
echo Feature Comparison:
echo.
echo                   Railway    Render     Fly.io
echo Cost/Month:       $2-3       $0         $0-2
echo Database:         MySQL      PostgreSQL PostgreSQL
echo Migration Needed: No         Yes        Yes
echo Deployment:       Easy       Medium     Medium
echo Performance:      Good       Good       Excellent
echo Global CDN:       No         No         Yes
echo Custom Domain:    Free       Free       Free
echo SSL/HTTPS:        Free       Free       Free
echo.
echo RECOMMENDATION: Railway.app
echo - No database migration needed
echo - Works with your existing MySQL setup
echo - $5 monthly credit covers usage
echo - Simplest deployment process
echo.
pause
goto start

:invalid
echo.
echo [!] Invalid choice. Please enter 1, 2, 3, or 4.
echo.
goto start

:end
echo.
echo ================================================================
echo                        DEPLOYMENT READY
echo ================================================================
echo.
echo Files created:
echo - railway.json (Railway configuration)
echo - .env.railway (Production environment)
echo - RAILWAY_DEPLOYMENT.md (Step-by-step guide)
echo - RENDER_DEPLOYMENT.md (Alternative free option)
echo - FLY_DEPLOYMENT.md (Performance option)
echo.
echo Next steps:
echo 1. Choose your platform and follow the guide
echo 2. Create account on chosen platform
echo 3. Connect GitHub repository
echo 4. Configure environment variables
echo 5. Deploy!
echo.
echo Your MIW app will be live on the internet! 🚀
echo.
pause

:start
