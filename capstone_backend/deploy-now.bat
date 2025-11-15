@echo off
echo ========================================
echo DEPLOYING CORS FIX TO RAILWAY
echo ========================================
echo.

cd /d "%~dp0"

echo [1/4] Checking git status...
git status

echo.
echo [2/4] Adding changed files...
git add config/cors.php app/Http/Middleware/Cors.php

echo.
echo [3/4] Committing changes...
git commit -m "fix: Add localhost:8082 to CORS allowed origins for charity registration"

echo.
echo [4/4] Pushing to GitHub (Railway will auto-deploy)...
git push origin main

echo.
echo ========================================
echo DEPLOYMENT STARTED!
echo ========================================
echo.
echo Railway will now deploy your changes.
echo This usually takes 2-3 minutes.
echo.
echo Check deployment status at:
echo https://railway.app
echo.
echo After deployment completes:
echo 1. Clear browser cache (Ctrl + Shift + Delete)
echo 2. Reload frontend (Ctrl + F5)
echo 3. Try charity registration again
echo.
pause
