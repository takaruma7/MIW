@echo off
echo Cleaning up additional unnecessary files...
echo.
del /F /Q fix_database_schema.php
del /F /Q upload_fix.php
del /F /Q create_file_metadata_table.php
del /F /Q add_file_metadata_table.sql
rmdir /S /Q "MIW - Copy (14)"
del /F /Q "MIW - Copy (14).rar"

echo.
echo Additional cleanup complete!
echo.
pause
