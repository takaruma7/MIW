@echo off
echo MIW Travel - Update Composer Lock File
echo =====================================
echo.

echo [*] This script will update your composer.lock file to match composer.json
echo [*] You need to have Composer installed locally for this to work
echo.

echo [*] Updating composer.lock file...
composer update --no-install
echo.

echo [*] If successful, you can now run the clean_restart_docker.bat script
echo [*] to rebuild your Docker containers with the updated lock file.
echo.
echo =====================================
