@echo off
echo ================================================================
echo    Deleting Non-Docker/Non-Heroku Deployment Files
echo ================================================================
echo.

echo Deleting Railway deployment files...
del /F /Q deploy_to_railway.bat
del /F /Q deploy_to_railway.sh
del /F /Q railway_quick_deploy.bat
del /F /Q complete_railway_implementation.bat
del /F /Q setup_web_service_railway.bat
del /F /Q import_database_railway.bat
del /F /Q setup_database_console.bat
echo Railway deployment files deleted.
echo.

echo Deleting Render deployment files...
del /F /Q migrate_to_render.bat
echo Render deployment files deleted.
echo.

echo Deleting Fly.io deployment files...
del /F /Q setup_hosting.bat
echo Fly.io deployment files deleted.
echo.

echo Deleting other deployment scripts...
del /F /Q push_to_dockerhub.bat
del /F /Q deploy_from_dockerhub.bat
del /F /Q deploy_web_service_final.bat
del /F /Q quick_fix_web.bat
del /F /Q start_fixed_web.bat
del /F /Q start_simple_web.bat
echo Other deployment scripts deleted.
echo.

echo Preserving Docker and Heroku related files...
echo - docker-compose.yml (preserved)
echo - Dockerfile (preserved)
echo - deploy.bat (preserved)
echo - deploy.sh (preserved)
echo - deploy_heroku.bat (preserved)
echo - clean_restart_docker.bat (preserved)
echo - restart_docker.bat (preserved)
echo.

echo Non-Docker/Non-Heroku deployment files cleanup completed!
pause
