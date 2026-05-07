@echo off
REM Quick Test Runner - Single Command

echo Starting Laravel Server...
start /B php artisan serve
timeout /t 3 >nul

echo Running Browser Tests...
php artisan dusk

echo Stopping Server...
taskkill /F /IM php.exe >nul 2>&1

pause
