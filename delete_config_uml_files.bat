@echo off
echo ================================================================
echo        Deleting Unnecessary Configuration and UML Files
echo ================================================================
echo.

echo Deleting UML diagram directories...
rmdir /S /Q diagram_self
rmdir /S /Q diagram_surpass
rmdir /S /Q diagrams
echo UML diagram directories deleted.
echo.

echo Deleting configuration files...
del /F /Q configure_firewall.bat
del /F /Q update_composer_lock.bat
del /F /Q fix_composer.bat
echo Configuration files deleted.
echo.

echo Unnecessary configuration and UML files cleanup completed!
pause
