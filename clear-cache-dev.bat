@echo off
echo ========================================
echo Laravel Cache Clear (Development)
echo ========================================
echo.

echo [1/5] Clearing application cache...
php artisan cache:clear
if %errorlevel% neq 0 (
    echo ERROR: Failed to clear application cache
    pause
    exit /b 1
)

echo [2/5] Clearing configuration cache...
php artisan config:clear
if %errorlevel% neq 0 (
    echo ERROR: Failed to clear configuration cache
    pause
    exit /b 1
)

echo [3/5] Clearing route cache...
php artisan route:clear
if %errorlevel% neq 0 (
    echo ERROR: Failed to clear route cache
    pause
    exit /b 1
)

echo [4/5] Clearing view cache...
php artisan view:clear
if %errorlevel% neq 0 (
    echo ERROR: Failed to clear view cache
    pause
    exit /b 1
)

echo [5/5] Clearing compiled class files...
php artisan clear-compiled
if %errorlevel% neq 0 (
    echo ERROR: Failed to clear compiled files
    pause
    exit /b 1
)

echo.
echo ========================================
echo Cache clear completed!
echo ========================================
echo.
echo All Laravel caches have been cleared.
echo This is ideal for development environments.
echo.
pause 