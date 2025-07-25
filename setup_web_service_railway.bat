@echo off
echo ================================================================
echo            MIW Web Service Setup for Railway
echo ================================================================
echo.

echo [STEP 1] Add Web Service:
echo ─────────────────────────────────────────────────────────────────
echo 1. Go to Railway Dashboard
echo 2. Click "+ New Service"
echo 3. Select "GitHub Repo"
echo 4. Choose "takaruma7/MIW" repository
echo 5. Railway will auto-detect Dockerfile and start building
echo.

echo [STEP 2] Configure Environment Variables:
echo ─────────────────────────────────────────────────────────────────
echo In your Web Service → Variables tab, add these:
echo.
echo DB_HOST=ballast.proxy.rlwy.net
echo DB_PORT=58773
echo DB_NAME=railway
echo DB_USER=root
echo DB_PASS=ULXtfrTxwgaMIRsOZCteLEvXZTvqvfWe
echo APP_ENV=production
echo SMTP_HOST=smtp.gmail.com
echo SMTP_USERNAME=drakestates@gmail.com
echo SMTP_PASSWORD=lqqj vnug vrau dkfa
echo SMTP_PORT=587
echo SMTP_ENCRYPTION=tls
echo MAX_FILE_SIZE=10M
echo MAX_EXECUTION_TIME=300
echo SECURE_HEADERS=true
echo.

echo [STEP 3] Connect Services:
echo ─────────────────────────────────────────────────────────────────
echo 1. In Web Service Variables tab
echo 2. Click "Reference" button  
echo 3. Select your MySQL service
echo 4. This creates automatic connection
echo.

echo [STEP 4] Deploy and Test:
echo ─────────────────────────────────────────────────────────────────
echo 1. Railway builds your Docker image (5-10 minutes)
echo 2. Get your live URL from Railway dashboard
echo 3. Test your MIW application!
echo.

echo ================================================================
echo                      DEPLOYMENT STATUS
echo ================================================================
echo.
echo ✅ MySQL Service: Created and configured
echo ✅ Database Connection: ballast.proxy.rlwy.net:58773
echo ✅ Environment Config: Ready (.env.railway)
echo ⏳ Web Service: Ready to deploy
echo ⏳ Database Import: Pending (use import_database_railway.bat)
echo.

echo ================================================================
echo Opening Railway Dashboard...
start https://railway.app/project/2725c7e0-071b-43ea-9be7-33142b967d77
echo.
echo Press any key to continue...
pause
