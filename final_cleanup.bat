@echo off
echo ================================================================
echo             Final Cleanup of Unnecessary Files
echo ================================================================
echo.

echo Deleting any remaining batch files that are no longer needed...
del /F /Q delete_non_docker_heroku_files.bat
del /F /Q delete_config_uml_files.bat
del /F /Q delete_duplicate_docs.bat
del /F /Q extract_clean_files.bat
del /F /Q finalize_migration.bat
echo Unnecessary batch files deleted.
echo.

echo Ensuring backup config.php is preserved...
if not exist config.php.bak (
    echo No backup config.php found, creating one...
    copy config.php config.php.bak
    echo Backup created: config.php.bak
) else (
    echo Backup already exists: config.php.bak
)
echo.

echo Final cleanup completed! The project directory should now be much cleaner.
echo All essential files have been preserved.
echo.
pause
