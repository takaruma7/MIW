@echo off
echo ===============================================================
echo         Deleting Redundant Documentation Files
echo ===============================================================
echo.
echo The following documentation files will be deleted since they've been
echo consolidated into MIW_COMPREHENSIVE_DOCUMENTATION.md:
echo.

echo Deployment guides:
del /F /Q "RAILWAY_DEPLOYMENT.md"
del /F /Q "RAILWAY_DEPLOY_MANUAL.md" 
del /F /Q "RAILWAY_DATABASE_SETUP.md"
del /F /Q "RENDER_DEPLOYMENT.md"
del /F /Q "RENDER_DEPLOY_GUIDE.md"
del /F /Q "FLY_DEPLOYMENT.md"
del /F /Q "DOCKER_DEPLOYMENT.md"
del /F /Q "DOCKER_BUILD_FIX.md"
del /F /Q "DOCKERHUB_README.md"
del /F /Q "HEROKU_DEPLOY_GUIDE.md"
del /F /Q "HEROKU_DEPLOYMENT_SUCCESS.md"
del /F /Q "HEROKU_FILE_UPLOAD_ISSUE.md"
del /F /Q "HEROKU_READY.md"
del /F /Q "HEROKU_CLI_SUCCESS.md"

echo Implementation guides:
del /F /Q "HOSTING_ALTERNATIVES.md"
del /F /Q "HOSTING_COMPLETE.md"
del /F /Q "IMPLEMENTATION_COMPLETE.md"
del /F /Q "FINAL_DEPLOYMENT_READY.md"
del /F /Q "CLOUD_HOSTING_GUIDE.md"
del /F /Q "DEPLOYMENT_STATUS_FINAL.md"

echo.
echo All redundant documentation files have been deleted.
echo The comprehensive documentation is preserved in MIW_COMPREHENSIVE_DOCUMENTATION.md
echo.
pause
