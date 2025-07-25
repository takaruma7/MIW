@echo off
echo ================================================================
echo               MIW Railway Deployment Checklist
echo ================================================================
echo Project ID: 2725c7e0-071b-43ea-9be7-33142b967d77
echo.

echo ✅ Step 1: Access Railway Project
echo    URL: https://railway.app/project/2725c7e0-071b-43ea-9be7-33142b967d77
echo.

echo ✅ Step 2: Add MySQL Database
echo    - Click "+ New Service"
echo    - Select "Database" → "MySQL"
echo.

echo ✅ Step 3: Deploy Web App
echo    - Click "+ New Service" 
echo    - Select "GitHub Repo"
echo    - Choose "MIW" repository
echo.

echo ✅ Step 4: Set Environment Variables
echo    Copy from .env.railway file:
echo    - APP_ENV=production
echo    - DB_HOST=${{MySQL.MYSQL_HOST}}
echo    - DB_PORT=${{MySQL.MYSQL_PORT}}
echo    - DB_NAME=${{MySQL.MYSQL_DATABASE}}
echo    - DB_USER=${{MySQL.MYSQL_USER}}
echo    - DB_PASS=${{MySQL.MYSQL_PASSWORD}}
echo    - SMTP_HOST=smtp.gmail.com
echo    - SMTP_USERNAME=your-email@gmail.com
echo    - SMTP_PASSWORD=your-app-password
echo    - SMTP_PORT=587
echo    - SMTP_ENCRYPTION=tls
echo.

echo ✅ Step 5: Connect Services
echo    - In web service Variables tab
echo    - Click "Reference" and select MySQL
echo.

echo ✅ Step 6: Deploy!
echo    Railway will build and deploy automatically
echo.

echo 🎯 Expected URL: https://miw-production.up.railway.app
echo 💰 Cost: ~$2-3/month (FREE with $5 credit!)
echo.

echo ================================================================
echo Open Railway dashboard? (Y/N)
set /p choice="Enter choice: "
if /i "%choice%"=="Y" (
    start https://railway.app/project/2725c7e0-071b-43ea-9be7-33142b967d77
)

echo.
echo 📖 For detailed guide, see: RAILWAY_DEPLOY_MANUAL.md
pause
