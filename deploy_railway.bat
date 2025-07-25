@echo off
echo MIW Travel - Railway Deployment Setup
echo ======================================
echo.

echo [1] Preparing for Railway deployment...
echo.

REM Check if git is initialized
if not exist .git (
    echo [*] Initializing Git repository...
    git init
    git add .
    git commit -m "Initial commit for Railway deployment"
    echo [✓] Git repository initialized
) else (
    echo [*] Git repository already exists
    echo [*] Committing latest changes...
    git add .
    git commit -m "Prepare for Railway deployment" || echo [!] No changes to commit
)

echo.
echo [2] Railway deployment files created:
echo     - railway.json (Railway configuration)
echo     - .env.railway (Production environment template)
echo     - docker-compose.railway.yml (Railway-specific compose)
echo.

echo [3] Next steps:
echo     1. Go to https://railway.app
echo     2. Sign up with GitHub
echo     3. Click "Deploy from GitHub repo"
echo     4. Select your MIW repository
echo     5. Add MySQL service in Railway dashboard
echo     6. Set environment variables from .env.railway file
echo.

echo [✓] Ready for Railway deployment!
echo.
echo [*] Estimated monthly cost: $2-3 (within $5 free credit)
echo [*] Your app will be available at: https://miw-production.up.railway.app
echo.
pause
