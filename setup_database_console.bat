@echo off
echo ================================================================
echo         MIW Database Setup via Railway MySQL Console
echo ================================================================
echo.

echo [STEP 1] Access Railway MySQL Console:
echo ─────────────────────────────────────────────────────────────────
echo 1. In Railway MySQL service, click "Data" tab
echo 2. Click "Connect to the database MySQL" button
echo 3. This opens a MySQL console in your browser
echo.

echo [STEP 2] Copy and Execute SQL Commands:
echo ─────────────────────────────────────────────────────────────────
echo 1. Open file: railway_mysql_setup.sql (in your MIW folder)
echo 2. Copy ALL the SQL commands from that file
echo 3. Paste into the Railway MySQL console
echo 4. Press Enter to execute
echo.

echo [STEP 3] Verify Database Setup:
echo ─────────────────────────────────────────────────────────────────
echo After execution, you should see:
echo ✓ Tables created successfully
echo ✓ Sample data inserted
echo ✓ "SHOW TABLES;" displays your tables
echo.

echo Expected tables:
echo • data_invoice
echo • data_jamaah  
echo • manifest
echo • paket
echo • pembatalan
echo.

echo [STEP 4] Test Database Connection:
echo ─────────────────────────────────────────────────────────────────
echo Run this query to verify data:
echo SELECT * FROM paket;
echo.
echo You should see 3 sample travel packages.
echo.

echo ================================================================
echo                      TROUBLESHOOTING
echo ================================================================
echo.
echo If you get errors:
echo 1. Make sure you're in the Railway MySQL console
echo 2. Copy commands one table at a time
echo 3. Check for any syntax errors in the console
echo 4. Ensure database name is 'railway'
echo.

echo ================================================================
echo Opening files for you...
echo.

echo Opening Railway dashboard...
start https://railway.app/project/2725c7e0-071b-43ea-9be7-33142b967d77

echo.
echo Opening SQL setup file...
start notepad railway_mysql_setup.sql

echo.
echo [NEXT] After database setup, run: setup_web_service_railway.bat
echo.
pause
