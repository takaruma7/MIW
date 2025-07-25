@echo off
echo ================================================================
echo               MIW Database Import to Railway
echo ================================================================
echo.
echo Railway MySQL Connection Details:
echo Host: ballast.proxy.rlwy.net
echo Port: 58773
echo Database: railway
echo User: root
echo.

echo [OPTION 1] Manual Import via Railway Dashboard:
echo ─────────────────────────────────────────────────────────────────
echo 1. Go to your Railway MySQL service dashboard
echo 2. Click on "Data" tab
echo 3. Click "Import" or "Query" button
echo 4. Upload file: backup_sql/data_miw (27).sql
echo 5. Execute the import
echo.

echo [OPTION 2] Using MySQL Client (if installed):
echo ─────────────────────────────────────────────────────────────────
echo mysql -h ballast.proxy.rlwy.net -u root -p'ULXtfrTxwgaMIRsOZCteLEvXZTvqvfWe' --port 58773 --protocol=TCP railway ^< "backup_sql/data_miw (27).sql"
echo.

echo [OPTION 3] Using Railway CLI (if installed):
echo ─────────────────────────────────────────────────────────────────
echo 1. railway connect MySQL
echo 2. In MySQL prompt, run: source backup_sql/data_miw (27).sql
echo.

echo ================================================================
echo                      DATABASE VERIFICATION
echo ================================================================
echo.
echo After import, verify these tables exist in Railway MySQL:
echo ✓ admin_users
echo ✓ packages
echo ✓ registrations
echo ✓ pembatalan
echo ✓ manifest
echo ✓ documents
echo ✓ manifest_documents
echo ✓ rooms
echo.

echo ================================================================
echo                    ENVIRONMENT VARIABLES
echo ================================================================
echo.
echo Copy these to your Railway Web Service Variables:
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
echo.

echo ================================================================
echo Opening Railway MySQL dashboard...
start https://railway.app/project/2725c7e0-071b-43ea-9be7-33142b967d77
echo.
echo Press any key to continue...
pause
