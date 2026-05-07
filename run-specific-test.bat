@echo off
REM Run Specific Browser Test

if "%1"=="" (
    echo Usage: run-specific-test.bat TestFileName
    echo Example: run-specific-test.bat CustomerTest
    echo.
    echo Available Tests:
    echo - AuthenticationTest
    echo - CustomerTest
    echo - IntakeFormTest
    echo - ProjectMovementTest
    echo - EmployeeManagementTest
    echo - ServiceTicketTest
    echo - RolePermissionTest
    echo.
    pause
    exit /b
)

echo Starting Server...
start /B php artisan serve
timeout /t 3 >nul

echo Running %1...
php artisan dusk tests/Browser/%1.php

echo Stopping Server...
taskkill /F /IM php.exe >nul 2>&1

pause
