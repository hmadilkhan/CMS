@echo off
cls
echo ==========================================
echo Laravel CRM Browser Testing
echo ==========================================
echo.

echo Step 1: Checking Chrome Driver...
php artisan dusk:chrome-driver --detect
echo.

echo Step 2: Starting Server...
start /B php artisan serve --port=8000
timeout /t 3 /nobreak >nul
echo Server started at http://127.0.0.1:8000
echo.

echo Step 3: Running Browser Tests...
echo.
php artisan dusk
echo.

echo Step 4: Stopping Server...
taskkill /F /IM php.exe >nul 2>&1
echo.

echo ==========================================
echo Testing Complete!
echo ==========================================
echo.
pause
