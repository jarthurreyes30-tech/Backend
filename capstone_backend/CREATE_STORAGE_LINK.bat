@echo off
echo ============================================
echo CREATING STORAGE SYMBOLIC LINK
echo ============================================
echo.

cd /d "%~dp0"

echo Running: php artisan storage:link
php artisan storage:link

echo.
echo ============================================
echo TESTING IMAGE ACCESSIBILITY
echo ============================================
php artisan tinker --execute="echo 'Storage path: ' . storage_path('app/public') . PHP_EOL; echo 'Public path: ' . public_path('storage') . PHP_EOL; echo 'Link exists: ' . (file_exists(public_path('storage')) ? 'YES' : 'NO') . PHP_EOL;"

echo.
echo Done!
pause
