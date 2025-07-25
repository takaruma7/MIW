@echo off
echo ================================================================
echo         MIW Web Service Deployment to Railway
echo ================================================================
echo.

echo [STEP 1] Deploy Web Service:
echo ─────────────────────────────────────────────────────────────────
echo 1. In Railway dashboard, click "+ New Service"
echo 2. Select "GitHub Repo"
echo 3. Choose "takaruma7/MIW" repository  
echo 4. Railway will auto-detect Dockerfile and start building
echo.

echo [STEP 2] Configure Environment Variables:
echo ─────────────────────────────────────────────────────────────────
echo In your Web Service → Variables tab, add these variables:
echo.
echo *** COPY THESE EXACT VALUES ***
echo.
echo APP_ENV=production
echo.
echo # Database (use Railway's MySQL variables)
echo DB_HOST=${{MYSQLHOST}}
echo DB_PORT=${{MYSQLPORT}}
echo DB_NAME=${{MYSQLDATABASE}}
echo DB_USER=${{MYSQLUSER}}
echo DB_PASS=${{MYSQLPASSWORD}}
echo.
echo # Email Configuration
echo SMTP_HOST=smtp.gmail.com
echo SMTP_USERNAME=drakestates@gmail.com
echo SMTP_PASSWORD=lqqj vnug vrau dkfa
echo SMTP_PORT=587
echo SMTP_ENCRYPTION=tls
echo.
echo # Application Settings
echo MAX_FILE_SIZE=10M
echo MAX_EXECUTION_TIME=300
echo SECURE_HEADERS=true
echo.

echo [STEP 3] Reference MySQL Service:
echo ─────────────────────────────────────────────────────────────────
echo 1. In Web Service Variables tab
echo 2. Click "Reference" button
echo 3. Select your MySQL service
echo 4. This automatically connects database variables
echo.

echo [STEP 4] Wait for Deployment:
echo ─────────────────────────────────────────────────────────────────
echo 1. Railway builds Docker image (5-10 minutes)
echo 2. Deployment completes automatically
echo 3. Get your live URL from Railway dashboard
echo 4. Database tables will be created automatically on first access
echo.

echo ================================================================
echo                      EXPECTED RESULTS
echo ================================================================
echo.
echo After successful deployment:
echo ✅ Live MIW application at Railway URL
echo ✅ Automatic HTTPS/SSL certificate
echo ✅ Database connected via Railway variables
echo ✅ Tables created automatically by PHP app
echo ✅ Ready for customer registration!
echo.

echo ================================================================
echo                        TROUBLESHOOTING
echo ================================================================
echo.
echo If deployment fails:
echo 1. Check "Deployments" tab for build logs
echo 2. Verify all environment variables are set
echo 3. Ensure MySQL service is running
echo 4. Check web service logs for errors
echo.

echo ================================================================
echo Opening Railway dashboard...
start https://railway.app/project/2725c7e0-071b-43ea-9be7-33142b967d77
echo.
echo [NEXT] After web service is deployed, test your application!
echo.
pause
