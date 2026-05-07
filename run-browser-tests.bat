@echo off
echo ========================================
echo Laravel CRM - Automated Browser Testing
echo ========================================
echo.

echo [Step 1/5] Checking Chrome Driver...
php artisan dusk:chrome-driver --detect
if %errorlevel% neq 0 (
    echo Chrome Driver installation failed!
    pause
    exit /b 1
)
echo Chrome Driver OK
echo.

echo [Step 2/5] Preparing Test Database...
php artisan migrate:fresh --env=dusk.local --force
echo Database Ready
echo.

echo [Step 3/5] Starting Laravel Development Server...
start /B php artisan serve --port=8000
timeout /t 5 /nobreak >nul
echo Server started at http://127.0.0.1:8000
echo.

echo [Step 4/5] Running Browser Tests...
echo.
echo ----------------------------------------
php artisan dusk --stop-on-failure
set TEST_RESULT=%errorlevel%
echo ----------------------------------------
echo.

echo [Step 5/5] Cleaning Up...
taskkill /F /IM php.exe >nul 2>&1
echo Server stopped
echo.

if %TEST_RESULT% equ 0 (
    echo ========================================
    echo ALL TESTS PASSED!
    echo ========================================
) else (
    echo ========================================
    echo SOME TESTS FAILED!
    echo Check screenshots in tests/Browser/screenshots/
    echo ========================================
)

echo.
pause
