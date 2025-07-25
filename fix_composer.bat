@echo off
echo MIW Travel - Fix Composer Dependencies
echo =======================================
echo.

echo [*] Removing old composer.lock file...
if exist composer.lock (
    del composer.lock
    echo     composer.lock removed successfully
) else (
    echo     composer.lock not found, continuing...
)
echo.

echo [*] Clearing Composer cache...
composer clear-cache
echo.

echo [*] Installing dependencies from scratch...
composer install --no-dev --optimize-autoloader
echo.

echo [*] Dependency update completed!
echo [*] You can now run Docker build again.
echo.
echo =======================================
