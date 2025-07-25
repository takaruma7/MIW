@echo off
echo MIW Travel - Database Migration to PostgreSQL
echo ==============================================
echo.

echo [*] This script will help migrate your MySQL data to PostgreSQL for Render.com
echo.

echo [1] Export current MySQL data...
docker-compose exec db sh -c "mysqldump -u miw_user -pmiw_password data_miw --no-create-info --complete-insert" > miw_data_export.sql

if %ERRORLEVEL% neq 0 (
    echo [!] Failed to export data. Make sure your containers are running.
    echo     Run: docker-compose up -d
    pause
    exit /b 1
)

echo [✓] Data exported to miw_data_export.sql

echo.
echo [2] Creating PostgreSQL conversion script...

REM Create conversion script using PowerShell
powershell -Command "& { 
    $content = Get-Content 'miw_data_export.sql' -Raw;
    $content = $content -replace '`([^`]+)`', '$1';
    $content = $content -replace 'AUTO_INCREMENT=[0-9]+', '';
    $content = $content -replace 'ENGINE=InnoDB', '';
    $content = $content -replace 'DEFAULT CHARSET=[a-zA-Z0-9_]+', '';
    $content = $content -replace 'COLLATE=[a-zA-Z0-9_]+', '';
    Set-Content 'miw_data_postgres.sql' -Value $content;
}"

echo [✓] PostgreSQL data file created: miw_data_postgres.sql

echo.
echo [3] Next steps for Render deployment:
echo     1. Create account at render.com
echo     2. Create PostgreSQL database
echo     3. Create Web Service from GitHub
echo     4. Import miw_data_postgres.sql to your Render database
echo     5. Set environment variables from .env.railway file
echo.

echo [*] Files ready for Render deployment:
echo     - migrate_to_postgres.sql (database schema)
echo     - miw_data_postgres.sql (your data)
echo     - config.render.php (PostgreSQL configuration)
echo.

echo [✓] Migration preparation complete!
pause
