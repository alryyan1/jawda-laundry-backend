@echo off
echo ========================================
echo Laravel Cache Clear and Recache Script
echo ========================================
echo.

echo [1/8] Clearing application cache...
php artisan cache:clear
if %errorlevel% neq 0 (
    echo ERROR: Failed to clear application cache
    pause
    exit /b 1
)

echo [2/8] Clearing configuration cache...
php artisan config:clear
if %errorlevel% neq 0 (
    echo ERROR: Failed to clear configuration cache
    pause
    exit /b 1
)

echo [3/8] Clearing route cache...
php artisan route:clear
if %errorlevel% neq 0 (
    echo ERROR: Failed to clear route cache
    pause
    exit /b 1
)

echo [4/8] Clearing view cache...
php artisan view:clear
if %errorlevel% neq 0 (
    echo ERROR: Failed to clear view cache
    pause
    exit /b 1
)

echo [5/8] Clearing compiled class files...
php artisan clear-compiled
if %errorlevel% neq 0 (
    echo ERROR: Failed to clear compiled files
    pause
    exit /b 1
)

echo [6/8] Optimizing autoloader...
composer dump-autoload --optimize
if %errorlevel% neq 0 (
    echo ERROR: Failed to optimize autoloader
    pause
    exit /b 1
)

echo [7/8] Caching configuration...
php artisan config:cache
if %errorlevel% neq 0 (
    echo ERROR: Failed to cache configuration
    pause
    exit /b 1
)

echo [8/8] Caching routes...
php artisan route:cache
if %errorlevel% neq 0 (
    echo ERROR: Failed to cache routes
    pause
    exit /b 1
)

echo.
echo ========================================
echo Cache clear and recache completed!
echo ========================================
echo.
echo All Laravel caches have been cleared and recached.
echo The application should now be running with fresh caches.
echo.
pause 